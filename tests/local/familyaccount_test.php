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

use local_oksigeniaaty\external\family_claim;
use local_oksigeniaaty\output\mobile;
use local_oksigeniafamily\api as family;

/**
 * The account of an adult of a family in the app. Only where Oksigenia Family is installed; its own tests cover the records.
 *
 * @package    local_oksigeniaaty
 * @category   test
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_oksigeniaaty\local\familyaccount
 * @covers     \local_oksigeniaaty\external\family_claim
 * @covers     \local_oksigeniaaty\output\mobile
 */
final class familyaccount_test extends \advanced_testcase {
    /**
     * Only with the family plugin, and on a site that uses the app.
     */
    protected function setUp(): void {
        parent::setUp();
        if (!class_exists(family::class)) {
            $this->markTestSkipped('Oksigenia Family is not installed.');
        }
        global $CFG;
        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_oksigeniafamily');
        set_config('enablewebservices', 1);
        set_config('enablemobilewebservice', 1);
        set_config('forcedurlscheme', 'oksigeniaaty', 'tool_mobile');
        set_config('typeoflogin', 1, 'tool_mobile');
    }

    /**
     * A student and an adult who has claimed their code, with no account.
     *
     * @return array [the student, the adult's record]
     */
    private function claimed(): array {
        $student = $this->getDataGenerator()->create_user(['firstname' => 'Hugo', 'lastname' => 'Rey']);
        $code = family::issue_claim_code((int) $student->id);

        return [$student, family::claim($code, ['firstname' => 'Ana', 'lastname' => 'Rey', 'email' => 'ana@example.org'])];
    }

    /**
     * A link says who it is for only to this site, and stops when it expires.
     */
    public function test_link(): void {
        $url = new \moodle_url(familyaccount::link(42));
        $sealed = $url->get_param('k');

        $this->assertSame(42, familyaccount::read($sealed));
        $this->assertStringNotContainsString('42', base64_decode(strtr($sealed, '-_', '+/')));
        $this->assertNull(familyaccount::read('rubbish'));
        $this->assertNull(familyaccount::read(substr($sealed, 0, -4) . 'AAAA'));

        $this->setCurrentTimeStart();
        $old = rtrim(strtr(base64_encode(\core\encryption::encrypt('42:' . (time() - 1))), '+/', '-_'), '=');
        $this->assertNull(familyaccount::read($old));
    }

    /**
     * The account is made when asked for, cannot enter the web, and is the same one the next time.
     */
    public function test_account(): void {
        global $DB;
        [, $adult] = $this->claimed();
        $this->assertEmpty($DB->get_field('local_oksigeniafamily_adult', 'userid', ['id' => $adult->id]));

        $userid = familyaccount::ensure((int) $adult->id);
        $user = \core_user::get_user($userid);

        $this->assertSame('webservice', $user->auth);
        $this->assertTrue(familyaccount::is_own($user));
        $this->assertSame('ana@example.org', $user->email);
        $this->assertFalse(get_auth_plugin('webservice')->user_login($user->username, 'whatever'));
        $this->assertSame($userid, familyaccount::ensure((int) $adult->id));
        $this->assertSame($userid, (int) $DB->get_field('local_oksigeniafamily_adult', 'userid', ['id' => $adult->id]));
    }

    /**
     * With no surname or address, Moodle would send the account to fill in a profile it cannot edit.
     */
    public function test_account_is_complete_with_little_data(): void {
        $student = $this->getDataGenerator()->create_user();
        $adult = family::claim(family::issue_claim_code((int) $student->id), ['firstname' => 'Ana']);

        $user = \core_user::get_user(familyaccount::ensure((int) $adult->id));

        $this->assertFalse(user_not_fully_set_up($user, false));
        $this->assertSame(1, (int) $user->emailstop);
    }

    /**
     * Holding a child's code never leads into a real account of the site, even a link made before.
     */
    public function test_real_accounts_are_never_entered(): void {
        $parent = $this->getDataGenerator()->create_user(['firstname' => 'Eva']);
        $student = $this->getDataGenerator()->create_user();
        $adult = family::claim(family::issue_claim_code((int) $student->id), [
            'userid' => (int) $parent->id,
            'firstname' => 'Eva',
            'lastname' => 'Real',
        ]);

        $this->assertNull(familyaccount::ensure((int) $adult->id));
        $this->assertSame([], \local_oksigeniaaty_oksigeniafamily_links($adult, (int) $student->id));
    }

    /**
     * What the claim page shows: the way in, only where the site uses the app.
     */
    public function test_claim_page_link(): void {
        [$student, $adult] = $this->claimed();

        $links = \local_oksigeniaaty_oksigeniafamily_links($adult, (int) $student->id);
        $this->assertCount(1, $links);
        $this->assertStringContainsString('/local/oksigeniaaty/family.php?k=', $links[0]['url']);

        set_config('forcedurlscheme', '', 'tool_mobile');
        $this->assertSame([], \local_oksigeniaaty_oksigeniafamily_links($adult, (int) $student->id));
    }

    /**
     * Whoever leaves takes the account made for them; a real account stays.
     */
    public function test_leaving(): void {
        [, $adult] = $this->claimed();
        $userid = familyaccount::ensure((int) $adult->id);
        family::remove_adult((int) $adult->id);
        $this->assertTrue((bool) \core_user::get_user($userid)->deleted);

        $parent = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $real = family::claim(family::issue_claim_code((int) $student->id), [
            'userid' => (int) $parent->id,
            'firstname' => 'Eva',
            'lastname' => 'Real',
        ]);
        family::remove_adult((int) $real->id);
        $this->assertFalse((bool) \core_user::get_user((int) $parent->id)->deleted);
        $this->assertNull(familyaccount::ensure((int) $real->id));
    }

    /**
     * A child's page opens only for an adult of their family, and grades only if the centre shows them.
     */
    public function test_child_page(): void {
        [$student, $adult] = $this->claimed();
        $course = $this->getDataGenerator()->create_course(['shortname' => 'ENG']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id);
        $this->setAdminUser();
        $this->getDataGenerator()->create_event([
            'name' => 'Exam of English', 'courseid' => $course->id, 'eventtype' => 'course', 'timestart' => time() + DAYSECS,
        ]);
        $this->getDataGenerator()->create_event([
            'name' => 'Private entry',
            'courseid' => 0,
            'userid' => $student->id,
            'eventtype' => 'user',
            'timestart' => time() + DAYSECS,
        ]);
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'name' => 'Essay', 'grade' => 10]);
        $item = \grade_item::fetch(['itemtype' => 'mod', 'itemmodule' => 'assign', 'iteminstance' => $assign->id]);
        $item->update_final_grade((int) $student->id, 8, 'test');

        $this->setUser(\core_user::get_user(familyaccount::ensure((int) $adult->id)));
        $html = mobile::mobile_child(['childid' => (int) $student->id])['templates'][0]['html'];
        $this->assertStringNotContainsString('Hugo Rey', $html);
        $this->assertStringContainsString('Exam of English', $html);
        $this->assertStringNotContainsString('Private entry', $html);
        $this->assertStringNotContainsString('Essay', $html);

        set_config('familygrades', 1, 'local_oksigeniaaty');
        $html = mobile::mobile_child(['childid' => (int) $student->id])['templates'][0]['html'];
        $this->assertStringContainsString('Essay', $html);

        $home = mobile::mobile_home([])['templates'][0]['html'];
        $this->assertStringContainsString('mobile_child', $home);
        $this->assertStringContainsString('mobile_family_add', $home);

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertStringNotContainsString('mobile_family_add', mobile::mobile_home([])['templates'][0]['html']);
        $this->expectException(\moodle_exception::class);
        mobile::mobile_child(['childid' => (int) $student->id]);
    }

    /**
     * Another code typed in the app joins the same account; wrong ones slow down.
     */
    public function test_adding_with_a_code(): void {
        [, $adult] = $this->claimed();
        $this->setUser(\core_user::get_user(familyaccount::ensure((int) $adult->id)));
        $sister = $this->getDataGenerator()->create_user(['firstname' => 'Lola', 'lastname' => 'Rey']);

        $result = family_claim::execute(family::issue_claim_code((int) $sister->id));

        $this->assertSame('Lola Rey', $result['name']);
        $this->assertCount(2, family::children_of_adult((int) $adult->id));

        for ($i = 0; $i < 10; $i++) {
            try {
                family_claim::execute('NOPE' . $i);
                $this->fail('A wrong code joins nobody.');
            } catch (\moodle_exception $e) {
                $this->assertSame('claiminvalid', $e->errorcode);
            }
        }
        $this->expectExceptionMessage(get_string('family:toomany', 'local_oksigeniaaty'));
        family_claim::execute('NOPE');
    }
}
