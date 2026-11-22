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
 * What other plugins add to the app's home for a user: a section of rows and a tile, through
 * `<component>_oksigeniaaty_home_cards(int $userid): array`, in the plugin's lib.php.
 *
 * It returns a list of cards, each with any of:
 * - heading (string) and items (list), the section. An item has title, detail, urgent (bool), its chip (date, a unix
 *   time; or count and countlabel) and where it opens. And, optionally:
 *   - actionlabel (string): a button on the row ("Sign", "Answer") that opens the same as the row. A row with one is
 *     something to do: the home lifts it to "Today" and names it in the line under the greeting.
 *   - kind ('due' or 'close'): the date is a deadline, and the row says so next to its hour ("Due 23:59").
 *   - time (bool): the date has an hour that matters (a class at 16:30), not only a day. On the home, a row of today
 *     then shows its hour big in place of the date.
 *   - mark (string): with actionlabel, one word over the name of what it asks («Sign»).
 * - seeall (array, optional): label and where it opens, the card's own "See all". The home shows two rows of a card at
 *   most, and this link under its heading; without it, the card's tile takes its place, with "See all".
 * - tile (array): label, icon (fas-*) and where it opens, among the home's shortcuts.
 *
 * And, from `<component>_oksigeniaaty_child_cards()` for the family's app (15 November 2026):
 * - facet (array): the child's line of today on the family's home: text, icon (fas-*), urgent (bool) and, the canteen,
 *   menu (its letter) and menucolor (#rrggbb). The letter is always there, never the colour alone.
 * - block (array): the plugin's own block on the child's page, which Aty frames with its title and its way to all of it:
 *   html (the block's body, already rendered by the plugin from inert data: norm 9), title, icon (fas-*), open (where
 *   its foot leads, as any other target) and openlabel. Without a block, the card's rows go in a frame of the same look.
 * - A row may carry cover: a data: URL of an image (a book's cover), for the library's shelf; and the card, layout
 *   'shelf', for a shelf of plain spines when no book has a cover. A row with pickup (bool) is a book waiting to be
 *   picked up: a striped spine.
 *
 * Where it opens: method and args, a page of the plugin's own in the app, with title for its header; or url.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class homecards {
    /** @var int The most cards a home shows, so that no plugin buries the rest. */
    private const MAX_CARDS = 4;

    /** @var int The most rows a card shows. */
    private const MAX_ITEMS = 5;

    /** @var int The most rows a card shows on the home, where "See all" leads to the rest. */
    private const HOME_ITEMS = 2;

    /** @var string[] What a deadline can be. */
    private const KINDS = ['due', 'close'];

    /**
     * The cards of all the plugins for a user. One plugin that fails does not take the home down.
     *
     * @param int $userid The user.
     * @return array A list of ['heading', 'hasitems', 'items', 'tile'], ready for the template.
     */
    public static function for_user(int $userid): array {
        $cards = [];
        $off = self::off_in_app();
        foreach (get_plugins_with_function('oksigeniaaty_home_cards') as $plugintype => $plugins) {
            foreach ($plugins as $pluginname => $function) {
                if (isset($off[$plugintype . '_' . $pluginname])) {
                    continue;
                }
                try {
                    $given = $function($userid);
                } catch (\Throwable $e) {
                    debugging('Home cards of ' . $plugintype . '_' . $pluginname . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                    continue;
                }
                foreach (is_array($given) ? $given : [] as $card) {
                    // A card that cannot be read (a title that is an object, for one) is left out, not the whole page.
                    try {
                        $card = self::clean($card, $plugintype . '_' . $pluginname);
                    } catch (\Throwable $e) {
                        debugging('A card of ' . $plugintype . '_' . $pluginname . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                        continue;
                    }
                    if ($card !== null) {
                        $cards[] = $card;
                    }
                }
            }
        }

        return array_slice($cards, 0, self::MAX_CARDS);
    }

    /**
     * What the plugins add to a child's page in the app of an adult of their family, through
     * `<component>_oksigeniaaty_child_cards(int $adultuserid, int $childuserid): array`, with the same cards as the home. Tiles
     * do not show there. A row may also say `'plain' => true`: it opens nothing, for what the adult may know exists but not
     * see. One plugin that fails does not take the page down.
     *
     * @param int $adultuserid The adult. Whoever calls this has checked that they are in the child's family.
     * @param int $childuserid The child.
     * @return array A list of cards with items.
     */
    public static function for_child(int $adultuserid, int $childuserid): array {
        $cards = [];
        $off = self::off_in_app();
        foreach (get_plugins_with_function('oksigeniaaty_child_cards') as $plugintype => $plugins) {
            foreach ($plugins as $pluginname => $function) {
                if (isset($off[$plugintype . '_' . $pluginname])) {
                    continue;
                }
                try {
                    $given = $function($adultuserid, $childuserid);
                } catch (\Throwable $e) {
                    debugging('Child cards of ' . $plugintype . '_' . $pluginname . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                    continue;
                }
                foreach (is_array($given) ? $given : [] as $card) {
                    try {
                        $card = self::clean($card, $plugintype . '_' . $pluginname, true);
                    } catch (\Throwable $e) {
                        debugging('A card of ' . $plugintype . '_' . $pluginname . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                        continue;
                    }
                    if ($card !== null && ($card['hasitems'] || $card['hasblock'] || $card['facet'] !== null)) {
                        $cards[] = $card;
                    }
                }
            }
        }

        return array_slice($cards, 0, self::MAX_CARDS);
    }

    /**
     * The plugins the site has turned off in the app: every handler they have there is among its disabled features (Site
     * administration ▸ Mobile app ▸ Mobile features). Their cards do not show either, so that one switch hides a plugin
     * from the app while, say, a library gets ready.
     *
     * @return array The components, as keys.
     */
    public static function off_in_app(): array {
        $disabled = array_filter(array_map('trim', explode(',', (string) get_config('tool_mobile', 'disabledfeatures'))));
        if (!$disabled || !class_exists(\tool_mobile\api::class)) {
            return [];
        }
        $features = [];
        foreach (\tool_mobile\api::get_plugins_supporting_mobile() as $plugin) {
            $features[$plugin['component']][] = 'sitePlugin_' . $plugin['component'] . '_' . $plugin['addon'];
        }
        $off = [];
        foreach ($features as $component => $names) {
            if (!array_diff($names, $disabled)) {
                $off[$component] = true;
            }
        }

        return $off;
    }

    /**
     * A card as a plugin returned it, checked and ready for the template, or null when nothing in it can be shown.
     *
     * @param mixed $card What the plugin returned.
     * @param string $component The plugin, whose pages the card may open.
     * @param bool $plainrows Whether a row may open nothing: on a child's page, not on the home.
     * @return array|null
     */
    public static function clean($card, string $component, bool $plainrows = false): ?array {
        if (!is_array($card)) {
            return null;
        }

        $items = [];
        foreach (is_array($card['items'] ?? null) ? $card['items'] : [] as $item) {
            $item = is_array($item) ? self::item($item, $component, $plainrows) : null;
            if ($item !== null) {
                $items[] = $item;
            }
        }
        $items = array_slice($items, 0, self::MAX_ITEMS);
        $heading = (string) ($card['heading'] ?? '');
        if ($heading === '') {
            $items = [];
        }
        $tile = is_array($card['tile'] ?? null) ? self::tile($card['tile'], $component) : null;
        if (!$items && $tile === null && !is_array($card['facet'] ?? null) && !is_array($card['block'] ?? null)) {
            return null;
        }
        $seeall = is_array($card['seeall'] ?? null) ? self::seeall($card['seeall'], $component, $heading) : null;
        $facet = is_array($card['facet'] ?? null) ? self::facet($card['facet']) : null;
        $block = is_array($card['block'] ?? null) ? self::block($card['block'], $component, $heading) : null;

        return [
            // The plugin, so that the home can tell its tile from its door in «Your work».
            'component' => $component,
            'heading' => template::inert($heading),
            'hasitems' => (bool) $items,
            'items' => $items,
            'tile' => $tile,
            'seeall' => $items ? $seeall : null,
            'facet' => $facet,
            'block' => $block,
            'hasblock' => $block !== null,
            // A shelf of books: with a cover on any row, or when the plugin asks (an older reader's plain spines).
            'hascovers' => ($card['layout'] ?? '') === 'shelf' || array_filter($items, fn($item) => $item['hascover']),
        ];
    }

    /**
     * The cards as the home shows them: two rows each at most, with the card's "See all" when there is more to see, and
     * the rows with something to do taken out for "Today".
     *
     * @param array $cards From for_user().
     * @param int $now The time it is.
     * @return array [cards, todo]: the cards with rows left, and the rows with something to do, in the cards' order.
     */
    public static function on_home(array $cards, int $now): array {
        $today = usergetmidnight($now);
        $shown = [];
        $todo = [];
        foreach ($cards as $card) {
            if (!$card['hasitems']) {
                continue;
            }
            $items = array_slice($card['items'], 0, self::HOME_ITEMS);
            $rest = [];
            foreach ($items as $item) {
                // Today's hour, big, where the date was: today is known.
                $item['hashour'] = $item['hasdate'] && $item['hour'] !== ''
                    && usergetmidnight((int) ($item['timestamp'] / 1000)) === $today;
                if ($item['actionlabel'] !== '') {
                    $todo[] = $item;
                } else {
                    $rest[] = $item;
                }
            }
            if (!$rest) {
                continue;
            }
            $seeall = $card['seeall'];
            if ($seeall === null && count($card['items']) > count($items) && $card['tile'] !== null) {
                $seeall = ['label' => get_string('home:seeall', 'local_oksigeniaaty')] + $card['tile'];
            }
            $card['items'] = $rest;
            $card['seeall'] = $seeall;
            $shown[] = $card;
        }

        return [$shown, $todo];
    }

    /**
     * A row of a card.
     *
     * @param array $item What the plugin returned.
     * @param string $component The plugin.
     * @param bool $plainrows Whether the row may open nothing.
     * @return array|null
     */
    private static function item(array $item, string $component, bool $plainrows = false): ?array {
        $title = (string) ($item['title'] ?? '');
        $target = $plainrows && !empty($item['plain'])
            ? ['ispage' => false, 'isplain' => true]
            : self::target($item, $component, $title);
        if ($title === '' || $target === null) {
            return null;
        }

        $date = (int) ($item['date'] ?? 0);
        $count = (string) ($item['count'] ?? '');
        $kind = $date > 0 && in_array($item['kind'] ?? '', self::KINDS, true) ? $item['kind'] : '';
        // A deadline has its hour; anything else, only when the plugin says the hour matters.
        $hour = $date > 0 && ($kind !== '' || !empty($item['time']))
            ? userdate($date, get_string('strftimetime', 'langconfig'))
            : '';

        return template::inert([
            'title' => $title,
            'detail' => (string) ($item['detail'] ?? ''),
            'urgent' => !empty($item['urgent']),
            'hasdate' => $date > 0,
            // As in the agenda: the app redraws the date in the phone's own time zone.
            'timestamp' => $date * 1000,
            'day' => $date > 0 ? ltrim(userdate($date, '%e')) : '',
            'month' => $date > 0 ? \core_text::strtoupper(trim(userdate($date, '%b'))) : '',
            'hour' => $hour,
            'hashour' => false,
            'kind' => $kind,
            'kindlabel' => $kind !== '' ? get_string('home:kind:' . $kind, 'local_oksigeniaaty') : '',
            'actionlabel' => is_scalar($item['actionlabel'] ?? null) ? trim((string) $item['actionlabel']) : '',
            // Over the name of something to do, one word of what it asks («Sign»): its mark, apart from what only tells.
            'mark' => is_scalar($item['mark'] ?? null) ? trim((string) $item['mark']) : '',
            // On the library's shelf, a book waiting to be picked up: a striped spine.
            'pickup' => !empty($item['pickup']),
            'hascount' => $date <= 0 && $count !== '',
            'count' => $count,
            'countlabel' => (string) ($item['countlabel'] ?? ''),
        ]) + $target + self::cover($item['cover'] ?? null);
    }

    /**
     * A book's cover on a row, for the library's shelf: an image as a data: URL (the app has no session to fetch the
     * library's files), JPEG, PNG or WebP, of 80 KB at most. Never through inert(), which would leave it an address.
     *
     * @param mixed $cover What the plugin returned.
     * @return array hascover and cover.
     */
    private static function cover($cover): array {
        $ok = is_string($cover) && strlen($cover) <= 110000
            && preg_match('#^data:image/(jpeg|png|webp);base64,[A-Za-z0-9+/]+=*$#', $cover);

        return ['hascover' => (bool) $ok, 'cover' => $ok ? $cover : ''];
    }

    /**
     * A child's line of today, for the family's home.
     *
     * @param array $facet What the plugin returned.
     * @return array|null text, icon, urgent, hasmenu, menu and menucolor; null without a text.
     */
    private static function facet(array $facet): ?array {
        $text = is_scalar($facet['text'] ?? null) ? trim((string) $facet['text']) : '';
        if ($text === '') {
            return null;
        }
        $icon = (string) ($facet['icon'] ?? '');
        $menu = is_scalar($facet['menu'] ?? null) ? trim((string) $facet['menu']) : '';
        $menu = preg_match('/^[A-Za-z0-9]{1,2}$/', $menu) ? \core_text::strtoupper($menu) : '';
        $colour = (string) ($facet['menucolor'] ?? '');

        return template::inert([
            'text' => $text,
            'icon' => preg_match('/^fas-[a-z0-9-]+$/', $icon) ? $icon : 'fas-circle-info',
            'urgent' => !empty($facet['urgent']),
            'hasmenu' => $menu !== '',
            'menu' => $menu,
        ]) + ['menucolor' => preg_match('/^#[0-9a-fA-F]{6}$/', $colour) ? $colour : ''];
    }

    /**
     * A plugin's own block on a child's page.
     *
     * The body is the plugin's, rendered on the server from inert data (norm 9 of the Moodle conventions). Aty only takes
     * away what the app's compiler would read as its own: braces, also written as references, and every at sign.
     *
     * @param array $block What the plugin returned.
     * @param string $component The plugin.
     * @param string $heading The card's heading, for the block's title when none is given.
     * @return array|null
     */
    private static function block(array $block, string $component, string $heading): ?array {
        $html = is_string($block['html'] ?? null) ? trim($block['html']) : '';
        $title = is_scalar($block['title'] ?? null) ? trim((string) $block['title']) : $heading;
        if ($html === '' || $title === '') {
            return null;
        }
        // Braces written as references too, numeric or named («&lcub;», «&lbrace;»): the compiler decodes them before it
        // looks for expressions. Any reference that decodes to a brace or an at sign is first made the character itself.
        $html = preg_replace_callback('/&(#[0-9]+|#x[0-9a-f]+|[a-z][a-z0-9]*);/i', function (array $match): string {
            $char = html_entity_decode($match[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return in_array($char, ['{', '}', '@'], true) ? $char : $match[0];
        }, $html);
        $html = strtr($html, ['{' => '&#65371;', '}' => '&#65373;']);
        // Every at sign, not only before the compiler's own words: an address such as «info@school.org» is read as the
        // start of a block and leaves the page blank. Written as a reference, it shows the same and the compiler leaves it.
        $html = str_replace('@', '&#64;', $html);
        $icon = (string) ($block['icon'] ?? '');
        $open = is_array($block['open'] ?? null) ? self::target($block['open'], $component, $title) : null;
        $label = is_scalar($block['openlabel'] ?? null) ? trim((string) $block['openlabel']) : '';

        return [
            'title' => template::inert($title),
            'icon' => preg_match('/^fas-[a-z0-9-]+$/', $icon) ? $icon : 'fas-shapes',
            // Never through inert(): it is the plugin's own markup.
            'html' => $html,
            'open' => $open !== null && $label !== '' ? ['label' => template::inert($label)] + $open : null,
        ];
    }

    /**
     * A card's own "See all".
     *
     * @param array $seeall What the plugin returned.
     * @param string $component The plugin.
     * @param string $heading The card's heading, for the header of its page when none is given.
     * @return array|null
     */
    private static function seeall(array $seeall, string $component, string $heading): ?array {
        $target = self::target($seeall, $component, $heading);
        if ($target === null) {
            return null;
        }
        $label = is_scalar($seeall['label'] ?? null) ? trim((string) $seeall['label']) : '';

        return ['label' => template::inert($label !== '' ? $label : get_string('home:seeall', 'local_oksigeniaaty'))] + $target;
    }

    /**
     * A tile next to the home's shortcuts.
     *
     * @param array $tile What the plugin returned.
     * @param string $component The plugin.
     * @return array|null
     */
    private static function tile(array $tile, string $component): ?array {
        $label = (string) ($tile['label'] ?? '');
        $target = self::target($tile, $component, $label);
        if ($label === '' || $target === null) {
            return null;
        }
        $icon = (string) ($tile['icon'] ?? '');

        return template::inert([
            'label' => $label,
            // Only solid Font Awesome names the app ships, as in the site's shortcuts.
            'icon' => preg_match('/^fas-[a-z0-9-]+$/', $icon) ? $icon : 'fas-circle-chevron-right',
        ]) + $target;
    }

    /**
     * Where a row or a tile opens: a page of the plugin in the app, or an address.
     *
     * @param array $given The row or the tile.
     * @param string $component The plugin.
     * @param string $title The title for the page's header when none is given.
     * @return array|null
     */
    private static function target(array $given, string $component, string $title): ?array {
        $method = (string) ($given['method'] ?? '');
        if (preg_match('/^[a-z][a-z0-9_]*$/', $method)) {
            $args = is_array($given['args'] ?? null) ? template::inert($given['args'], true) : [];
            return [
                'ispage' => true,
                'component' => $component,
                'method' => $method,
                'pagetitle' => template::inert((string) ($given['pagetitle'] ?? $title)),
                // Never through inert() after this: its braces are the object the template binds.
                'argsjson' => json_encode((object) $args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ];
        }

        $url = (string) ($given['url'] ?? '');
        if (preg_match('#^https?://#i', $url)) {
            // A page its plugin wants in the phone's browser opens there: from a row (browser) or a tile (external, as a
            // shortcut that leaves the site, with its mark).
            $browser = browserpages::matches($url);
            return ['ispage' => false, 'url' => template::inert($url, true), 'browser' => $browser, 'external' => $browser];
        }

        return null;
    }
}
