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
 * Self-service page: shows the current user their own one-time link to open and sign in to the Aty app.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

require_login();

// Do not hand out a sign-in key to the guest, nor to someone who is logged in as another user (that would mint the
// impersonated user's token). Administrators get a key too, but token.php refuses to mint one for them.
if (isguestuser() || \core\session\manager::is_loggedinas()) {
    throw new moodle_exception('signinfailed', 'local_oksigeniaaty');
}

// Self-service may be limited to certain cohorts (e.g. staff and older students).
if (!local_oksigeniaaty_user_can_selfservice($USER->id)) {
    throw new moodle_exception('selfservicenotallowed', 'local_oksigeniaaty');
}

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/oksigeniaaty/mylink.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mylink:heading', 'local_oksigeniaaty'));
$PAGE->set_heading(get_string('mylink:heading', 'local_oksigeniaaty'));

// A fresh one-time key for this user on every visit.
$key = \local_oksigeniaaty\local\loginkey::create($USER->id);
$openurl = new moodle_url('/local/oksigeniaaty/open.php', ['key' => $key]);

header('Referrer-Policy: no-referrer');

// A QR of the same one-time link: scan it with the Aty app (or any camera) to sign in on a phone. It is drawn in the
// site's brand; should that fail, Moodle's plain QR is shown instead.
try {
    $qrsvg = \local_oksigeniaaty\local\qrdesign::svg(
        $openurl->out(false),
        (string) get_config('local_oksigeniaaty', 'brandcolour'),
        \local_oksigeniaaty\local\qrdesign::sitelogo()
    );
    $qrsrc = 'data:image/svg+xml;base64,' . base64_encode($qrsvg);
} catch (\Throwable $e) {
    $qrcode = new core_qrcode($openurl->out(false));
    $qrsrc = 'data:image/png;base64,' . base64_encode($qrcode->getBarcodePngData(10, 10));
}

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('mylink:intro', 'local_oksigeniaaty'));
echo html_writer::link(
    $openurl,
    get_string('open:openbtn', 'local_oksigeniaaty'),
    ['class' => 'btn btn-primary']
);
echo html_writer::div(
    html_writer::empty_tag('img', [
        'src' => $qrsrc,
        'alt' => get_string('mylink:qralt', 'local_oksigeniaaty'),
        'width' => 280,
        'height' => 280,
    ]),
    'oksigeniaaty-qr mt-3'
);
echo html_writer::tag('p', get_string('mylink:qrhint', 'local_oksigeniaaty'), ['class' => 'text-muted']);
echo html_writer::tag('p', get_string('mylink:expires', 'local_oksigeniaaty'), ['class' => 'text-muted mt-3']);
echo $OUTPUT->footer();
