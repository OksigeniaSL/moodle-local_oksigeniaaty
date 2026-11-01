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
 * The sheet of the app's home, loaded by the handler in db/mobile.php: public, like the site's mobile CSS.
 *
 * The official app does not apply the site's mobile CSS but loads this one, so the home reads well in both apps.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../config.php');

$css = \local_oksigeniaaty\local\appcss::homesheet((string) get_config('local_oksigeniaaty', 'brandcolour'));

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=31536000, immutable');
echo $css;
