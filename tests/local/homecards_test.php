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
     * Moodle's s() lets numeric character references through and the app's compiler decodes them before it looks for
     * expressions, so no reference may reach a template, whatever its form or depth.
     *
     * @dataProvider references_provider
     * @param string $given The text.
     * @param string $inert What it has to become.
     */
    public function test_references_are_inert(string $given, string $inert): void {
        $this->assertSame($inert, template::inert($given));
        // What Mustache writes in the template, in a text and in an address.
        $this->assertDoesNotMatchRegularExpression('/&#(\d+|x[0-9a-f]+);/i', s(template::inert($given)));
        $this->assertDoesNotMatchRegularExpression('/&#(\d+|x[0-9a-f]+);/i', s(template::inert($given, true)));
    }

    /**
     * Texts with character references.
     *
     * @return array
     */
    public static function references_provider(): array {
        return [
            'decimal' => ['&#123;&#123;7*7&#125;&#125;', '｛｛7*7｝｝'],
            'hexadecimal' => ['&#x7b;&#X7B;7*7&#x7d;&#X7D;', '｛｛7*7｝｝'],
            'leading zeros' => ['&#0000123;&#x0007b;7*7&#0125;&#x7D;', '｛｛7*7｝｝'],
            'nested' => ['&#38;#123;&#38;#123;7*7&#38;#125;&#38;#125;', '｛｛7*7｝｝'],
            'nested twice' => ['&amp;#38;#123;&amp;amp;#123;7*7', '｛｛7*7'],
            'named' => ['&lbrace;&lcub;7*7&rbrace;&rcub;', '｛｛7*7｝｝'],
            'at sign' => ['&commat;if (a)', '＠if (a)'],
            'numeric at sign' => ['&#64;let a = 1;', '＠let a = 1;'],
            'out of range' => ['a&#9999999999;b&#x110000;c', 'abc'],
            'joined after removing one' => ['&#&#1;123;&#&#1;123;7*7', '｛｛7*7'],
            'an ordinary text' => ['Tom &amp; Jerry', 'Tom & Jerry'],
            'not a reference' => ['R&D; 5 &#; &#x;', 'R&D; 5 &#; &#x;'],
        ];
    }

    /**
     * A reference to a quotation mark does not leave its string in the page's arguments, which the app reads as an
     * expression.
     */
    public function test_references_stay_inside_the_arguments(): void {
        $card = homecards::clean([
            'heading' => 'Heading',
            'items' => [['title' => 'Title', 'method' => 'mobile_record_view', 'args' => ['q' => '&#34;+a+&#34;']]],
        ], 'local_x');

        $this->assertSame('{"q":"\"+a+\""}', $card['items'][0]['argsjson']);
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

    /**
     * The new fields of a card, all optional: a row's button, what its deadline is and whether its hour matters, and the
     * card's own "See all". Without them a row is as it was.
     */
    public function test_new_fields(): void {
        $this->resetAfterTest();
        $date = 1800000000;

        $card = homecards::clean([
            'heading' => 'Permissions',
            'items' => [
                ['title' => 'Museum trip', 'date' => $date, 'actionlabel' => '{{ Sign }}', 'kind' => 'due',
                    'method' => 'mobile_consent_view', 'args' => ['id' => 4]],
                ['title' => 'Tutorial', 'date' => $date, 'time' => true, 'kind' => 'later', 'url' => 'https://example.org/t'],
                ['title' => 'Menu', 'date' => $date, 'url' => 'https://example.org/m'],
                ['title' => 'No date', 'kind' => 'close', 'time' => true, 'url' => 'https://example.org/n'],
            ],
            'seeall' => ['label' => 'All the permissions', 'method' => 'mobile_consents'],
        ], 'local_x');

        [$sign, $tutorial, $menu, $nodate] = $card['items'];
        $hour = userdate($date, get_string('strftimetime', 'langconfig'));
        $this->assertSame('｛｛ Sign ｝｝', $sign['actionlabel']);
        $this->assertSame('due', $sign['kind']);
        $this->assertSame(get_string('home:kind:due', 'local_oksigeniaaty'), $sign['kindlabel']);
        $this->assertSame($hour, $sign['hour']);
        $this->assertSame('', $tutorial['kind']);
        $this->assertSame($hour, $tutorial['hour']);
        // Without the new fields, as before: no button, no kind, no hour.
        $this->assertSame(['', '', ''], [$menu['actionlabel'], $menu['kind'], $menu['hour']]);
        $this->assertSame(['', ''], [$nodate['kind'], $nodate['hour']]);

        $this->assertSame('All the permissions', $card['seeall']['label']);
        $this->assertTrue($card['seeall']['ispage']);
        $this->assertSame('mobile_consents', $card['seeall']['method']);
        $this->assertSame('Permissions', $card['seeall']['pagetitle']);

        // A "See all" with no label says so; one that opens nothing is none.
        $card = homecards::clean(['heading' => 'Books', 'items' => [['title' => 'A', 'url' => 'https://example.org/a']],
            'seeall' => ['url' => 'https://example.org/all']], 'local_x');
        $this->assertSame(get_string('home:seeall', 'local_oksigeniaaty'), $card['seeall']['label']);
        $this->assertSame('https://example.org/all', $card['seeall']['url']);
        $card = homecards::clean(['heading' => 'Books', 'items' => [['title' => 'A', 'url' => 'https://example.org/a']],
            'seeall' => ['label' => 'All', 'url' => 'javascript:alert(1)']], 'local_x');
        $this->assertNull($card['seeall']);
    }

    /**
     * On the home a card shows two rows at most; a row with something to do goes to "Today", and a card with no row left
     * is not shown. Its "See all" stays, or its tile takes its place when rows were left out.
     */
    public function test_on_home(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $now = time();
        $today = usergetmidnight($now) + 12 * HOURSECS;
        $rows = fn(int $count) => array_map(
            fn($i) => ['title' => 'Row ' . $i, 'url' => 'https://example.org/' . $i],
            range(1, $count)
        );

        $permissions = homecards::clean([
            'heading' => 'Permissions',
            'items' => [
                ['title' => 'Class photo', 'date' => $today, 'time' => true, 'url' => 'https://example.org/photo'],
                ['title' => 'Museum trip', 'actionlabel' => 'Sign', 'url' => 'https://example.org/trip'],
                ['title' => 'Third', 'url' => 'https://example.org/third'],
            ],
            'seeall' => ['label' => 'All of them', 'url' => 'https://example.org/all'],
        ], 'local_x');
        $canteen = homecards::clean([
            'heading' => 'Canteen',
            'items' => $rows(3),
            'tile' => ['label' => 'Canteen', 'icon' => 'fas-utensils', 'url' => 'https://example.org/canteen'],
        ], 'local_y');
        $books = homecards::clean(['heading' => 'Books', 'items' => $rows(3)], 'local_z');
        $todoonly = homecards::clean([
            'heading' => 'To sign',
            'items' => [['title' => 'Swimming', 'actionlabel' => 'Sign', 'url' => 'https://example.org/swim']],
        ], 'local_x');
        $tileonly = homecards::clean(['tile' => ['label' => 'Library', 'url' => 'https://example.org/library']], 'local_z');

        [$shown, $todo] = homecards::on_home([$permissions, $canteen, $books, $todoonly, $tileonly], $now);

        $this->assertSame(['Museum trip', 'Swimming'], array_column($todo, 'title'));
        $this->assertSame(['Permissions', 'Canteen', 'Books'], array_column($shown, 'heading'));
        $this->assertSame(['Class photo'], array_column($shown[0]['items'], 'title'));
        $this->assertTrue($shown[0]['items'][0]['hashour']);
        $this->assertSame('All of them', $shown[0]['seeall']['label']);
        $this->assertSame(['Row 1', 'Row 2'], array_column($shown[1]['items'], 'title'));
        $this->assertSame(get_string('home:seeall', 'local_oksigeniaaty'), $shown[1]['seeall']['label']);
        $this->assertSame('https://example.org/canteen', $shown[1]['seeall']['url']);
        $this->assertFalse($shown[1]['items'][0]['hashour']);
        // Rows left out and nothing to see them with: two rows, never "and 1 more".
        $this->assertCount(2, $shown[2]['items']);
        $this->assertNull($shown[2]['seeall']);
    }

    /**
     * What the app receives of the new fields: the row with something to do keeps still and its button opens it; a
     * deadline says what it is over its hour; a card's "See all" sits by its heading.
     */
    public function test_template_new_fields(): void {
        global $OUTPUT;
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $card = homecards::clean([
            'heading' => 'Permissions',
            'items' => [
                ['title' => 'Museum trip', 'actionlabel' => 'Sign', 'urgent' => true, 'method' => 'mobile_consent_view',
                    'args' => ['id' => 4]],
                ['title' => 'Map of the islands', 'date' => time() + 3 * DAYSECS, 'kind' => 'due',
                    'url' => 'https://example.org/map'],
                ['title' => 'Old row', 'date' => time() + 3 * DAYSECS, 'url' => 'https://example.org/old'],
            ],
            'seeall' => ['label' => 'All of them', 'url' => 'https://example.org/all'],
        ], 'local_x');
        [$shown, $todo] = homecards::on_home([$card], time());
        $html = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_home', [
            'hastoday' => true, 'todo' => $todo, 'cards' => $shown,
        ]);

        $this->assertStringContainsString('<div class="aty-event aty-urgent">', $html);
        $this->assertStringContainsString('<button type="button" class="aty-hacer" core-site-plugins-new-content '
            . 'component="local_x" method="mobile_consent_view" title="Museum trip" '
            . '[args]="{&quot;id&quot;:4}">Sign</button>', $html);
        $this->assertStringContainsString('<small>Due</small>', $html);
        $this->assertStringContainsString('class="aty-plazo"', $html);
        $this->assertStringContainsString('<a class="aty-ver" core-link href="https://example.org/all" capture="true" '
            . 'inApp="true">All of them</a>', $html);
        $this->assertStringNotContainsString('Old row', $html);
        // A row without the new fields opens whole, as before.
        $html = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_home', ['cards' => [homecards::clean([
            'heading' => 'Books',
            'items' => [['title' => 'A book', 'date' => 1800000000, 'url' => 'https://example.org/b']],
        ], 'local_x')]]);
        $this->assertStringContainsString('<a class="aty-event" core-link href="https://example.org/b"', $html);
        $this->assertStringNotContainsString('aty-hacer', $html);
        $this->assertStringNotContainsString('aty-plazo', $html);
    }

    /**
     * A row that opens nothing: on a child's page, for what the adult may know exists but not see; never on the home.
     */
    public function test_plain_rows(): void {
        global $OUTPUT;
        $this->resetAfterTest();

        $given = ['heading' => 'In the library', 'items' => [['title' => 'A book', 'date' => 1800000000, 'plain' => true,
            'method' => 'mobile_library_view']]];
        $this->assertSame('mobile_library_view', homecards::clean($given, 'local_x')['items'][0]['method']);
        unset($given['items'][0]['method']);
        $this->assertNull(homecards::clean($given, 'local_x'));

        $given['items'][0]['method'] = 'mobile_library_view';
        $card = homecards::clean($given, 'local_x', true);
        $this->assertTrue($card['items'][0]['isplain']);
        $this->assertFalse($card['items'][0]['ispage']);
        $this->assertArrayNotHasKey('method', $card['items'][0]);

        $html = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_child', ['cards' => [$card]]);
        $this->assertStringContainsString('<div class="aty-event">', $html);
        $this->assertStringContainsString('A book', $html);
        $this->assertStringNotContainsString('<button', $html);
        $this->assertStringNotContainsString('<a ', $html);
    }

    /**
     * A plugin whose handlers in the app are all among the site's disabled features is off in the app, and so are its cards.
     */
    public function test_off_in_app(): void {
        $this->resetAfterTest();
        // As in the app, signed in: without a session Moodle lists only the sign-in plugins that support the app.
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame([], homecards::off_in_app());

        $mine = [];
        foreach (\tool_mobile\api::get_plugins_supporting_mobile() as $plugin) {
            if ($plugin['component'] === 'local_oksigeniaaty') {
                $mine[] = 'sitePlugin_local_oksigeniaaty_' . $plugin['addon'];
            }
        }
        $this->assertNotEmpty($mine);

        set_config('disabledfeatures', implode(',', array_merge(['CoreMainMenuDelegate_AddonMessages'], $mine)), 'tool_mobile');
        $this->assertArrayHasKey('local_oksigeniaaty', homecards::off_in_app());

        // Another feature off is not this plugin off.
        set_config('disabledfeatures', 'CoreMainMenuDelegate_AddonMessages', 'tool_mobile');
        $this->assertArrayNotHasKey('local_oksigeniaaty', homecards::off_in_app());
    }
}
