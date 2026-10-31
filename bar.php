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
 * Hide the "open this page in the Aty app" bar on this browser for 30 days, then go back to the page.
 *
 * Works signed in or not (the bar also shows on the login page), so it needs no login, only the session key.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- it only sets a cookie for this browser, signed in or not.
require(__DIR__ . '/../../config.php');

require_sesskey();
$returnurl = optional_param('returnurl', '/', PARAM_LOCALURL);

setcookie(\local_oksigeniaaty\hook_callbacks::BAR_COOKIE, '1', [
    'expires' => time() + 30 * DAYSECS,
    'path' => empty($CFG->sessioncookiepath) ? '/' : $CFG->sessioncookiepath,
    'secure' => is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
]);

redirect(new moodle_url($returnurl));
