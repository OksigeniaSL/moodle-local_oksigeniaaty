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
 * The site's QR code, in its brand, for posters, letters and welcome packs: scanned with the Aty app it opens this
 * site's sign-in; scanned with a phone camera, the site in the browser.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_oksigeniaaty_centreqr');

$svg = \local_oksigeniaaty\local\qrdesign::svg(
    $CFG->wwwroot,
    (string) get_config('local_oksigeniaaty', 'brandcolour'),
    \local_oksigeniaaty\local\qrdesign::sitelogo()
);

if (optional_param('download', 0, PARAM_BOOL)) {
    require_sesskey();
    $filename = clean_filename('qr-' . parse_url($CFG->wwwroot, PHP_URL_HOST) . '.svg');
    header('Content-Type: image/svg+xml');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    echo $svg;
    die();
}

$title = get_string('centreqr:heading', 'local_oksigeniaaty');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('centreqr:intro', 'local_oksigeniaaty', s($CFG->wwwroot)));
echo html_writer::div(
    html_writer::empty_tag('img', [
        'src' => 'data:image/svg+xml;base64,' . base64_encode($svg),
        'alt' => get_string('centreqr:alt', 'local_oksigeniaaty'),
        'width' => 280,
        'height' => 280,
    ]),
    'oksigeniaaty-qr my-3'
);
echo html_writer::link(
    new moodle_url('/local/oksigeniaaty/centreqr.php', ['download' => 1, 'sesskey' => sesskey()]),
    get_string('centreqr:download', 'local_oksigeniaaty'),
    ['class' => 'btn btn-primary']
);
echo html_writer::tag('p', get_string('centreqr:hint', 'local_oksigeniaaty'), ['class' => 'text-muted mt-3']);
echo $OUTPUT->footer();
