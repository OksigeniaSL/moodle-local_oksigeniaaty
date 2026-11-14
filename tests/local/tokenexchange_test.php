<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_oksigeniaaty\local;

/**
 * What token.php answers the app for a sign-in key: the token, a PIN to ask for, or an error that says nothing inside.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(tokenexchange::class)]
final class tokenexchange_test extends \advanced_testcase {
    /**
     * A site with the app's way in open: web services, the mobile service and its record on.
     */
    private function open_site(): void {
        global $DB;

        set_config('enablewebservices', 1);
        set_config('enablemobilewebservice', 1);
        $DB->set_field('external_services', 'enabled', 1, ['shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE]);
    }

    /**
     * The generic failure the app is shown.
     *
     * @return array
     */
    private static function failed(): array {
        return ['error' => get_string('signinfailed', 'local_oksigeniaaty')];
    }

    /**
     * A good key gives the token, the private token and who it signs in, and is spent.
     */
    public function test_key_gives_the_token_once(): void {
        global $DB;
        $this->resetAfterTest();
        $this->open_site();
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Ana', 'lastname' => 'Ruiz', 'lang' => 'en']);
        $key = loginkey::create((int) $user->id);

        $answer = tokenexchange::respond($key, '', true);
        $this->assertArrayNotHasKey('error', $answer);
        $stored = $DB->get_record('external_tokens', ['token' => $answer['token']], '*', MUST_EXIST);
        $this->assertEquals($user->id, $stored->userid);
        $this->assertSame($stored->privatetoken, $answer['privatetoken']);
        $this->assertTrue($answer['needspinsetup']);
        $this->assertSame(get_string('pintitlesetup', 'local_oksigeniaaty'), $answer['pinui']['title']);
        $this->assertSame('Ana Ruiz', $answer['fullname']);
        $this->assertSame($user->username, $answer['username']);
        $this->assertSame('en', $answer['lang']);

        // Single use.
        $this->assertSame(self::failed(), tokenexchange::respond($key, '', true));
    }

    /**
     * Without the mobile service, or not in a POST over HTTPS, or with a key that does not exist, nothing; and a key that
     * came the wrong way is not spent.
     */
    public function test_what_stops_it(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $key = loginkey::create((int) $user->id);

        $this->assertSame(self::failed(), tokenexchange::respond($key, '', true));
        $this->open_site();
        $this->assertSame(self::failed(), tokenexchange::respond($key, '', false));
        $this->assertTrue(loginkey::is_live($key));
        $this->assertSame(self::failed(), tokenexchange::respond('notakey', '', true));

        // The site sends the app's sign-in to the browser (to keep its second factor): the key does not go round it.
        set_config('typeoflogin', 2, 'tool_mobile');
        $this->assertSame(self::failed(), tokenexchange::respond($key, '', true));
        set_config('typeoflogin', 1, 'tool_mobile');

        // A suspended account.
        $suspended = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $this->assertSame(self::failed(), tokenexchange::respond(loginkey::create((int) $suspended->id), '', true));

        $this->assertArrayHasKey('token', tokenexchange::respond($key, '', true));
    }

    /**
     * An administrator is told to use their password, and gets no token.
     */
    public function test_not_for_admins(): void {
        $this->resetAfterTest();
        $this->open_site();
        $key = loginkey::create((int) get_admin()->id);

        $this->assertSame(
            ['error' => get_string('selfservicenotforadmins', 'local_oksigeniaaty')],
            tokenexchange::respond($key, '', true)
        );
    }

    /**
     * With a PIN, the key alone asks for it; a wrong one says so and leaves the key live; five wrong lock it, and then
     * even the right one waits; the right one before that gives the token without offering to set a PIN.
     */
    public function test_pin(): void {
        $this->resetAfterTest();
        $this->open_site();
        $user = $this->getDataGenerator()->create_user();
        pin::set((int) $user->id, '2468');
        $key = loginkey::create((int) $user->id);

        $answer = tokenexchange::respond($key, '', true);
        $this->assertTrue($answer['needspin']);
        $this->assertArrayNotHasKey('token', $answer);
        $this->assertSame(get_string('pintitleenter', 'local_oksigeniaaty'), $answer['pinui']['title']);

        $answer = tokenexchange::respond($key, '1111', true);
        $this->assertTrue($answer['needspin']);
        $this->assertFalse($answer['locked']);
        $this->assertSame(get_string('pinwrong', 'local_oksigeniaaty'), $answer['error']);
        $this->assertTrue(loginkey::is_live($key));

        $answer = tokenexchange::respond($key, '2468', true);
        $this->assertArrayHasKey('token', $answer);
        $this->assertFalse($answer['needspinsetup']);
        $this->assertNull($answer['pinui']);

        // Another key, and five wrong tries.
        $key = loginkey::create((int) $user->id);
        for ($i = 1; $i < pin::MAXFAILS; $i++) {
            $this->assertFalse(tokenexchange::respond($key, '1111', true)['locked']);
        }
        $answer = tokenexchange::respond($key, '1111', true);
        $this->assertTrue($answer['locked']);
        $this->assertSame(get_string('pinlocked', 'local_oksigeniaaty'), $answer['error']);
        $answer = tokenexchange::respond($key, '2468', true);
        $this->assertTrue($answer['locked']);
        $this->assertArrayNotHasKey('token', $answer);
        $this->assertTrue(loginkey::is_live($key));
    }

    /**
     * The key of «Forgot the PIN» signs in without the PIN, clears it and offers to set a new one.
     */
    public function test_reset_key(): void {
        $this->resetAfterTest();
        $this->open_site();
        $user = $this->getDataGenerator()->create_user();
        pin::set((int) $user->id, '2468');

        $answer = tokenexchange::respond(loginkey::create_reset((int) $user->id), '', true);
        $this->assertArrayHasKey('token', $answer);
        $this->assertTrue($answer['needspinsetup']);
        $this->assertFalse(pin::has_pin((int) $user->id));
    }
}
