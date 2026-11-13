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
 * - seeall (array, optional): label and where it opens, the card's own "See all". The home shows two rows of a card at
 *   most, and this link under its heading; without it, the card's tile takes its place, with "See all".
 * - tile (array): label, icon (fas-*) and where it opens, among the home's shortcuts.
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
                    if ($card !== null && $card['hasitems']) {
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
        if (!$items && $tile === null) {
            return null;
        }
        $seeall = is_array($card['seeall'] ?? null) ? self::seeall($card['seeall'], $component, $heading) : null;

        return [
            'heading' => template::inert($heading),
            'hasitems' => (bool) $items,
            'items' => $items,
            'tile' => $tile,
            'seeall' => $items ? $seeall : null,
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
            'hascount' => $date <= 0 && $count !== '',
            'count' => $count,
            'countlabel' => (string) ($item['countlabel'] ?? ''),
        ]) + $target;
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
