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
 * Tests for «forgot my PIN»: who is asking and how the link reaches them.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oksigeniaaty\local;

/**
 * Tests for «forgot my PIN»: who is asking and how the link reaches them.
 *
 * @covers \local_oksigeniaaty\local\pinreset
 */
#[\PHPUnit\Framework\Attributes\CoversClass(pinreset::class)]
final class pinreset_test extends \advanced_testcase {
    /**
     * A username or a unique e-mail finds the account; a shared e-mail, an unknown one or a suspended account find nobody.
     */
    public function test_find_by_username_or_email(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $ana = $gen->create_user(['username' => 'ana.ejemplo', 'email' => 'ana@example.com']);
        $gen->create_user(['email' => 'familia@example.com']);
        $gen->create_user(['email' => 'familia@example.com']);
        $gen->create_user(['username' => 'luis.ejemplo', 'email' => 'luis@example.com', 'suspended' => 1]);

        $this->assertEquals($ana->id, pinreset::find_user('Ana.Ejemplo')->id);
        $this->assertEquals($ana->id, pinreset::find_user(' ana@example.com ')->id);
        $this->assertNull(pinreset::find_user('familia@example.com'));
        $this->assertNull(pinreset::find_user('nadie@example.com'));
        $this->assertNull(pinreset::find_user('luis.ejemplo'));
        $this->assertNull(pinreset::find_user(''));
    }

    /**
     * With Oksigenia Family, a mobile number known to be the adult's finds their account, however it is typed; a number
     * only typed by someone, or shared by two accounts, finds nobody.
     */
    public function test_find_by_mobile_number(): void {
        global $DB;
        if (!class_exists('\local_oksigeniafamily\api')) {
            $this->markTestSkipped('Oksigenia Family is not installed.');
        }
        $this->resetAfterTest();
        set_config('phoneprefix', '+34', 'local_oksigeniafamily');
        $gen = $this->getDataGenerator();
        $ana = $gen->create_user();
        $luis = $gen->create_user();
        $marta = $gen->create_user();
        $now = time();
        $family = $DB->insert_record('local_oksigeniafamily_family', (object) ['status' => \local_oksigeniafamily\api::ACTIVE,
            'timecreated' => $now, 'timemodified' => $now]);
        $adult = fn(int $userid, string $phone, int $verified) => $DB->insert_record('local_oksigeniafamily_adult', (object) [
            'familyid' => $family, 'userid' => $userid, 'phone' => $phone, 'phoneverified' => $verified,
            'status' => \local_oksigeniafamily\api::ACTIVE, 'timecreated' => $now, 'timemodified' => $now]);
        $adult($ana->id, '+34600111222', 1);
        $adult($luis->id, '+34600333444', 0);
        $adult($marta->id, '+34600555666', 1);
        $adult($luis->id, '+34600555666', 1);

        $this->assertEquals($ana->id, pinreset::find_user('600 111 222')->id);
        $this->assertEquals($ana->id, pinreset::find_user('+34 600-111-222')->id);
        $this->assertNull(pinreset::find_user('600333444'));
        $this->assertNull(pinreset::find_user('600555666'));
    }

    /**
     * Without Oksigenia Family, or when it is not a number, a mobile number finds nobody.
     */
    public function test_phone_needs_family_and_a_number(): void {
        $this->resetAfterTest();
        $this->assertSame([], pinreset::userids_by_phone('abc'));
        $this->assertSame([], pinreset::userids_by_phone('12'));
        if (!class_exists('\local_oksigeniafamily\api')) {
            $this->assertSame([], pinreset::userids_by_phone('600111222'));
        }
    }

    /**
     * A real address gets the link by e-mail; an account whose address is a placeholder gets it as a notice to the phone.
     */
    public function test_send_by_email_or_to_the_phone(): void {
        $this->resetAfterTest();
        $this->preventResetByRollback();
        $gen = $this->getDataGenerator();
        $mailbox = $gen->create_user(['email' => 'ana@example.com']);
        $nomailbox = $gen->create_user(['email' => 'atyfamily-abc@invalid.invalid']);
        $url = new \moodle_url('/local/oksigeniaaty/open.php', ['key' => 'abc123']);

        $this->assertTrue(pinreset::has_mailbox($mailbox));
        $this->assertFalse(pinreset::has_mailbox($nomailbox));
        $this->assertFalse(pinreset::has_mailbox((object) ['email' => '']));

        $emails = $this->redirectEmails();
        $this->assertSame('email', pinreset::send($mailbox, $url));
        $this->assertCount(1, $emails->get_messages());
        $this->assertSame('ana@example.com', $emails->get_messages()[0]->to);
        $emails->close();

        $notices = $this->redirectMessages();
        $this->assertSame('notice', pinreset::send($nomailbox, $url));
        $sent = $notices->get_messages();
        $this->assertCount(1, $sent);
        $this->assertEquals($nomailbox->id, $sent[0]->useridto);
        $this->assertSame('pinreset', $sent[0]->eventtype);
        $this->assertSame($url->out(false), $sent[0]->contexturl);
        $this->assertStringContainsString($url->out(false), $sent[0]->fullmessage);
    }
}
