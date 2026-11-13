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

namespace local_oksigeniaaty\local;

/**
 * Tests for the app's PIN: the count of wrong tries, the lock, its trace in the logs, and that it goes with the account.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_oksigeniaaty\local\pin
 */
#[\PHPUnit\Framework\Attributes\CoversClass(pin::class)]
final class pin_test extends \advanced_testcase {
    /**
     * Wrong tries are counted and leave a trace; at the limit the PIN locks, and a locked PIN fails even when right.
     * The right one clears the count.
     */
    public function test_wrong_tries_lock(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        pin::set((int) $user->id, '4826');
        $this->assertTrue(pin::has_pin((int) $user->id));

        $sink = $this->redirectEvents();
        $this->assertFalse(pin::verify((int) $user->id, '0000'));
        $this->assertTrue(pin::verify((int) $user->id, '4826'));
        for ($i = 0; $i < pin::MAXFAILS; $i++) {
            $this->assertFalse(pin::verify((int) $user->id, '1111'));
        }
        $this->assertTrue(pin::is_locked((int) $user->id));
        $this->assertFalse(pin::verify((int) $user->id, '4826'));

        $events = $sink->get_events();
        $failed = array_filter($events, fn($event) => $event instanceof \local_oksigeniaaty\event\pin_failed);
        $locked = array_filter($events, fn($event) => $event instanceof \local_oksigeniaaty\event\pin_locked);
        $this->assertCount(pin::MAXFAILS + 1, $failed);
        $this->assertCount(1, $locked);
        $this->assertSame((int) $user->id, (int) reset($locked)->relateduserid);
    }

    /**
     * A deleted account takes its PIN with it.
     */
    public function test_deleted_account_takes_its_pin(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        pin::set((int) $user->id, '4826');
        delete_user($user);
        $this->assertFalse($DB->record_exists('local_oksigeniaaty_pin', ['userid' => $user->id]));
    }
}
