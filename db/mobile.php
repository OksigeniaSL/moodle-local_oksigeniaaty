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
 * Mobile app support: the per-site home tab of Oksigenia Aty.
 *
 * The handler adds a home tab to the main menu (CoreMainMenuHomeDelegate). It renders a server template, so the home of
 * each site changes from its settings without a new app build, and it works in the official Moodle app too.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'local_oksigeniaaty' => [
        'handlers' => [
            'home' => [
                'delegate' => 'CoreMainMenuHomeDelegate',
                'method' => 'mobile_home',
                'displaydata' => [
                    'title' => 'pluginname',
                    'icon' => 'home',
                ],
            ],
        ],
        'lang' => [
            ['pluginname', 'local_oksigeniaaty'],
        ],
    ],
];
