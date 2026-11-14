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

namespace local_oksigeniaaty\local;

/**
 * The exchange of a one-time sign-in key for an Aty app token, behind token.php: what the app is answered, as an array.
 *
 * On success {token, privatetoken, siteurl, needspinsetup, pinui, lang, fullname, username, validuntil}; with a PIN to
 * ask for or a wrong one, {needspin, pinui} with locked and error; on failure {error}. The key is single use and short
 * lived, and is consumed only once everything checks out; the token is an ordinary mobile web service token, revocable
 * from Moodle.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tokenexchange {
    /** @var string[] The failures said as they are; any other is the generic "sign-in failed", so nothing leaks. */
    private const SAID = ['selfservicenotforadmins', 'appnotallowed'];

    /**
     * The answer to the app.
     *
     * @param string $keyvalue The key.
     * @param string $pin The PIN typed, digits kept as a string; empty when none has been typed yet.
     * @param bool $securepost Whether the key came in a POST over HTTPS, so it never lands in access logs or travels in
     *     the clear.
     * @return array
     */
    public static function respond(string $keyvalue, string $pin, bool $securepost): array {
        try {
            return self::exchange($keyvalue, $pin, $securepost);
        } catch (\Throwable $e) {
            // Not the internals: a generic message, except for an administrator, who is told to use their password, and
            // for someone whose plan does not include the app.
            $said = $e instanceof \moodle_exception && in_array($e->errorcode, self::SAID, true);
            return ['error' => get_string($said ? $e->errorcode : 'signinfailed', 'local_oksigeniaaty')];
        }
    }

    /**
     * The exchange itself; it throws on anything that stops it.
     *
     * @param string $keyvalue The key.
     * @param string $pin The PIN typed, or empty.
     * @param bool $securepost Whether the key came in a POST over HTTPS.
     * @return array
     */
    private static function exchange(string $keyvalue, string $pin, bool $securepost): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

        if (empty($CFG->enablewebservices) || empty($CFG->enablemobilewebservice)) {
            throw new \moodle_exception('mobilenotenabled', 'local_oksigeniaaty');
        }
        if (!$securepost) {
            throw new \moodle_exception('signinfailed', 'local_oksigeniaaty');
        }

        // Peek the key before consuming it, so a missing or wrong PIN can be reported without burning the key: only the
        // final step, once everything checks out, consumes it.
        $peeked = loginkey::peek($keyvalue);
        if (!$peeked) {
            throw new \moodle_exception('invalidkey', 'local_oksigeniaaty');
        }
        $userid = $peeked['userid'];
        // A reset key (emailed by forgotpin.php) clears the PIN and sets a new one, so it does not pass the PIN gate.
        $isreset = $peeked['reset'];
        $user = \core_user::get_user($userid, '*', MUST_EXIST);
        \core_user::require_active_user($user, true, true);

        // Never mint a passwordless app token for an administrator-level account, and say why.
        if (local_oksigeniaaty_is_admin_account((int) $user->id)) {
            throw new \moodle_exception('selfservicenotforadmins', 'local_oksigeniaaty');
        }

        // A plugin may say this person's plan does not include the app.
        if (!local_oksigeniaaty_user_allowed((int) $user->id)) {
            throw new \moodle_exception('appnotallowed', 'local_oksigeniaaty');
        }

        // Respect the site's app login type: when it forces browser login (e.g. to enforce MFA), do not bypass it.
        if ((int) get_config('tool_mobile', 'typeoflogin') > 1) {
            throw new \moodle_exception('signinfailed', 'local_oksigeniaaty');
        }

        // PIN gate. Once a user has set a PIN it is a required second factor: the key alone no longer signs in. The key is
        // not consumed here, so a wrong PIN leaves it live for another try (throttled server-side against brute force).
        // The pad's wording travels back localised, so the app ships no PIN strings of its own.
        $haspin = !$isreset && pin::has_pin($userid);
        if ($haspin) {
            $pinui = [
                'title' => get_string('pintitleenter', 'local_oksigeniaaty'),
                'ok' => get_string('pinokenter', 'local_oksigeniaaty'),
                'cancel' => get_string('pincancel', 'local_oksigeniaaty'),
                'forgot' => get_string('forgotpin:link', 'local_oksigeniaaty'),
                'biometric' => get_string('pinbiometric', 'local_oksigeniaaty'),
            ];
            if ($pin === '') {
                return ['needspin' => true, 'pinui' => $pinui];
            }
            if (pin::is_locked($userid)) {
                return [
                    'needspin' => true,
                    'locked' => true,
                    'error' => get_string('pinlocked', 'local_oksigeniaaty'),
                    'pinui' => $pinui,
                ];
            }
            if (!pin::verify($userid, $pin)) {
                $locked = pin::is_locked($userid);
                return [
                    'needspin' => true,
                    'locked' => $locked,
                    'error' => get_string($locked ? 'pinlocked' : 'pinwrong', 'local_oksigeniaaty'),
                    'pinui' => $pinui,
                ];
            }
        }

        $servicename = defined('MOODLE_OFFICIAL_MOBILE_SERVICE') ? MOODLE_OFFICIAL_MOBILE_SERVICE : 'moodle_mobile_app';
        $service = $DB->get_record('external_services', ['shortname' => $servicename, 'enabled' => 1], '*', MUST_EXIST);

        // Everything checks out: consume the key now (atomic single use) and mint the token.
        loginkey::consume($keyvalue);

        // A reset key clears the PIN, so the app then offers to set a new one (needspinsetup, since $haspin is false).
        if ($isreset) {
            pin::delete($userid);
        }

        // Mint the token exactly as login/token.php does, private token included (loginkey::app_token).
        \core\session\manager::set_user($user);
        $apptoken = loginkey::app_token($user, $service);

        // When the user has no PIN yet, tell the app to offer setting one (trust on first use), with the pad wording
        // already localised here. The user's own language travels back too, so the app can dress itself in it: open a
        // link, land in your language.
        $setupui = $haspin ? null : [
            'title' => get_string('pintitlesetup', 'local_oksigeniaaty'),
            'message' => get_string('pinsetup', 'local_oksigeniaaty'),
            'ok' => get_string('pinoksetup', 'local_oksigeniaaty'),
            'cancel' => get_string('pinskip', 'local_oksigeniaaty'),
            // The new PIN is typed twice: the second pad confirms it, and says so when they do not match.
            'confirmtitle' => get_string('pintitleconfirm', 'local_oksigeniaaty'),
            'confirmmessage' => get_string('pinconfirm', 'local_oksigeniaaty'),
            'confirmok' => get_string('pinokconfirm', 'local_oksigeniaaty'),
            'mismatch' => get_string('pinmismatch', 'local_oksigeniaaty'),
            // Said by the app when the new PIN could not be saved, so nobody trusts a PIN that does not exist.
            'notsaved' => get_string('pinnotsaved', 'local_oksigeniaaty'),
            'biometric' => get_string('pinbiometric', 'local_oksigeniaaty'),
            'biometricask' => get_string('pinbiometricask', 'local_oksigeniaaty'),
        ];

        return [
            'token' => $apptoken['token'],
            'privatetoken' => $apptoken['privatetoken'],
            'siteurl' => $CFG->wwwroot,
            'needspinsetup' => !$haspin,
            'pinui' => $setupui,
            'lang' => !empty($user->lang) ? $user->lang : $CFG->lang,
            // Who the link signs in, so an app that already holds other accounts can ask «Sign in as …?» before
            // switching: a link or a QR someone else made would otherwise put the phone in their account without a word.
            'fullname' => fullname($user),
            'username' => $user->username,
            // When the token expires (0 = never), so the app can renew it before it does.
            'validuntil' => $apptoken['validuntil'],
        ];
    }
}
