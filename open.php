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

$keyvalue = required_param('key', PARAM_ALPHANUMEXT);

$live = \local_oksigeniaaty\local\loginkey::is_live($keyvalue);
$payload = base64_encode($CFG->wwwroot . ':::' . $keyvalue);
$appurl = 'oksigeniaaty://atylogin=' . $payload;

$androidurl = trim((string) get_config('local_oksigeniaaty', 'androidstoreurl'));
$iosurl = trim((string) get_config('local_oksigeniaaty', 'iosstoreurl'));

$title = s(get_string('open:title', 'local_oksigeniaaty'));
$lang = str_replace('_', '-', current_language());

if ($live) {
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

$css = <<<'CSS'
:root { color-scheme: light dark; }
* { box-sizing: border-box; }
body {
    margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    padding: 24px; background: #f4f7fb; color: #1c1a19;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
}
.card {
    width: 100%; background: #fff; border: 1px solid #d9e2ee; border-radius: 20px;
    padding: 28px 24px; box-shadow: 0 10px 30px rgba(10, 46, 80, .08); text-align: center;
}
h1 { font-size: 1.4rem; margin: 0 0 8px; color: #0a2e50; }
p { font-size: 1rem; line-height: 1.5; margin: 8px 0; color: #495057; }
.btn {
    display: block; width: 100%; margin: 18px 0 6px; padding: 15px 18px; border: 0; border-radius: 14px;
    background: #0a2e50; color: #fff; font-size: 1.05rem; font-weight: 600; text-decoration: none;
}
.stores { margin-top: 22px; padding-top: 18px; border-top: 1px solid #e9ecef; }
.stores a { display: block; margin: 8px 0; color: #0a66c2; font-weight: 600; text-decoration: none; }
.muted { color: #6a737b; font-size: .92rem; }
@media (prefers-color-scheme: dark) {
    body { background: #14181d; color: #e9ecef; }
    .card { background: #1f242b; border-color: #333a42; box-shadow: none; }
    h1 { color: #5cd6ff; }
    p { color: #b7c0c9; }
    .btn { background: #5cd6ff; color: #0a2e50; }
    .stores { border-color: #333a42; }
    .stores a { color: #8fd3ff; }
}
CSS;

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');

echo <<<HTML
<!doctype html>
<html lang="$lang">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>$title</title>
<style>
$css
</style>
</head>
<body>
<div class="card">
$body
</div>
</body>
</html>
HTML;
