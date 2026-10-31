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

namespace local_oksigeniaaty;

/**
 * Tests for the hook callbacks.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_oksigeniaaty\hook_callbacks
 */
#[\PHPUnit\Framework\Attributes\CoversClass(hook_callbacks::class)]
final class hook_callbacks_test extends \advanced_testcase {
    /**
     * A signed-in user gets "Open in the Aty app" in the user menu; a guest and an administrator do not (administrators
     * never sign in to the app without a password).
     */
    public function test_user_menu(): void {
        $this->resetAfterTest();

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertCount(1, self::items());

        $this->setGuestUser();
        $this->assertCount(0, self::items());

        $this->setAdminUser();
        $this->assertCount(0, self::items());
    }

    /**
     * The "open this page in the app" bar: only when the site turns it on, on Android, outside the app and until hidden,
     * and it opens that same page in the site's app, through its scheme and package.
     */
    public function test_openinapp_bar(): void {
        global $CFG, $PAGE;
        $this->resetAfterTest();

        $android = 'Mozilla/5.0 (Linux; Android 16; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) '
            . 'Chrome/141.0 Mobile Safari/537.36';
        $CFG->enablemobilewebservice = 1;
        $PAGE->set_url('/course/view.php', ['id' => 2]);
        \core_useragent::instance(true, $android);

        // Off by default.
        $this->assertSame('', hook_callbacks::openinapp_bar());

        // On, it opens the site's app: Moodle's own by default, Aty where the site points its mobile settings to it.
        set_config('openinappbar', 1, 'local_oksigeniaaty');
        $page = 'intent://link=' . $CFG->wwwroot . '/course/view.php?id=2#Intent;';
        $this->assertStringContainsString($page . 'scheme=moodlemobile;package=com.moodle.moodlemobile;end',
            hook_callbacks::openinapp_bar());
        set_config('forcedurlscheme', 'oksigeniaaty', 'tool_mobile');
        set_config('androidappid', 'com.oksigenia.aty', 'tool_mobile');
        $html = hook_callbacks::openinapp_bar();
        $this->assertStringContainsString($page . 'scheme=oksigeniaaty;package=com.oksigenia.aty;end', $html);
        $this->assertStringContainsString('/local/oksigeniaaty/bar.php', $html);

        // The login page opens the site's home in the app.
        $PAGE = new \moodle_page();
        $PAGE->set_url('/login/index.php');
        $this->assertStringContainsString('intent://link=' . $CFG->wwwroot . '/#Intent;', hook_callbacks::openinapp_bar());

        // Not on iPhone, nor inside the app (its in-app browser says MoodleMobile).
        \core_useragent::instance(true, 'Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 '
            . 'Mobile/15E148');
        $this->assertSame('', hook_callbacks::openinapp_bar());
        \core_useragent::instance(true, $android . ' MoodleMobile 5.2.1 (52102)');
        $this->assertSame('', hook_callbacks::openinapp_bar());

        // Not once the person has hidden it.
        \core_useragent::instance(true, $android);
        $_COOKIE[hook_callbacks::BAR_COOKIE] = '1';
        $this->assertSame('', hook_callbacks::openinapp_bar());
        unset($_COOKIE[hook_callbacks::BAR_COOKIE]);
        \core_useragent::instance(true);
    }

    /**
     * The menu items the callback adds.
     *
     * @return array
     */
    private static function items(): array {
        $hook = new \core_user\hook\extend_user_menu();
        hook_callbacks::extend_user_menu($hook);

        return method_exists($hook, 'get_menu_items') ? $hook->get_menu_items() : $hook->get_navitems();
    }
}
