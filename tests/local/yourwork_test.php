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
                ['component' => 'local_library', 'title' => 'Library · Library of the school',
                    'url' => 'https://example.org/library/', 'alertcount' => 0, 'actions' => []],
                ['component' => 'local_screens', 'title' => 'Screens', 'url' => 'https://example.org/screens/',
                    'status' => 'One screen without signal · 12 screens on · 3 groups · next notice at 8:00',
                    'alertcount' => 1, 'urgent' => true, 'actions' => []],
            ],
            'alerts' => [
                ['text' => '4 pupils without a menu', 'url' => 'https://example.org/canteen/missing', 'urgent' => true,
                    'due' => $now + 600, 'service' => 'Canteen', 'serviceicon' => 'https://example.org/canteen.svg'],
                ['text' => 'Ana is waiting for an answer', 'url' => '/local/inbox/conversation.php?id=7', 'urgent' => false,
                    'service' => 'Families', 'serviceicon' => ''],
                ['text' => 'Notes to review.', 'url' => 'https://example.org/canteen/notes', 'urgent' => false,
                    'due' => $now + 3 * DAYSECS, 'service' => 'Canteen', 'serviceicon' => 'https://example.org/canteen.svg'],
                ['text' => 'A line that leads nowhere', 'url' => 'ftp://example.org/', 'urgent' => false],
                ['text' => 'One more', 'url' => 'https://example.org/canteen/more', 'urgent' => false, 'service' => 'Canteen'],
                ['text' => 'A screen gives no sign.', 'url' => 'https://example.org/screens/', 'urgent' => true,
                    'service' => 'Screens'],
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

        $this->assertNull(yourwork::for_user((int) $user->id));
    }

    /**
     * The doors and the alerts as the app takes them: the doors with something urgent first, then with something waiting,
     * then the rest; every text inert, every address whole and of the web; on the home every urgent alert and the others
     * up to three, with «See all» for the rest.
     */
    public function test_shape(): void {
        global $CFG;
        $this->resetAfterTest();
        $work = yourwork::shape($this->board(time()));

        $titles = ['Canteen', 'Screens', 'Families ｛｛ x ｝｝', 'Library of the school'];
        $this->assertSame($titles, array_column($work['doors'], 'title'));
        $this->assertSame(['local_canteen', 'local_screens', 'local_inbox', 'local_library'], $work['components']);
        [$canteen, $screens, $inbox, $library] = $work['doors'];
        $this->assertSame('One screen without signal · 12 screens on', $screens['statusshort']);
        $this->assertSame('312 menus today', $canteen['statusshort']);
        $this->assertSame($CFG->wwwroot . '/local/inbox/', $inbox['url']);
        $this->assertSame('', $inbox['icon']);
        $this->assertTrue($canteen['urgent']);
        // A dot, not the count of lines: «4 pupils without a menu» is one line and four things.
        $this->assertTrue($canteen['hasalerts']);
        $this->assertSame('Something urgent to sort out', $canteen['alertlabel']);
        $this->assertSame('Something to sort out', $inbox['alertlabel']);
        $menu = ['label' => 'Menu of the day', 'url' => $CFG->wwwroot . '/local/canteen/today.php', 'browser' => false];
        $this->assertSame([$menu], $canteen['actions']);
        $this->assertFalse($library['hasalerts']);

        $texts = ['4 pupils without a menu', 'Ana is waiting for an answer', 'Notes to review', 'One more',
            'A screen gives no sign'];
        $this->assertSame($texts, array_column($work['alerts'], 'text'));
        $shown = ['4 pupils without a menu', 'A screen gives no sign', 'Ana is waiting for an answer'];
        $this->assertSame($shown, array_column($work['shown'], 'text'));
        $this->assertTrue($work['hasmore']);
        $this->assertSame(5, $work['total']);
        $this->assertSame('2 more to sort out', $work['morelabel']);
        $this->assertFalse($work['moreurgent']);
        $this->assertSame('', $work['beyondlabel']);
        [$missing, $ana] = $work['alerts'];
        $this->assertTrue($missing['urgent']);
        $this->assertSame('Canteen', $missing['service']);
        $this->assertSame('https://example.org/canteen.svg', $missing['icon']);
        // When it is due only orders the list, as in the block.
        $this->assertArrayNotHasKey('due', $missing);
        $this->assertSame($CFG->wwwroot . '/local/inbox/conversation.php?id=7', $ana['url']);
    }

    /**
     * A name that says twice what its logo says keeps its second part; a line of state on the home, whole parts only.
     */
    public function test_name_and_short(): void {
        $this->assertSame('Library of the school', yourwork::name('Library · Library of the school'));
        $this->assertSame('Canteen · North', yourwork::name('Canteen · North'));
        $this->assertSame('Library', yourwork::name('Library'));
        $long = 'A first part that is longer than two lines of a half door';
        $this->assertSame($long, yourwork::short($long . ' · and more'));
        $this->assertSame('', yourwork::short(''));
    }

    /**
     * A hard limit: on the home, six urgent at most and a line with how many more, marked when urgent ones are among them;
     * in «See all», fifty, and a line to the rest on the web.
     */
    public function test_limits(): void {
        global $CFG;
        $this->resetAfterTest();
        $board = $this->board(time());
        $alert = fn(int $i, bool $urgent): array => ['text' => ($urgent ? 'Urgent ' : 'Waiting ') . $i,
            'url' => 'https://example.org/canteen/' . $i, 'urgent' => $urgent, 'service' => 'Canteen'];
        $board['alerts'] = array_merge(
            array_map(fn(int $i): array => $alert($i, true), range(1, 8)),
            array_map(fn(int $i): array => $alert($i, false), range(1, 60))
        );
        $work = yourwork::shape($board);

        $this->assertSame(68, $work['total']);
        $this->assertSame(array_map(fn(int $i): string => 'Urgent ' . $i, range(1, 6)), array_column($work['shown'], 'text'));
        $this->assertSame('62 more to sort out', $work['morelabel']);
        $this->assertTrue($work['moreurgent']);
        $this->assertCount(yourwork::ALL_ALERTS, $work['alerts']);
        $this->assertSame('18 more to sort out, on the web', $work['beyondlabel']);
        $this->assertSame($CFG->wwwroot . '/my/', $work['beyondurl']);

        // Two urgent: both, and one more to make three.
        $board['alerts'] = array_merge([$alert(1, true), $alert(2, true)], [$alert(1, false), $alert(2, false)]);
        $work = yourwork::shape($board);
        $this->assertSame(['Urgent 1', 'Urgent 2', 'Waiting 1'], array_column($work['shown'], 'text'));
        $this->assertSame('1 more to sort out', $work['morelabel']);
        $this->assertFalse($work['moreurgent']);
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

        $this->assertFalse(yourwork::shape($board)['hasmore']);
        $this->assertSame('', yourwork::shape($board)['morelabel']);
        $this->assertNull(yourwork::shape(['doors' => [], 'alerts' => $board['alerts']]));
        $this->assertNull(yourwork::shape(['doors' => [['title' => 'No address']]]));
    }

    /**
     * What the app receives: the alerts that open in its own window, the doors, and «See all» opening the whole panel.
     */
    public function test_templates(): void {
        global $OUTPUT;
        $this->resetAfterTest();
        $now = time();
        $work = yourwork::shape($this->board($now));

        $home = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_home', [
            'work' => ['heading' => 'Your work', 'seeall' => 'See all', 'short' => true] + $work,
        ]);
        $this->assertStringContainsString('method="mobile_work"', $home);
        $this->assertStringContainsString('class="aty-event aty-aviso aty-urgent"', $home);
        $this->assertStringContainsString('inApp="true"', $home);
        $this->assertStringContainsString('class="aty-puerta aty-urgent"', $home);
        // The door's dot says what it means; an urgent line says so to screen readers, not only with its colour.
        $dot = '<span class="aty-puerta-punto" role="img" aria-label="Something urgent to sort out">';
        $this->assertStringContainsString($dot, $home);
        $this->assertStringNotContainsString('aty-puerta-avisos', $home);
        $this->assertStringContainsString('<span class="sr-only">Urgent: </span>4 pupils without a menu', $home);
        $this->assertStringNotContainsString('<span class="sr-only">Urgent: </span>Ana', $home);
        // The rest, behind a line that says how many and opens «See all».
        $this->assertStringContainsString('2 more to sort out', $home);
        $this->assertSame(2, substr_count($home, 'method="mobile_work"'));
        $this->assertStringNotContainsString('One more', $home);
        $this->assertStringContainsString('12 screens on', $home);
        $this->assertStringNotContainsString('3 groups', $home);

        $page = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_work', [
            'hasdoors' => true, 'hasalerts' => true, 'alerts' => $work['alerts'], 'doors' => $work['doors'],
            'alertsheading' => 'To sort out', 'servicesheading' => 'Your services',
        ]);
        $this->assertStringContainsString('One more', $page);
        $this->assertStringContainsString('3 groups', $page);
        $this->assertStringContainsString('Menu of the day', $page);
        // Staff pages of the web, signed in, never through a plugin's handler of links: Ayvurenda's took «Catalogue» to the
        // reader's page of the library (seen on the Pixel, 20 November 2026).
        $this->assertStringContainsString('capture="false" inApp="true"', $home);
        $this->assertStringContainsString('capture="false" inApp="true"', $page);
        $this->assertStringNotContainsString('capture="true"', $page);

        $this->assertStringNotContainsString('on the web', $page);
        $beyond = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_work', [
            'hasdoors' => true, 'hasalerts' => true, 'alerts' => $work['alerts'], 'doors' => [],
            'beyondlabel' => '18 more to sort out, on the web', 'beyondurl' => 'https://example.org/my/',
        ]);
        $this->assertStringContainsString('href="https://example.org/my/" capture="false" inApp="true"', $beyond);

        $none = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_work', ['hasdoors' => false, 'none' => 'Nothing.']);
        $this->assertStringContainsString('Nothing.', $none);
    }
}
