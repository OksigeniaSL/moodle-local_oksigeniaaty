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

$strs = [
    'title' => get_string('open:title', 'local_oksigeniaaty'),
    'opening' => get_string('open:opening', 'local_oksigeniaaty'),
    'openbtn' => get_string('open:openbtn', 'local_oksigeniaaty'),
    'noapp' => get_string('open:noapp', 'local_oksigeniaaty'),
    'android' => get_string('open:android', 'local_oksigeniaaty'),
    'ios' => get_string('open:ios', 'local_oksigeniaaty'),
    'expired' => get_string('open:expired', 'local_oksigeniaaty'),
];

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

$appurljs = json_encode($appurl);
$appurlattr = s($appurl);
?><!doctype html>
<html lang="<?php echo str_replace('_', '-', current_language()); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo s($strs['title']); ?></title>
<style>
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
    background: #0a2e50; color: #fff; font-size: 1.05rem; font-weight: 600; text-decoration: none; cursor: pointer;
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
</style>
</head>
<body>
<div class="card">
<?php if ($live) { ?>
    <h1><?php echo s($strs['title']); ?></h1>
    <p><?php echo s($strs['opening']); ?></p>
    <a class="btn" href="<?php echo $appurlattr; ?>"><?php echo s($strs['openbtn']); ?></a>
    <div class="stores">
        <p class="muted"><?php echo s($strs['noapp']); ?></p>
        <?php if ($androidurl !== '') { ?><a href="<?php echo s($androidurl); ?>"><?php echo s($strs['android']); ?></a><?php } ?>
        <?php if ($iosurl !== '') { ?><a href="<?php echo s($iosurl); ?>"><?php echo s($strs['ios']); ?></a><?php } ?>
    </div>
    <script>
    // Try to open the app straight away; the button stays for a manual retry (e.g. after installing).
    setTimeout(function () { window.location.href = <?php echo $appurljs; ?>; }, 400);
    </script>
<?php } else { ?>
    <h1><?php echo s($strs['title']); ?></h1>
    <p><?php echo s($strs['expired']); ?></p>
<?php } ?>
</div>
</body>
</html>
