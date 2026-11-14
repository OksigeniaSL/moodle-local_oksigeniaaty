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
 * Exchange a one-time sign-in key for an Aty app token. Called by the app, needs no session.
 *
 * Returns JSON: what local\tokenexchange answers (the token on success, a PIN to ask for, or an error).
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$keyvalue = required_param('key', PARAM_ALPHANUMEXT);
// Digits only; kept as a string so a leading zero is not lost. Empty means "no PIN supplied yet".
$pin = optional_param('pin', '', PARAM_ALPHANUMEXT);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// The key must be POSTed over HTTPS, so it never lands in access logs or travels in the clear.
$securepost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && is_https();
echo json_encode(\local_oksigeniaaty\local\tokenexchange::respond($keyvalue, $pin, $securepost));
