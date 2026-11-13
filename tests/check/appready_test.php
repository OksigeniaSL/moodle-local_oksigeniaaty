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

namespace local_oksigeniaaty\check;

/**
 * Tests for the status check of the Aty app.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_oksigeniaaty\check\appready
 */
#[\PHPUnit\Framework\Attributes\CoversClass(appready::class)]
final class appready_test extends \advanced_testcase {
    /**
     * A site set up for Aty has nothing missing; each piece that goes is named, and the quiet ones are warnings.
     */
    public function test_problems(): void {
        global $CFG;
        $this->resetAfterTest();

        require_once($CFG->libdir . '/adminlib.php');
        set_config('enablewebservices', 1);
        // As Moodle's page turns it on: the service, REST and the right to use it.
        (new \admin_setting_enablemobileservice('enablemobilewebservice', '', '', 0))->write_setting(1);
        set_config('forcedurlscheme', appready::SCHEME, 'tool_mobile');
        set_config('androidappid', appready::APPID, 'tool_mobile');
        set_config('typeoflogin', 1, 'tool_mobile');
        set_config('autologinmintimebetweenreq', 10, 'tool_mobile');
        set_config('airnotifierurl', 'https://push.example.com');
        set_config('airnotifierappname', 'demo.example.com');
        set_config('airnotifieraccesskey', 'not-a-real-key');
        set_config('airnotifiermobileappname', appready::APPID);
        set_config('mobilecssurl', $CFG->wwwroot . '/local/oksigeniaaty/styles_app.php?v=1');

        $this->assertSame([[], []], appready::problems(true));

        // Off the list, and the list unread.
        $this->assertSame(['notlisted'], appready::problems(false)[0]);
        $this->assertSame(['listunknown'], appready::problems(null)[1]);

        // The quiet ones.
        set_config('autologinmintimebetweenreq', 360, 'tool_mobile');
        set_config('mobilecssurl', '');
        $this->assertSame([[], ['autologin', 'css']], appready::problems(true));

        // What stops the app.
        set_config('forcedurlscheme', 'moodlemobile', 'tool_mobile');
        set_config('typeoflogin', 2, 'tool_mobile');
        set_config('airnotifiermobileappname', 'com.moodle.moodlemobile');
        $this->assertSame(['scheme', 'typeoflogin', 'pushapp'], appready::problems(true)[0]);
        set_config('airnotifieraccesskey', '');
        $this->assertContains('push', appready::problems(true)[0]);

        // The switch on, but REST gone: the app cannot sign in, and it says so.
        $this->assertTrue(appready::mobile_service_works());
        set_config('webserviceprotocols', '');
        $this->assertContains('mobileservice', appready::problems(true)[0]);
        set_config('enablemobilewebservice', 0);
        $this->assertContains('mobileservice', appready::problems(true)[0]);
    }

    /**
     * The site's status checks include it.
     */
    public function test_registered(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

        $checks = \local_oksigeniaaty_status_checks();
        $this->assertInstanceOf(appready::class, $checks[0]);
    }
}
