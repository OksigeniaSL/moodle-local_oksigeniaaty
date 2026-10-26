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

namespace local_oksigeniaaty\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Web service: renew the current app token without a password, so the session "never falls".
 *
 * It mirrors the sign-in flow. The app calls it with an empty PIN first: if the user has a PIN, it hands back the
 * localised pad wording so the app can ask (without doing anything); if not, it renews there and then (silent). The
 * app then calls it again with the PIN. While the token is still valid this pushes its expiry forward — no new magic
 * link, no password. A set PIN is required (so a leaked token cannot extend its own life); a wrong one is reported in
 * the response (so the pad can shake), not thrown.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renew extends external_api {
    /**
     * The parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pin' => new external_value(PARAM_ALPHANUMEXT, 'The PIN, when the user has one set', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Renew the current user's app token.
     *
     * @param string $pin The PIN (empty to just ask for the pad wording, or when the user has none).
     * @return array The outcome: either the pad wording to ask for a PIN, or the renewed token and its new expiry.
     */
    public static function execute(string $pin): array {
        global $USER, $CFG, $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['pin' => $pin]);

        $context = \context_system::instance();
        self::validate_context($context);

        $isprivileged = is_siteadmin($USER) || has_capability('moodle/site:config', $context);
        if (isguestuser() || \core\session\manager::is_loggedinas() || $isprivileged) {
            throw new \moodle_exception('signinfailed', 'local_oksigeniaaty');
        }

        $userid = (int) $USER->id;

        // A set PIN is required to renew; without one, a valid token is enough (the token is the only factor anyway).
        if (\local_oksigeniaaty\local\pin::has_pin($userid)) {
            $pinui = [
                'title' => get_string('pintitleenter', 'local_oksigeniaaty'),
                'ok' => get_string('pinokenter', 'local_oksigeniaaty'),
                'cancel' => get_string('pincancel', 'local_oksigeniaaty'),
                'biometric' => get_string('pinbiometric', 'local_oksigeniaaty'),
            ];

            if ($params['pin'] === '') {
                return self::ask($pinui, '');
            }
            if (\local_oksigeniaaty\local\pin::is_locked($userid)) {
                return self::ask($pinui, get_string('pinlocked', 'local_oksigeniaaty'));
            }
            if (!\local_oksigeniaaty\local\pin::verify($userid, $params['pin'])) {
                $locked = \local_oksigeniaaty\local\pin::is_locked($userid);
                return self::ask($pinui, get_string($locked ? 'pinlocked' : 'pinwrong', 'local_oksigeniaaty'));
            }
        }

        $servicename = defined('MOODLE_OFFICIAL_MOBILE_SERVICE') ? MOODLE_OFFICIAL_MOBILE_SERVICE : 'moodle_mobile_app';
        $service = $DB->get_record('external_services', ['shortname' => $servicename, 'enabled' => 1], '*', MUST_EXIST);

        // generate_token_for_current_user returns the user's existing token (with its old expiry) and only mints a new
        // one when none is valid, so push the expiry forward in place rather than minting (which would log the user's
        // other devices out). Update by the token value, which is always present, not by an id the object may not carry.
        $token = \core_external\util::generate_token_for_current_user($service);
        \core_external\util::log_token_request($token);

        $duration = (int) ($CFG->tokenduration ?? 0);
        $validuntil = $duration > 0 ? time() + $duration : 0;
        $DB->set_field('external_tokens', 'validuntil', $validuntil, ['token' => $token->token]);

        return [
            'needspin' => false,
            'token' => $token->token,
            'validuntil' => $validuntil,
            'error' => '',
            'title' => '',
            'ok' => '',
            'cancel' => '',
            'biometric' => '',
        ];
    }

    /**
     * Build an "ask the user for the PIN" response (optionally with an error to show).
     *
     * @param array $pinui The localised pad wording (title, ok, cancel).
     * @param string $error A localised error to show, or empty.
     * @return array
     */
    protected static function ask(array $pinui, string $error): array {
        return [
            'needspin' => true,
            'token' => '',
            'validuntil' => 0,
            'error' => $error,
            'title' => $pinui['title'],
            'ok' => $pinui['ok'],
            'cancel' => $pinui['cancel'],
            'biometric' => $pinui['biometric'],
        ];
    }

    /**
     * The return description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'needspin' => new external_value(PARAM_BOOL, 'Whether the app must ask the user for the PIN'),
            'token' => new external_value(PARAM_ALPHANUMEXT, 'The app token (unchanged; its expiry was extended), or empty'),
            'validuntil' => new external_value(PARAM_INT, 'The new expiry as a unix time, 0 if it never expires or none yet'),
            'error' => new external_value(PARAM_TEXT, 'A localised error to show (wrong or locked PIN), or empty'),
            'title' => new external_value(PARAM_TEXT, 'Localised pad title, when asking for the PIN'),
            'ok' => new external_value(PARAM_TEXT, 'Localised confirm label, when asking for the PIN'),
            'cancel' => new external_value(PARAM_TEXT, 'Localised cancel label, when asking for the PIN'),
            'biometric' => new external_value(PARAM_TEXT, 'Localised biometric prompt, when asking for the PIN'),
        ]);
    }
}
