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
 * Nothing and not knowing are not the same: a service that does not answer keeps its door, which says since when, and has
 * its line among what is waiting (from the block's 0.3.0). That line counts nothing: how many things there are is not known.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class yourwork {
    /** @var int The alerts the home shows at least, when there are: the urgent first, then the others. */
    public const HOME_ALERTS = 3;

    /** @var int The urgent alerts the home shows at most; a line that says how many more leads to «See all». */
    public const HOME_URGENT = 6;

    /** @var int The alerts «See all» shows at most; a line that says how many more leads to the dashboard of the web. */
    public const ALL_ALERTS = 50;

    /** @var int The letters of a door's line of state on the home, in whole parts between «·»: two lines of a half door. */
    public const HOME_STATUS = 44;

    /** @var string The block's board, which knows the contract and orders what the plugins give. */
    private const BOARD = '\block_oksigeniatembiapo\local\board';

    /** @var string The block's services: the order and the hidden ones the site chose. */
    private const SERVICES = '\block_oksigeniatembiapo\local\services';

    /**
     * «Your work» of a person, ready for the templates, or null when there is none: no block, or nothing they run.
     *
     * @param int $userid The person.
     * @param bool $failed Set to whether it could not be asked in full, so that a null is not read as nothing waiting.
     * @return array|null See shape().
     */
    public static function for_user(int $userid, bool &$failed = false): ?array {
        global $DB;

        $failed = false;
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
            if (method_exists($board, 'ask')) {
                // The block keeps apart the services that do not answer, with the doors the person had.
                $asked = $board::ask($userid, array_keys($off));
                [$raw, $silent] = [array_filter($asked['raw']), $asked['silent']];
            } else {
                [$raw, $silent] = [self::ask($userid, array_merge($settings['hidden'], array_keys($off)), $failed), []];
            }
            if (!$raw && !$silent) {
                return null;
            }
            $built = $board::build($raw, $settings['order'], [$board, 'icon'], $silent);
        } catch (\Throwable $e) {
            debugging('«Your work» in the app: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $failed = true;
            return null;
        }

        return self::shape($built);
    }

    /**
     * The doors each service gives, with a block older than 0.3.0, which does not keep those of one that does not answer.
     *
     * @param int $userid The person.
     * @param string[] $skip The components to leave out.
     * @param bool $failed Set to true when a service does not answer.
     * @return array Component => its doors.
     */
    private static function ask(int $userid, array $skip, bool &$failed): array {
        $board = self::BOARD;
        $raw = [];
        foreach ($board::services() as $component => $function) {
            if (in_array($component, $skip, true)) {
                continue;
            }
            // One service that fails does not take the home down, as in the block.
            try {
                $doors = $function($userid);
            } catch (\Throwable $e) {
                debugging('«Your work» in the app, ' . $component . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                $failed = true;
                continue;
            }
            if (is_array($doors) && $doors) {
                $raw[$component] = $doors;
            }
        }

        return $raw;
    }

    /**
     * What the block's board built, as the app's templates take it: every text inert, every address one the app can open
     * (in its own window, or in the phone's browser for the pages a plugin wants there).
     *
     * @param array $board From the block's board::build(): doors (ordered) and alerts (the urgent first, then by when due).
     * @return array|null total (every alert known), unsure (a service did not answer), alerts («See all»'s, up to ALL_ALERTS),
     *     beyondlabel and beyondurl (the line to the rest, on the web), shown (the home's), hasshown, morelabel and moreurgent
     *     (the home's line to the rest), doors, single (one door with something waiting: the home opens it), hasmore
     *     (something only «See all» has), components; null without doors.
     */
    public static function shape(array $board): ?array {
        global $CFG;

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
        // The urgent first, up to six, then the services that did not answer, always, and the others up to three in all; the
        // rest, behind a line that says how many and opens «See all», marked when urgent ones are among them. A hard limit: a
        // service that piles up a hundred alerts does not make the home a list of a hundred.
        $unknown = array_values(array_filter($alerts, fn(array $alert): bool => $alert['unknown']));
        $urgent = array_values(array_filter($alerts, fn(array $alert): bool => $alert['urgent']));
        $others = array_values(array_filter($alerts, fn(array $alert): bool => !$alert['urgent'] && !$alert['unknown']));
        $known = count($urgent) + count($others);
        $shown = array_merge(array_slice($urgent, 0, self::HOME_URGENT), $unknown);
        $shown = array_merge($shown, array_slice($others, 0, max(0, self::HOME_ALERTS - count($shown))));
        $more = count($alerts) - count($shown);
        $beyond = max(0, $known - self::ALL_ALERTS);
        $listed = [];
        $room = self::ALL_ALERTS;
        foreach ($alerts as $alert) {
            if ($alert['unknown'] || $room-- > 0) {
                $listed[] = $alert;
            }
        }
        // The doors with something urgent first, then those that did not answer, then those with something waiting, then the
        // rest; in each, the block's order (the site's, then by name).
        $rank = fn(array $door): int => $door['urgent'] ? 0 : ($door['silent'] ? 1 : ($door['hasalerts'] ? 2 : 3));
        $place = array_flip(array_keys($doors));
        uksort($doors, fn(int $a, int $b): int => [$rank($doors[$a]), $place[$a]] <=> [$rank($doors[$b]), $place[$b]]);
        $doors = array_values($doors);

        return [
            'total' => $known,
            'unsure' => (bool) $unknown,
            'alerts' => $listed,
            'hasalerts' => (bool) $alerts,
            // Past what «See all» shows, the whole list is the dashboard's block, on the web.
            'beyondlabel' => $beyond ? template::inert(get_string('yourwork:moreweb', 'local_oksigeniaaty', $beyond)) : '',
            'beyondurl' => template::inert($CFG->wwwroot . '/my/', true),
            'shown' => $shown,
            'hasshown' => (bool) $shown,
            'morelabel' => $more ? template::inert(get_string('yourwork:more', 'local_oksigeniaaty', $more)) : '',
            'moreurgent' => count($urgent) > self::HOME_URGENT,
            'doors' => $doors,
            // One door only, with something waiting: its lines go inside it (mobile_home_work), not in a list over it that
            // says the same thing.
            'single' => count($doors) === 1 && (bool) $shown,
            // «See all» only when it has something the home has not: more alerts, or a service's shortcuts.
            'hasmore' => $more > 0 || (bool) array_filter(array_column($doors, 'hasactions')),
            'components' => array_values(array_unique(array_column($doors, 'component'))),
        ];
    }

    /**
     * A door into a service.
     *
     * @param array $door As the board gives it: component, title, url, status, icon, alertcount, urgent, silent, actions.
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
        // How many lines of alerts, not how many things: «4 pupils without a menu» is one line. The contract does not say
        // how many things each line is, so the door shows a dot, not a number that would say less than there is.
        $count = max(0, (int) ($door['alertcount'] ?? 0));
        $urgent = !empty($door['urgent']);
        $silent = !empty($door['silent']);
        $status = trim((string) ($door['status'] ?? ''));

        return template::inert([
            'title' => self::name($title),
            'status' => $status,
            'statusshort' => self::short($status),
            'alertlabel' => $count > 0
                ? get_string($urgent ? 'yourwork:alertdoturgent' : 'yourwork:alertdot', 'local_oksigeniaaty')
                : '',
            'silentlabel' => $silent ? get_string('yourwork:silent', 'local_oksigeniaaty') : '',
        ]) + [
            'component' => (string) ($door['component'] ?? ''),
            'icon' => template::inert(self::icon($door['icon'] ?? ''), true),
            'alertcount' => $count,
            'hasalerts' => $count > 0,
            'urgent' => $urgent,
            'silent' => $silent,
            'actions' => $actions,
            'hasactions' => (bool) $actions,
        ] + $target;
    }

    /**
     * A line of what is waiting to be sorted out: what, and of which service. When it is due only orders the list, as in
     * the block: it may be a time gone by (the oldest of the late books), and the text already says the hour that matters.
     *
     * @param array $alert As the board gives it: text, url, urgent, unknown (its service did not answer), service, serviceicon.
     * @return array|null
     */
    private static function alert(array $alert): ?array {
        $text = trim((string) ($alert['text'] ?? ''));
        $target = self::target($alert['url'] ?? '');
        if ($text === '' || $target === null) {
            return null;
        }
        $unknown = !empty($alert['unknown']);

        return template::inert([
            // All alike, without the full stop some plugins put and others do not («…» and «?» stay); the block's own line of
            // a service that did not answer is two sentences, whole.
            'text' => $unknown ? $text : preg_replace('/(?<!\.)\.$/u', '', $text),
            'service' => self::name(trim((string) ($alert['service'] ?? ''))),
        ]) + [
            'icon' => template::inert(self::icon($alert['serviceicon'] ?? ''), true),
            'urgent' => !$unknown && !empty($alert['urgent']),
            'unknown' => $unknown,
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
     * A service's name without saying twice what its logo already says: «Library · Library of the school» is «Library of
     * the school». A name in two parts whose second one starts with the first keeps only the second.
     *
     * @param string $name The name the plugin gave.
     * @return string
     */
    public static function name(string $name): string {
        $parts = array_map('trim', explode(' · ', $name, 2));
        if (count($parts) !== 2 || $parts[0] === '') {
            return $name;
        }
        [$first, $second] = array_map(fn(string $part): string => \core_text::strtolower($part), $parts);
        if (\core_text::strpos($second, $first) === 0) {
            return $parts[1];
        }

        return $name;
    }

    /**
     * A door's line of state for the home, never cut in the middle of a part: the whole parts between «·» that fit in two
     * lines of a half door; always the first. The whole line is behind «See all».
     *
     * @param string $status The line the plugin gave.
     * @return string
     */
    public static function short(string $status): string {
        $short = '';
        foreach (explode(' · ', $status) as $part) {
            $longer = $short === '' ? $part : $short . ' · ' . $part;
            if ($short !== '' && \core_text::strlen($longer) > self::HOME_STATUS) {
                break;
            }
            $short = $longer;
        }

        return $short;
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
