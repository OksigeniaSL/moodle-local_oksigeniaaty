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
 * Tests for «Your work» on the app's home.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_oksigeniaaty\local\yourwork
 */
#[\PHPUnit\Framework\Attributes\CoversClass(yourwork::class)]
final class yourwork_test extends \advanced_testcase {
    /**
     * A board as the block builds it: doors in the site's order, the alerts the urgent first.
     *
     * @param int $now The time it is.
     * @return array
     */
    private function board(int $now): array {
        return [
            'doors' => [
                ['component' => 'local_canteen', 'title' => 'Canteen', 'url' => 'https://example.org/canteen/',
                    'status' => '312 menus today', 'icon' => 'https://example.org/canteen.svg', 'alertcount' => 2,
                    'urgent' => true, 'actions' => [['label' => 'Menu of the day', 'url' => '/local/canteen/today.php']]],
                ['component' => 'local_inbox', 'title' => 'Families {{ x }}', 'url' => '/local/inbox/',
                    'status' => '', 'icon' => 'javascript:alert(1)', 'alertcount' => 1, 'urgent' => false, 'actions' => []],
                ['component' => 'local_broken', 'title' => 'No way in', 'url' => 'javascript:alert(1)'],
                ['component' => 'local_library', 'title' => 'Library', 'url' => 'https://example.org/library/',
                    'alertcount' => 0, 'actions' => []],
            ],
            'alerts' => [
                ['text' => '4 pupils without a menu', 'url' => 'https://example.org/canteen/missing', 'urgent' => true,
                    'due' => $now + 600, 'service' => 'Canteen', 'serviceicon' => 'https://example.org/canteen.svg'],
                ['text' => 'Ana is waiting for an answer', 'url' => '/local/inbox/conversation.php?id=7', 'urgent' => false,
                    'service' => 'Families', 'serviceicon' => ''],
                ['text' => 'Notes to review', 'url' => 'https://example.org/canteen/notes', 'urgent' => false,
                    'due' => $now + 3 * DAYSECS, 'service' => 'Canteen', 'serviceicon' => 'https://example.org/canteen.svg'],
                ['text' => 'A line that leads nowhere', 'url' => 'ftp://example.org/', 'urgent' => false],
                ['text' => 'One more', 'url' => 'https://example.org/canteen/more', 'urgent' => false, 'service' => 'Canteen'],
            ],
        ];
    }

    /**
     * Without the block, no «Your work»: it is the site's choice of having the panel.
     */
    public function test_without_the_block(): void {
        $this->resetAfterTest();
        if (class_exists('\block_oksigeniatembiapo\local\board')) {
            $this->markTestSkipped('The block is installed here.');
        }
        $user = $this->getDataGenerator()->create_user();

        $this->assertNull(yourwork::for_user((int) $user->id, time()));
    }

    /**
     * The doors and the alerts as the app takes them: in the board's order, every text inert, every address whole and of
     * the web, and the home's three first alerts with «See all» for the rest.
     */
    public function test_shape(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setTimezone('Europe/Madrid');
        $now = make_timestamp(2026, 11, 20, 9, 0, 0);

        $work = yourwork::shape($this->board($now), $now);

        $this->assertSame(['Canteen', 'Families ｛｛ x ｝｝', 'Library'], array_column($work['doors'], 'title'));
        $this->assertSame(['local_canteen', 'local_inbox', 'local_library'], $work['components']);
        [$canteen, $inbox, $library] = $work['doors'];
        $this->assertSame($CFG->wwwroot . '/local/inbox/', $inbox['url']);
        $this->assertSame('', $inbox['icon']);
        $this->assertTrue($canteen['urgent']);
        $this->assertSame(2, $canteen['alertcount']);
        $this->assertSame('2 to sort out', $canteen['alertlabel']);
        $this->assertSame([['label' => 'Menu of the day', 'url' => $CFG->wwwroot . '/local/canteen/today.php', 'browser' => false]],
            $canteen['actions']);
        $this->assertFalse($library['hasalerts']);

        $this->assertSame(['4 pupils without a menu', 'Ana is waiting for an answer', 'Notes to review', 'One more'],
            array_column($work['alerts'], 'text'));
        $this->assertCount(3, $work['shown']);
        $this->assertTrue($work['hasmore']);
        [$missing, $ana, $notes] = $work['alerts'];
        $this->assertTrue($missing['duetoday']);
        $this->assertSame(($now + 600) * 1000, $missing['duetimestamp']);
        $this->assertFalse($ana['hasdue']);
        $this->assertSame($CFG->wwwroot . '/local/inbox/conversation.php?id=7', $ana['url']);
        $this->assertTrue($notes['hasdue']);
        $this->assertFalse($notes['duetoday']);
        $this->assertSame('23 Nov', $notes['duetext']);
    }

    /**
     * «See all» only when the panel has more than the home: here, two alerts and no shortcuts. Without doors, nothing.
     */
    public function test_see_all_and_nothing(): void {
        $this->resetAfterTest();
        $now = time();
        $board = $this->board($now);
        $board['alerts'] = array_slice($board['alerts'], 0, 2);
        $board['doors'][0]['actions'] = [];

        $this->assertFalse(yourwork::shape($board, $now)['hasmore']);
        $this->assertNull(yourwork::shape(['doors' => [], 'alerts' => $board['alerts']], $now));
        $this->assertNull(yourwork::shape(['doors' => [['title' => 'No address']]], $now));
    }

    /**
     * What the app receives: the alerts that open in its own window, the doors, and «See all» opening the whole panel.
     */
    public function test_templates(): void {
        global $OUTPUT;
        $this->resetAfterTest();
        $now = time();
        $work = yourwork::shape($this->board($now), $now);

        $home = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_home', [
            'work' => ['heading' => 'Your work', 'seeall' => 'See all'] + $work,
        ]);
        $this->assertStringContainsString('method="mobile_work"', $home);
        $this->assertStringContainsString('class="aty-event aty-aviso aty-urgent"', $home);
        $this->assertStringContainsString('inApp="true"', $home);
        $this->assertStringContainsString('class="aty-puerta aty-urgent"', $home);
        $this->assertStringNotContainsString('One more', $home);

        $page = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_work', [
            'hasdoors' => true, 'hasalerts' => true, 'alerts' => $work['alerts'], 'doors' => $work['doors'],
            'alertsheading' => 'To sort out', 'servicesheading' => 'Your services',
        ]);
        $this->assertStringContainsString('One more', $page);
        $this->assertStringContainsString('Menu of the day', $page);

        $none = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_work', ['hasdoors' => false, 'none' => 'Nothing.']);
        $this->assertStringContainsString('Nothing.', $none);
    }
}
