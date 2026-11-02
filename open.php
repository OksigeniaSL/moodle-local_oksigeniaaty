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
 * Sign-in bridge page: opens the Aty app with a one-time key, or invites the visitor to install it.
 *
 * Public, pre-login and self-contained (it runs before the user is signed in). It does not consume the key; the app
 * does that when it calls token.php. Reloading it is safe.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

$keyvalue = required_param('key', PARAM_ALPHANUMEXT);

$live = \local_oksigeniaaty\local\loginkey::is_live($keyvalue);
// Base64url (no +, / or padding) so the payload never needs URL-encoding and is not corrupted by the app's trailing-slash trim.
$payload = rtrim(strtr(base64_encode($CFG->wwwroot . ':::' . $keyvalue), '+/', '-_'), '=');
$appurl = 'oksigeniaaty://atylogin=' . $payload;

$androidurl = trim((string) get_config('local_oksigeniaaty', 'androidstoreurl'));
$iosurl = trim((string) get_config('local_oksigeniaaty', 'iosstoreurl'));

$title = s(get_string('open:title', 'local_oksigeniaaty'));

if ($live && local_oksigeniaaty_is_ios_without_app()) {
    // No app to open or to install: the way on is the browser, and the key stays good for an Android phone.
    $noios = s(get_string('noios', 'local_oksigeniaaty'));
    $siteurl = s($CFG->wwwroot);
    $gotosite = s(get_string('open:gotosite', 'local_oksigeniaaty'));
    $body = <<<HTML
    <h1>$title</h1>
    <p>$noios</p>
    <a class="btn" href="$siteurl">$gotosite</a>
HTML;
} else if ($live) {
    $opening = s(get_string('open:opening', 'local_oksigeniaaty'));
    $openbtn = s(get_string('open:openbtn', 'local_oksigeniaaty'));
    $noapp = s(get_string('open:noapp', 'local_oksigeniaaty'));
    $appurlattr = s($appurl);

    $storelinks = '';
    if ($androidurl !== '') {
        $storelinks .= '<a href="' . s($androidurl) . '">' . s(get_string('open:android', 'local_oksigeniaaty')) . '</a>';
    }
    if ($iosurl !== '') {
        $storelinks .= '<a href="' . s($iosurl) . '">' . s(get_string('open:ios', 'local_oksigeniaaty')) . '</a>';
    }

    $appurljs = json_encode($appurl);
    $body = <<<HTML
    <h1>$title</h1>
    <p>$opening</p>
    <a class="btn" href="$appurlattr">$openbtn</a>
    <div class="stores">
        <p class="muted">$noapp</p>
        $storelinks
    </div>
    <script>
    setTimeout(function () { window.location.href = $appurljs; }, 400);
    </script>
HTML;
} else {
    $expired = s(get_string('open:expired', 'local_oksigeniaaty'));
    $body = <<<HTML
    <h1>$title</h1>
    <p>$expired</p>
HTML;
}

\local_oksigeniaaty\local\bridgepage::output($title, $body);
