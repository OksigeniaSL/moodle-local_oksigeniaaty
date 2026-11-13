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

use local_oksigeniaaty\form\sendapp_audience;
use local_oksigeniaaty\local\linkroster;
use local_oksigeniaaty\local\linksender;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

$mode = optional_param('mode', 'course', PARAM_ALPHA);
$q = trim(optional_param('q', '', PARAM_TEXT));
$courseid = optional_param('courseid', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$cohortid = optional_param('cohortid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$sendall = optional_param('sendall', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$sendto = optional_param('sendto', 0, PARAM_INT);
$resetpin = optional_param('resetpin', 0, PARAM_INT);

$canseecohorts = has_capability('moodle/cohort:view', context_system::instance());
// Looking for anyone in the whole site is for who may see the site's people; by e-mail or username, for who may see
// those. Sending the app alone does not show everyone's name, photo and whether they use the app.
$cansearch = has_capability('moodle/site:viewparticipants', context_system::instance());
$canseeidentity = has_capability('moodle/site:viewuseridentity', context_system::instance());
$modes = array_merge(['course'], $cansearch ? ['search'] : [], $canseecohorts ? ['cohort'] : []);
if (!in_array($mode, $modes, true)) {
    $mode = 'course';
}

$params = ['mode' => $mode];
if ($mode === 'search' && $q !== '') {
    $params['q'] = $q;
} else if ($mode === 'course' && $courseid) {
    $params += ['courseid' => $courseid] + ($groupid ? ['groupid' => $groupid] : []);
} else if ($mode === 'cohort' && $cohortid) {
    $params['cohortid'] = $cohortid;
}

admin_externalpage_setup('local_oksigeniaaty_sendapp', '', $params, new moodle_url('/local/oksigeniaaty/sendapp.php'));
$title = get_string('sendapp:heading', 'local_oksigeniaaty');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$here = new moodle_url('/local/oksigeniaaty/sendapp.php', $params);

// Who the list holds, worked out here on every request: what a request can send to is never what it says.
$users = [];
$chosen = false;
if ($mode === 'search' && $q !== '') {
    $chosen = true;
    $users = linkroster::search($q, $canseeidentity);
} else if ($mode === 'course' && $courseid && $courseid != SITEID) {
    require_capability('moodle/course:viewparticipants', context_course::instance($courseid));
    // A group left over from the course chosen before.
    if ($groupid && !$DB->record_exists('groups', ['id' => $groupid, 'courseid' => $courseid])) {
        $groupid = 0;
    }
    $chosen = true;
    $users = linkroster::course($courseid, $groupid);
} else if ($mode === 'cohort' && $cohortid) {
    $DB->get_record('cohort', ['id' => $cohortid], 'id', MUST_EXIST);
    $chosen = true;
    $users = linkroster::cohort($cohortid);
}
$rows = linkroster::rows($users);
$canmobile = !empty($CFG->enablewebservices) && !empty($CFG->enablemobilewebservice);

// One person who forgot their PIN and cannot get it back by e-mail (no address, or an account made for a family): the
// school clears it, and the next link they open lets them set a new one. Only for someone in the list on screen.
if ($resetpin && confirm_sesskey()) {
    $inlist = (bool) array_filter($rows, fn($row) => (int) $row['user']->id === $resetpin);
    $done = $inlist && \local_oksigeniaaty\local\pin::has_pin($resetpin);
    if ($done) {
        \local_oksigeniaaty\local\pin::delete($resetpin);
        \local_oksigeniaaty\event\pin_cleared::create([
            'context' => context_system::instance(),
            'relateduserid' => $resetpin,
        ])->trigger();
    }
    redirect(
        $here,
        get_string($done ? 'sendapp:pincleared' : 'sendapp:pinnotcleared', 'local_oksigeniaaty'),
        null,
        $done ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING
    );
}

// One person, from the list: to send it again, or for the first time.
if ($sendto && confirm_sesskey()) {
    $row = current(array_filter($rows, fn($row) => (int) $row['user']->id === $sendto));
    $allowed = $canmobile && $row && in_array($row['state'], [linkroster::WAITING, linkroster::SENT], true)
        && !linksender::just_sent($sendto);
    $result = $allowed ? linksender::send([$sendto]) : ['sent' => 0];
    redirect(
        $here,
        get_string($result['sent'] ? 'sendapp:sentone' : 'sendapp:notsent', 'local_oksigeniaaty'),
        null,
        $result['sent'] ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING
    );
}

$waiting = linkroster::waiting($rows);

// Everybody still waiting: first it asks, as this writes to many people at a stroke.
if ($sendall && (!$waiting || !$canmobile)) {
    redirect($here);
}

if ($sendall && $confirm && confirm_sesskey()) {
    $result = linksender::send($waiting);
    if ($result['queued']) {
        $message = get_string('sendapp:queued', 'local_oksigeniaaty', $result['queued']);
    } else {
        $message = get_string('sendapp:sentmany', 'local_oksigeniaaty', $result['sent']);
    }
    if (!empty($result['failed'])) {
        $message .= ' ' . get_string('sendapp:failed', 'local_oksigeniaaty', $result['failed']);
    }
    $type = empty($result['failed']) ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING;
    redirect($here, $message, null, $type);
}

if ($sendall) {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        count($waiting) === 1
            ? get_string('sendapp:confirmone', 'local_oksigeniaaty')
            : get_string('sendapp:confirm', 'local_oksigeniaaty', count($waiting)),
        new moodle_url($here, ['sendall' => 1, 'confirm' => 1, 'sesskey' => sesskey()]),
        $here
    );
    echo $OUTPUT->footer();
    exit;
}

$tabs = [];
foreach ($modes as $tab) {
    $tabs[] = new tabobject(
        $tab,
        new moodle_url('/local/oksigeniaaty/sendapp.php', ['mode' => $tab]),
        get_string('sendapp:tab' . $tab, 'local_oksigeniaaty')
    );
}

$form = new sendapp_audience(
    new moodle_url('/local/oksigeniaaty/sendapp.php'),
    ['mode' => $mode, 'courseid' => $courseid],
    'get'
);
$form->set_data(['mode' => $mode, 'q' => $q, 'courseid' => $courseid, 'groupid' => $groupid, 'cohortid' => $cohortid]);

$perpage = 50;
$shown = [];
$pagerows = array_slice($rows, $page * $perpage, $perpage);
$withpin = [];
if ($pagerows) {
    [$in, $inparams] = $DB->get_in_or_equal(array_map(fn($row) => (int) $row['user']->id, $pagerows));
    $withpin = array_flip($DB->get_fieldset_select('local_oksigeniaaty_pin', 'userid', "userid $in", $inparams));
}
foreach ($pagerows as $row) {
    $state = $row['state'];
    $until = $row['until'] ? userdate($row['until'], get_string('strftimedate', 'langconfig')) : '';
    if ($state === linkroster::BLOCKED) {
        $label = get_string('sendapp:blocked' . $row['why'], 'local_oksigeniaaty');
    } else if ($state === linkroster::SENT) {
        $label = get_string('sendapp:statesent', 'local_oksigeniaaty', $until);
    } else if ($state === linkroster::WAITING && $until !== '') {
        $label = get_string('sendapp:stateexpired', 'local_oksigeniaaty', $until);
    } else {
        $label = get_string('sendapp:state' . $state, 'local_oksigeniaaty');
    }
    $cansend = $canmobile && in_array($state, [linkroster::WAITING, linkroster::SENT], true);
    $shown[] = [
        'picture' => $OUTPUT->user_picture($row['user'], ['size' => 35, 'link' => false, 'alttext' => false]),
        'name' => fullname($row['user']),
        'state' => $state,
        'label' => $label,
        'cansend' => $cansend,
        'sendurl' => $here->out(false),
        'sendto' => (int) $row['user']->id,
        'sendtext' => get_string($state === linkroster::SENT ? 'sendapp:again' : 'sendapp:send', 'local_oksigeniaaty'),
        'haspin' => isset($withpin[(int) $row['user']->id]),
        'sesskey' => sesskey(),
    ];
}

$counts = linkroster::counts($rows);
$a = count($waiting);
$context = [
    'chosen' => $chosen,
    'hasrows' => (bool) $rows,
    'canmobile' => $canmobile,
    'rows' => $shown,
    'inapp' => $counts[linkroster::INAPP],
    'sent' => $counts[linkroster::SENT],
    'waiting' => $counts[linkroster::WAITING],
    'blocked' => $counts[linkroster::BLOCKED],
    'hasblocked' => $counts[linkroster::BLOCKED] > 0,
    'cansendall' => $canmobile && $a > 0,
    'sendallurl' => (new moodle_url($here, ['sendall' => 1]))->out(false),
    'sendalltext' => get_string($a === 1 ? 'sendapp:sendallone' : 'sendapp:sendall', 'local_oksigeniaaty', $a),
    'alldone' => $chosen && $rows && $a === 0,
    'truncated' => count($users) >= linkroster::MAX,
    'max' => linkroster::MAX,
];

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('sendapp:intro', 'local_oksigeniaaty'));
if (!$canmobile) {
    echo $OUTPUT->notification(get_string('mobilenotenabled', 'local_oksigeniaaty'), \core\output\notification::NOTIFY_ERROR);
}
echo $OUTPUT->tabtree($tabs, $mode);
$form->display();
echo $OUTPUT->render_from_template('local_oksigeniaaty/sendapp', $context);
if (count($rows) > $perpage) {
    echo $OUTPUT->paging_bar(count($rows), $page, $perpage, $here);
}
echo $OUTPUT->footer();
