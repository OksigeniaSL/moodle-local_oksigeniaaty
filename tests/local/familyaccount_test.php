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
        set_config('familyapp', 1, 'local_oksigeniaaty');
        local_oksigeniaaty_familyapp_changed();
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
        // The account and its time («42:…») never show: the ciphertext is base64, which has no colon, so «42» alone can
        // turn up in it by chance.
        $this->assertStringNotContainsString('42:', base64_decode(strtr($sealed, '-_', '+/')));
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
        // Moodle lets it use the web services: without this, the app signs in and the first call is refused.
        $this->assertTrue(is_enabled_auth('webservice'));
        if (class_exists(\core_auth\validate_user::class)) {
            \core\di::get(\core_auth\validate_user::class)->validate_before_external_login($user);
        }
        $this->assertSame($userid, familyaccount::ensure((int) $adult->id));
        $this->assertSame($userid, (int) $DB->get_field('local_oksigeniafamily_adult', 'userid', ['id' => $adult->id]));
    }

    /**
     * An adult the school's data brought is told nothing until they are in: neither the link opened nor the account made
     * wakes them; coming into the app does, and so does the home for whoever was in already. A real account is left alone.
     */
    public function test_coming_in_wakes_a_quiet_adult(): void {
        global $DB;
        [$student, $adult] = $this->claimed();
        $table = 'local_oksigeniafamily_adult';
        $DB->set_field($table, 'quiet', 1, ['id' => $adult->id]);
        $DB->set_field('external_services', 'enabled', 1, ['shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE]);

        $userid = familyaccount::ensure((int) $adult->id);
        $key = loginkey::create_issued($userid, 1);
        $this->assertSame([], family::adults_of_child((int) $student->id));

        $this->assertArrayHasKey('token', tokenexchange::respond($key, '', true));
        $this->assertSame([(int) $adult->id], array_keys(family::adults_of_child((int) $student->id)));
        $this->assertTrue(familyaccount::in_app($userid));

        // In already before this was said.
        $DB->set_field($table, 'quiet', 1, ['id' => $adult->id]);
        $this->setUser($userid);
        mobile::mobile_home([]);
        $this->assertEquals(0, $DB->get_field($table, 'quiet', ['id' => $adult->id]));

        // A teacher who is also a parent, in the app as a teacher.
        $teacher = $this->getDataGenerator()->create_user();
        [, $other] = $this->claimed();
        family::set_adult_user((int) $other->id, (int) $teacher->id);
        $DB->set_field($table, 'quiet', 1, ['id' => $other->id]);
        familyaccount::came_in($teacher);
        $this->assertEquals(1, $DB->get_field($table, 'quiet', ['id' => $other->id]));
    }

    /**
     * With no surname or address, Moodle would send the account to fill in a profile it cannot edit.
     */
    public function test_account_is_complete_with_little_data(): void {
        $student = $this->getDataGenerator()->create_user();
        $adult = family::claim(family::issue_claim_code((int) $student->id), ['firstname' => 'Ana']);

        $user = \core_user::get_user(familyaccount::ensure((int) $adult->id));

        $this->assertFalse(user_not_fully_set_up($user, false));
        // Mail stopped would stop the notices to the phone too; without an address, the .invalid domain is what sends no mail.
        $this->assertSame(0, (int) $user->emailstop);
        $this->assertStringEndsWith('.invalid', $user->email);
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
     * A teacher who is also a parent goes into the app with their own account, only signed in to it here (never
     * through a link that came by email, which a history or a log can open again); and only if they may have a link of
     * their own.
     */
    public function test_teacher_parent(): void {
        global $DB;
        $teacher = $this->getDataGenerator()->create_user(['firstname' => 'Eva', 'email' => 'eva@school.example']);
        $student = $this->getDataGenerator()->create_user();
        $adult = family::claim(family::issue_claim_code((int) $student->id), [
            'userid' => (int) $teacher->id,
            'firstname' => 'Eva',
            'lastname' => 'Real',
        ]);

        $this->assertSame([], \local_oksigeniaaty_oksigeniafamily_links($adult, (int) $student->id));
        // Proven by the link sent to the address, but not signed in: no way into the real account.
        $this->assertSame([], \local_oksigeniaaty_oksigeniafamily_links($adult, (int) $student->id, true));
        $this->setUser($teacher);
        $links = \local_oksigeniaaty_oksigeniafamily_links($adult, (int) $student->id, true);
        $this->assertCount(1, $links);
        $this->assertStringContainsString('/local/oksigeniaaty/open.php?key=', $links[0]['url']);
        $this->assertNull(familyaccount::ensure((int) $adult->id));

        // Where links of one's own are only for some cohorts, and they are not in them: nothing.
        $cohort = $this->getDataGenerator()->create_cohort();
        set_config('selfservicecohorts', (string) $cohort->id, 'local_oksigeniaaty');
        $this->assertSame([], \local_oksigeniaaty_oksigeniafamily_links($adult, (int) $student->id, true));
        set_config('selfservicecohorts', '', 'local_oksigeniaaty');

        // An administrator never gets one.
        $DB->set_field('local_oksigeniafamily_adult', 'userid', get_admin()->id, ['id' => $adult->id]);
        $adult->userid = get_admin()->id;
        $this->assertSame([], \local_oksigeniaaty_oksigeniafamily_links($adult, (int) $student->id, true));
    }

    /**
     * A family's account never takes an address another account already has: two accounts with one address break
     * «Forgotten your password?» for both.
     */
    public function test_address_of_another_account(): void {
        $this->getDataGenerator()->create_user(['email' => 'Ana@Example.org']);
        [, $adult] = $this->claimed();

        $user = \core_user::get_user(familyaccount::ensure((int) $adult->id));

        $this->assertStringEndsWith('@invalid.invalid', $user->email);
    }

    /**
     * Adding another child: the letter's QR, read with the app's scanner, or the code typed.
     */
    public function test_add_page_scans(): void {
        $page = mobile::mobile_family_add([]);

        $this->assertStringContainsString('atyScanCode()', $page['templates'][0]['html']);
        $this->assertStringContainsString(get_string('family:scan', 'local_oksigeniaaty'), $page['templates'][0]['html']);
        $this->assertStringContainsString('CoreQRScanService.scanQR()', $page['javascript']);
        // Without Marandu's channels there is nothing more to connect, and no card for it on the home.
        if (!class_exists(\local_oksigeniamarandu\api::class)) {
            $this->assertNull(channels::home_card(get_admin()));
        }
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
        set_config('forcedurlscheme', 'oksigeniaaty', 'tool_mobile');

        // Until the centre turns it on, nothing is offered.
        set_config('familyapp', 0, 'local_oksigeniaaty');
        $this->assertSame([], \local_oksigeniaaty_oksigeniafamily_links($adult, (int) $student->id));
        set_config('familyapp', 1, 'local_oksigeniaaty');
        \core\plugininfo\auth::enable_plugin('webservice', 0);
        $this->assertSame([], \local_oksigeniaaty_oksigeniafamily_links($adult, (int) $student->id));
    }

    /**
     * An account made here keeps, of the home's ways in, what leads outside the site and the pages in the app: not the
     * site's web pages, whether a shortcut of the site or a plugin's tile.
     */
    public function test_outside_site(): void {
        global $CFG;
        $links = [
            ['label' => 'Badges', 'url' => $CFG->wwwroot . '/badges/mybadges.php'],
            ['label' => 'Site', 'url' => $CFG->wwwroot],
            ['label' => 'School website', 'url' => 'https://school.example.org/'],
            ['label' => 'Library tile', 'ispage' => false, 'url' => $CFG->wwwroot . '/local/library/'],
            ['label' => 'Canteen tile', 'ispage' => true, 'component' => 'local_canteen', 'method' => 'mobile_menu'],
        ];

        $kept = familyaccount::outside_site($links);
        $this->assertSame(['School website', 'Canteen tile'], array_column($kept, 'label'));
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
     * An account made here, given to another person, is no longer the family's: the adult stays in the family without it,
     * and the account sees no child. A real account in a family is Family's, and stays.
     */
    public function test_reassigned(): void {
        global $DB;
        [$student, $adult] = $this->claimed();
        $userid = familyaccount::ensure((int) $adult->id);
        $this->assertSame([(int) $student->id], family::children_of_user($userid));

        \local_oksigeniaaty\api::account_reassigned($userid);

        $this->assertEmpty($DB->get_field('local_oksigeniafamily_adult', 'userid', ['id' => $adult->id]));
        $this->assertSame([], family::children_of_user($userid));
        $this->assertFalse((bool) \core_user::get_user($userid)->deleted);

        $parent = $this->getDataGenerator()->create_user();
        $child = $this->getDataGenerator()->create_user();
        family::claim(family::issue_claim_code((int) $child->id), ['userid' => (int) $parent->id, 'firstname' => 'Eva',
            'lastname' => 'Real']);
        \local_oksigeniaaty\api::account_reassigned((int) $parent->id);
        $this->assertSame([(int) $child->id], family::children_of_user((int) $parent->id));
    }

    /**
     * A child's page opens only for an adult of their family, and grades only if the centre shows them.
     */
    public function test_child_page(): void {
        global $CFG;
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
        // The child's page has them at its head, by first name and surnames.
        $this->assertStringContainsString('<h1 class="aty-ficha-nombre">Hugo Rey</h1>', $html);
        $this->assertStringContainsString('Exam of English', $html);
        $this->assertStringNotContainsString('Private entry', $html);
        $this->assertStringNotContainsString('Essay', $html);

        set_config('familygrades', 1, 'local_oksigeniaaty');
        $html = mobile::mobile_child(['childid' => (int) $student->id])['templates'][0]['html'];
        $this->assertStringContainsString('Essay', $html);

        set_config('homeshortcuts', "My badges | {$CFG->wwwroot}/badges/mybadges.php | fas-award\n"
            . "School website | https://school.example.org | fas-school", 'local_oksigeniaaty');
        $home = mobile::mobile_home([])['templates'][0]['html'];
        // It cannot enter the site's pages: only the shortcut that leads outside.
        $this->assertStringContainsString('School website', $home);
        $this->assertStringNotContainsString('My badges', $home);
        $this->assertStringContainsString('mobile_child', $home);
        $this->assertStringContainsString('mobile_family_add', $home);
        // Another adult of the household is invited from here, where Family has it.
        if (method_exists(\local_oksigeniafamily\output\mobile::class, 'mobile_invite_view')) {
            $this->assertStringContainsString('mobile_invite_view', $home);
        }
        // A family's own account greets in a line, without the band, and has a card for each child, named in the bar.
        $this->assertStringContainsString('<p class="aty-saludo">', $home);
        $this->assertStringNotContainsString('aty-franja', $home);
        $this->assertStringContainsString('<div class="aty-hijo-tarjeta"', $home);
        $this->assertStringContainsString('title="Hugo"', $home);

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
