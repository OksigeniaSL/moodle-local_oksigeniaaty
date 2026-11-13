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

namespace local_oksigeniaaty\local;

/**
 * «Send the app»: who the person looking may reach, and what they may do with them.
 *
 * Everything is worked out on the server on every request, from what the person looking may see: what a request can send
 * to, or clear, is never what it says. Courses need the course's participants, cohorts the cohorts, and a person from
 * anywhere in the site, the site's participants.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sendapp {
    /** @var int The courses under way shown on the first screen. */
    public const CARDS = 12;

    /** @var int The courses under way with most people whose states are counted, to choose those cards from. */
    public const CANDIDATES = 24;

    /** @var int The courses, and the cohorts, a search shows. */
    public const FOUND = 6;

    /** @var int The cohorts shown on the first screen; the search finds the rest. */
    public const COHORTS = 12;

    /** @var int Up to this many people, a phone for each; more, a bar. */
    public const PHONES = 60;

    /** @var int The people a list shows before «See the whole list». */
    public const PERLIST = 48;

    /**
     * What the person looking may do across the site.
     *
     * @return bool[] 'people' (look for anyone in the site), 'identity' (by username or e-mail too), 'cohorts'.
     */
    public static function rights(): array {
        $system = \context_system::instance();

        return [
            'people' => has_capability('moodle/site:viewparticipants', $system),
            'identity' => has_capability('moodle/site:viewuseridentity', $system),
            'cohorts' => has_capability('moodle/cohort:view', $system),
        ];
    }

    /**
     * Whether the site has the app on: without it, no link can work.
     *
     * @return bool
     */
    public static function app_on(): bool {
        global $CFG;

        return !empty($CFG->enablewebservices) && !empty($CFG->enablemobilewebservice);
    }

    /**
     * Who the page is about, from what it was asked: a course or one of its groups, a cohort, a search, or nothing yet.
     *
     * @param int $courseid The course, or 0.
     * @param int $groupid One of its groups, or 0 for the whole course.
     * @param int $cohortid The cohort, or 0.
     * @param string $q What was looked for.
     * @return \stdClass kind (course, cohort, search or home), course, groupid, cohort, q and users.
     * @throws \required_capability_exception When the person looking may not see those people.
     */
    public static function audience(int $courseid, int $groupid, int $cohortid, string $q): \stdClass {
        global $DB;

        $view = (object) ['kind' => 'home', 'course' => null, 'groupid' => 0, 'cohort' => null, 'q' => '', 'users' => []];
        $q = trim($q);
        if ($courseid && $courseid != SITEID) {
            $course = get_course($courseid);
            $context = \context_course::instance($courseid);
            require_capability('moodle/course:viewparticipants', $context);
            if (!$course->visible) {
                require_capability('moodle/course:viewhiddencourses', $context);
            }
            // A group left over from the course chosen before.
            if ($groupid && !$DB->record_exists('groups', ['id' => $groupid, 'courseid' => $courseid])) {
                $groupid = 0;
            }
            $view->kind = 'course';
            $view->course = $course;
            $view->groupid = $groupid;
            $view->users = linkroster::course($courseid, $groupid);
        } else if ($cohortid) {
            $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
            require_capability('moodle/cohort:view', \context_system::instance());
            require_capability('moodle/cohort:view', \context::instance_by_id($cohort->contextid));
            $view->kind = 'cohort';
            $view->cohort = $cohort;
            $view->users = linkroster::cohort($cohortid);
        } else if ($q !== '') {
            $rights = self::rights();
            $view->kind = 'search';
            $view->q = $q;
            // Without the site's participants, a search finds courses and cohorts, and nobody by name.
            $view->users = $rights['people'] ? linkroster::search($q, $rights['identity']) : [];
        }

        return $view;
    }

    /**
     * Whether the person looking may see a course's people here.
     *
     * @param \stdClass $course The course, with its visibility.
     * @return bool
     */
    public static function can_see_course(\stdClass $course): bool {
        $context = \context_course::instance($course->id);

        return has_capability('moodle/course:viewparticipants', $context)
            && ($course->visible || has_capability('moodle/course:viewhiddencourses', $context));
    }

    /**
     * The courses under way whose people the person looking may see, those with most people still waiting first.
     *
     * Those the person may not see are left out before counting, so that how many there are says nothing about the rest
     * of the site. Only the biggest of them are counted, so that a site with hundreds of courses does not weigh.
     *
     * @return array[] A list of ['course', 'rows'].
     */
    public static function running_courses(): array {
        global $DB;

        $now = time();
        $ctxselect = \context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT c.id, c.fullname, c.shortname, c.category, c.visible, n.people, $ctxselect
                  FROM {course} c
                  JOIN (SELECT e.courseid, COUNT(DISTINCT ue.userid) AS people
                          FROM {enrol} e
                          JOIN {user_enrolments} ue ON ue.enrolid = e.id
                          JOIN {user} u ON u.id = ue.userid
                         WHERE e.status = :enabled
                           AND ue.status = :active
                           AND ue.timestart <= :now1
                           AND (ue.timeend = 0 OR ue.timeend > :now2)
                           AND u.deleted = 0
                           AND u.suspended = 0
                      GROUP BY e.courseid) n ON n.courseid = c.id
                  JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :level
                 WHERE c.id <> :siteid
                   AND c.visible = 1
                   AND c.startdate <= :now3
                   AND (c.enddate = 0 OR c.enddate > :now4)
              ORDER BY n.people DESC, c.fullname, c.id";
        $params = [
            'enabled' => ENROL_INSTANCE_ENABLED,
            'active' => ENROL_USER_ACTIVE,
            'now1' => $now,
            'now2' => $now,
            'level' => CONTEXT_COURSE,
            'siteid' => SITEID,
            'now3' => $now,
            'now4' => $now,
        ];

        $found = self::visible_courses($DB->get_recordset_sql($sql, $params), self::CANDIDATES);
        usort($found, fn($a, $b) => [-linkroster::counts($a['rows'])[linkroster::WAITING], $a['course']->fullname]
            <=> [-linkroster::counts($b['rows'])[linkroster::WAITING], $b['course']->fullname]);

        return array_slice($found, 0, self::CARDS);
    }

    /**
     * The courses whose full or short name contains a text, among those whose people the person looking may see.
     *
     * @param string $text The text.
     * @return array[] A list of ['course', 'rows'].
     */
    public static function find_courses(string $text): array {
        global $DB;

        $text = trim($text);
        if ($text === '') {
            return [];
        }
        $ctxselect = \context_helper::get_preload_record_columns_sql('ctx');
        $full = $DB->sql_like('c.fullname', ':full', false, false);
        $short = $DB->sql_like('c.shortname', ':short', false, false);
        $sql = "SELECT c.id, c.fullname, c.shortname, c.category, c.visible, $ctxselect
                  FROM {course} c
                  JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :level
                 WHERE c.id <> :siteid
                   AND ($full OR $short)
              ORDER BY c.fullname, c.id";
        $like = '%' . $DB->sql_like_escape($text) . '%';
        $params = ['level' => CONTEXT_COURSE, 'siteid' => SITEID, 'full' => $like, 'short' => $like];

        return self::visible_courses($DB->get_recordset_sql($sql, $params), self::FOUND);
    }

    /**
     * The cohorts the person looking may see, the biggest first, with how many people can sign in in each.
     *
     * @param string $text Part of the name or the id number, or nothing for all.
     * @param int $limit How many at most.
     * @return \stdClass[] id, name, contextid and total.
     */
    public static function cohorts(string $text, int $limit): array {
        global $DB;

        if (!self::rights()['cohorts']) {
            return [];
        }
        $text = trim($text);
        $where = '';
        $params = [];
        if ($text !== '') {
            $where = 'WHERE ' . $DB->sql_like('h.name', ':name', false, false)
                . ' OR ' . $DB->sql_like('h.idnumber', ':idnumber', false, false);
            $like = '%' . $DB->sql_like_escape($text) . '%';
            $params = ['name' => $like, 'idnumber' => $like];
        }
        $sql = "SELECT h.id, h.name, h.contextid, COUNT(u.id) AS total
                  FROM {cohort} h
             LEFT JOIN {cohort_members} m ON m.cohortid = h.id
             LEFT JOIN {user} u ON u.id = m.userid AND u.deleted = 0 AND u.suspended = 0
                $where
              GROUP BY h.id, h.name, h.contextid
              ORDER BY COUNT(u.id) DESC, h.name, h.id";

        $cohorts = [];
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $cohort) {
            if (has_capability('moodle/cohort:view', \context::instance_by_id($cohort->contextid))) {
                $cohort->total = (int) $cohort->total;
                $cohorts[] = $cohort;
            }
            if (count($cohorts) >= $limit) {
                break;
            }
        }
        $records->close();

        return $cohorts;
    }

    /**
     * Send the app to one person of the list: for the first time, or again, once an hour at most.
     *
     * @param array[] $rows The list, from linkroster::rows().
     * @param int $userid The person.
     * @return bool Whether it went.
     */
    public static function send_one(array $rows, int $userid): bool {
        $row = self::row_of($rows, $userid);
        $allowed = self::app_on()
            && $row
            && in_array($row['state'], [linkroster::WAITING, linkroster::SENT], true)
            && !linksender::just_sent($userid);

        return $allowed && linksender::send([$userid])['sent'] > 0;
    }

    /**
     * Send the app to everybody in the list who is still waiting: no link that works, and not in the app.
     *
     * @param array[] $rows The list, from linkroster::rows().
     * @return array ['sent', 'failed', 'queued'], as linksender::send().
     */
    public static function send_waiting(array $rows): array {
        $waiting = linkroster::waiting($rows);
        if (!$waiting || !self::app_on()) {
            return ['sent' => 0, 'failed' => 0, 'queued' => 0];
        }

        return linksender::send($waiting);
    }

    /**
     * Clear the PIN of one person of the list who forgot it and cannot get it back by e-mail (no address, or an account
     * made for a family): the next link they open lets them set a new one. It is logged.
     *
     * @param array[] $rows The list, from linkroster::rows().
     * @param int $userid The person.
     * @return bool Whether there was one to clear.
     */
    public static function clear_pin(array $rows, int $userid): bool {
        if (!self::row_of($rows, $userid) || !pin::has_pin($userid)) {
            return false;
        }
        pin::delete($userid);
        \local_oksigeniaaty\event\pin_cleared::create([
            'context' => \context_system::instance(),
            'relateduserid' => $userid,
        ])->trigger();

        return true;
    }

    /**
     * Who among some people has a PIN, in one query.
     *
     * @param int[] $userids The people.
     * @return array User ids as keys.
     */
    public static function with_pin(array $userids): array {
        global $DB;

        if (!$userids) {
            return [];
        }
        [$in, $params] = $DB->get_in_or_equal($userids);

        return array_flip(array_map('intval', $DB->get_fieldset_select('local_oksigeniaaty_pin', 'userid', "userid $in", $params)));
    }

    /**
     * The first courses of a list whose people the person looking may see, each with how its people stand.
     *
     * @param \moodle_recordset $records The courses, with their context.
     * @param int $limit How many at most.
     * @return array[] A list of ['course', 'rows'].
     */
    private static function visible_courses(\moodle_recordset $records, int $limit): array {
        $courses = [];
        foreach ($records as $course) {
            \context_helper::preload_from_record($course);
            if (self::can_see_course($course)) {
                $courses[] = $course;
            }
            if (count($courses) >= $limit) {
                break;
            }
        }
        $records->close();

        return array_map(fn($course) => [
            'course' => $course,
            'rows' => linkroster::rows(linkroster::course((int) $course->id)),
        ], $courses);
    }

    /**
     * One person's row in a list.
     *
     * @param array[] $rows The list.
     * @param int $userid The person.
     * @return array|null
     */
    private static function row_of(array $rows, int $userid): ?array {
        foreach ($rows as $row) {
            if ((int) $row['user']->id === $userid) {
                return $row;
            }
        }

        return null;
    }
}
