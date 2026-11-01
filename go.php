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
 * A page of this site that opens in the app on the phone that has it: the buttons of e-mails, which mail programs would
 * otherwise open in the browser, asking to sign in again. Elsewhere, and where the app is not installed, it goes on to the page.
 *
 * Public and pre-login. It only ever leads to a page of this site, which asks for what it asks for.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);

require(__DIR__ . '/../../config.php');

$target = \local_oksigeniaaty\api::local_target(required_param('to', PARAM_RAW));
if ($target === null) {
    throw new moodle_exception('invalidurl', 'error');
}

$PAGE->set_url('/local/oksigeniaaty/go.php');
$PAGE->set_context(context_system::instance());

if (stripos((string) \core_useragent::get_user_agent_string(), 'Android') === false) {
    redirect($target);
}

$title = s(get_string('go:title', 'local_oksigeniaaty'));
$opening = s(get_string('open:opening', 'local_oksigeniaaty'));
$openbtn = s(get_string('go:openbtn', 'local_oksigeniaaty'));
$web = s(get_string('go:web', 'local_oksigeniaaty'));
$intent = \local_oksigeniaaty\api::app_intent($target);
$intentattr = s($intent);
$targetattr = s($target);
$intentjs = json_encode($intent);

$body = <<<HTML
    <h1>$title</h1>
    <p>$opening</p>
    <a class="btn" href="$intentattr">$openbtn</a>
    <div class="stores"><a href="$targetattr">$web</a></div>
    <script>
    setTimeout(function () { window.location.href = $intentjs; }, 200);
    </script>
HTML;

\local_oksigeniaaty\local\bridgepage::output($title, $body);
