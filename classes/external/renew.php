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
 * While the token is still valid, the app calls this (with the PIN, if the user set one) to push the token's expiry
 * forward. No new magic link, no password. If the user has no PIN, holding a valid token is enough; if they do, it is
 * required, so a leaked token cannot extend its own life.
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
     * @param string $pin The PIN (empty when the user has none).
     * @return array The token and its new expiry.
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

        // A set PIN is required to renew; without one, a valid token is enough (the token is the only factor anyway).
        if (\local_oksigeniaaty\local\pin::has_pin((int) $USER->id)) {
            if (\local_oksigeniaaty\local\pin::is_locked((int) $USER->id)) {
                throw new \moodle_exception('pinlocked', 'local_oksigeniaaty');
            }
            if ($params['pin'] === '' || !\local_oksigeniaaty\local\pin::verify((int) $USER->id, $params['pin'])) {
                throw new \moodle_exception('pinwrong', 'local_oksigeniaaty');
            }
        }

        $servicename = defined('MOODLE_OFFICIAL_MOBILE_SERVICE') ? MOODLE_OFFICIAL_MOBILE_SERVICE : 'moodle_mobile_app';
        $service = $DB->get_record('external_services', ['shortname' => $servicename, 'enabled' => 1], '*', MUST_EXIST);

        // generate_token_for_current_user returns the user's existing token (with its old expiry), so push that expiry
        // forward in place rather than minting a new token (which would log the user's other devices out).
        $token = \core_external\util::generate_token_for_current_user($service);
        \core_external\util::log_token_request($token);

        $duration = (int) ($CFG->tokenduration ?? 0);
        $validuntil = $duration > 0 ? time() + $duration : 0;
        if (!empty($token->id)) {
            $DB->set_field('external_tokens', 'validuntil', $validuntil, ['id' => $token->id]);
        }

        return [
            'token' => $token->token,
            'validuntil' => $validuntil,
        ];
    }

    /**
     * The return description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'token' => new external_value(PARAM_ALPHANUMEXT, 'The app token (unchanged; its expiry was extended)'),
            'validuntil' => new external_value(PARAM_INT, 'The new expiry as a unix time, or 0 if the token never expires'),
        ]);
    }
}
