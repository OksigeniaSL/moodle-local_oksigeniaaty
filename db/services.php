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
 * Web service functions for local_oksigeniaaty.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_oksigeniaaty_set_pin' => [
        'classname' => 'local_oksigeniaaty\external\set_pin',
        'methodname' => 'execute',
        'description' => 'Set or change the current user\'s Aty sign-in PIN.',
        'type' => 'write',
        'ajax' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_oksigeniaaty_get_user_style' => [
        'classname' => 'local_oksigeniaaty\external\get_user_style',
        'methodname' => 'execute',
        'description' => 'The current user\'s own brand CSS (a library in a shared site), for the Aty app.',
        'type' => 'read',
        'ajax' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_oksigeniaaty_renew' => [
        'classname' => 'local_oksigeniaaty\external\renew',
        'methodname' => 'execute',
        'description' => 'Renew the current Aty app token (extend its expiry) without a password.',
        'type' => 'write',
        'ajax' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
