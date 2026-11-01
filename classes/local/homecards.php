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
 *   time; or count and countlabel) and where it opens.
 * - tile (array): label, icon (fas-*) and where it opens, next to the home's shortcuts.
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

    /**
     * The cards of all the plugins for a user. One plugin that fails does not take the home down.
     *
     * @param int $userid The user.
     * @return array A list of ['heading', 'hasitems', 'items', 'tile'], ready for the template.
     */
    public static function for_user(int $userid): array {
        $cards = [];
        foreach (get_plugins_with_function('oksigeniaaty_home_cards') as $plugintype => $plugins) {
            foreach ($plugins as $pluginname => $function) {
                try {
                    $given = $function($userid);
                } catch (\Throwable $e) {
                    debugging('Home cards of ' . $plugintype . '_' . $pluginname . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                    continue;
                }
                foreach (is_array($given) ? $given : [] as $card) {
                    $card = self::clean($card, $plugintype . '_' . $pluginname);
                    if ($card !== null) {
                        $cards[] = $card;
                    }
                }
            }
        }

        return array_slice($cards, 0, self::MAX_CARDS);
    }

    /**
     * A card as a plugin returned it, checked and ready for the template, or null when nothing in it can be shown.
     *
     * @param mixed $card What the plugin returned.
     * @param string $component The plugin, whose pages the card may open.
     * @return array|null
     */
    public static function clean($card, string $component): ?array {
        if (!is_array($card)) {
            return null;
        }

        $items = [];
        foreach (is_array($card['items'] ?? null) ? $card['items'] : [] as $item) {
            $item = is_array($item) ? self::item($item, $component) : null;
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

        return [
            'heading' => template::inert($heading),
            'hasitems' => (bool) $items,
            'items' => $items,
            'tile' => $tile,
        ];
    }

    /**
     * A row of a card.
     *
     * @param array $item What the plugin returned.
     * @param string $component The plugin.
     * @return array|null
     */
    private static function item(array $item, string $component): ?array {
        $title = (string) ($item['title'] ?? '');
        $target = self::target($item, $component, $title);
        if ($title === '' || $target === null) {
            return null;
        }

        $date = (int) ($item['date'] ?? 0);
        $count = (string) ($item['count'] ?? '');

        return template::inert([
            'title' => $title,
            'detail' => (string) ($item['detail'] ?? ''),
            'urgent' => !empty($item['urgent']),
            'hasdate' => $date > 0,
            // As in the agenda: the app redraws the date in the phone's own time zone.
            'timestamp' => $date * 1000,
            'day' => $date > 0 ? ltrim(userdate($date, '%e')) : '',
            'month' => $date > 0 ? \core_text::strtoupper(trim(userdate($date, '%b'))) : '',
            'hascount' => $date <= 0 && $count !== '',
            'count' => $count,
            'countlabel' => (string) ($item['countlabel'] ?? ''),
        ]) + $target;
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
            $args = is_array($given['args'] ?? null) ? template::inert($given['args']) : [];
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
            return ['ispage' => false, 'url' => template::inert($url)];
        }

        return null;
    }
}
