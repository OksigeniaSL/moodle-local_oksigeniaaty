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

use local_oksigeniaaty\local\pin;

/**
 * Tests for setting the app's PIN: a first one with the token alone, a change only with the one there is.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_oksigeniaaty\external\set_pin
 */
#[\PHPUnit\Framework\Attributes\CoversClass(set_pin::class)]
final class set_pin_test extends \advanced_testcase {
    /**
     * The first PIN needs nothing else; changing it needs the current one, so whoever holds the token alone cannot put
     * theirs and renew with it.
     */
    public function test_change_needs_the_current_pin(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertTrue(set_pin::execute('4826')['status']);
        $this->assertTrue(pin::verify((int) $user->id, '4826'));

        try {
            set_pin::execute('9999');
            $this->fail('A PIN must not change without the current one.');
        } catch (\moodle_exception $e) {
            $this->assertSame('pinwrong', $e->errorcode);
        }
        try {
            set_pin::execute('9999', '1234');
            $this->fail('A PIN must not change with a wrong current one.');
        } catch (\moodle_exception $e) {
            $this->assertSame('pinwrong', $e->errorcode);
        }
        $this->assertTrue(pin::verify((int) $user->id, '4826'));

        $this->assertTrue(set_pin::execute('9999', '4826')['status']);
        $this->assertTrue(pin::verify((int) $user->id, '9999'));
    }

    /**
     * Neither the function nor the renewal can be called from a web page's JavaScript.
     */
    public function test_not_for_ajax(): void {
        foreach (['local_oksigeniaaty_set_pin', 'local_oksigeniaaty_renew'] as $name) {
            $this->assertFalse((bool) \core_external\external_api::external_function_info($name)->allowed_from_ajax);
        }
    }
}
