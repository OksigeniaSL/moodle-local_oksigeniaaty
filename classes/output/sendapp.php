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

namespace local_oksigeniaaty\output;

use local_oksigeniaaty\local\linkroster;
use local_oksigeniaaty\local\sendapp as logic;

/**
 * The page «Send the app» (Jujol, 13 November 2026): one search for a course, a person or a cohort; the courses under way
 * as cards, those with most people still without the app first; and, once one is chosen, a phone for each person, the
 * button for those still waiting, with its question in place, and the people by how they stand.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sendapp implements \renderable, \templatable {
    /** @var string[] The states in the order of the phones, the bar and the legend. */
    private const ORDER = [linkroster::INAPP, linkroster::SENT, linkroster::WAITING, linkroster::BLOCKED];

    /** @var string[] The lists of people, in the order they are shown, with their titles. */
    private const LISTS = [
        linkroster::WAITING => 'sendapp:groupwaiting',
        linkroster::SENT => 'sendapp:groupsent',
        linkroster::INAPP => 'sendapp:groupinapp',
        linkroster::BLOCKED => 'sendapp:groupblocked',
    ];

    /** @var string The list of the people a search found, for «See the whole list». */
    public const PEOPLE = 'people';

    /** @var \stdClass Who the page is about, from logic::audience(). */
    protected $view;

    /** @var array[] How each of them stands, from linkroster::rows(). */
    protected $rows;

    /** @var \moodle_url This page, with what is chosen. */
    protected $here;

    /** @var string The list shown whole, or ''. */
    protected $whole;

    /**
     * The page.
     *
     * @param \stdClass $view Who the page is about, from logic::audience().
     * @param array[] $rows How each of them stands, from linkroster::rows().
     * @param \moodle_url $here This page, with what is chosen.
     * @param string $whole The list shown whole (a state, or «people»), or ''.
     */
    public function __construct(\stdClass $view, array $rows, \moodle_url $here, string $whole = '') {
        $this->view = $view;
        $this->rows = $rows;
        $this->here = $here;
        $this->whole = $whole;
    }

    /**
     * The context of local_oksigeniaaty/sendapp.
     *
     * @param \renderer_base $output The renderer.
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        $rights = logic::rights();
        // The help under the question says only what the search can find for this person.
        if ($rights['people']) {
            $hint = $rights['cohorts'] ? 'sendapp:searchhint' : 'sendapp:searchhintpeople';
        } else {
            $hint = $rights['cohorts'] ? 'sendapp:searchhintcohorts' : 'sendapp:searchhintcourses';
        }
        $context = [
            'searchurl' => (new \moodle_url('/local/oksigeniaaty/sendapp.php'))->out(false),
            'q' => $this->view->q,
            'searchhint' => get_string($hint, 'local_oksigeniaaty'),
            'canmobile' => logic::app_on(),
            'sesskey' => sesskey(),
        ];

        if ($this->view->kind === 'home') {
            $courses = array_map(fn($found) => $this->card($found['course'], $found['rows']), logic::running_courses());
            $cohorts = array_map(fn($cohort) => $this->chip($cohort), logic::cohorts('', logic::COHORTS));
            $context += [
                'home' => true,
                'courses' => $courses,
                'hascourses' => (bool) $courses,
                'cohorts' => $cohorts,
                'hascohorts' => (bool) $cohorts,
            ];
        } else if ($this->view->kind === 'search') {
            $context['results'] = true;
            $context += $this->results($output);
        } else {
            $context['chosen'] = $this->chosen($output);
        }

        return $context;
    }

    /**
     * What a search found: courses, cohorts and, for who may look for them, people.
     *
     * @param \renderer_base $output The renderer.
     * @return array
     */
    protected function results(\renderer_base $output): array {
        $q = $this->view->q;
        $courses = array_map(fn($found) => $this->card($found['course'], $found['rows']), logic::find_courses($q));
        $cohorts = array_map(fn($cohort) => $this->chip($cohort), logic::cohorts($q, logic::FOUND));
        $list = $this->slice($this->rows, self::PEOPLE);
        $people = $this->people($list['rows'], false, $output);

        return [
            'resultstitle' => get_string('sendapp:results', 'local_oksigeniaaty', $q),
            'rescourses' => $courses,
            'hasrescourses' => (bool) $courses,
            'rescohorts' => $cohorts,
            'hasrescohorts' => (bool) $cohorts,
            'respeople' => $people,
            'hasrespeople' => (bool) $people,
            'respeoplemore' => $list['more'],
            'respeoplemoreurl' => $list['moreurl'],
            'respeoplemoretext' => $list['moretext'],
            'noresults' => !$courses && !$cohorts && !$people,
            'noresultstext' => get_string('sendapp:noresults', 'local_oksigeniaaty', $q),
            'truncated' => count($this->view->users) >= linkroster::MAX,
            'max' => linkroster::MAX,
        ];
    }

    /**
     * The course, group or cohort chosen: its phones, the button for who is missing and its people by how they stand.
     *
     * @param \renderer_base $output The renderer.
     * @return array
     */
    protected function chosen(\renderer_base $output): array {
        $component = 'local_oksigeniaaty';
        $counts = linkroster::counts($this->rows);
        $people = count($this->rows);
        $waiting = $counts[linkroster::WAITING];
        $appon = logic::app_on();

        if ($this->view->kind === 'course') {
            $course = $this->view->course;
            $name = format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]);
            $a = (object) ['area' => $this->area($course), 'people' => $people];
            $detail = get_string($people === 1 ? 'sendapp:chosendetailone' : 'sendapp:chosendetail', $component, $a);
            $groups = $this->groups($course);
        } else {
            $cohort = $this->view->cohort;
            $name = format_string($cohort->name, true, ['context' => \context::instance_by_id($cohort->contextid)]);
            $detail = get_string($people === 1 ? 'sendapp:cohortdetailone' : 'sendapp:cohortdetail', $component, $people);
            $groups = [];
        }

        $lists = [];
        foreach (self::LISTS as $state => $title) {
            $staterows = array_values(array_filter($this->rows, fn($row) => $row['state'] === $state));
            if (!$staterows) {
                continue;
            }
            $lists[] = ['state' => $state, 'title' => get_string($title, $component), 'open' => $state !== linkroster::INAPP]
                + $this->slice($staterows, $state);
        }
        // Who has a PIN, in one query for all the people on screen.
        $shown = [];
        foreach ($lists as $list) {
            $shown = array_merge($shown, $list['rows']);
        }
        $withpin = logic::with_pin(array_map(fn($row) => (int) $row['user']->id, $shown));
        foreach ($lists as $i => $list) {
            $lists[$i]['rows'] = $this->people($list['rows'], true, $output, $withpin);
        }

        $legend = [];
        foreach (self::ORDER as $state) {
            if ($counts[$state]) {
                $one = $counts[$state] === 1 && in_array($state, [linkroster::WAITING, linkroster::BLOCKED], true);
                $legend[] = [
                    'state' => $state,
                    'count' => $counts[$state],
                    'label' => get_string('sendapp:count' . $state . ($one ? 'one' : ''), $component),
                ];
            }
        }

        return [
            'backurl' => (new \moodle_url('/local/oksigeniaaty/sendapp.php'))->out(false),
            'name' => $name,
            'detail' => $detail,
            'groups' => $groups,
            'hasgroups' => (bool) $groups,
            'empty' => !$people,
            'hasphones' => $people <= logic::PHONES,
            'phones' => $people <= logic::PHONES ? $this->phones($counts) : [],
            'bar' => $this->bar($counts),
            'legend' => $legend,
            'cansendall' => $appon && $waiting > 0,
            'sendalltext' => get_string($waiting === 1 ? 'sendapp:sendallone' : 'sendapp:sendall', $component, $waiting),
            'confirmtext' => $waiting === 1
                ? get_string('sendapp:confirmone', $component)
                : get_string('sendapp:confirm', $component, $waiting),
            'yestext' => get_string($waiting === 1 ? 'sendapp:yesone' : 'sendapp:yes', $component),
            'sendallurl' => $this->here->out(false),
            'sesskey' => sesskey(),
            'alldone' => $waiting === 0 && $counts[linkroster::INAPP] + $counts[linkroster::SENT] > 0,
            'lists' => $lists,
            'truncated' => count($this->view->users) >= linkroster::MAX,
            'max' => linkroster::MAX,
        ];
    }

    /**
     * Some people of a list, up to the most a list shows unless it is the one asked for whole, and the link to all of them.
     *
     * @param array[] $rows The people, from linkroster::rows().
     * @param string $list Which list (a state, or «people»).
     * @return array rows (still from linkroster::rows()), count, expanded, more, moreurl and moretext.
     */
    protected function slice(array $rows, string $list): array {
        $whole = $this->whole === $list;
        $shown = $whole ? $rows : array_slice($rows, 0, logic::PERLIST);
        $more = count($shown) < count($rows);
        $moreurl = new \moodle_url($this->here, ['all' => $list], 'local-oksigeniaaty-send-' . $list);

        return [
            'rows' => $shown,
            'count' => count($rows),
            'expanded' => $whole,
            'more' => $more,
            'moreurl' => $more ? $moreurl->out(false) : '',
            'moretext' => $more ? get_string('sendapp:seeall', 'local_oksigeniaaty', count($rows)) : '',
        ];
    }

    /**
     * The rows of some people for local_oksigeniaaty/sendapp_row.
     *
     * @param array[] $rows The people, from linkroster::rows().
     * @param bool $short In a list of one state, whose title says it already: the short labels.
     * @param \renderer_base $output The renderer.
     * @param array|null $withpin Who has a PIN (user ids as keys), or null to look it up.
     * @return array[]
     */
    protected function people(array $rows, bool $short, \renderer_base $output, ?array $withpin = null): array {
        $component = 'local_oksigeniaaty';
        $appon = logic::app_on();
        $canfix = has_capability('moodle/user:update', \context_system::instance());
        $withpin = $withpin ?? logic::with_pin(array_map(fn($row) => (int) $row['user']->id, $rows));
        $sendurl = $this->here->out(false);

        $people = [];
        foreach ($rows as $row) {
            $id = (int) $row['user']->id;
            $state = $row['state'];
            // The day without the year, as a link lasts a week; with it, for one of another year.
            $thisyear = !$row['until'] || userdate($row['until'], '%Y') === userdate(time(), '%Y');
            $format = get_string($thisyear ? 'strftimedateshort' : 'strftimedate', 'langconfig');
            $until = $row['until'] ? userdate($row['until'], $format) : '';
            if ($state === linkroster::BLOCKED) {
                $label = get_string('sendapp:blocked' . $row['why'], $component);
            } else if ($state === linkroster::SENT) {
                $label = get_string($short ? 'sendapp:stateuntil' : 'sendapp:statesent', $component, $until);
            } else if ($state === linkroster::WAITING && $until !== '') {
                $label = get_string('sendapp:stateexpired', $component, $until);
            } else if ($state === linkroster::INAPP && $short) {
                $label = '';
            } else {
                $label = get_string('sendapp:state' . $state, $component);
            }
            $fix = $canfix && $row['why'] === 'noemail';
            $people[] = [
                'picture' => $output->user_picture($row['user'], ['size' => 35, 'link' => false, 'alttext' => false]),
                'name' => fullname($row['user']),
                'state' => $state,
                'label' => $label,
                'cansend' => $appon && in_array($state, [linkroster::WAITING, linkroster::SENT], true),
                'again' => $state === linkroster::SENT,
                'sendurl' => $sendurl,
                'sendto' => $id,
                'sesskey' => sesskey(),
                'sendtext' => get_string($state === linkroster::SENT ? 'sendapp:again' : 'sendapp:send', $component),
                'haspin' => isset($withpin[$id]),
                'fixurl' => $fix ? (new \moodle_url('/user/editadvanced.php', ['id' => $id]))->out(false) : '',
                'fixtext' => $fix ? get_string('sendapp:fixemail', $component) : '',
            ];
        }

        return $people;
    }

    /**
     * A course as a card for local_oksigeniaaty/sendapp_course.
     *
     * @param \stdClass $course The course.
     * @param array[] $rows How its people stand.
     * @return array
     */
    protected function card(\stdClass $course, array $rows): array {
        $component = 'local_oksigeniaaty';
        $counts = linkroster::counts($rows);
        $people = count($rows);
        $waiting = $counts[linkroster::WAITING];
        $a = (object) ['area' => $this->area($course), 'people' => $people, 'inapp' => $counts[linkroster::INAPP]];

        return [
            'id' => (int) $course->id,
            'url' => (new \moodle_url('/local/oksigeniaaty/sendapp.php', ['courseid' => $course->id]))->out(false),
            'name' => format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]),
            'detail' => get_string($people === 1 ? 'sendapp:coursedetailone' : 'sendapp:coursedetail', $component, $a),
            'waiting' => $waiting,
            'badge' => get_string($waiting === 1 ? 'sendapp:badgewaitingone' : 'sendapp:badgewaiting', $component),
            'alldone' => $waiting === 0 && $counts[linkroster::INAPP] + $counts[linkroster::SENT] > 0,
            'bar' => $this->bar($counts),
        ];
    }

    /**
     * A cohort as a chip.
     *
     * @param \stdClass $cohort The cohort, with its total.
     * @return array
     */
    protected function chip(\stdClass $cohort): array {
        return [
            'url' => (new \moodle_url('/local/oksigeniaaty/sendapp.php', ['cohortid' => $cohort->id]))->out(false),
            'name' => format_string($cohort->name, true, ['context' => \context::instance_by_id($cohort->contextid)]),
            'total' => (int) $cohort->total,
        ];
    }

    /**
     * The groups of a course as chips, the whole course first, or none if it has no groups.
     *
     * @param \stdClass $course The course.
     * @return array[]
     */
    protected function groups(\stdClass $course): array {
        $groups = groups_get_all_groups($course->id);
        if (!$groups) {
            return [];
        }
        $context = \context_course::instance($course->id);
        $url = new \moodle_url('/local/oksigeniaaty/sendapp.php', ['courseid' => $course->id]);
        $chips = [[
            'url' => $url->out(false),
            'name' => get_string('sendapp:wholecourse', 'local_oksigeniaaty'),
            'current' => !$this->view->groupid,
        ]];
        foreach ($groups as $group) {
            $chips[] = [
                'url' => (new \moodle_url($url, ['groupid' => $group->id]))->out(false),
                'name' => format_string($group->name, true, ['context' => $context]),
                'current' => (int) $group->id === (int) $this->view->groupid,
            ];
        }

        return $chips;
    }

    /**
     * The category of a course, as the area it belongs to.
     *
     * @param \stdClass $course The course.
     * @return string
     */
    protected function area(\stdClass $course): string {
        $category = \core_course_category::get($course->category, IGNORE_MISSING, true);

        return $category ? $category->get_formatted_name() : '';
    }

    /**
     * A phone for each person, in the order of the states.
     *
     * @param int[] $counts By state.
     * @return array[]
     */
    protected function phones(array $counts): array {
        $phones = [];
        foreach (self::ORDER as $state) {
            $phones = array_merge($phones, array_fill(0, $counts[$state], ['state' => $state]));
        }

        return $phones;
    }

    /**
     * A bar with a share for each state that has someone.
     *
     * @param int[] $counts By state.
     * @return array[]
     */
    protected function bar(array $counts): array {
        $bar = [];
        foreach (self::ORDER as $state) {
            if ($counts[$state]) {
                $bar[] = ['state' => $state, 'count' => $counts[$state]];
            }
        }

        return $bar;
    }
}
