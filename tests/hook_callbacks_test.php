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
     * A signed-in user gets "Open in the Aty app" in the user menu; a guest does not.
     */
    public function test_user_menu(): void {
        $this->resetAfterTest();

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertCount(1, self::items());

        $this->setGuestUser();
        $this->assertCount(0, self::items());
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
