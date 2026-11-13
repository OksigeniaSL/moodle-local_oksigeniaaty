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

use local_oksigeniaaty\external\post_to_class;

/**
 * Tests for the app's home.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_oksigeniaaty\output\mobile
 */
#[\PHPUnit\Framework\Attributes\CoversClass(mobile::class)]
final class mobile_test extends \advanced_testcase {
    /**
     * A teacher sees the assignment with a submission waiting for a grade, with its count; once graded, it goes; a
     * student never sees the section.
     */
    public function test_to_grade(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['shortname' => 'HARM']);
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $student = $generator->create_and_enrol($course, 'student');
        $assign = $generator->create_module('assign', ['course' => $course->id, 'name' => 'Bach chorale']);
        $now = time();
        $DB->insert_record('assign_submission', (object) [
            'assignment' => $assign->id, 'userid' => $student->id, 'status' => 'submitted', 'latest' => 1,
            'attemptnumber' => 0, 'groupid' => 0, 'timecreated' => $now, 'timemodified' => $now,
        ]);

        $this->setUser($teacher);
        $html = self::home();
        $this->assertStringContainsString('aty-grading', $html);
        $this->assertStringContainsString('Bach chorale', $html);
        $this->assertStringContainsString('HARM', $html);

        $this->setUser($student);
        $this->assertStringNotContainsString('aty-grading', self::home());

        // Graded after the submission: nothing left to grade.
        $DB->insert_record('assign_grades', (object) [
            'assignment' => $assign->id, 'userid' => $student->id, 'attemptnumber' => 0, 'grade' => 8,
            'grader' => $teacher->id, 'timecreated' => $now + 60, 'timemodified' => $now + 60,
        ]);
        $this->setUser($teacher);
        $this->assertStringNotContainsString('aty-grading', self::home());
    }

    /**
     * The names of a course and of its events cannot run as templates in the app of whoever sees them.
     */
    public function test_names_are_inert(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => '{{ course }} @let c = 1;', 'shortname' => '{{ short }}']);
        $student = $generator->create_and_enrol($course, 'student', ['firstname' => '@let f = 1;']);
        $this->setAdminUser();
        $generator->create_event([
            'name' => '{{ event }} @if (a)', 'courseid' => $course->id, 'eventtype' => 'course', 'timestart' => time() + DAYSECS,
        ]);

        $this->setUser($student);
        $html = self::home();
        // The course's short name labels its events; its full name is no longer on the home (the course cards are gone).
        $this->assertStringContainsString('｛｛ short ｝｝', $html);
        $this->assertStringContainsString('｛｛ event ｝｝ ＠if (a)', $html);
        $this->assertStringContainsString('＠let f = 1;', $html);
        $this->assertStringNotContainsString('{{', $html);
        $this->assertDoesNotMatchRegularExpression('/@[a-z]/i', $html);
    }

    /**
     * Today's things, for anyone: what is left of today apart from what comes later, and the unread messages.
     */
    public function test_today(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $other = $generator->create_and_enrol($course, 'student');
        $this->setAdminUser();
        $midnight = usergetmidnight(time(), \core_date::get_user_timezone($student));
        $generator->create_event([
            'name' => 'Later today', 'courseid' => $course->id, 'eventtype' => 'course', 'timestart' => time() + 60,
        ]);
        $generator->create_event([
            'name' => 'In three days',
            'courseid' => $course->id,
            'eventtype' => 'course',
            'timestart' => $midnight + 3 * DAYSECS + 3600,
        ]);

        $this->setUser($student);
        $html = self::home();
        $today = get_string('home:today', 'local_oksigeniaaty');
        $upcoming = get_string('home:upcoming', 'local_oksigeniaaty');
        $this->assertStringNotContainsString(get_string('home:unread', 'local_oksigeniaaty'), $html);
        // Unless the test runs in the last minute of the day.
        if (usergetmidnight(time() + 60) === usergetmidnight(time())) {
            $this->assertLessThan(strpos($html, 'Later today'), strpos($html, '>' . $today . '<'));
            $this->assertLessThan(strpos($html, $upcoming), strpos($html, 'Later today'));
        }
        $this->assertGreaterThan(strpos($html, $upcoming), strpos($html, 'In three days'));

        $conversation = \core_message\api::create_conversation(
            \core_message\api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [(int) $other->id, (int) $student->id]
        );
        \core_message\api::send_message_to_conversation($other->id, $conversation->id, 'Hello', FORMAT_PLAIN);
        $this->assertStringContainsString(get_string('home:unread', 'local_oksigeniaaty'), self::home());

        set_config('disabledfeatures', 'CoreMainMenuDelegate_AddonMessages', 'tool_mobile');
        $this->assertStringNotContainsString(get_string('home:unread', 'local_oksigeniaaty'), self::home());
    }

    /**
     * Writing to a course goes by permission, not by role: a teacher can, a manager who is not enrolled can, a student cannot.
     */
    public function test_write_to_a_course(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/forum/lib.php');
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'English B2']);
        $forum = forum_get_course_forum($course->id, 'news');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $student = $generator->create_and_enrol($course, 'student');
        $manager = $generator->create_user();
        role_assign($DB->get_field('role', 'id', ['shortname' => 'manager']), $manager->id, \context_system::instance());

        $this->setUser($teacher);
        $this->assertStringContainsString('mobile_compose', self::home());
        $page = mobile::mobile_compose([]);
        $this->assertStringContainsString('English B2', $page['templates'][0]['html']);
        $this->assertSame((string) $forum->id, $page['otherdata']['forumid']);

        $this->setUser($manager);
        $this->assertStringContainsString('mobile_compose', self::home());

        $this->setUser($student);
        $this->assertStringNotContainsString('mobile_compose', self::home());
        $this->assertStringNotContainsString('English B2', mobile::mobile_compose([])['templates'][0]['html']);
        try {
            post_to_class::execute((int) $forum->id, 'Title', 'Words');
            $this->fail('A student cannot post in the announcements.');
        } catch (\moodle_exception $e) {
            $this->assertSame('cannotcreatediscussion', $e->errorcode);
        }

        $this->setUser($teacher);
        $result = post_to_class::execute((int) $forum->id, 'No class tomorrow', "See you on <b>Monday</b>\nBye");
        $post = $DB->get_record('forum_posts', ['discussion' => $result['discussionid']], '*', MUST_EXIST);
        $this->assertSame('No class tomorrow', $post->subject);
        $this->assertStringContainsString('&lt;b&gt;Monday&lt;/b&gt;', $post->message);
        $this->assertStringContainsString('<br', $post->message);
        $this->assertSame(1, (int) $post->mailnow);

        $this->expectException(\invalid_parameter_exception::class);
        post_to_class::execute((int) $forum->id, 'Title', '   ');
    }

    /**
     * The band of the brand: its small logo and the greeting by the first name, never a role's or a test account's word;
     * no longer the big card with the centre's name, its welcome or its picture.
     */
    public function test_band(): void {
        $this->resetAfterTest();
        set_config('homelogourl', 'https://example.org/logo.svg', 'local_oksigeniaaty');
        set_config('homeheading', 'Academia Ejemplo', 'local_oksigeniaaty');
        set_config('homeintro', 'Your week at a glance', 'local_oksigeniaaty');

        $generator = $this->getDataGenerator();
        $this->setUser($generator->create_user(['firstname' => 'Lucía']));
        $html = self::home();
        $this->assertStringContainsString('<section class="aty-franja">', $html);
        $this->assertStringContainsString('<img class="aty-franja-logo" src="https://example.org/logo.svg" alt="">', $html);
        $this->assertStringContainsString('>' . get_string('home:greeting', 'local_oksigeniaaty', 'Lucía') . '<', $html);
        $this->assertStringContainsString('<p class="aty-franja-dia">', $html);
        $this->assertStringNotContainsString('aty-hero', $html);
        $this->assertStringNotContainsString('Academia Ejemplo', $html);
        $this->assertStringNotContainsString('Your week at a glance', $html);

        $hello = '>' . get_string('home:hello', 'local_oksigeniaaty') . '<';
        $placeholders = [
            ['firstname' => 'Student'],
            ['firstname' => 'Teacher'],
            ['firstname' => 'app.alumno', 'username' => 'app.alumno'],
        ];
        foreach ($placeholders as $fields) {
            $this->setUser($generator->create_user($fields));
            $html = self::home();
            $this->assertStringContainsString($hello, $html);
            $this->assertStringNotContainsString(get_string('home:greeting', 'local_oksigeniaaty', $fields['firstname']), $html);
        }
    }

    /**
     * The line under the greeting says what matters most of the day: what there is to do, then what there is today, then
     * what waits for a grade, and with nothing, what comes next.
     */
    public function test_day_line(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $line = new \ReflectionMethod(mobile::class, 'day_line');
        $now = time();
        $midnight = usergetmidnight($now);
        $say = fn(string $id, $a = null) => get_string('home:day:' . $id, 'local_oksigeniaaty', $a);
        $row = fn(string $title) => ['title' => $title];
        $event = fn(string $kind, bool $live = false, int $at = 0) => ['kind' => $kind, 'live' => $live,
            'timestamp' => ($at ?: $now + 60) * 1000];

        // Something to do comes first, whatever else there is.
        $this->assertSame(
            'You have something to do: Museum trip.',
            $line->invoke(null, [$row('Museum trip')], [['id' => 1]], [], 3, [], [], $now)
        );
        $this->assertSame(
            'You have things to do: Museum trip and Parents\' evening.',
            $line->invoke(null, [$row('Museum trip'), $row('Parents\' evening')], [], [], 0, [], [], $now)
        );
        $this->assertSame(
            'You have 3 things to do.',
            $line->invoke(null, [$row('A'), $row('B'), $row('C')], [], [], 0, [], [], $now)
        );

        // Then today: what is starting and the live classes, the deadlines, the rest and the unread conversations.
        $this->assertSame(
            'Today you have a live class and something due.',
            $line->invoke(null, [], [['id' => 1]], [$event('due')], 0, [], [], $now)
        );
        $this->assertSame(
            'Today you have 2 live classes, an activity that closes, 2 events and an unread conversation.',
            $line->invoke(
                null,
                [],
                [],
                [$event('', true), $event('', true), $event('close'), $event(''), $event('')],
                1,
                [],
                [],
                $now
            )
        );

        // Then what waits for a grade.
        $this->assertSame(
            'You have 5 submissions to grade.',
            $line->invoke(null, [], [], [], 0, [['count' => 2], ['count' => 3]], [], $now)
        );

        // With nothing, what comes next.
        $this->assertSame($say('nothing'), $line->invoke(null, [], [], [], 0, [], [], $now));
        $this->assertSame(
            $say('nexttomorrow'),
            $line->invoke(null, [], [], [], 0, [], [$event('', false, $midnight + DAYSECS + 3600)], $now)
        );
        $later = $midnight + 3 * DAYSECS + 3600;
        $this->assertSame(
            $say('next', trim(userdate($later, $say('dateformat')))),
            $line->invoke(null, [], [], [], 0, [], [$event('', false, $later)], $now)
        );
    }

    /**
     * What is about to start, with its way in: a BigBlueButton room from half an hour before it opens until it closes, an
     * event whose location is an address while it lasts; nothing for what has no way in that can be known.
     */
    public function test_starting_now(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['shortname' => 'ENG']);
        $student = $generator->create_and_enrol($course, 'student');
        $this->setAdminUser();
        $now = time();
        $room = $generator->create_module('bigbluebuttonbn', ['course' => $course->id, 'name' => 'Asking the way',
            'openingtime' => $now + 12 * MINSECS, 'closingtime' => $now + 90 * MINSECS]);
        $generator->create_module('bigbluebuttonbn', ['course' => $course->id, 'name' => 'Phrasal verbs',
            'openingtime' => $now + 45 * MINSECS]);
        $generator->create_event(['name' => 'Meet with the tutor', 'courseid' => $course->id, 'eventtype' => 'course',
            'timestart' => $now + 5 * MINSECS, 'timeduration' => HOURSECS, 'location' => 'https://meet.example.org/abc-defg']);
        $generator->create_event(['name' => 'In the classroom', 'courseid' => $course->id, 'eventtype' => 'course',
            'timestart' => $now + 10 * MINSECS, 'location' => 'Room 4']);

        $this->setUser($student);
        $html = self::home();
        $join = (new \moodle_url('/mod/bigbluebuttonbn/bbb_view.php', ['action' => 'join', 'id' => $room->cmid,
            'bn' => $room->id]))->out();
        $this->assertSame(2, substr_count($html, 'class="aty-event aty-ahora"'));
        $this->assertStringContainsString('<a class="aty-entrar" core-link href="' . $join . '" capture="false">Join</a>', $html);
        $this->assertMatchesRegularExpression('/<small>in 1[12] min<\/small>/', $html);
        $this->assertStringContainsString('href="https://meet.example.org/abc-defg" capture="false"', $html);
        // The room's start is the activity itself, not "Asking the way starts".
        $this->assertStringContainsString('<span class="aty-event-name">Asking the way</span>', $html);
        // Further away than half an hour, and without a way in: not about to start, but there today all the same.
        $ahora = substr($html, 0, strrpos($html, 'aty-entrar'));
        $this->assertStringNotContainsString('Phrasal verbs', $ahora);
        $this->assertStringNotContainsString('In the classroom', $ahora);
        if (usergetmidnight($now + 50 * MINSECS) === usergetmidnight($now)) {
            $this->assertStringContainsString('In the classroom', $html);
        }

        // Started ten minutes ago: on while it lasts, gone when its end cannot be known.
        if (usergetmidnight($now - 10 * MINSECS) === usergetmidnight($now)) {
            $this->setAdminUser();
            $generator->create_event(['name' => 'Speaking club', 'courseid' => $course->id, 'eventtype' => 'course',
                'timestart' => $now - 10 * MINSECS, 'timeduration' => HOURSECS, 'location' => 'https://zoom.example.org/j/1']);
            $generator->create_event(['name' => 'Open office', 'courseid' => $course->id, 'eventtype' => 'course',
                'timestart' => $now - 10 * MINSECS, 'location' => 'https://zoom.example.org/j/2']);
            $this->setUser($student);
            $html = self::home();
            $this->assertStringContainsString('<small>now</small>', $html);
            $this->assertStringContainsString('https://zoom.example.org/j/1', $html);
            $this->assertStringNotContainsString('https://zoom.example.org/j/2', $html);
            $this->assertStringNotContainsString('Open office', $html);
        }

        // Whoever may not join the room has no way in.
        $this->setUser($generator->create_user());
        $this->assertStringNotContainsString('aty-ahora', self::home());
    }

    /**
     * Today's deadlines and those to come say what they are: "Due" under the hour, big, today; over the hour later on.
     */
    public function test_deadlines(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $generator->create_module('assign', ['course' => $course->id, 'name' => 'My neighbourhood',
            'duedate' => usergetmidnight(time()) + 3 * DAYSECS + 23 * HOURSECS]);

        $this->setUser($student);
        $html = self::home();
        $this->assertMatchesRegularExpression(
            '#<span class="aty-plazo"><small>Due</small><b [^>]+>[^<]+</b></span>#',
            $html
        );
        $this->assertStringContainsString('<span class="aty-event-name">My neighbourhood</span>', $html);
        // The kind is next to the hour, not repeated next to the course.
        $this->assertStringNotContainsString(' · ' . get_string('home:event:due', 'local_oksigeniaaty') . '<', $html);

        // Due later today, unless the test runs in the last two hours of the day.
        if (usergetmidnight(time() + 2 * HOURSECS) === usergetmidnight(time())) {
            $generator->create_module('assign', ['course' => $course->id, 'name' => 'Short essay',
                'duedate' => time() + HOURSECS]);
            $html = self::home();
            $this->assertStringContainsString('<span class="aty-event-hora aty-hora-plazo">', $html);
            $this->assertStringContainsString('<small>Due</small>', $html);
            $this->assertStringContainsString('Today you have something due.', $html);
        }
    }

    /**
     * "Also": only the shortcuts that the tab bar does not have, the plugins' tiles among them; none, no section. The
     * course cards are the tab "My courses", and not on the home any more.
     */
    public function test_also(): void {
        global $CFG;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Natural sciences']);
        $student = $generator->create_and_enrol($course, 'student');
        $tabs = "My courses | {$CFG->wwwroot}/my/courses.php | fas-book-open\n"
            . "Notices | {$CFG->wwwroot}/message/output/popup/notifications.php | fas-bell\n"
            . "Agenda | {$CFG->wwwroot}/calendar/view.php?view=upcoming | fas-calendar\n"
            . "Messages | {$CFG->wwwroot}/message/index.php | fas-comments";
        set_config('homeshortcuts', $tabs, 'local_oksigeniaaty');

        $this->setUser($student);
        $html = self::home();
        $this->assertStringNotContainsString('aty-atajo', $html);
        $this->assertStringNotContainsString('>' . get_string('home:shortcuts', 'local_oksigeniaaty') . '<', $html);
        $this->assertStringNotContainsString('Natural sciences', $html);

        set_config('homeshortcuts', $tabs . "\nLibrary | https://library.example.org/ | fas-book\n"
            . "Timetable | {$CFG->wwwroot}/mod/page/view.php?id=1 | fas-clock", 'local_oksigeniaaty');
        $html = self::home();
        $this->assertStringContainsString('>' . get_string('home:shortcuts', 'local_oksigeniaaty') . '<', $html);
        $this->assertSame(2, substr_count($html, 'class="aty-atajo"'));
        $this->assertStringContainsString('Library', $html);
        $this->assertStringContainsString('Timetable', $html);
        $this->assertStringNotContainsString('/my/courses.php', $html);

        // Messages off in the app: no tab for them, so their shortcut stays.
        set_config('disabledfeatures', 'CoreMainMenuDelegate_AddonMessages', 'tool_mobile');
        $html = self::home();
        $this->assertSame(3, substr_count($html, 'class="aty-atajo"'));
        $this->assertStringContainsString('/message/index.php', $html);
    }

    /**
     * "Your agenda on your phone": the user's own export feed, as Google Calendar's link, as webcal and as the plain
     * address; and a plain notice when the site has calendar export turned off.
     */
    public function test_calendar(): void {
        global $CFG;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $html = mobile::mobile_calendar([])['templates'][0]['html'];
        $this->assertStringContainsString('https://calendar.google.com/calendar/r?cid=webcal%3A%2F%2F', $html);
        $this->assertStringContainsString('webcal://', $html);
        $this->assertStringContainsString('/calendar/export_execute.php?userid=' . $user->id . '&amp;authtoken=', $html);

        $CFG->enablecalendarexport = 0;
        $html = mobile::mobile_calendar([])['templates'][0]['html'];
        $this->assertStringNotContainsString('export_execute.php', $html);
        $this->assertStringContainsString(get_string('calendarapp:disabled', 'local_oksigeniaaty'), $html);
    }

    /**
     * The home's HTML for the current user.
     *
     * @return string
     */
    private static function home(): string {
        return mobile::mobile_home([])['templates'][0]['html'];
    }

    /**
     * Nobody is kept out of the app unless a plugin says so; the page for those who are says why and where to go.
     */
    public function test_not_allowed_page(): void {
        global $CFG, $OUTPUT;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');
        $user = $this->getDataGenerator()->create_user();

        $this->assertTrue(local_oksigeniaaty_user_allowed((int) $user->id));

        $html = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_notallowed', [
            'message' => 'Not in your plan',
            'url' => 'https://example.com',
        ]);
        $this->assertStringContainsString('Not in your plan', $html);
        $this->assertStringContainsString('core-link href="https://example.com" capture="false"', $html);
    }
    /**
     * A child's agenda, seen by an adult of their family who is in none of their courses, has the dates of the child's
     * activities: the calendar is asked as the child, and given back as it was.
     */
    public function test_child_events_as_the_child(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['shortname' => 'MAT']);
        $child = $generator->create_user();
        $adult = $generator->create_user();
        $generator->enrol_user($child->id, $course->id, 'student');
        $generator->create_module('assign', ['course' => $course->id, 'name' => 'Fracciones', 'duedate' => time() + 3 * DAYSECS]);

        $this->setUser($adult);
        $method = new \ReflectionMethod(mobile::class, 'child_events');
        $events = $method->invoke(null, (int) $child->id);

        $this->assertNotEmpty($events);
        $this->assertSame('Fracciones', $events[0]['name']);
        $this->assertSame((int) $adult->id, (int) \core_calendar\local\event\container::get_requesting_user());
    }
}
