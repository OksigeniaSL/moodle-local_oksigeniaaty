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
 * Hook callbacks for local_oksigeniaaty.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    // The app's password form, closed to whoever the site asks for a second factor (login/token.php skips tool_mfa).
    [
        'hook' => 'core\hook\after_config',
        'callback' => [\local_oksigeniaaty\hook_callbacks::class, 'after_config'],
    ],
    // "Open in the Aty app" in the user menu, so everyone finds their own QR whatever the theme or profile page.
    // The way back to the app's launch page, which the site's policies may forget on a first sign-in.
    [
        'hook' => 'tool_mfa\hook\after_user_passed_mfa',
        'callback' => [\local_oksigeniaaty\hook_callbacks::class, 'after_user_passed_mfa'],
    ],
    [
        'hook' => 'core_user\hook\after_login_completed',
        'callback' => [\local_oksigeniaaty\hook_callbacks::class, 'after_login_completed'],
    ],
    [
        'hook' => 'core_user\hook\extend_user_menu',
        'callback' => [\local_oksigeniaaty\hook_callbacks::class, 'extend_user_menu'],
    ],
    [
        'hook' => 'core\hook\output\before_standard_top_of_body_html_generation',
        'callback' => [\local_oksigeniaaty\hook_callbacks::class, 'before_standard_top_of_body_html'],
    ],
];
