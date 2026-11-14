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
    /** @var int How long before a class it shows as about to start, with its way in. */
    private const SOON = 30 * MINSECS;

    /** @var int The most courses the home lists when the app has no tab "My courses"; "See all" leads to the rest. */
    private const HOME_COURSES = 8;

    /**
     * The home tab of the site: a low band of the brand with the greeting and a line that says the day, the children of an
     * adult of a family, "Today" first (what is about to start, with "Join"; what there is to do; what is left of today),
     * what other plugins add, what comes next and the shortcuts the tab bar does not have.
     *
     * The server decides what is starting when it draws the home; the app asks for it again when it opens and when the
     * home is refreshed, so no JavaScript is needed in the app.
     *
     * @param array $args The arguments the app sends (site URL, user id, language...).
     * @return array The template, javascript and data the app expects.
     */
    public static function mobile_home(array $args): array {
        global $OUTPUT, $CFG, $USER;

        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

        // Whoever entered Aty with a password, on a plan without the app. The official app stays as it is.
        if (($args['appid'] ?? '') === \local_oksigeniaaty\api::ATY_PACKAGE && !local_oksigeniaaty_user_allowed((int) $USER->id)) {
            return [
                'templates' => [
                    [
                        'id' => 'main',
                        'html' => $OUTPUT->render_from_template('local_oksigeniaaty/mobile_notallowed', template::inert([
                            'message' => get_string('appnotallowed', 'local_oksigeniaaty'),
                            'url' => $CFG->wwwroot,
                        ], true)),
                    ],
                ],
                'javascript' => '',
                'otherdata' => [],
            ];
        }

        // The brand's logo, small, in the band; the centre's name is already in the header above it.
        $logourl = (string) get_config('local_oksigeniaaty', 'homelogourl');
        // In a site shared by several schools or libraries, the home is the user's own one's.
        $brand = \local_oksigeniaaty\local\brand::for_user((int) $USER->id);
        if ($brand !== null) {
            $logourl = \local_oksigeniaaty\local\brand::logo_for_app($brand['logo'], (int) $USER->id);
        }
        $now = time();
        $events = self::events($now);
        $starting = self::starting_now($now);
        [$today, $upcoming] = self::split_events($events, array_column($starting, 'id'));
        $grading = self::grading();
        $unread = self::unread();
        $children = self::children();
        // What other plugins add for this user, each of their texts already inert; what asks for something to do goes to
        // "Today".
        $cards = homecards::for_user((int) $USER->id);
        [$shown, $todo] = homecards::on_home($cards, $now);
        $name = self::greeting_name($USER);

        $data = template::inert([
            'logourl' => $logourl,
            'greeting' => $name !== ''
                ? get_string('home:greeting', 'local_oksigeniaaty', $name)
                : get_string('home:hello', 'local_oksigeniaaty'),
            'familyheading' => get_string('family:heading', 'local_oksigeniaaty'),
            'hasfamily' => $children !== null,
            'children' => $children ?? [],
            'haschildren' => !empty($children),
            'familyaddname' => get_string('family:add', 'local_oksigeniaaty'),
            'todayheading' => get_string('home:today', 'local_oksigeniaaty'),
            'starting' => $starting,
            'joinlabel' => get_string('home:join', 'local_oksigeniaaty'),
            'today' => $today,
            'hastoday' => $starting || $todo || $today || $unread > 0,
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
            'opensoutside' => get_string('home:opensoutside', 'local_oksigeniaaty'),
        ]);
        $data['dayline'] = template::inert(self::day_line($todo, $starting, $today, $unread, $grading, $events, $now));
        $data['todo'] = $todo;
        $data['cards'] = $shown;
        // The course cards left the home for the tab "My courses"; where the app has no such tab, they come back.
        if (!self::app_has_my_courses()) {
            $courses = self::courses_card();
            if ($courses !== null) {
                array_unshift($data['cards'], $courses);
            }
        }

        // Never through inert(): its braces are the object the template binds.
        foreach ($data['children'] as $index => $child) {
            $data['children'][$index]['argsjson'] = json_encode(['childid' => $child['id']]);
        }

        // The shortcuts the tab bar does not have already, the plugins' tiles and writing to a course.
        $shortcuts = template::inert(self::off_the_tabs(local_oksigeniaaty_get_shortcuts()));
        foreach ($cards as $card) {
            if ($card['tile'] !== null) {
                $shortcuts[] = $card['tile'];
            }
        }
        // Whoever may post in a course's announcements, whatever their role is called.
        if (self::writable_classes(1)) {
            $label = get_string('compose:title', 'local_oksigeniaaty');
            $shortcuts[] = template::inert(['label' => $label, 'icon' => 'fas-pen-to-square']) + [
                'ispage' => true,
                'component' => 'local_oksigeniaaty',
                'method' => 'mobile_compose',
                'pagetitle' => template::inert($label),
                // Never through inert(): its braces are the object the template binds.
                'argsjson' => '{}',
            ];
        }
        $data['shortcuts'] = $shortcuts;
        $data['hasshortcuts'] = !empty($shortcuts);

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
     * @return array|null A list of { id, name, label, initial }: the label, the first name, short for a pill.
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
                $first = trim(format_string($user->firstname));
                $children[] = [
                    'id' => (int) $user->id,
                    'name' => $name,
                    'label' => $first !== '' ? $first : $name,
                    'initial' => \core_text::strtoupper(\core_text::substr(trim($name), 0, 1)),
                ];
            }
        }
        // Two children with the same first name are told apart by their full names.
        $labels = array_count_values(array_map(fn($child) => \core_text::strtolower($child['label']), $children));
        foreach ($children as $index => $child) {
            if ($labels[\core_text::strtolower($child['label'])] > 1) {
                $children[$index]['label'] = $child['name'];
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
        // The calendar checks each event against whoever asks, here the adult, who is in none of the child's courses: every
        // activity's date was dropped before the child's own check below. Ask as the child, and give the calendar back.
        $previous = \core_calendar\local\event\container::get_requesting_user();
        \core_calendar\local\event\container::set_requesting_user($childid);
        try {
            $events = calendar_get_legacy_events(
                $now,
                $now + 14 * DAYSECS,
                false,
                $groupids ?: false,
                array_keys($courses),
                true,
                true
            );
        } finally {
            \core_calendar\local\event\container::set_requesting_user($previous);
        }
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
     * @param array $starting The ids of what is starting now, which "Today" shows apart.
     * @return array [today, upcoming], at most five each.
     */
    private static function split_events(array $events, array $starting = []): array {
        $istoday = fn($event) => usergetmidnight((int) ($event['timestamp'] / 1000)) === usergetmidnight(time());
        $today = array_values(array_filter(
            $events,
            fn($event) => $istoday($event) && !in_array($event['id'], $starting, true)
        ));
        $upcoming = array_values(array_filter($events, fn($event) => !$istoday($event)));

        return [array_slice($today, 0, 5), array_slice($upcoming, 0, 5)];
    }

    /**
     * What is about to start and can be joined from the phone: a class in the 30 minutes before it starts, and while it
     * lasts when its end is known. Only what has a way in that can be known: a BigBlueButton room the user can join, or an
     * event whose location is an address (a Zoom, Meet or Teams link put there by whoever made it). Without a known end, it
     * shows until it starts.
     *
     * @param int $now The time it is.
     * @return array A list of { id, name, timestamp, time, context, when, joinurl }, soonest first.
     */
    private static function starting_now(int $now): array {
        global $CFG;

        require_once($CFG->dirroot . '/calendar/lib.php');

        $courses = calendar_get_default_courses();
        [$courseids, $groupids, $userids] = calendar_set_filters($courses);
        // From the start of today: what started earlier and has not ended is still on.
        $events = calendar_get_legacy_events(
            usergetmidnight($now),
            $now + self::SOON,
            $userids,
            $groupids,
            $courseids,
            true,
            true
        );
        \core_collator::asort_objects_by_property($events, 'timestart', \core_collator::SORT_NUMERIC);

        $shortnames = self::shortnames($courses);
        $starting = [];
        foreach ($events as $event) {
            $join = self::join_link($event);
            $start = (int) $event->timestart;
            if ($join === null || $now < $start - self::SOON) {
                continue;
            }
            // While it lasts when its end is known; until it starts when it is not.
            $on = $join['end'] > $start ? $now < $join['end'] : $now <= $start;
            if (!$on) {
                continue;
            }
            $minutes = (int) ceil(($start - $now) / MINSECS);
            $item = self::agenda_item($event, $shortnames);
            $starting[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'timestamp' => $item['timestamp'],
                'time' => userdate($start, get_string('strftimetime', 'langconfig')),
                'context' => $item['context'],
                'when' => $minutes > 0
                    ? get_string('home:inminutes', 'local_oksigeniaaty', $minutes)
                    : get_string('home:now', 'local_oksigeniaaty'),
                'joinurl' => $join['url'],
            ];
        }

        return $starting;
    }

    /**
     * The way into a live class from its calendar event, or null when there is none that can be known.
     *
     * @param \stdClass $event A legacy calendar event.
     * @return array|null [url, end]: the address to join it and when it ends (0 when that is not known).
     */
    private static function join_link(\stdClass $event): ?array {
        $start = (int) $event->timestart;
        $duration = (int) $event->timeduration;

        // BigBlueButton: its own way in, the one of its page, for whoever may join, in their group.
        if (
            ($event->modulename ?? '') === 'bigbluebuttonbn' && ($event->eventtype ?? '') === 'meeting_start'
            && !empty($event->instance) && class_exists(\mod_bigbluebuttonbn\instance::class)
        ) {
            try {
                $instance = \mod_bigbluebuttonbn\instance::get_from_instanceid((int) $event->instance);
                if ($instance === null || !$instance->get_cm()->uservisible) {
                    return null;
                }
                $group = groups_get_activity_group($instance->get_cm());
                if ($group) {
                    $instance->set_group_id((int) $group);
                }
                if (!$instance->can_join()) {
                    return null;
                }
                return [
                    'url' => $instance->get_join_url()->out(false),
                    'end' => (int) $instance->get_instance_var('closingtime'),
                ];
            } catch (\Throwable $e) {
                debugging('Joining a BigBlueButton room from the home: ' . $e->getMessage(), DEBUG_DEVELOPER);
                return null;
            }
        }

        // Any other event whose location is just an address.
        $location = trim((string) ($event->location ?? ''));
        if (preg_match('#^https?://\S+$#i', $location) && clean_param($location, PARAM_URL) === $location) {
            return ['url' => $location, 'end' => $duration > 0 ? $start + $duration : 0];
        }

        return null;
    }

    /**
     * The first name to greet the user by, or '' when there is none worth saying: an empty one, or a word that stands for
     * a role or a test account ("Student", the site's names of its roles, the username) rather than a person.
     *
     * @param \stdClass $user The user.
     * @return string
     */
    private static function greeting_name(\stdClass $user): string {
        $name = trim(format_string((string) $user->firstname));
        if ($name === '') {
            return '';
        }
        $words = [\core_text::strtolower(trim((string) $user->username))];
        foreach ([current_language(), 'en'] as $lang) {
            $list = get_string_manager()->get_string('home:placeholdernames', 'local_oksigeniaaty', null, $lang);
            $words = array_merge($words, array_map('trim', explode(',', \core_text::strtolower($list))));
        }
        foreach (role_fix_names(get_all_roles(), \context_system::instance(), ROLENAME_ORIGINAL) as $role) {
            $words[] = \core_text::strtolower(trim(strip_tags((string) $role->localname)));
            $words[] = \core_text::strtolower((string) $role->shortname);
        }

        return in_array(\core_text::strtolower($name), $words, true) ? '' : $name;
    }

    /**
     * The line under the greeting: what matters most of the day, in a few words. First what there is to do, then what
     * there is today, then what is waiting for a grade; with nothing, what comes next.
     *
     * @param array $todo The rows of other plugins with something to do.
     * @param array $starting What is starting now.
     * @param array $today What is left of today, apart from that.
     * @param int $unread The unread conversations.
     * @param array $grading The assignments waiting for a grade.
     * @param array $events The agenda of the coming days, for what comes next.
     * @param int $now The time it is.
     * @return string
     */
    private static function day_line(
        array $todo,
        array $starting,
        array $today,
        int $unread,
        array $grading,
        array $events,
        int $now
    ): string {
        // A title that already ends a sentence (a question, an exclamation) does not take the sentence's full stop.
        $say = fn(string $id, $a = null) => preg_replace(
            '/([?!…])\.$/u',
            '$1',
            get_string('home:day:' . $id, 'local_oksigeniaaty', $a)
        );
        $some = fn(string $id, int $count) => $count === 1 ? $say($id . '1') : $say($id, $count);

        if ($todo) {
            $titles = array_column($todo, 'title');
            if (count($titles) === 1) {
                return $say('todo1', $titles[0]);
            }
            return count($titles) === 2 ? $say('todo', self::listed($titles)) : $say('todomany', count($titles));
        }

        $live = count($starting);
        $due = $close = $other = 0;
        foreach ($today as $event) {
            if ($event['live']) {
                $live++;
            } else if ($event['kind'] === 'due') {
                $due++;
            } else if ($event['kind'] === 'close') {
                $close++;
            } else {
                $other++;
            }
        }
        $parts = [];
        foreach (['live' => $live, 'due' => $due, 'close' => $close, 'event' => $other, 'unread' => $unread] as $id => $count) {
            if ($count > 0) {
                $parts[] = $some($id, $count);
            }
        }
        if ($parts) {
            return $say('today', self::listed($parts));
        }

        $submissions = array_sum(array_column($grading, 'count'));
        if ($submissions > 0) {
            return $some('grading', $submissions);
        }

        $tomorrow = usergetmidnight($now) + DAYSECS;
        foreach ($events as $event) {
            $start = (int) ($event['timestamp'] / 1000);
            if ($start >= $tomorrow) {
                return usergetmidnight($start) === $tomorrow
                    ? $say('nexttomorrow')
                    : $say('next', trim(userdate($start, $say('dateformat'))));
            }
        }

        return $say('nothing');
    }

    /**
     * Some texts said as a list: "a, b and c".
     *
     * @param string[] $parts The texts.
     * @return string
     */
    private static function listed(array $parts): string {
        $last = array_pop($parts);
        if (!$parts) {
            return $last;
        }

        return get_string('home:day:and', 'local_oksigeniaaty', (object) ['first' => implode(', ', $parts), 'last' => $last]);
    }

    /**
     * Whether the app shows its tab "My courses" on this site, decided as the app decides it: "My courses" on, that part
     * of the app not turned off, and a block on the site's courses page (Moodle's course overview, which the app draws).
     * A site that took the block away has no list of courses in the app.
     *
     * @return bool
     */
    public static function app_has_my_courses(): bool {
        global $CFG, $DB;

        if (isset($CFG->enablemycourses) && empty($CFG->enablemycourses)) {
            return false;
        }
        $disabled = array_map('trim', explode(',', (string) get_config('tool_mobile', 'disabledfeatures')));
        if (in_array('CoreMainMenuDelegate_CoreCourses', $disabled, true)) {
            return false;
        }

        require_once($CFG->dirroot . '/my/lib.php');
        $page = my_get_page(null, MY_PAGE_PUBLIC, MY_PAGE_COURSES);
        if (!$page) {
            return false;
        }

        return $DB->record_exists_select(
            'block_instances',
            'pagetypepattern = :type AND subpagepattern = :page',
            ['type' => 'my-index', 'page' => (string) $page->id]
        );
    }

    /**
     * The user's courses as a card of the home, for a site where the app has no tab "My courses": each row opens its
     * course in the app, and "See all" the site's page of courses, in the phone's browser.
     *
     * @return array|null The card, as homecards::on_home() leaves one; null without courses.
     */
    private static function courses_card(): ?array {
        $mycourses = enrol_get_my_courses(['id', 'fullname'], 'visible DESC, sortorder ASC');
        unset($mycourses[SITEID]);
        if (!$mycourses) {
            return null;
        }

        $items = [];
        foreach (array_slice($mycourses, 0, self::HOME_COURSES) as $course) {
            $items[] = template::inert([
                'title' => format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]),
                'detail' => '',
                'urgent' => false,
                'hasdate' => false,
                'timestamp' => 0,
                'day' => '',
                'month' => '',
                'hour' => '',
                'hashour' => false,
                'kind' => '',
                'kindlabel' => '',
                'actionlabel' => '',
                'hascount' => false,
                'count' => '',
                'countlabel' => '',
            ]) + [
                'ispage' => false,
                'url' => template::inert((new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false), true),
                'browser' => false,
                'external' => false,
            ];
        }
        $seeall = null;
        if (count($mycourses) > count($items)) {
            $seeall = template::inert(['label' => get_string('home:seeall', 'local_oksigeniaaty')]) + [
                'ispage' => false,
                'url' => template::inert((new \moodle_url('/my/courses.php'))->out(false), true),
                'browser' => true,
                'external' => true,
            ];
        }

        return [
            'heading' => template::inert(get_string('home:mycourses', 'local_oksigeniaaty')),
            'hasitems' => true,
            'items' => $items,
            'tile' => null,
            'seeall' => $seeall,
        ];
    }

    /**
     * The shortcuts without those the app already has in its tab bar: my courses, the calendar, the notices and the
     * messages, unless the site has turned that part of the app off. My courses stays off the shortcuts also where the app
     * has no tab for it: the home lists the courses then.
     *
     * @param array $shortcuts From local_oksigeniaaty_get_shortcuts().
     * @return array
     */
    private static function off_the_tabs(array $shortcuts): array {
        global $CFG;

        $disabled = array_map('trim', explode(',', (string) get_config('tool_mobile', 'disabledfeatures')));
        $tabs = [
            '/my/courses.php' => 'CoreMainMenuDelegate_CoreCourses',
            '/calendar/view.php' => 'CoreMainMenuDelegate_AddonCalendar',
            '/message/output/popup/notifications.php' => 'CoreMainMenuDelegate_AddonNotifications',
        ];
        if (!empty($CFG->messaging)) {
            $tabs['/message/index.php'] = 'CoreMainMenuDelegate_AddonMessages';
        }
        $tabs = array_diff($tabs, $disabled);

        return array_values(array_filter($shortcuts, function (array $shortcut) use ($tabs, $CFG): bool {
            $url = (string) $shortcut['url'];
            if (strpos($url, $CFG->wwwroot . '/') !== 0) {
                return true;
            }
            $path = strtok(substr($url, strlen($CFG->wwwroot)), '?#');

            return !isset($tabs[$path]);
        }));
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
     * @param int $now The time it is.
     * @return array A list of agenda_item()s, soonest first, at most ten.
     */
    private static function events(int $now): array {
        global $CFG;

        require_once($CFG->dirroot . '/calendar/lib.php');

        $courses = calendar_get_default_courses();
        // The calendar's own filters: course ids, group ids and the user id (or false for each when not applicable).
        [$courseids, $groupids, $userids] = calendar_set_filters($courses);

        $events = calendar_get_legacy_events($now, $now + 7 * DAYSECS, $userids, $groupids, $courseids, true, true);
        if (empty($events)) {
            return [];
        }

        // Soonest first, so the agenda reads top to bottom in time.
        \core_collator::asort_objects_by_property($events, 'timestart', \core_collator::SORT_NUMERIC);

        $shortnames = self::shortnames($courses);
        $agenda = [];
        foreach ($events as $event) {
            $agenda[] = self::agenda_item($event, $shortnames);
            if (count($agenda) >= 10) {
                break;
            }
        }

        return $agenda;
    }

    /**
     * A course id to short name map, to label each event with its course.
     *
     * @param array $courses The user's courses.
     * @return array
     */
    private static function shortnames(array $courses): array {
        $shortnames = [];
        foreach ($courses as $course) {
            $shortnames[$course->id] = format_string($course->shortname);
        }

        return $shortnames;
    }

    /**
     * An event of the agenda, ready for the template.
     *
     * @param \stdClass $event A legacy calendar event.
     * @param array $shortnames The courses' short names, by id.
     * @return array { id, name, timestamp, day, month, time, context, viewurl, kind, kindlabel, live }.
     */
    private static function agenda_item(\stdClass $event, array $shortnames): array {
        // All-day events (a day or longer) show just the date; timed ones also show the start time.
        $isallday = (int) $event->timeduration >= DAYSECS;
        $labels = [];
        if (!empty($event->courseid) && $event->courseid != SITEID && isset($shortnames[$event->courseid])) {
            $labels[] = $shortnames[$event->courseid];
        }
        // An activity's dates are named by Moodle like "Assignment is due": show the activity's own name and say what the
        // date is, next to the hour for a deadline ("Due 23:59") and next to the course for the rest ("Customer care ·
        // Opens"). A live room's start is the activity itself.
        $name = format_string($event->name);
        $activity = self::activityname($event);
        $kind = in_array($event->eventtype, ['due', 'close'], true) ? $event->eventtype : '';
        $label = 'home:event:' . $event->eventtype;
        if ($activity !== null && $kind !== '') {
            $name = $activity;
        } else if ($activity !== null && $event->eventtype === 'meeting_start') {
            $name = $activity;
        } else if ($activity !== null && get_string_manager()->string_exists($label, 'local_oksigeniaaty')) {
            $name = $activity;
            $labels[] = get_string($label, 'local_oksigeniaaty');
        }
        if ($activity === null) {
            $kind = '';
        }

        return [
            'id' => (int) $event->id,
            'name' => $name,
            // The app redraws the date and time from this in the phone's own time zone, as its calendar does; the
            // server's text (in the user's Moodle time zone) is only what shows before that.
            'timestamp' => (int) $event->timestart * 1000,
            'day' => ltrim(userdate($event->timestart, '%e')),
            'month' => \core_text::strtoupper(trim(userdate($event->timestart, '%b'))),
            'time' => $isallday ? '' : userdate($event->timestart, get_string('strftimetime', 'langconfig')),
            'context' => implode(' · ', $labels),
            'viewurl' => (new \moodle_url('/calendar/view.php', ['view' => 'day', 'time' => $event->timestart]))->out(false),
            'kind' => $kind,
            'kindlabel' => $kind !== '' ? get_string('home:kind:' . $kind, 'local_oksigeniaaty') : '',
            'live' => self::join_link($event) !== null,
        ];
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
