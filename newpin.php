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
 * Where the notice of «Forgot my PIN» leads, for an account without a mailbox: the reset key, to its owner's own session.
 *
 * The notice itself carries no key, since it travels through the push bridge, Firebase, WhatsApp or Telegram, and a reset
 * key signs in without the PIN. Here, signed in as the account (in the app, or on the web), the key it asked for in the
 * last half hour goes on to the sign-in bridge, which opens the app to set a new PIN. Without one, it says so.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

require_login(null, false);

// Never for the guest, nor for an impersonated session: that would hand over the impersonated user's key.
if (isguestuser() || \core\session\manager::is_loggedinas()) {
    throw new moodle_exception('signinfailed', 'local_oksigeniaaty');
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/oksigeniaaty/newpin.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('forgotpin:emailheading', 'local_oksigeniaaty'));
$PAGE->set_heading(get_string('forgotpin:emailheading', 'local_oksigeniaaty'));

header('Referrer-Policy: no-referrer');

$key = \local_oksigeniaaty\local\loginkey::live_reset((int) $USER->id);
if ($key !== null) {
    redirect(new moodle_url('/local/oksigeniaaty/open.php', ['key' => $key]));
}

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('newpin:gone', 'local_oksigeniaaty'));
echo html_writer::link(
    new moodle_url('/local/oksigeniaaty/forgotpin.php'),
    get_string('newpin:again', 'local_oksigeniaaty'),
    ['class' => 'btn btn-primary']
);
echo $OUTPUT->footer();
