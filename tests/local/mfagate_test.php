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
 * The app's password form on a site with a second factor: refused, after the password, only to whoever must give one.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(mfagate::class)]
final class mfagate_test extends \advanced_testcase {
    /** The password of the accounts made here. */
    private const PASSWORD = 'Tablero-del-38!';

    /**
     * Multi-factor authentication on, with the given factors on at 100 points each.
     *
     * @param string[] $factors Factor names.
     * @return void
     */
    private function mfa(array $factors): void {
        set_config('enabled', 1, 'tool_mfa');
        foreach ($factors as $factor) {
            set_config('enabled', 1, 'factor_' . $factor);
            set_config('weight', 100, 'factor_' . $factor);
        }
    }

    /**
     * An account with a password.
     *
     * @param array $fields More fields.
     * @return \stdClass
     */
    private function account(array $fields = []): \stdClass {
        return $this->getDataGenerator()->create_user(['password' => self::PASSWORD] + $fields);
    }

    /**
     * Someone the site asks for a second factor gets, for the right password, the words and the launch address instead of
     * a token; the attempt goes to the logs. A wrong password is left to token.php.
     */
    public function test_refused_after_the_right_password(): void {
        $this->resetAfterTest();
        $this->mfa(['email', 'nosetup']);
        $user = $this->account(['email' => 'staff@example.com']);

        $this->assertNull(mfagate::decide($user->username, 'wrong'));

        // Where the site hands tokens to another app, only the words.
        set_config('forcedurlscheme', 'moodlemobile', 'tool_mobile');
        $this->assertArrayNotHasKey(mfagate::FIELD, mfagate::decide($user->username, self::PASSWORD));

        set_config('forcedurlscheme', mfagate::APPSCHEME, 'tool_mobile');
        $sink = $this->redirectEvents();
        $answer = mfagate::decide(strtoupper($user->username), self::PASSWORD);
        $events = $sink->get_events();
        $sink->close();

        $this->assertSame('invalidlogin', $answer['errorcode']);
        $this->assertStringContainsString('/local/oksigeniaaty/launch.php?k=', $answer[mfagate::FIELD]);
        $this->assertNotEmpty($answer['error']);
        $this->assertCount(1, array_filter($events,
            fn($e) => $e instanceof \local_oksigeniaaty\event\app_signin_needs_factor));
        $this->assertEquals($user->id, reset($events)->relateduserid);
    }

    /**
     * Whoever the site lets in without a second factor keeps the app's form, and so does everyone when the site has no
     * multi-factor authentication or has turned the gate off.
     */
    public function test_form_stays_without_a_factor_to_give(): void {
        $this->resetAfterTest();
        $user = $this->account(['email' => 'staff@example.com']);

        $this->assertFalse(mfagate::enabled());
        $this->assertNull(mfagate::decide($user->username, self::PASSWORD));

        // Nothing to give: no setup lets them in, as on the web. The e-mail factor is on in a new site.
        set_config('enabled', 0, 'factor_email');
        $this->mfa(['nosetup']);
        $this->assertTrue(mfagate::enabled());
        $this->assertFalse(mfagate::needs_factor($user));
        $this->assertNull(mfagate::decide($user->username, self::PASSWORD));

        // With a factor to give, refused; with the gate off, the form again.
        $this->mfa(['email']);
        $this->assertTrue(mfagate::needs_factor($user));
        set_config('mfagate', 0, 'local_oksigeniaaty');
        $this->assertFalse(mfagate::enabled());
        $this->assertNull(mfagate::decide($user->username, self::PASSWORD));
    }

    /**
     * A suspended or locked account, or one whose sign-in method is off, is left to token.php, which refuses it as usual:
     * the gate never says the password was right there.
     */
    public function test_closed_accounts_are_left_to_token_php(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->mfa(['email']);

        $suspended = $this->account(['email' => 'a@example.com', 'suspended' => 1]);
        $this->assertNull(mfagate::decide($suspended->username, self::PASSWORD));

        $CFG->lockoutthreshold = 3;
        $locked = $this->account(['email' => 'b@example.com']);
        login_lock_account($locked);
        $this->assertNull(mfagate::decide($locked->username, self::PASSWORD));

        $this->assertNull(mfagate::decide('nobody', self::PASSWORD));
    }

    /**
     * Where the site signs in with the e-mail, the e-mail typed in the app is the account too.
     */
    public function test_email_as_username(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->mfa(['email']);
        $user = $this->account(['email' => 'staff@example.com']);

        $CFG->authloginviaemail = 0;
        $this->assertNull(mfagate::decide('staff@example.com', self::PASSWORD));
        $CFG->authloginviaemail = 1;
        $this->assertNotNull(mfagate::decide('Staff@Example.com', self::PASSWORD));
    }

    /**
     * The launch key works once, for its account and from the network address it was given to, and a new one replaces
     * the one before.
     */
    public function test_launch_key_once(): void {
        $this->resetAfterTest();
        $user = $this->account();
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';

        $elsewhere = mfagate::launch_url((int) $user->id)->get_param('k');
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7';
        $this->assertNull(mfagate::spend_key($elsewhere));

        $first = mfagate::launch_url((int) $user->id)->get_param('k');
        $second = mfagate::launch_url((int) $user->id)->get_param('k');

        $this->assertNull(mfagate::spend_key($first));
        $this->assertSame((int) $user->id, mfagate::spend_key($second));
        $this->assertNull(mfagate::spend_key($second));
        $this->assertNull(mfagate::spend_key('nonsense'));
    }
}
