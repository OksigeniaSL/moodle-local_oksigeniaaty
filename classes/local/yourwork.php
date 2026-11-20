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
 * «Your work» on the app's home: the panel of the dashboard block of the Oksigenia plugins (block_oksigeniatembiapo), for
 * whoever runs something (the office, the canteen, the library, the families' inbox), on the phone. Above, what is waiting
 * to be sorted out, the urgent first, each line straight to where it is sorted out; below, a door into each service.
 *
 * The plugins give their doors through `<component>_oksigenia_work(int $userid)`, the block's contract 2; the app asks
 * nothing else of them, so a plugin that takes part in the block is in the app as it is. It shows only where the block is
 * installed and enabled, which is the site's choice of having «Your work», and with the block's order and hidden services.
 * A plugin turned off in the app is not in it either.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class yourwork {
    /** @var int The alerts the home shows; the rest, with the services' shortcuts, are behind «See all». */
    public const HOME_ALERTS = 3;

    /** @var string The block's board, which knows the contract and orders what the plugins give. */
    private const BOARD = '\block_oksigeniatembiapo\local\board';

    /** @var string The block's services: the order and the hidden ones the site chose. */
    private const SERVICES = '\block_oksigeniatembiapo\local\services';

    /**
     * «Your work» of a person, ready for the templates, or null when there is none: no block, or nothing they run.
     *
     * @param int $userid The person.
     * @return array|null See shape().
     */
    public static function for_user(int $userid): ?array {
        global $DB;

        $board = self::BOARD;
        $services = self::SERVICES;
        if (
            $userid <= 0 || isguestuser($userid) || !class_exists($board) || !class_exists($services)
            || !method_exists($board, 'build') || !method_exists($services, 'settings')
            || !$DB->get_field('block', 'visible', ['name' => 'oksigeniatembiapo'])
        ) {
            return null;
        }

        try {
            $settings = $services::settings();
            $off = homecards::off_in_app();
            $raw = [];
            foreach ($board::services() as $component => $function) {
                if (in_array($component, $settings['hidden'], true) || isset($off[$component])) {
                    continue;
                }
                // One service that fails does not take the home down, as in the block.
                try {
                    $doors = $function($userid);
                } catch (\Throwable $e) {
                    debugging('«Your work» in the app, ' . $component . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                    continue;
                }
                if (is_array($doors) && $doors) {
                    $raw[$component] = $doors;
                }
            }
            if (!$raw) {
                return null;
            }
            $built = $board::build($raw, $settings['order'], [$board, 'icon']);
        } catch (\Throwable $e) {
            debugging('«Your work» in the app: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }

        return self::shape($built);
    }

    /**
     * What the block's board built, as the app's templates take it: every text inert, every address one the app can open
     * (in its own window, or in the phone's browser for the pages a plugin wants there).
     *
     * @param array $board From the block's board::build(): doors (ordered) and alerts (the urgent first, then by when due).
     * @return array|null alerts, shown (the home's), hasshown, doors, hasmore (something only «See all» has), components;
     *     null without doors.
     */
    public static function shape(array $board): ?array {
        $doors = [];
        foreach (is_array($board['doors'] ?? null) ? $board['doors'] : [] as $door) {
            $door = is_array($door) ? self::door($door) : null;
            if ($door !== null) {
                $doors[] = $door;
            }
        }
        if (!$doors) {
            return null;
        }
        $alerts = [];
        foreach (is_array($board['alerts'] ?? null) ? $board['alerts'] : [] as $alert) {
            $alert = is_array($alert) ? self::alert($alert) : null;
            if ($alert !== null) {
                $alerts[] = $alert;
            }
        }
        $shown = array_slice($alerts, 0, self::HOME_ALERTS);

        return [
            'alerts' => $alerts,
            'hasalerts' => (bool) $alerts,
            'shown' => $shown,
            'hasshown' => (bool) $shown,
            'doors' => $doors,
            // «See all» only when it has something the home has not: more alerts, or a service's shortcuts.
            'hasmore' => count($alerts) > count($shown) || (bool) array_filter(array_column($doors, 'hasactions')),
            'components' => array_values(array_unique(array_column($doors, 'component'))),
        ];
    }

    /**
     * A door into a service.
     *
     * @param array $door As the board gives it: component, title, url, status, icon, alertcount, urgent, actions.
     * @return array|null
     */
    private static function door(array $door): ?array {
        $title = trim((string) ($door['title'] ?? ''));
        $target = self::target($door['url'] ?? '');
        if ($title === '' || $target === null) {
            return null;
        }
        $actions = [];
        foreach (is_array($door['actions'] ?? null) ? $door['actions'] : [] as $action) {
            $label = is_array($action) ? trim((string) ($action['label'] ?? '')) : '';
            $to = is_array($action) ? self::target($action['url'] ?? '') : null;
            if ($label !== '' && $to !== null) {
                $actions[] = template::inert(['label' => $label]) + $to;
            }
        }
        $count = max(0, (int) ($door['alertcount'] ?? 0));

        return template::inert([
            'title' => $title,
            'status' => trim((string) ($door['status'] ?? '')),
            'alertlabel' => $count > 0 ? get_string('yourwork:alertcount', 'local_oksigeniaaty', $count) : '',
        ]) + [
            'component' => (string) ($door['component'] ?? ''),
            'icon' => template::inert(self::icon($door['icon'] ?? ''), true),
            'alertcount' => $count,
            'hasalerts' => $count > 0,
            'urgent' => !empty($door['urgent']),
            'actions' => $actions,
            'hasactions' => (bool) $actions,
        ] + $target;
    }

    /**
     * A line of what is waiting to be sorted out: what, and of which service. When it is due only orders the list, as in
     * the block: it may be a time gone by (the oldest of the late books), and the text already says the hour that matters.
     *
     * @param array $alert As the board gives it: text, url, urgent, service, serviceicon.
     * @return array|null
     */
    private static function alert(array $alert): ?array {
        $text = trim((string) ($alert['text'] ?? ''));
        $target = self::target($alert['url'] ?? '');
        if ($text === '' || $target === null) {
            return null;
        }

        return template::inert([
            'text' => $text,
            'service' => trim((string) ($alert['service'] ?? '')),
        ]) + [
            'icon' => template::inert(self::icon($alert['serviceicon'] ?? ''), true),
            'urgent' => !empty($alert['urgent']),
        ] + $target;
    }

    /**
     * Where a door, a shortcut or an alert leads, for the app: an address of the web, absolute, opened in the app's own
     * window or, for the pages a plugin wants there, in the phone's browser. An address of the site given from its root is
     * made whole; anything else leads nowhere.
     *
     * @param mixed $url What the board gave.
     * @return array|null url and browser.
     */
    private static function target($url): ?array {
        global $CFG;

        $url = is_scalar($url) ? trim((string) $url) : '';
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $url = $CFG->wwwroot . $url;
        }
        if (!preg_match('#^https?://#i', $url)) {
            return null;
        }

        return ['url' => template::inert($url, true), 'browser' => browserpages::matches($url)];
    }

    /**
     * A logo's address, only when it is one of the web.
     *
     * @param mixed $icon What the board gave.
     * @return string
     */
    private static function icon($icon): string {
        $icon = is_scalar($icon) ? trim((string) $icon) : '';

        return preg_match('#^https?://#i', $icon) ? $icon : '';
    }
}
