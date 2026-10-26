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
 * Self-service page: a signed-in user clears their Aty sign-in PIN (the secure "forgot my PIN" recovery).
 *
 * It needs a full web login (the password), so a stolen magic link cannot reset the PIN: that is what keeps the PIN
 * meaningful against an intercepted link. After resetting, the next passwordless sign-in sets a fresh PIN.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

require_login();

// Never for the guest, nor for an impersonated session (that would reset the impersonated user's PIN).
if (isguestuser() || \core\session\manager::is_loggedinas()) {
    throw new moodle_exception('signinfailed', 'local_oksigeniaaty');
}

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/oksigeniaaty/resetpin.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('resetpin:heading', 'local_oksigeniaaty'));
$PAGE->set_heading(get_string('resetpin:heading', 'local_oksigeniaaty'));

$profileurl = new moodle_url('/user/profile.php');

if (optional_param('confirm', 0, PARAM_BOOL) && confirm_sesskey()) {
    \local_oksigeniaaty\local\pin::delete((int) $USER->id);

    redirect($profileurl, get_string('resetpin:done', 'local_oksigeniaaty'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

if (\local_oksigeniaaty\local\pin::has_pin((int) $USER->id)) {
    $confirmurl = new moodle_url('/local/oksigeniaaty/resetpin.php', ['confirm' => 1, 'sesskey' => sesskey()]);
    echo $OUTPUT->confirm(get_string('resetpin:confirm', 'local_oksigeniaaty'), $confirmurl, $profileurl);
} else {
    echo html_writer::tag('p', get_string('resetpin:nopin', 'local_oksigeniaaty'));
    echo html_writer::link($profileurl, get_string('continue'), ['class' => 'btn btn-primary']);
}

echo $OUTPUT->footer();
