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
 * Web service: another child's code, typed in the app by an adult who is already in. It joins them to the same account, which
 * only someone signed in can ask for: here the person is known, so no one else's family is at stake.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class family_claim extends external_api {
    /** @var int Failed tries before it stops listening for a while (the cache keeps them for 15 minutes). */
    private const MAX_TRIES = 10;

    /**
     * The code.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'code' => new external_value(PARAM_TEXT, 'The code on the letter'),
        ]);
    }

    /**
     * Claim it.
     *
     * @param string $code The code.
     * @return array ['name' => the child's name]
     */
    public static function execute(string $code): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['code' => $code]);
        self::validate_context(\context_system::instance());
        if (!class_exists(\local_oksigeniafamily\api::class) || !\local_oksigeniafamily\api::is_available()) {
            throw new \moodle_exception('family:notavailable', 'local_oksigeniaaty');
        }

        $cache = \cache::make('local_oksigeniafamily', 'claimattempts');
        $key = 'aty' . (int) $USER->id;
        $tries = (int) $cache->get($key);
        if ($tries >= self::MAX_TRIES) {
            throw new \moodle_exception('family:toomany', 'local_oksigeniaaty');
        }

        try {
            $done = \local_oksigeniafamily\api::claim($params['code'], [
                'userid' => (int) $USER->id,
                'firstname' => $USER->firstname,
                'lastname' => $USER->lastname,
            ]);
        } catch (\moodle_exception $e) {
            $cache->set($key, $tries + 1);
            throw $e;
        }

        return ['name' => fullname(\core_user::get_user((int) $done->childuserid))];
    }

    /**
     * The return description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'name' => new external_value(PARAM_TEXT, 'Who joined'),
        ]);
    }
}
