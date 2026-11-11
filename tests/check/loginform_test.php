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

use core\check\result;

/**
 * The login form in the app against the one on the web.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(loginform::class)]
final class loginform_test extends \advanced_testcase {
    /**
     * With the core setting on, the app shows the form, whatever the theme.
     */
    public function test_form_on(): void {
        $this->resetAfterTest();
        set_config('showloginform', 1);
        set_config('theme', 'boost_union');

        $this->assertSame(result::OK, (new loginform())->get_result()->get_status());
    }

    /**
     * With the core setting off and a theme that follows it, the web and the app both hide the form.
     */
    public function test_form_off_with_a_theme_that_follows_it(): void {
        $this->resetAfterTest();
        set_config('showloginform', 0);
        set_config('theme', 'boost');

        $result = (new loginform())->get_result();
        $this->assertSame(result::OK, $result->get_status());
        $this->assertSame(get_string('check:loginform_hidden', 'local_oksigeniaaty'), $result->get_summary());
    }

    /**
     * Boost Union shows the form on its own switch: with the core setting off, the app would hide what the web shows.
     */
    public function test_form_off_with_boost_union(): void {
        $this->resetAfterTest();
        set_config('showloginform', 0);
        set_config('theme', 'boost_union');

        // Its "Local login" is on unless set to "no", also before anyone saves it.
        unset_config('loginlocalloginenable', 'theme_boost_union');
        $this->assertSame('boost_union', loginform::form_theme('boost_union'));
        $result = (new loginform())->get_result();
        $this->assertSame(result::WARNING, $result->get_status());
        $this->assertStringContainsString(get_string('showloginform', 'core_auth'), $result->get_summary());

        set_config('loginlocalloginenable', 'yes', 'theme_boost_union');
        $this->assertSame(result::WARNING, (new loginform())->get_result()->get_status());

        // Hidden in the theme too: the web and the app agree.
        set_config('loginlocalloginenable', 'no', 'theme_boost_union');
        $this->assertNull(loginform::form_theme('boost_union'));
        $this->assertSame(result::OK, (new loginform())->get_result()->get_status());
    }

    /**
     * Moodle lists the check in the site status report.
     */
    public function test_listed_in_status_report(): void {
        $checks = array_filter(
            \core\check\manager::get_checks('status'),
            fn($check) => $check instanceof loginform
        );
        $this->assertCount(1, $checks);
    }
}
