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
        $this->assertStringContainsString('｛｛ course ｝｝ ＠let c = 1;', $html);
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
     * The hero takes the picture only when there is one.
     */
    public function test_hero_picture(): void {
        global $OUTPUT;
        $this->resetAfterTest();

        $pictured = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_home', [
            'heading' => 'Library', 'pictureurl' => 'https://example.org/room.webp',
        ]);
        $this->assertStringContainsString('aty-hero-pictured', $pictured);
        $this->assertStringContainsString('<img class="aty-hero-picture" src="https://example.org/room.webp"', $pictured);

        $plain = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_home', ['heading' => 'Library']);
        $this->assertStringNotContainsString('aty-hero-pictured', $plain);
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
