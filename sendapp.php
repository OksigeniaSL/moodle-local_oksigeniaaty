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
 * "Send the app": whoever the centre allows sends a person, a course or a cohort their way into the app, by e-mail, and
 * sees who has entered already. The links go to each person's own address and never show here.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_oksigeniaaty\local\linkroster;
use local_oksigeniaaty\local\sendapp;
use local_oksigeniaaty\output;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

// One search for a course, a person or a cohort, and what is chosen from it. A «mode» from an old link is ignored.
$q = trim(optional_param('q', '', PARAM_TEXT));
$courseid = optional_param('courseid', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$cohortid = optional_param('cohortid', 0, PARAM_INT);
$whole = optional_param('all', '', PARAM_ALPHA);
$sendall = optional_param('sendall', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$sendto = optional_param('sendto', 0, PARAM_INT);
$resetpin = optional_param('resetpin', 0, PARAM_INT);

$params = [];
if ($courseid && $courseid != SITEID) {
    $params = ['courseid' => $courseid] + ($groupid ? ['groupid' => $groupid] : []);
} else if ($cohortid) {
    $params = ['cohortid' => $cohortid];
} else if ($q !== '') {
    $params = ['q' => $q];
}
// A long list shown whole.
$lists = [linkroster::WAITING, linkroster::SENT, linkroster::INAPP, linkroster::BLOCKED, output\sendapp::PEOPLE];
if ($params && in_array($whole, $lists, true)) {
    $params['all'] = $whole;
} else {
    $whole = '';
}

admin_externalpage_setup('local_oksigeniaaty_sendapp', '', $params, new moodle_url('/local/oksigeniaaty/sendapp.php'));
$title = get_string('sendapp:heading', 'local_oksigeniaaty');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Who the list holds, worked out here on every request, from what the person looking may see: what a request can send
// to, or clear, is never what it says.
$view = sendapp::audience($courseid, $groupid, $cohortid, $q);
if ($view->kind === 'course' && $view->groupid !== $groupid) {
    unset($params['groupid']);
}
$here = new moodle_url('/local/oksigeniaaty/sendapp.php', $params);
$rows = linkroster::rows($view->users);

// What sends or clears comes by POST with the session key; the same in an address does nothing.
if (($sendto || $resetpin || $sendall) && data_submitted()) {
    require_sesskey();

    // One person who forgot their PIN and cannot get it back by e-mail (no address, or an account made for a family):
    // the school clears it, and the next link they open lets them set a new one. Only for someone in the list.
    if ($resetpin) {
        $done = sendapp::clear_pin($rows, $resetpin);
        redirect(
            $here,
            get_string($done ? 'sendapp:pincleared' : 'sendapp:pinnotcleared', 'local_oksigeniaaty'),
            null,
            $done ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING
        );
    }

    // One person, from the list: to send it again (once an hour at most), or for the first time.
    if ($sendto) {
        $done = sendapp::send_one($rows, $sendto);
        redirect(
            $here,
            get_string($done ? 'sendapp:sentone' : 'sendapp:notsent', 'local_oksigeniaaty'),
            null,
            $done ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING
        );
    }

    // Everybody still waiting, once they said yes to the question that opens in place.
    if ($confirm) {
        $result = sendapp::send_waiting($rows);
        if ($result['queued']) {
            $message = get_string('sendapp:queued', 'local_oksigeniaaty', $result['queued']);
        } else {
            $message = get_string('sendapp:sentmany', 'local_oksigeniaaty', $result['sent']);
        }
        if (!empty($result['failed'])) {
            $message .= ' ' . get_string('sendapp:failed', 'local_oksigeniaaty', $result['failed']);
        }
        $done = $result['queued'] || ($result['sent'] && empty($result['failed']));
        redirect(
            $here,
            $message,
            null,
            $done ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING
        );
    }
    redirect($here);
}

echo $OUTPUT->header();
if ($view->kind === 'home' || $view->kind === 'search') {
    echo html_writer::tag('p', get_string('sendapp:intro', 'local_oksigeniaaty'));
}
if (!sendapp::app_on()) {
    echo $OUTPUT->notification(get_string('mobilenotenabled', 'local_oksigeniaaty'), \core\output\notification::NOTIFY_ERROR);
}
echo $OUTPUT->render_from_template(
    'local_oksigeniaaty/sendapp',
    (new output\sendapp($view, $rows, $here, $whole))->export_for_template($OUTPUT)
);
echo $OUTPUT->footer();
