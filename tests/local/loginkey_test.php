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
 * The app token a sign-in key gives.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(loginkey::class)]
final class loginkey_test extends \advanced_testcase {
    /**
     * The mobile service.
     *
     * @return \stdClass
     */
    private function service(): \stdClass {
        global $DB;

        $DB->set_field('external_services', 'enabled', 1, ['shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE]);
        return $DB->get_record('external_services', ['shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE], '*', MUST_EXIST);
    }

    /**
     * A user signed in by key gets the private token with the token, the first time and when the token is reused, so the
     * app can open the site's pages signed in.
     */
    public function test_app_token_brings_the_private_token(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->enablewebservices = 1;
        $CFG->enablemobilewebservice = 1;
        $this->assertTrue(is_https());

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $service = $this->service();

        $first = loginkey::app_token($user, $service);
        $stored = $DB->get_record('external_tokens', ['token' => $first['token']], '*', MUST_EXIST);
        $this->assertNotEmpty($first['privatetoken']);
        $this->assertSame($stored->privatetoken, $first['privatetoken']);

        $again = loginkey::app_token($user, $service);
        $this->assertSame($first['token'], $again['token']);
        $this->assertSame($stored->privatetoken, $again['privatetoken']);
    }

    /**
     * A site admin gets no private token, as with login/token.php.
     */
    public function test_app_token_no_private_token_for_admins(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enablewebservices = 1;
        $CFG->enablemobilewebservice = 1;

        $this->setAdminUser();
        $result = loginkey::app_token(get_admin(), $this->service());
        $this->assertNotEmpty($result['token']);
        $this->assertNull($result['privatetoken']);
    }
}
