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
 * Web service: the signed-in user sets or changes their own Aty sign-in PIN.
 *
 * The app calls this with its own token after the first passwordless sign-in (trust on first use), so the PIN is a
 * second factor for later sign-ins and for renewing an expiring token.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_pin extends external_api {

    /**
     * The parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pin' => new external_value(PARAM_ALPHANUMEXT, 'The PIN to set (4 to 8 digits)'),
        ]);
    }

    /**
     * Set the current user's PIN.
     *
     * @param string $pin The new PIN.
     * @return array The status.
     */
    public static function execute(string $pin): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['pin' => $pin]);

        $context = \context_system::instance();
        self::validate_context($context);

        // A PIN is only meaningful for an ordinary user signing in to the app; never for the guest, an impersonated
        // session or an administrator-level account (which cannot use passwordless sign-in anyway).
        if (isguestuser() || \core\session\manager::is_loggedinas()
                || is_siteadmin($USER) || has_capability('moodle/site:config', $context)) {
            throw new \moodle_exception('signinfailed', 'local_oksigeniaaty');
        }

        \local_oksigeniaaty\local\pin::set((int) $USER->id, $params['pin']);

        return ['status' => true];
    }

    /**
     * The return description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Whether the PIN was saved'),
        ]);
    }
}
