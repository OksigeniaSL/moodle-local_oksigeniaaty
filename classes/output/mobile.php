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
 * Output for the Moodle app.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oksigeniaaty\output;

/**
 * Builds what the app shows: for now, the per-site home tab.
 */
class mobile {
    /**
     * The home tab of the site, rendered from the plugin settings.
     *
     * The real home (cards, shortcuts and the site CSS) is built from these settings in a later version; this skeleton
     * shows the configured heading and welcome so the handler can be wired and tried in the app.
     *
     * @param array $args The arguments the app sends (site URL, user id, language...).
     * @return array The template, javascript and data the app expects.
     */
    public static function mobile_home(array $args): array {
        global $OUTPUT, $CFG, $USER;

        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

        $config = get_config('local_oksigeniaaty');
        $heading = !empty($config->homeheading) ? $config->homeheading : format_string(get_site()->fullname);
        $intro = !empty($config->homeintro) ? $config->homeintro : '';
        $shortcuts = local_oksigeniaaty_get_shortcuts();
        $courses = self::courses();
        $events = self::events();
        $grading = self::grading();

        $data = [
            'logourl' => !empty($config->homelogourl) ? $config->homelogourl : '',
            'greeting' => trim($USER->firstname) !== ''
                ? get_string('home:greeting', 'local_oksigeniaaty', format_string($USER->firstname))
                : '',
            'heading' => $heading,
            'intro' => $intro,
            'gradingheading' => get_string('home:tograde', 'local_oksigeniaaty'),
            'grading' => $grading,
            'hasgrading' => !empty($grading),
            'eventsheading' => get_string('home:upcoming', 'local_oksigeniaaty'),
            'events' => $events,
            'hasevents' => !empty($events),
            'shortcutsheading' => get_string('home:shortcuts', 'local_oksigeniaaty'),
            'shortcuts' => $shortcuts,
            'hasshortcuts' => !empty($shortcuts),
            'coursesheading' => get_string('home:mycourses', 'local_oksigeniaaty'),
            'courses' => $courses,
            'hascourses' => !empty($courses),
            'opensoutside' => get_string('home:opensoutside', 'local_oksigeniaaty'),
        ];

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('local_oksigeniaaty/mobile_home', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => [],
        ];
    }

    /**
     * The user's courses for the home panel: branded cards (gradient + initial) that open in the app.
     *
     * @return array A list of { name, initial, hue, viewurl }, at most eight.
     */
    private static function courses(): array {
        $mycourses = enrol_get_my_courses(['id', 'fullname'], 'visible DESC, sortorder ASC');

        $courses = [];
        foreach ($mycourses as $course) {
            if ($course->id == SITEID) {
                continue;
            }

            $name = format_string($course->fullname);
            $initial = \core_text::strtoupper(\core_text::substr(trim($name), 0, 1));
            $courses[] = [
                'name' => $name,
                'initial' => $initial !== '' ? $initial : '#',
                // A small per-course hue shift (-30 to +30 degrees) so the brand cards are not all identical.
                'hue' => ($course->id * 37) % 61 - 30,
                'viewurl' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            ];

            if (count($courses) >= 8) {
                break;
            }
        }

        return $courses;
    }

    /**
     * For teachers: the assignments with submissions waiting for a grade, the one waiting longest first.
     *
     * One query finds the assignments of the user's courses with a submission newer than its grade; Moodle's own count
     * then settles each of them (enrolled students only, and the teacher's group when the activity uses groups), so the
     * numbers match the assignment's grading summary. Only where the user may grade.
     *
     * @return array A list of { name, context, count, countlabel, viewurl }, at most five.
     */
    private static function grading(): array {
        global $CFG, $DB;

        $courses = enrol_get_my_courses(['id', 'shortname']);
        $gradable = [];
        foreach ($courses as $course) {
            if ($course->id != SITEID && has_capability('mod/assign:grade', \context_course::instance($course->id))) {
                $gradable[$course->id] = $course;
            }
        }
        if (empty($gradable)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal(array_keys($gradable), SQL_PARAMS_NAMED);
        $params['submitted'] = 'submitted';
        $candidates = $DB->get_records_sql(
            "SELECT a.id, a.course, MIN(s.timemodified) AS oldest
               FROM {assign} a
               JOIN {assign_submission} s ON s.assignment = a.id
          LEFT JOIN {assign_grades} g ON g.assignment = s.assignment AND g.userid = s.userid
                                     AND g.attemptnumber = s.attemptnumber
              WHERE a.course $insql
                AND s.latest = 1 AND s.status = :submitted AND s.timemodified IS NOT NULL
                AND (g.id IS NULL OR g.grade IS NULL OR g.grade = -1 OR s.timemodified >= g.timemodified)
           GROUP BY a.id, a.course
           ORDER BY MIN(s.timemodified) ASC",
            $params
        );
        if (empty($candidates)) {
            return [];
        }

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $list = [];
        foreach ($candidates as $candidate) {
            $cm = get_fast_modinfo($candidate->course)->get_instances_of('assign')[$candidate->id] ?? null;
            if (!$cm || !$cm->uservisible || !has_capability('mod/assign:grade', $cm->context)) {
                continue;
            }
            $assign = new \assign($cm->context, $cm, $gradable[$candidate->course]);
            $count = (int) $assign->count_submissions_need_grading();
            if ($count < 1) {
                continue;
            }
            $list[] = [
                'name' => format_string($cm->name, true, ['context' => $cm->context]),
                'context' => format_string($gradable[$candidate->course]->shortname),
                'count' => $count,
                'countlabel' => get_string($count === 1 ? 'home:submission' : 'home:submissions', 'local_oksigeniaaty'),
                'viewurl' => (new \moodle_url('/mod/assign/view.php', ['id' => $cm->id]))->out(false),
            ];
            if (count($list) >= 5) {
                break;
            }
        }

        return $list;
    }

    /**
     * The user's upcoming events for the home panel: a short agenda of what is scheduled or due over the next week.
     *
     * Built from the same filters as the site calendar (the user's courses, their groups and their own events), so the
     * app agenda matches what the user sees on the web.
     *
     * @return array A list of { name, day, month, time, context, viewurl }, soonest first, at most five.
     */
    private static function events(): array {
        global $CFG;

        require_once($CFG->dirroot . '/calendar/lib.php');

        $courses = calendar_get_default_courses();
        // The calendar's own filters: course ids, group ids and the user id (or false for each when not applicable).
        [$courseids, $groupids, $userids] = calendar_set_filters($courses);

        $now = time();
        $events = calendar_get_legacy_events($now, $now + 7 * DAYSECS, $userids, $groupids, $courseids, true, true);
        if (empty($events)) {
            return [];
        }

        // Soonest first, so the agenda reads top to bottom in time.
        \core_collator::asort_objects_by_property($events, 'timestart', \core_collator::SORT_NUMERIC);

        // A course id to short name map, to label each event with its course.
        $shortnames = [];
        foreach ($courses as $course) {
            $shortnames[$course->id] = format_string($course->shortname);
        }

        $agenda = [];
        foreach ($events as $event) {
            // All-day events (a day or longer) show just the date; timed ones also show the start time.
            $isallday = (int) $event->timeduration >= DAYSECS;
            $labels = [];
            if (!empty($event->courseid) && $event->courseid != SITEID && isset($shortnames[$event->courseid])) {
                $labels[] = $shortnames[$event->courseid];
            }
            // An activity's dates are named by Moodle like "Assignment is due": show the activity's own name and say
            // what the date is next to the course ("Customer care · Due").
            $name = format_string($event->name);
            $activity = self::activityname($event);
            $kind = 'home:event:' . $event->eventtype;
            if ($activity !== null && get_string_manager()->string_exists($kind, 'local_oksigeniaaty')) {
                $name = $activity;
                $labels[] = get_string($kind, 'local_oksigeniaaty');
            }
            $agenda[] = [
                'name' => $name,
                // The app redraws the date and time from this in the phone's own time zone, as its calendar does; the
                // server's text (in the user's Moodle time zone) is only what shows before that.
                'timestamp' => (int) $event->timestart * 1000,
                'day' => ltrim(userdate($event->timestart, '%e')),
                'month' => \core_text::strtoupper(trim(userdate($event->timestart, '%b'))),
                'time' => $isallday ? '' : userdate($event->timestart, get_string('strftimetime', 'langconfig')),
                'context' => implode(' · ', $labels),
                'viewurl' => (new \moodle_url('/calendar/view.php', ['view' => 'day', 'time' => $event->timestart]))->out(false),
            ];
            if (count($agenda) >= 5) {
                break;
            }
        }

        return $agenda;
    }

    /**
     * The name of the activity an event belongs to, or null when it is not an activity's event or the activity is not
     * visible to the user.
     *
     * @param \stdClass $event A legacy calendar event.
     * @return string|null
     */
    private static function activityname(\stdClass $event): ?string {
        if (empty($event->modulename) || empty($event->instance) || empty($event->courseid)) {
            return null;
        }
        try {
            $instances = get_fast_modinfo($event->courseid)->get_instances_of($event->modulename);
        } catch (\moodle_exception $e) {
            return null;
        }
        $cm = $instances[$event->instance] ?? null;

        return $cm && $cm->uservisible ? $cm->get_formatted_name() : null;
    }
}
