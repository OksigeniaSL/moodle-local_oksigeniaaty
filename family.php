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
 * Takes an adult of a family into the app: the claim page leads here. Their account is made now, not before, and the link
 * goes on to the page that opens the app or invites to install it. Once they have a PIN, the link alone no longer signs in.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_oksigeniaaty\local\familyaccount;

define('NO_MOODLE_COOKIES', true);

require(__DIR__ . '/../../config.php');

$sealed = required_param('k', PARAM_ALPHANUMEXT);

header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

$adultid = familyaccount::available() ? familyaccount::read($sealed) : null;
$userid = $adultid === null ? null : familyaccount::ensure($adultid);
if ($userid === null) {
    $title = s(get_string('family:linktitle', 'local_oksigeniaaty'));
    $text = s(get_string('family:linkgone', 'local_oksigeniaaty'));
    \local_oksigeniaaty\local\bridgepage::output($title, "<h1>$title</h1><p>$text</p>");
    exit;
}

redirect(\local_oksigeniaaty\api::issue_link($userid, 1)['url']);
