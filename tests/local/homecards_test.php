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
 * Tests for what other plugins add to the app's home.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_oksigeniaaty\local\homecards
 * @covers \local_oksigeniaaty\local\template
 */
#[\PHPUnit\Framework\Attributes\CoversClass(homecards::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(template::class)]
final class homecards_test extends \advanced_testcase {
    /**
     * Nothing that can be shown, no card: no heading for its rows, no way to open them, an address that is not one.
     */
    public function test_clean_refuses(): void {
        $this->assertNull(homecards::clean(null, 'local_x'));
        $this->assertNull(homecards::clean([], 'local_x'));
        $this->assertNull(homecards::clean(['heading' => 'Books', 'items' => [['title' => 'No way to open it']]], 'local_x'));
        $this->assertNull(homecards::clean(['items' => [['title' => 'No heading', 'url' => 'https://example.org/']]], 'local_x'));
        $this->assertNull(homecards::clean([
            'heading' => 'Books',
            'items' => [['title' => 'A script', 'url' => 'javascript:alert(1)']],
        ], 'local_x'));
        $this->assertNull(homecards::clean(['tile' => ['label' => 'Bad method', 'method' => 'x" onclick="y']], 'local_x'));
    }

    /**
     * A row with a date opens a page of the plugin; another, with a count, opens an address. Only so many rows.
     */
    public function test_clean_rows(): void {
        $this->resetAfterTest();

        $items = [
            ['title' => 'The sleeping lighthouse', 'detail' => 'Central', 'date' => 1800000000, 'urgent' => true,
                'method' => 'mobile_record_view', 'args' => ['recordid' => 3, 'library' => 'demo-1']],
            ['title' => 'Waiting', 'count' => '2', 'countlabel' => 'books', 'url' => 'https://example.org/holds'],
        ];
        for ($i = 0; $i < 8; $i++) {
            $items[] = ['title' => 'Row ' . $i, 'url' => 'https://example.org/' . $i];
        }
        $card = homecards::clean(['heading' => 'Your books', 'items' => $items], 'local_x');

        $this->assertTrue($card['hasitems']);
        $this->assertCount(5, $card['items']);
        $this->assertNull($card['tile']);

        $page = $card['items'][0];
        $this->assertTrue($page['ispage']);
        $this->assertTrue($page['urgent']);
        $this->assertTrue($page['hasdate']);
        $this->assertSame(1800000000000, $page['timestamp']);
        $this->assertSame('local_x', $page['component']);
        $this->assertSame('mobile_record_view', $page['method']);
        $this->assertSame('The sleeping lighthouse', $page['pagetitle']);
        $this->assertSame(['recordid' => 3, 'library' => 'demo-1'], json_decode($page['argsjson'], true));

        $address = $card['items'][1];
        $this->assertFalse($address['ispage']);
        $this->assertFalse($address['hasdate']);
        $this->assertTrue($address['hascount']);
        $this->assertSame('https://example.org/holds', $address['url']);
    }

    /**
     * A tile alone is a card too, and an icon the app does not ship falls back to a neutral one.
     */
    public function test_clean_tile(): void {
        $card = homecards::clean([
            'tile' => ['label' => 'Library', 'icon' => 'book', 'method' => 'mobile_library_view'],
        ], 'local_x');

        $this->assertFalse($card['hasitems']);
        $this->assertSame('Library', $card['tile']['label']);
        $this->assertSame('fas-circle-chevron-right', $card['tile']['icon']);
        $this->assertSame('{}', $card['tile']['argsjson']);
        $this->assertSame('Library', $card['tile']['pagetitle']);
    }

    /**
     * A title with braces cannot run in the app, and the page's arguments stay an object the template can bind.
     */
    public function test_braces_are_inert(): void {
        $card = homecards::clean([
            'heading' => '{{ heading }}',
            'items' => [['title' => '{{ title }}', 'detail' => '{{ detail }}', 'method' => 'mobile_record_view',
                'args' => ['q' => '{{ q }}']]],
        ], 'local_x');

        $text = json_encode($card, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('{{', $text);
        $this->assertSame('｛｛ title ｝｝', $card['items'][0]['title']);
        $this->assertSame(['q' => '｛｛ q ｝｝'], json_decode($card['items'][0]['argsjson'], true));
        $this->assertSame('{"a":{"b":"｛"}}', json_encode(template::inert(['a' => ['b' => '{']]), JSON_UNESCAPED_UNICODE));
        $this->assertSame(5, template::inert(5));
    }

    /**
     * An at sign cannot start a block in a text, and stays where it is in an address and in a page's arguments.
     */
    public function test_at_signs(): void {
        $this->assertSame('＠let a = 1;', template::inert('@let a = 1;'));
        $data = template::inert([
            'name' => '@if (a)',
            'viewurl' => 'https://example.org/@user',
            'deep' => ['logourl' => 'https://example.org/@a{'],
        ]);
        $this->assertSame('＠if (a)', $data['name']);
        $this->assertSame('https://example.org/@user', $data['viewurl']);
        $this->assertSame('https://example.org/@a｛', $data['deep']['logourl']);

        $card = homecards::clean([
            'heading' => '@let h = 1;',
            'items' => [
                [
                    'title' => '@let t = 1;',
                    'detail' => '@if (d)',
                    'method' => 'mobile_record_view',
                    'args' => ['mail' => 'a@example.org'],
                ],
                ['title' => 'Address', 'url' => 'https://example.org/@user'],
            ],
            'tile' => ['label' => '@let l = 1;', 'url' => 'https://example.org/@tile'],
        ], 'local_x');

        $this->assertSame('＠let h = 1;', $card['heading']);
        $this->assertSame('＠let t = 1;', $card['items'][0]['title']);
        $this->assertSame('＠if (d)', $card['items'][0]['detail']);
        $this->assertSame('＠let t = 1;', $card['items'][0]['pagetitle']);
        $this->assertSame(['mail' => 'a@example.org'], json_decode($card['items'][0]['argsjson'], true));
        $this->assertSame('https://example.org/@user', $card['items'][1]['url']);
        $this->assertSame('＠let l = 1;', $card['tile']['label']);
        $this->assertSame('https://example.org/@tile', $card['tile']['url']);
    }

    /**
     * What the app receives: the card's page, with its arguments as an attribute that survives the quotes.
     */
    public function test_template(): void {
        global $OUTPUT;
        $this->resetAfterTest();

        $card = homecards::clean([
            'heading' => 'Your books',
            'items' => [['title' => 'A book', 'date' => 1800000000, 'method' => 'mobile_record_view', 'args' => ['id' => 3]]],
            'tile' => ['label' => 'Library', 'icon' => 'fas-book-open', 'method' => 'mobile_library_view'],
        ], 'local_x');
        $html = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_home', [
            'heading' => 'Home', 'cards' => [$card], 'hasshortcuts' => true, 'shortcuts' => [$card['tile']],
        ]);

        $this->assertStringContainsString('core-site-plugins-new-content component="local_x" method="mobile_record_view"', $html);
        $this->assertStringContainsString('[args]="{&quot;id&quot;:3}"', $html);
        $this->assertStringContainsString('method="mobile_library_view"', $html);
        $this->assertStringContainsString('<button type="button" class="aty-event"', $html);
        $this->assertStringContainsString('</button>', $html);
    }
}
