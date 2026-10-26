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
 * Public page: email a PIN-reset link to a user who forgot their Aty PIN.
 *
 * Pre-login, like Moodle's own "forgot password". The link is a one-time reset key (a different kind from an ordinary
 * sign-in key), sent only to the user's registered email, so an intercepted sign-in link can never reset the PIN. The
 * reply is the same whether or not the account exists, so this does not reveal who has an account, and a short cooldown
 * stops it being used to flood an inbox.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

/** @var int How long before another reset email may be sent for the same user, in seconds. */
const LOCAL_OKSIGENIAATY_RESEND_COOLDOWN = 300;

$search = optional_param('search', '', PARAM_RAW_TRIMMED);
$submitted = optional_param('submitted', 0, PARAM_BOOL);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/oksigeniaaty/forgotpin.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('forgotpin:heading', 'local_oksigeniaaty'));
$PAGE->set_heading(get_string('forgotpin:heading', 'local_oksigeniaaty'));

header('Referrer-Policy: no-referrer');

if ($submitted && confirm_sesskey() && $search !== '') {
    // Find exactly one active, confirmed, local account by username or (unambiguous) email. Never say whether we did.
    $base = ['mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0, 'suspended' => 0, 'confirmed' => 1];
    $user = $DB->get_record('user', ['username' => core_text::strtolower($search)] + $base);
    if (!$user && validate_email($search)) {
        $matches = $DB->get_records('user', ['email' => core_text::strtolower($search)] + $base);
        $user = count($matches) === 1 ? reset($matches) : null;
    }

    if ($user && !isguestuser($user)) {
        $systemcontext = context_system::instance();
        $eligible = !is_siteadmin($user)
            && !has_capability('moodle/site:config', $systemcontext, $user)
            && local_oksigeniaaty_user_can_selfservice($user->id)
            && \local_oksigeniaaty\local\pin::has_pin($user->id);

        $last = \local_oksigeniaaty\local\loginkey::last_reset_request($user->id);
        $cooled = $last === null || $last < time() - LOCAL_OKSIGENIAATY_RESEND_COOLDOWN;

        if ($eligible && $cooled) {
            $key = \local_oksigeniaaty\local\loginkey::create_reset($user->id);
            $openurl = new moodle_url('/local/oksigeniaaty/open.php', ['key' => $key]);

            $data = (object) ['name' => fullname($user), 'link' => $openurl->out(false)];
            $subject = get_string('forgotpin:emailsubject', 'local_oksigeniaaty');
            $bodytext = get_string('forgotpin:emailbody', 'local_oksigeniaaty', $data);

            email_to_user($user, core_user::get_support_user(), $subject, $bodytext);
        }
    }

    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('forgotpin:sent', 'local_oksigeniaaty'), \core\output\notification::NOTIFY_SUCCESS);
    echo html_writer::link(new moodle_url('/'), get_string('continue'), ['class' => 'btn btn-primary']);
    echo $OUTPUT->footer();

    exit;
}

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('forgotpin:intro', 'local_oksigeniaaty'));
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submitted', 'value' => 1]);
echo html_writer::label(get_string('forgotpin:search', 'local_oksigeniaaty'), 'oksigeniaaty-search');
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'search',
    'id' => 'oksigeniaaty-search',
    'class' => 'form-control',
    'autocomplete' => 'username',
    'required' => 'required',
]);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-primary mt-3',
    'value' => get_string('forgotpin:submit', 'local_oksigeniaaty'),
]);
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
