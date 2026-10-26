<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Exchange a one-time sign-in key for an Aty app token. Called by the app, needs no session.
 *
 * Returns JSON {token, siteurl} on success or {error} on failure. The key is single use and short lived; the token is
 * an ordinary mobile web service token, revocable from Moodle.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../config.php');

$keyvalue = required_param('key', PARAM_ALPHANUMEXT);
// Digits only; kept as a string so a leading zero is not lost. Empty means "no PIN supplied yet".
$pin = optional_param('pin', '', PARAM_ALPHANUMEXT);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    if (empty($CFG->enablewebservices) || empty($CFG->enablemobilewebservice)) {
        throw new moodle_exception('mobilenotenabled', 'local_oksigeniaaty');
    }

    // The key must be POSTed over HTTPS, so it never lands in access logs or travels in the clear.
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !is_https()) {
        throw new moodle_exception('signinfailed', 'local_oksigeniaaty');
    }

    // Peek the key before consuming it, so a missing or wrong PIN can be reported without burning the key: only the
    // final step, once everything checks out, consumes it.
    $peeked = \local_oksigeniaaty\local\loginkey::peek($keyvalue);
    if (!$peeked) {
        throw new moodle_exception('invalidkey', 'local_oksigeniaaty');
    }
    $userid = $peeked['userid'];
    // A reset key (emailed by forgotpin.php) clears the PIN and sets a new one, so it does not pass the PIN gate.
    $isreset = $peeked['reset'];
    $user = core_user::get_user($userid, '*', MUST_EXIST);
    core_user::require_active_user($user, true, true);

    // Never mint a passwordless app token for an administrator-level account.
    $systemcontext = context_system::instance();
    if (is_siteadmin($user) || has_capability('moodle/site:config', $systemcontext, $user)) {
        throw new moodle_exception('signinfailed', 'local_oksigeniaaty');
    }

    // Respect the site's app login type: when it forces browser login (e.g. to enforce MFA), do not bypass it.
    if ((int) get_config('tool_mobile', 'typeoflogin') > 1) {
        throw new moodle_exception('signinfailed', 'local_oksigeniaaty');
    }

    // PIN gate. Once a user has set a PIN it is a required second factor: the key alone no longer signs in. The key is
    // not consumed here, so a wrong PIN leaves it live for another try (throttled server-side against brute force).
    // The pad's wording travels back localised, so the app ships no PIN strings of its own.
    $haspin = !$isreset && \local_oksigeniaaty\local\pin::has_pin($userid);
    if ($haspin) {
        $pinui = [
            'title' => get_string('pintitleenter', 'local_oksigeniaaty'),
            'ok' => get_string('pinokenter', 'local_oksigeniaaty'),
            'cancel' => get_string('pincancel', 'local_oksigeniaaty'),
            'forgot' => get_string('forgotpin:link', 'local_oksigeniaaty'),
            'biometric' => get_string('pinbiometric', 'local_oksigeniaaty'),
        ];
        if ($pin === '') {
            echo json_encode(['needspin' => true, 'pinui' => $pinui]);
            exit;
        }
        if (\local_oksigeniaaty\local\pin::is_locked($userid)) {
            echo json_encode([
                'needspin' => true,
                'locked' => true,
                'error' => get_string('pinlocked', 'local_oksigeniaaty'),
                'pinui' => $pinui,
            ]);
            exit;
        }
        if (!\local_oksigeniaaty\local\pin::verify($userid, $pin)) {
            $locked = \local_oksigeniaaty\local\pin::is_locked($userid);
            echo json_encode([
                'needspin' => true,
                'locked' => $locked,
                'error' => get_string($locked ? 'pinlocked' : 'pinwrong', 'local_oksigeniaaty'),
                'pinui' => $pinui,
            ]);
            exit;
        }
    }

    $servicename = defined('MOODLE_OFFICIAL_MOBILE_SERVICE') ? MOODLE_OFFICIAL_MOBILE_SERVICE : 'moodle_mobile_app';
    $service = $DB->get_record('external_services', ['shortname' => $servicename, 'enabled' => 1], '*', MUST_EXIST);

    // Everything checks out: consume the key now (atomic single use) and mint the token.
    \local_oksigeniaaty\local\loginkey::consume($keyvalue);

    // A reset key clears the PIN, so the app then offers to set a new one (needspinsetup, since $haspin is false).
    if ($isreset) {
        \local_oksigeniaaty\local\pin::delete($userid);
    }

    // Mint the token exactly as login/token.php does: this applies the service capability check, token reuse,
    // $CFG->tokenduration expiry, the creator id and the audit log, instead of a raw permanent token.
    \core\session\manager::set_user($user);
    $token = \core_external\util::generate_token_for_current_user($service);
    \core_external\util::log_token_request($token);

    $privatetoken = (!empty($token->privatetoken) && is_https()) ? $token->privatetoken : null;

    // When the user has no PIN yet, tell the app to offer setting one (trust on first use), with the pad wording
    // already localised here. The user's own language travels back too, so the app can dress itself in it: open a
    // link, land in your language.
    $setupui = $haspin ? null : [
        'title' => get_string('pintitlesetup', 'local_oksigeniaaty'),
        'message' => get_string('pinsetup', 'local_oksigeniaaty'),
        'ok' => get_string('pinoksetup', 'local_oksigeniaaty'),
        'cancel' => get_string('pinskip', 'local_oksigeniaaty'),
        'biometric' => get_string('pinbiometric', 'local_oksigeniaaty'),
        'biometricask' => get_string('pinbiometricask', 'local_oksigeniaaty'),
    ];
    echo json_encode([
        'token' => $token->token,
        'privatetoken' => $privatetoken,
        'siteurl' => $CFG->wwwroot,
        'needspinsetup' => !$haspin,
        'pinui' => $setupui,
        'lang' => !empty($user->lang) ? $user->lang : $CFG->lang,
        // When the token expires (0 = never), so the app can renew it before it does.
        'validuntil' => isset($token->validuntil) ? (int) $token->validuntil : 0,
    ]);
} catch (Throwable $e) {
    // Do not leak internals; a generic message is enough for the app.
    echo json_encode(['error' => get_string('signinfailed', 'local_oksigeniaaty')]);
}
