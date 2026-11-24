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
 * Tests of since when is what the app shows.
 *
 * @package    local_oksigeniaaty
 * @category   test
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_oksigeniaaty\local\freshness
 */
final class freshness_test extends \advanced_testcase {
    /**
     * A page is recent for half an hour, and never past the end of the user's own day.
     */
    public function test_due(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user(['timezone' => 'Atlantic/Canary']));
        $at = fn(string $time): int => (new \DateTime($time, new \DateTimeZone('Atlantic/Canary')))->getTimestamp();

        $this->assertSame($at('2026-11-24 09:42'), freshness::due($at('2026-11-24 09:12')));
        $this->assertSame($at('2026-11-25 00:00'), freshness::due($at('2026-11-24 23:50')));
        // The day is the user's, not the server's: there it is already the next one.
        $this->assertSame($at('2026-11-25 00:00'), freshness::due($at('2026-11-24 23:59')));
        // The night the clocks go back, a day of twenty-five hours.
        $this->assertSame($at('2026-10-26 00:00'), freshness::due($at('2026-10-25 23:50')));
    }

    /**
     * The line says the day and the time, in the user's own zone, and never «today».
     */
    public function test_line(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user(['timezone' => 'Atlantic/Canary']));
        $now = (new \DateTime('2026-11-24 09:12', new \DateTimeZone('Atlantic/Canary')))->getTimestamp();

        $line = freshness::line($now);
        $this->assertSame($now + 1800, $line['due']);
        $this->assertStringContainsString('Tuesday 24 November, 09:12', $line['text']);
    }

    /**
     * The three pages that may say «nothing» carry the line, hidden until it is due, and the moment for their JavaScript.
     */
    public function test_pages(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        foreach (['mobile_home', 'mobile_work'] as $method) {
            $before = time();
            $page = \local_oksigeniaaty\output\mobile::$method([]);
            $due = $page['otherdata']['atydue'];
            $this->assertGreaterThanOrEqual(freshness::due($before), $due);
            $this->assertStringContainsString(
                '[hidden]="!(CoreTime?.timestamp?.() >= ' . $due . ')"',
                $page['templates'][0]['html']
            );
            $this->assertSame(freshness::JS, $page['javascript']);
        }
        $this->assertStringContainsString(
            '[class.aty-vieja]="CoreTime?.timestamp?.() >= ',
            \local_oksigeniaaty\output\mobile::mobile_home([])['templates'][0]['html']
        );
    }
}
