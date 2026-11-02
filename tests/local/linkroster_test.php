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

use local_oksigeniaaty\api;
use local_oksigeniaaty\task\send_links;

/**
 * Who can be sent the app, how each one stands, and how it goes out.
 *
 * @package    local_oksigeniaaty
 * @category   test
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_oksigeniaaty\local\linkroster
 * @covers     \local_oksigeniaaty\local\linksender
 * @covers     \local_oksigeniaaty\task\send_links
 */
final class linkroster_test extends \advanced_testcase {
    /**
     * Give a person a token of the mobile service, as entering the app does.
     *
     * @param int $userid The person.
     */
    private function enter_app(int $userid): void {
        global $DB;

        $service = $DB->get_record('external_services', ['shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE], '*', MUST_EXIST);
        \core_external\util::generate_token(
            EXTERNAL_TOKEN_PERMANENT,
            $service,
            $userid,
            \context_system::instance()
        );
    }

    /**
     * The state of one person in some rows.
     *
     * @param array $rows The rows.
     * @param int $userid The person.
     * @return string
     */
    private function state(array $rows, int $userid): string {
        foreach ($rows as $row) {
            if ((int) $row['user']->id === $userid) {
                return $row['state'];
            }
        }
        return 'absent';
    }

    /**
     * Each state, and those waiting first.
     */
    public function test_states(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $inapp = $generator->create_user(['lastname' => 'Alba']);
        $sent = $generator->create_user(['lastname' => 'Bravo']);
        $expired = $generator->create_user(['lastname' => 'Cano']);
        $waiting = $generator->create_user(['lastname' => 'Duque']);
        $nomail = $generator->create_user(['lastname' => 'Egea', 'email' => '']);
        $manager = $generator->create_user(['lastname' => 'Feria']);
        $people = [$inapp, $sent, $expired, $waiting, $nomail, $manager];
        foreach ($people as $person) {
            $generator->enrol_user($person->id, $course->id);
        }
        $roleid = $generator->create_role();
        assign_capability('moodle/site:config', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $manager->id, \context_system::instance());

        $this->enter_app((int) $inapp->id);
        loginkey::create_issued((int) $sent->id);
        loginkey::create_issued((int) $expired->id);
        $DB->set_field(
            'user_private_key',
            'validuntil',
            time() - 10,
            ['script' => loginkey::ISSUED_SCRIPT, 'userid' => $expired->id]
        );

        $rows = linkroster::rows(linkroster::course((int) $course->id));

        $this->assertSame(linkroster::INAPP, $this->state($rows, (int) $inapp->id));
        $this->assertSame(linkroster::SENT, $this->state($rows, (int) $sent->id));
        $this->assertSame(linkroster::WAITING, $this->state($rows, (int) $expired->id));
        $this->assertSame(linkroster::WAITING, $this->state($rows, (int) $waiting->id));
        $this->assertSame(linkroster::BLOCKED, $this->state($rows, (int) $nomail->id));
        $this->assertSame(linkroster::BLOCKED, $this->state($rows, (int) $manager->id));

        $this->assertSame([(int) $expired->id, (int) $waiting->id], linkroster::waiting($rows));
        $this->assertSame(
            [linkroster::WAITING => 2, linkroster::SENT => 1, linkroster::INAPP => 1, linkroster::BLOCKED => 2],
            linkroster::counts($rows)
        );
        $this->assertSame((int) $expired->id, (int) $rows[0]['user']->id);
        $this->assertSame('noemail', current(array_filter($rows, fn($row) => $row['user']->id == $nomail->id))['why']);
    }

    /**
     * The three ways of choosing, and only people who can sign in.
     */
    public function test_audiences(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $group = $generator->create_group(['courseid' => $course->id]);
        $cohort = $generator->create_cohort();

        $ingroup = $generator->create_user(['firstname' => 'Zacarías']);
        $outside = $generator->create_user();
        $suspended = $generator->create_user(['suspended' => 1]);
        foreach ([$ingroup, $outside, $suspended] as $person) {
            $generator->enrol_user($person->id, $course->id);
            cohort_add_member($cohort->id, $person->id);
        }
        groups_add_member($group, $ingroup);

        $this->assertEqualsCanonicalizing(
            [$ingroup->id, $outside->id],
            array_keys(linkroster::course((int) $course->id))
        );
        $this->assertSame([(int) $ingroup->id], array_keys(linkroster::course((int) $course->id, (int) $group->id)));
        $this->assertEqualsCanonicalizing([$ingroup->id, $outside->id], array_keys(linkroster::cohort((int) $cohort->id)));
        $this->assertSame([(int) $ingroup->id], array_keys(linkroster::search('Zacar')));
        $this->assertSame([], linkroster::search('  '));
    }

    /**
     * A few people go at once; many go through a task, which sends nothing twice.
     */
    public function test_sending(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        $few = [$generator->create_user(), $generator->create_user()];
        $sink = $this->redirectEmails();
        $result = linksender::send(array_map(fn($user) => (int) $user->id, $few));
        $this->assertSame(['sent' => 2, 'failed' => 0, 'queued' => 0], $result);
        $this->assertCount(2, $sink->get_messages());
        $this->assertTrue(linksender::just_sent((int) $few[0]->id));
        $sink->clear();

        $many = [];
        for ($i = 0; $i <= linksender::AT_ONCE; $i++) {
            $many[] = (int) $generator->create_user()->id;
        }
        // One of them entered the app between the click and the task.
        $this->enter_app($many[0]);
        $result = linksender::send($many);
        $this->assertSame(count($many), $result['queued']);
        $this->assertCount(0, $sink->get_messages());

        $tasks = \core\task\manager::get_adhoc_tasks(send_links::class);
        $this->assertCount(1, $tasks);
        $this->assertSame((int) $USER->id, (int) current($tasks)->get_userid());
        ob_start();
        current($tasks)->execute();
        ob_end_clean();
        $this->assertCount(count($many) - 1, $sink->get_messages());
        $sink->clear();

        // The same task again: they all have their link now.
        ob_start();
        current($tasks)->execute();
        ob_end_clean();
        $this->assertCount(0, $sink->get_messages());
    }

    /**
     * An administrator gets no link, and the failure does not stop the others.
     */
    public function test_failures_do_not_stop_the_rest(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $person = $this->getDataGenerator()->create_user();
        $sink = $this->redirectEmails();

        $result = linksender::send_now([(int) get_admin()->id, (int) $person->id]);

        $this->assertSame(['sent' => 1, 'failed' => 1], $result);
        $this->assertCount(1, $sink->get_messages());
    }

    /**
     * The e-mail carries the link, in text and in its look, and comes from the centre.
     */
    public function test_the_mail(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('brandcolour', '#17574c', 'local_oksigeniaaty');
        $person = $this->getDataGenerator()->create_user(['firstname' => 'Ana']);
        $sink = $this->redirectEmails();

        $this->assertTrue(api::send_link((int) $person->id));

        $message = $sink->get_messages()[0];
        $raw = quoted_printable_decode($message->body);
        $this->assertStringContainsString('local/oksigeniaaty/open.php', $raw);
        $this->assertStringContainsString('#17574c', $raw);
        $this->assertStringContainsString('Ana', $raw);
    }
}
