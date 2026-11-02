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

use local_oksigeniaaty\local\channels;
use local_oksigeniaaty\local\homecards;
use local_oksigeniaaty\local\template;

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
        $logourl = !empty($config->homelogourl) ? $config->homelogourl : '';
        $pictureurl = '';
        // In a site shared by several schools or libraries, the home is the user's own one's.
        $brand = \local_oksigeniaaty\local\brand::for_user((int) $USER->id);
        if ($brand !== null) {
            $heading = format_string($brand['name']);
            $intro = format_string($brand['tagline']);
            $logourl = \local_oksigeniaaty\local\brand::logo_for_app($brand['logo'], (int) $USER->id);
            $pictureurl = \local_oksigeniaaty\local\brand::logo_for_app($brand['picture'], (int) $USER->id);
        }
        $shortcuts = local_oksigeniaaty_get_shortcuts();
        $courses = self::courses();
        [$today, $upcoming] = self::split_events(self::events());
        $grading = self::grading();
        $unread = self::unread();
        $children = self::children();

        $data = template::inert([
            'logourl' => $logourl,
            'pictureurl' => $pictureurl,
            'greeting' => trim($USER->firstname) !== ''
                ? get_string('home:greeting', 'local_oksigeniaaty', format_string($USER->firstname))
                : '',
            'heading' => $heading,
            'intro' => $intro,
            'familyheading' => get_string('family:heading', 'local_oksigeniaaty'),
            'hasfamily' => $children !== null,
            'children' => $children ?? [],
            'familyaddname' => get_string('family:add', 'local_oksigeniaaty'),
            'todayheading' => get_string('home:today', 'local_oksigeniaaty'),
            'today' => $today,
            'hastoday' => !empty($today) || $unread > 0,
            'hasunread' => $unread > 0,
            'unread' => $unread,
            'unreadlabel' => get_string('home:unread', 'local_oksigeniaaty'),
            'messagesname' => get_string('home:messages', 'local_oksigeniaaty'),
            'messagesurl' => (new \moodle_url('/message/index.php'))->out(false),
            'gradingheading' => get_string('home:tograde', 'local_oksigeniaaty'),
            'grading' => $grading,
            'hasgrading' => !empty($grading),
            'eventsheading' => get_string('home:upcoming', 'local_oksigeniaaty'),
            'events' => $upcoming,
            'hasevents' => !empty($upcoming),
            'shortcutsheading' => get_string('home:shortcuts', 'local_oksigeniaaty'),
            'shortcuts' => $shortcuts,
            'hasshortcuts' => !empty($shortcuts),
            'coursesheading' => get_string('home:mycourses', 'local_oksigeniaaty'),
            'courses' => $courses,
            'hascourses' => !empty($courses),
            'opensoutside' => get_string('home:opensoutside', 'local_oksigeniaaty'),
        ]);

        // Never through inert(): its braces are the object the template binds.
        foreach ($data['children'] as $index => $child) {
            $data['children'][$index]['argsjson'] = json_encode(['childid' => $child['id']]);
        }

        // What other plugins add for this user; each of their texts already inert.
        $cards = homecards::for_user((int) $USER->id);
        $data['cards'] = array_values(array_filter($cards, fn($card) => $card['hasitems']));
        foreach ($cards as $card) {
            if ($card['tile'] !== null) {
                $data['shortcuts'][] = $card['tile'];
                $data['hasshortcuts'] = true;
            }
        }

        // Whoever may post in a course's announcements, whatever their role is called.
        if (self::writable_classes(1)) {
            $label = get_string('compose:title', 'local_oksigeniaaty');
            $data['shortcuts'][] = template::inert(['label' => $label, 'icon' => 'fas-pen-to-square']) + [
                'ispage' => true,
                'component' => 'local_oksigeniaaty',
                'method' => 'mobile_compose',
                'pagetitle' => template::inert($label),
                // Never through inert(): its braces are the object the template binds.
                'argsjson' => '{}',
            ];
            $data['hasshortcuts'] = true;
        }

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
     * "Your agenda on your phone": subscribe the phone's calendar app to the user's Moodle calendar, so due dates,
     * classes and events show there and keep up to date on their own.
     *
     * It uses Moodle's own calendar export (the user's private feed), offered as Google Calendar's subscription link,
     * as the standard webcal link that other calendar apps open, and as the plain address for apps that ask for it.
     * Google's link (calendar/r?cid=) is not documented by Google; the plain address is the fallback.
     *
     * @param array $args The arguments the app sends.
     * @return array The template, javascript and data the app expects.
     */
    public static function mobile_calendar(array $args): array {
        global $CFG, $OUTPUT, $USER;

        require_once($CFG->dirroot . '/calendar/lib.php');

        $data = [
            'enabled' => !empty($CFG->enablecalendarexport),
            'intro' => get_string('calendarapp:intro', 'local_oksigeniaaty', format_string(get_site()->shortname)),
        ];
        if ($data['enabled']) {
            $feed = new \moodle_url('/calendar/export_execute.php', [
                'userid' => $USER->id,
                'authtoken' => calendar_get_export_token($USER),
                'preset_what' => 'all',
                'preset_time' => 'recentupcoming',
            ]);
            $address = $feed->out(false);
            $webcal = preg_replace('#^https?://#', 'webcal://', $address);
            $data += [
                'address' => $address,
                'webcalurl' => $webcal,
                'googleurl' => 'https://calendar.google.com/calendar/r?cid=' . rawurlencode($webcal),
            ];
        }
        $data = template::inert($data);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('local_oksigeniaaty/mobile_calendar', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => [],
        ];
    }

    /**
     * Whether "Where to get your notices" shows on this site.
     *
     * @param array $args The arguments the app sends.
     * @return array What the app expects from an init method.
     */
    public static function channels_init(array $args): array {
        return [
            'templates' => [],
            'javascript' => '',
            'otherdata' => [],
            'disabled' => !channels::available(),
        ];
    }

    /**
     * "Where to get your notices".
     *
     * @param array $args The arguments the app sends.
     * @return array The template, javascript and data the app expects.
     */
    public static function mobile_channels(array $args): array {
        global $OUTPUT, $USER;

        $component = 'local_oksigeniaaty';
        $data = template::inert([
            'intro' => get_string('channels:intro', $component, format_string(get_site()->shortname)),
            'rows' => channels::for_user($USER),
            'prefsurl' => (new \moodle_url('/message/notificationpreferences.php'))->out(false),
        ], true);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('local_oksigeniaaty/mobile_channels', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => [],
        ];
    }

    /**
     * "Write to a course": a course, a title and a few words, which go to its announcements.
     *
     * @param array $args The arguments the app sends.
     * @return array The template, javascript and data the app expects.
     */
    public static function mobile_compose(array $args): array {
        global $OUTPUT;

        $classes = self::writable_classes(100);
        $component = 'local_oksigeniaaty';
        $data = template::inert([
            'hasclasses' => !empty($classes),
            'classes' => $classes,
            'intro' => get_string('compose:intro', $component),
            'courselabel' => get_string('compose:course', $component),
            'subjectlabel' => get_string('compose:subject', $component),
            'messagelabel' => get_string('compose:message', $component),
            'publish' => get_string('compose:publish', $component),
            'confirm' => get_string('compose:confirm', $component),
            'sent' => get_string('compose:sent', $component),
            'empty' => get_string('compose:empty', $component),
        ]);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('local_oksigeniaaty/mobile_compose', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => [
                // With only one course, it is already chosen.
                'forumid' => count($classes) === 1 ? (string) $classes[0]['forumid'] : '',
                'subject' => '',
                'message' => '',
            ],
        ];
    }

    /**
     * A child, for an adult of their family: what comes next, their latest grades if the centre shows them, and what other
     * plugins add. Nothing for anyone else.
     *
     * @param array $args The arguments the app sends: childid.
     * @return array The template, javascript and data the app expects.
     */
    public static function mobile_child(array $args): array {
        global $OUTPUT, $USER;

        $component = 'local_oksigeniaaty';
        $childid = (int) ($args['childid'] ?? 0);
        $allowed = class_exists(\local_oksigeniafamily\api::class)
            && \local_oksigeniafamily\api::is_guardian((int) $USER->id, $childid);
        if (!$allowed) {
            throw new \moodle_exception('family:notyours', $component);
        }
        $events = get_config($component, 'familyagenda') === '0' ? [] : self::child_events($childid);
        $grades = get_config($component, 'familygrades') ? self::child_grades($childid) : [];
        $data = template::inert([
            'nothing' => get_string('family:nothing', $component),
            'eventsheading' => get_string('home:upcoming', $component),
            'events' => $events,
            'hasevents' => !empty($events),
            'gradesheading' => get_string('family:grades', $component),
            'grades' => $grades,
            'hasgrades' => !empty($grades),
        ]);
        // Each of their texts already inert.
        $data['cards'] = homecards::for_child((int) $USER->id, $childid);
        $data['empty'] = !$events && !$grades && !$data['cards'];

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('local_oksigeniaaty/mobile_child', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => [],
        ];
    }

    /**
     * Another child's code, typed in the app.
     *
     * @param array $args The arguments the app sends.
     * @return array The template, javascript and data the app expects.
     */
    public static function mobile_family_add(array $args): array {
        global $OUTPUT;

        $component = 'local_oksigeniaaty';
        $data = template::inert([
            'intro' => get_string('family:addintro', $component),
            'codelabel' => get_string('family:code', $component),
            'add' => get_string('family:addbutton', $component),
            'sent' => get_string('family:added', $component),
        ]);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('local_oksigeniaaty/mobile_family_add', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => ['code' => ''],
        ];
    }

    /**
     * The user's children, for an adult of a family; null for anyone who is not one.
     *
     * @return array|null A list of { id, name, initial }.
     */
    private static function children(): ?array {
        global $DB, $USER;

        if (!class_exists(\local_oksigeniafamily\api::class) || !\local_oksigeniafamily\api::is_available()) {
            return null;
        }
        if (!$DB->record_exists('local_oksigeniafamily_adult', ['userid' => $USER->id, 'status' => 'active'])) {
            return null;
        }

        $children = [];
        foreach (\local_oksigeniafamily\api::children_of_user((int) $USER->id) as $childid) {
            $user = \core_user::get_user((int) $childid);
            if ($user && !$user->deleted) {
                $name = fullname($user);
                $children[] = [
                    'id' => (int) $user->id,
                    'name' => $name,
                    'initial' => \core_text::strtoupper(\core_text::substr(trim($name), 0, 1)),
                ];
            }
        }

        return $children;
    }

    /**
     * What comes next for a child, in their courses and groups. Not their own calendar entries, which are theirs.
     *
     * @param int $childid The child.
     * @return array A list of { name, timestamp, day, month, time, context }, at most eight.
     */
    private static function child_events(int $childid): array {
        global $CFG;

        require_once($CFG->dirroot . '/calendar/lib.php');

        $courses = enrol_get_users_courses($childid, true, 'id, shortname');
        if (!$courses) {
            return [];
        }
        $groupids = [];
        foreach ($courses as $course) {
            $groupids = array_merge($groupids, array_keys(groups_get_all_groups($course->id, $childid)));
        }

        $now = time();
        $events = calendar_get_legacy_events(
            $now,
            $now + 14 * DAYSECS,
            false,
            $groupids ?: false,
            array_keys($courses),
            true,
            true
        );
        \core_collator::asort_objects_by_property($events, 'timestart', \core_collator::SORT_NUMERIC);

        $list = [];
        foreach ($events as $event) {
            // As in the home: the activity's own name, and what the date is next to the course.
            $name = format_string($event->name);
            $labels = isset($courses[$event->courseid]) ? [format_string($courses[$event->courseid]->shortname)] : [];
            if (!empty($event->modulename) && !empty($event->instance)) {
                $instances = get_fast_modinfo($event->courseid, $childid)->get_instances_of($event->modulename);
                if (!isset($instances[$event->instance]) || !$instances[$event->instance]->uservisible) {
                    continue;
                }
                $kind = 'home:event:' . $event->eventtype;
                if (get_string_manager()->string_exists($kind, 'local_oksigeniaaty')) {
                    $name = $instances[$event->instance]->get_formatted_name();
                    $labels[] = get_string($kind, 'local_oksigeniaaty');
                }
            }
            $list[] = [
                'name' => $name,
                'timestamp' => (int) $event->timestart * 1000,
                'day' => ltrim(userdate($event->timestart, '%e')),
                'month' => \core_text::strtoupper(trim(userdate($event->timestart, '%b'))),
                'time' => (int) $event->timeduration >= DAYSECS ? ''
                    : userdate($event->timestart, get_string('strftimetime', 'langconfig')),
                'context' => implode(' · ', $labels),
            ];
            if (count($list) >= 8) {
                break;
            }
        }

        return $list;
    }

    /**
     * A child's latest grades in activities, leaving out whatever a course or its grade book hides from them.
     *
     * @param int $childid The child.
     * @return array A list of { grade, name, context }, at most five.
     */
    private static function child_grades(int $childid): array {
        global $CFG, $DB;

        require_once($CFG->libdir . '/gradelib.php');

        $sql = "SELECT g.id, g.itemid, g.finalgrade, c.shortname
                  FROM {grade_grades} g
                  JOIN {grade_items} i ON i.id = g.itemid
                  JOIN {course} c ON c.id = i.courseid
                 WHERE g.userid = :userid
                   AND g.finalgrade IS NOT NULL
                   AND g.hidden = 0
                   AND i.hidden = 0
                   AND i.itemtype = 'mod'
                   AND c.showgrades = 1
              ORDER BY g.timemodified DESC";

        $list = [];
        foreach ($DB->get_records_sql($sql, ['userid' => $childid], 0, 5) as $row) {
            $item = \grade_item::fetch(['id' => $row->itemid]);
            if ($item) {
                $list[] = [
                    'grade' => grade_format_gradevalue($row->finalgrade, $item),
                    'name' => $item->get_name(),
                    'context' => format_string($row->shortname),
                ];
            }
        }

        return $list;
    }

    /**
     * Where the user may write: the courses whose announcements they may post in. It asks the permission, not the role, so it
     * reaches whoever the centre has allowed.
     *
     * @param int $limit The most courses to look at.
     * @return array A list of { forumid, name }.
     */
    private static function writable_classes(int $limit): array {
        global $DB;

        $courses = get_user_capability_course('mod/forum:addnews', null, true, 'fullname', 'fullname', $limit) ?: [];
        $classes = [];
        foreach ($courses as $course) {
            if ((int) $course->id === (int) SITEID) {
                continue;
            }
            $forum = $DB->get_record('forum', ['course' => $course->id, 'type' => 'news'], 'id', IGNORE_MULTIPLE);
            $cm = $forum ? (get_fast_modinfo($course->id)->get_instances_of('forum')[$forum->id] ?? null) : null;
            if ($cm && $cm->uservisible && has_capability('mod/forum:addnews', $cm->context)) {
                $classes[] = ['forumid' => (int) $forum->id, 'name' => format_string($course->fullname)];
            }
        }

        return $classes;
    }

    /**
     * How many conversations the user has unread; none where the site has messaging off, or off in the app.
     *
     * @return int
     */
    private static function unread(): int {
        global $CFG, $USER;

        $disabled = explode(',', (string) get_config('tool_mobile', 'disabledfeatures'));
        if (empty($CFG->messaging) || in_array('CoreMainMenuDelegate_AddonMessages', $disabled, true)) {
            return 0;
        }

        return (int) \core_message\api::count_unread_conversations($USER);
    }

    /**
     * An agenda in two: what is left of today, and what comes after it.
     *
     * @param array $events From events().
     * @return array [today, upcoming], at most five each.
     */
    private static function split_events(array $events): array {
        $istoday = fn($event) => usergetmidnight((int) ($event['timestamp'] / 1000)) === usergetmidnight(time());
        $today = array_values(array_filter($events, $istoday));
        $upcoming = array_values(array_filter($events, fn($event) => !$istoday($event)));

        return [array_slice($today, 0, 5), array_slice($upcoming, 0, 5)];
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
     * @return array A list of { name, timestamp, day, month, time, context, viewurl }, soonest first, at most ten.
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
            if (count($agenda) >= 10) {
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
