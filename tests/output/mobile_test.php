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
}
