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
 * Serves the app's remote CSS (mobilecssurl) for this site, generated from the plugin settings.
 *
 * The app loads this before a user logs in, so the login screen carries the brand too; the script needs no session.
 * The URL carries a ?v= that changes whenever the design changes, so the response is cached hard.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

$brand = (string) get_config('local_oksigeniaaty', 'brandcolour');
$css = \local_oksigeniaaty\local\appcss::generate($brand);

// Append the client's own app CSS (Boost Union's mobilescss box or this plugin's extracss) after the brand skin, so a
// site keeps its course styles and nothing is lost to a single mobilecssurl slot. The brand goes first; the client CSS
// last, so a site can fine-tune on top.
$clientcss = local_oksigeniaaty_client_css();
if (trim($clientcss) !== '') {
    $css .= "\n\n/* Client app CSS (Boost Union mobilescss or plugin extracss). */\n" . $clientcss;
}

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=31536000, immutable');
echo $css;
