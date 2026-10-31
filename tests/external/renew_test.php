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

namespace local_oksigeniaaty\external;

/**
 * Tests for the token renewal web service.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_oksigeniaaty\external\renew
 */
#[\PHPUnit\Framework\Attributes\CoversClass(renew::class)]
final class renew_test extends \advanced_testcase {
    /**
     * Without a PIN, a valid token is enough: the expiry moves forward in place, on the same token.
     */
    public function test_renews_without_pin(): void {
        global $CFG, $DB;
        $this->resetAfterTest();

        $this->setUser($this->getDataGenerator()->create_user());
        $token = self::token();
        $DB->set_field('external_tokens', 'validuntil', time() + DAYSECS, ['id' => $token->id]);

        $result = renew::execute('');

        $this->assertFalse($result['needspin']);
        $this->assertSame($token->token, $result['token']);
        $this->assertGreaterThanOrEqual(time() + $CFG->tokenduration - MINSECS, $result['validuntil']);
        $this->assertEquals($result['validuntil'], $DB->get_field('external_tokens', 'validuntil', ['id' => $token->id]));
    }

    /**
     * With a PIN, an empty request asks for it and changes nothing, but tells the app when the token expires, so an app
     * that signed in with a password can renew in time.
     */
    public function test_asks_for_pin_with_current_expiry(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        \local_oksigeniaaty\local\pin::set((int) $user->id, '4826');
        $token = self::token();
        $expiry = time() + DAYSECS;
        $DB->set_field('external_tokens', 'validuntil', $expiry, ['id' => $token->id]);

        $result = renew::execute('');

        $this->assertTrue($result['needspin']);
        $this->assertSame('', $result['token']);
        $this->assertEquals($expiry, $result['validuntil']);
        $this->assertEquals($expiry, $DB->get_field('external_tokens', 'validuntil', ['id' => $token->id]));

        $result = renew::execute('4826');
        $this->assertFalse($result['needspin']);
        $this->assertGreaterThan($expiry, $result['validuntil']);
    }

    /**
     * Administrators never get a token renewed without their password.
     */
    public function test_refuses_administrators(): void {
        $this->resetAfterTest();

        $this->setAdminUser();
        $this->expectException(\moodle_exception::class);
        renew::execute('');
    }

    /**
     * The current user's app token, as login/token.php would mint it.
     *
     * @return \stdClass The token record.
     */
    private static function token(): \stdClass {
        global $DB;
        $service = $DB->get_record('external_services', ['shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE], '*', MUST_EXIST);
        $DB->set_field('external_services', 'enabled', 1, ['id' => $service->id]);
        $token = \core_external\util::generate_token_for_current_user($service);

        return $DB->get_record('external_tokens', ['token' => $token->token], '*', MUST_EXIST);
    }
}
