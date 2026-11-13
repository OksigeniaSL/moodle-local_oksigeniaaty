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

use local_oksigeniaaty\event\pin_cleared;

/**
 * «Send the app»: who the person looking may reach, the cards of the courses under way, sending and clearing a PIN.
 *
 * @package    local_oksigeniaaty
 * @category   test
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_oksigeniaaty\local\sendapp
 * @covers     \local_oksigeniaaty\output\sendapp
 */
#[\PHPUnit\Framework\Attributes\CoversClass(sendapp::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_oksigeniaaty\output\sendapp::class)]
final class sendapp_test extends \advanced_testcase {
    /**
     * The app on, as on a hosted site.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('enablewebservices', 1);
        set_config('enablemobilewebservice', 1);
    }

    /**
     * Give a person a token of the mobile service, as entering the app does.
     *
     * @param int $userid The person.
     */
    private function enter_app(int $userid): void {
        global $DB;

        $service = $DB->get_record('external_services', ['shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE], '*', MUST_EXIST);
        \core_external\util::generate_token(EXTERNAL_TOKEN_PERMANENT, $service, $userid, \context_system::instance());
    }

    /**
     * Give a person a capability across the site.
     *
     * @param int $userid The person.
     * @param string[] $capabilities The capabilities.
     */
    private function allow(int $userid, array $capabilities): void {
        $roleid = $this->getDataGenerator()->create_role();
        foreach ($capabilities as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, \context_system::instance()->id, true);
        }
        role_assign($roleid, $userid, \context_system::instance()->id);
        accesslib_clear_all_caches_for_unit_testing();
    }

    /**
     * The ids of the courses of some cards.
     *
     * @param array[] $found From running_courses() or find_courses().
     * @return int[]
     */
    private function ids(array $found): array {
        return array_map(fn($item) => (int) $item['course']->id, $found);
    }

    /**
     * Anyone in the site only for who may see the site's people; by e-mail or username only for who may see those. Without
     * them, the search still finds the courses of the person looking.
     */
    public function test_search_rights(): void {
        $generator = $this->getDataGenerator();
        $target = $generator->create_user(['firstname' => 'Zacarías', 'username' => 'zq_user', 'email' => 'zq@example.com']);
        $course = $generator->create_course(['fullname' => 'Zoología marina', 'shortname' => 'ZOO1']);
        $other = $generator->create_course(['fullname' => 'Zoología de campo', 'shortname' => 'ZOO2']);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $generator->enrol_user($target->id, $other->id);
        $this->setUser($teacher);

        $this->assertSame(['people' => false, 'identity' => false, 'cohorts' => false], sendapp::rights());
        $view = sendapp::audience(0, 0, 0, 'Zacar');
        $this->assertSame('search', $view->kind);
        $this->assertSame([], $view->users);
        $this->assertSame([(int) $course->id], $this->ids(sendapp::find_courses('zoolog')));
        $this->assertSame([(int) $course->id], $this->ids(sendapp::find_courses('ZOO')));
        $this->assertSame([], sendapp::cohorts('', sendapp::COHORTS));

        $this->allow((int) $teacher->id, ['moodle/site:viewparticipants']);
        $this->assertSame([(int) $target->id], array_keys(sendapp::audience(0, 0, 0, 'Zacar')->users));
        $this->assertSame([], sendapp::audience(0, 0, 0, 'zq@example')->users);
        $this->assertSame([], sendapp::audience(0, 0, 0, 'zq_us')->users);

        $this->allow((int) $teacher->id, ['moodle/site:viewuseridentity']);
        $this->assertSame([(int) $target->id], array_keys(sendapp::audience(0, 0, 0, 'zq@example')->users));
        $this->assertSame([(int) $target->id], array_keys(sendapp::audience(0, 0, 0, 'zq_us')->users));
    }

    /**
     * A course takes its participants, a hidden one also its hidden courses, and a cohort the cohorts.
     */
    public function test_course_and_cohort_rights(): void {
        $generator = $this->getDataGenerator();
        $mine = $generator->create_course();
        $theirs = $generator->create_course();
        $hidden = $generator->create_course(['visible' => 0]);
        $group = $generator->create_group(['courseid' => $mine->id]);
        $leftover = $generator->create_group(['courseid' => $theirs->id]);
        $cohort = $generator->create_cohort();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $mine->id, 'editingteacher');
        // Who may see the participants of the hidden course, and no more.
        $roleid = $generator->create_role();
        assign_capability('moodle/course:viewparticipants', CAP_ALLOW, $roleid, \context_system::instance()->id, true);
        role_assign($roleid, $teacher->id, \context_course::instance($hidden->id)->id);
        $this->setUser($teacher);

        $view = sendapp::audience((int) $mine->id, (int) $group->id, 0, 'ignored');
        $this->assertSame('course', $view->kind);
        $this->assertSame((int) $group->id, $view->groupid);
        // A group of another course is dropped.
        $this->assertSame(0, sendapp::audience((int) $mine->id, (int) $leftover->id, 0, '')->groupid);

        foreach ([[$theirs->id, 0], [$hidden->id, 0], [0, $cohort->id]] as [$courseid, $cohortid]) {
            try {
                sendapp::audience((int) $courseid, 0, (int) $cohortid, '');
                $this->fail('It should not open');
            } catch (\required_capability_exception $e) {
                $this->assertInstanceOf(\required_capability_exception::class, $e);
            }
        }

        $this->allow((int) $teacher->id, ['moodle/cohort:view']);
        $this->assertSame('cohort', sendapp::audience(0, 0, (int) $cohort->id, '')->kind);
        $this->assertSame('home', sendapp::audience(SITEID, 0, 0, '')->kind);
    }

    /**
     * The cards are only of the courses whose people the person looking may see, chosen before counting, so that how many
     * there are says nothing about the others; and they go with most people still waiting first.
     */
    public function test_running_courses_only_visible(): void {
        $generator = $this->getDataGenerator();
        $now = time();
        // Bigger courses than the teacher's, more than the candidates that are counted.
        $crowd = [$generator->create_user(), $generator->create_user(), $generator->create_user(), $generator->create_user()];
        for ($i = 0; $i < sendapp::CANDIDATES + 2; $i++) {
            $course = $generator->create_course(['fullname' => 'Big ' . $i, 'startdate' => $now - DAYSECS]);
            foreach ($crowd as $person) {
                $generator->enrol_user($person->id, $course->id);
            }
        }

        $teacher = $generator->create_user();
        $few = $generator->create_course(['fullname' => 'Few waiting', 'startdate' => $now - DAYSECS]);
        $many = $generator->create_course(['fullname' => 'Many waiting', 'startdate' => $now - DAYSECS]);
        $ended = $generator->create_course(['startdate' => $now - 100 * DAYSECS, 'enddate' => $now - DAYSECS]);
        $future = $generator->create_course(['startdate' => $now + 10 * DAYSECS]);
        $hidden = $generator->create_course(['visible' => 0, 'startdate' => $now - DAYSECS]);
        foreach ([$few, $many, $ended, $future, $hidden] as $course) {
            $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        }
        $inapp = $generator->create_user();
        $this->enter_app((int) $inapp->id);
        $generator->enrol_user($inapp->id, $few->id);
        $generator->enrol_user($generator->create_user()->id, $many->id);
        $generator->enrol_user($generator->create_user()->id, $many->id);

        $this->setUser($teacher);
        $this->assertSame([(int) $many->id, (int) $few->id], $this->ids(sendapp::running_courses()));

        $this->setAdminUser();
        $cards = sendapp::running_courses();
        $this->assertCount(sendapp::CARDS, $cards);
        $this->assertNotContains((int) $ended->id, $this->ids($cards));
        $this->assertNotContains((int) $future->id, $this->ids($cards));
        $this->assertNotContains((int) $hidden->id, $this->ids($cards));
    }

    /**
     * Cohorts: for who may see them, the biggest first, counting only who can sign in, and found by name or id number.
     */
    public function test_cohorts(): void {
        $generator = $this->getDataGenerator();
        $small = $generator->create_cohort(['name' => 'Staff', 'idnumber' => 'st-1']);
        $big = $generator->create_cohort(['name' => 'Pupils', 'idnumber' => 'pu-1']);
        foreach ([$generator->create_user(), $generator->create_user(), $generator->create_user(['suspended' => 1])] as $user) {
            cohort_add_member($big->id, $user->id);
        }
        cohort_add_member($small->id, $generator->create_user()->id);
        $viewer = $generator->create_user();
        $this->setUser($viewer);
        $this->assertSame([], sendapp::cohorts('', sendapp::COHORTS));

        $this->allow((int) $viewer->id, ['moodle/cohort:view']);
        $cohorts = sendapp::cohorts('', sendapp::COHORTS);
        $this->assertSame([(int) $big->id, (int) $small->id], array_map(fn($cohort) => (int) $cohort->id, $cohorts));
        $this->assertSame(2, $cohorts[0]->total);
        $this->assertSame([(int) $small->id], array_map(fn($cohort) => (int) $cohort->id, sendapp::cohorts('ST-', 6)));
        $this->assertCount(1, sendapp::cohorts('', 1));
    }

    /**
     * «Send to the N still waiting» goes to them only, once: then they have their link.
     */
    public function test_send_waiting(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $waiting = [$generator->create_user(), $generator->create_user()];
        $inapp = $generator->create_user();
        $sent = $generator->create_user();
        $nomail = $generator->create_user(['email' => '']);
        foreach (array_merge($waiting, [$inapp, $sent, $nomail]) as $person) {
            $generator->enrol_user($person->id, $course->id);
        }
        $this->enter_app((int) $inapp->id);
        loginkey::create_issued((int) $sent->id);
        $this->setAdminUser();
        $sink = $this->redirectEmails();

        $result = sendapp::send_waiting(linkroster::rows(linkroster::course((int) $course->id)));
        $this->assertSame(['sent' => 2, 'failed' => 0, 'queued' => 0], $result);
        $this->assertEqualsCanonicalizing(
            [$waiting[0]->email, $waiting[1]->email],
            array_map(fn($message) => $message->to, $sink->get_messages())
        );
        $sink->clear();

        $result = sendapp::send_waiting(linkroster::rows(linkroster::course((int) $course->id)));
        $this->assertSame(['sent' => 0, 'failed' => 0, 'queued' => 0], $result);
        $this->assertCount(0, $sink->get_messages());

        // Without the app on the site, nothing goes.
        $late = $generator->create_user();
        $generator->enrol_user($late->id, $course->id);
        set_config('enablemobilewebservice', 0);
        $this->assertSame(0, sendapp::send_waiting(linkroster::rows(linkroster::course((int) $course->id)))['sent']);
        $this->assertCount(0, $sink->get_messages());
    }

    /**
     * One person of the list, at once; again, not before an hour; nobody outside the list or already in the app.
     */
    public function test_send_one_once_an_hour(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $person = $generator->create_user();
        $inapp = $generator->create_user();
        $outsider = $generator->create_user();
        $generator->enrol_user($person->id, $course->id);
        $generator->enrol_user($inapp->id, $course->id);
        $this->enter_app((int) $inapp->id);
        $this->setAdminUser();
        $sink = $this->redirectEmails();
        $rows = fn() => linkroster::rows(linkroster::course((int) $course->id));

        $this->assertTrue(sendapp::send_one($rows(), (int) $person->id));
        $this->assertCount(1, $sink->get_messages());

        // Its link is on its way: «Send again» waits an hour.
        $this->assertSame(linkroster::SENT, current(array_filter($rows(), fn($row) => $row['user']->id == $person->id))['state']);
        $this->assertFalse(sendapp::send_one($rows(), (int) $person->id));
        $DB->set_field('user_private_key', 'timecreated', time() - linksender::COOLDOWN - 1, ['userid' => $person->id]);
        $this->assertTrue(sendapp::send_one($rows(), (int) $person->id));
        $this->assertCount(2, $sink->get_messages());

        $this->assertFalse(sendapp::send_one($rows(), (int) $outsider->id));
        $this->assertFalse(sendapp::send_one($rows(), (int) $inapp->id));
        $this->assertCount(2, $sink->get_messages());
    }

    /**
     * «Clear the PIN» only for someone in the list who has one, and it is logged.
     */
    public function test_clear_pin(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $forgot = $generator->create_user();
        $nopin = $generator->create_user();
        $outsider = $generator->create_user();
        $generator->enrol_user($forgot->id, $course->id);
        $generator->enrol_user($nopin->id, $course->id);
        pin::set((int) $forgot->id, '482915');
        pin::set((int) $outsider->id, '771204');
        $rows = linkroster::rows(linkroster::course((int) $course->id));
        $this->assertSame([(int) $forgot->id => 0], sendapp::with_pin([(int) $forgot->id, (int) $nopin->id]));
        $this->setAdminUser();
        $sink = $this->redirectEvents();

        $this->assertFalse(sendapp::clear_pin($rows, (int) $outsider->id));
        $this->assertTrue(pin::has_pin((int) $outsider->id));
        $this->assertFalse(sendapp::clear_pin($rows, (int) $nopin->id));
        $this->assertTrue(sendapp::clear_pin($rows, (int) $forgot->id));
        $this->assertFalse(pin::has_pin((int) $forgot->id));

        $events = array_values(array_filter($sink->get_events(), fn($event) => $event instanceof pin_cleared));
        $this->assertCount(1, $events);
        $this->assertSame((int) $forgot->id, (int) $events[0]->relateduserid);
    }

    /**
     * The page of a big course: a bar in place of the phones, the first people of a long list and the link to all of them,
     * «Clear the PIN» where there is one, and the button for those waiting with its question in place.
     */
    public function test_page_of_a_big_course(): void {
        global $PAGE;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Inglés B1']);
        $count = sendapp::PHONES + 1;
        for ($i = 0; $i < $count; $i++) {
            $generator->enrol_user($generator->create_user(['lastname' => sprintf('P%03d', $i)])->id, $course->id);
        }
        $first = current(linkroster::rows(linkroster::course((int) $course->id)));
        pin::set((int) $first['user']->id, '482915');
        $this->setAdminUser();
        $PAGE->set_url(new \moodle_url('/local/oksigeniaaty/sendapp.php'));
        $output = $PAGE->get_renderer('core');
        $here = new \moodle_url('/local/oksigeniaaty/sendapp.php', ['courseid' => $course->id]);
        $view = sendapp::audience((int) $course->id, 0, 0, '');
        $rows = linkroster::rows($view->users);

        $page = (new \local_oksigeniaaty\output\sendapp($view, $rows, $here))->export_for_template($output);
        $chosen = $page['chosen'];
        $this->assertSame('Inglés B1', $chosen['name']);
        $this->assertFalse($chosen['hasphones']);
        $this->assertSame([['state' => linkroster::WAITING, 'count' => $count]], $chosen['bar']);
        $this->assertTrue($chosen['cansendall']);
        $this->assertStringContainsString((string) $count, $chosen['sendalltext']);
        $this->assertCount(1, $chosen['lists']);
        $list = $chosen['lists'][0];
        $this->assertSame($count, $list['count']);
        $this->assertCount(sendapp::PERLIST, $list['rows']);
        $this->assertTrue($list['more']);
        $this->assertStringContainsString('all=waiting', $list['moreurl']);
        $this->assertTrue($list['rows'][0]['haspin']);
        $this->assertFalse($list['rows'][1]['haspin']);

        $html = $output->render_from_template('local_oksigeniaaty/sendapp', $page);
        $this->assertStringContainsString('name="resetpin"', $html);
        $this->assertStringContainsString('name="confirm"', $html);
        $this->assertStringContainsString('<details class="local-oksigeniaaty-send-all">', $html);
        $this->assertStringNotContainsString('name="mode"', $html);

        $whole = (new \local_oksigeniaaty\output\sendapp($view, $rows, $here, linkroster::WAITING))->export_for_template($output);
        $this->assertCount($count, $whole['chosen']['lists'][0]['rows']);
        $this->assertFalse($whole['chosen']['lists'][0]['more']);
    }

    /**
     * The first screen and a search for whoever only teaches one course: their course, no cohorts, nobody by name, and a
     * help that says so.
     */
    public function test_page_for_a_teacher(): void {
        global $PAGE;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Alemán A1', 'startdate' => time() - DAYSECS]);
        $generator->create_course(['fullname' => 'Alemán A2', 'startdate' => time() - DAYSECS]);
        $generator->create_cohort(['name' => 'Alemán']);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $generator->create_user(['firstname' => 'Alemán', 'lastname' => 'Persona']);
        $this->setUser($teacher);
        $PAGE->set_url(new \moodle_url('/local/oksigeniaaty/sendapp.php'));
        $output = $PAGE->get_renderer('core');
        $here = new \moodle_url('/local/oksigeniaaty/sendapp.php');

        $home = (new \local_oksigeniaaty\output\sendapp(sendapp::audience(0, 0, 0, ''), [], $here))->export_for_template($output);
        $this->assertSame(['Alemán A1'], array_column($home['courses'], 'name'));
        $this->assertFalse($home['hascohorts']);
        $this->assertSame(get_string('sendapp:searchhintcourses', 'local_oksigeniaaty'), $home['searchhint']);

        $view = sendapp::audience(0, 0, 0, 'Alemán');
        $found = (new \local_oksigeniaaty\output\sendapp($view, linkroster::rows($view->users), $here))
            ->export_for_template($output);
        $this->assertSame(['Alemán A1'], array_column($found['rescourses'], 'name'));
        $this->assertFalse($found['hasrescohorts']);
        $this->assertFalse($found['hasrespeople']);
        $this->assertFalse($found['noresults']);
    }
}
