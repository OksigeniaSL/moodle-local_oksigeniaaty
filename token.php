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
 * Returns JSON {token, siteurl} on success or {error} on failure. The key is single use and short lived; the token is
 * an ordinary mobile web service token, revocable from Moodle.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../config.php');

$keyvalue = required_param('key', PARAM_ALPHANUMEXT);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    if (empty($CFG->enablewebservices) || empty($CFG->enablemobilewebservice)) {
        throw new moodle_exception('mobilenotenabled', 'local_oksigeniaaty');
    }

    $userid = \local_oksigeniaaty\local\loginkey::consume($keyvalue);
    $user = core_user::get_user($userid, '*', MUST_EXIST);
    core_user::require_active_user($user, true, true);

    $servicename = defined('MOODLE_OFFICIAL_MOBILE_SERVICE') ? MOODLE_OFFICIAL_MOBILE_SERVICE : 'moodle_mobile_app';
    $service = $DB->get_record('external_services', ['shortname' => $servicename, 'enabled' => 1], '*', MUST_EXIST);

    $token = \core_external\util::generate_token(
        EXTERNAL_TOKEN_PERMANENT,
        $service,
        $user->id,
        context_system::instance(),
        0,
        ''
    );

    echo json_encode(['token' => $token, 'siteurl' => $CFG->wwwroot]);
} catch (Throwable $e) {
    // Do not leak internals; a generic message with the error code is enough for the app.
    echo json_encode(['error' => get_string('signinfailed', 'local_oksigeniaaty')]);
}
