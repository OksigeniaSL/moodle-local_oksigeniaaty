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
 * The door of the app in «Your work», the dashboard block of the Oksigenia plugins: for whoever may send people their way
 * into the app, how many have it, and the site's QR code at hand for whoever runs the site.
 *
 * It runs on every load of the dashboard, so the figure is kept for a minute; it is the same for everybody.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class work {
    /** The plugin. */
    private const COMPONENT = 'local_oksigeniaaty';

    /**
     * The door of a person.
     *
     * @param int $userid The person.
     * @param bool $keep Whether to keep the figure for a minute; the tests do not.
     * @return array As the block takes them: key, title, url, status, actions (label, url).
     */
    public static function for_user(int $userid, bool $keep = true): array {
        $system = \context_system::instance();
        if (!has_capability('local/oksigeniaaty:issuelink', $system, $userid)) {
            return [];
        }

        $people = $keep ? self::kept_people() : self::people();
        $actions = [];
        if (has_capability('moodle/site:config', $system, $userid)) {
            $actions[] = ['label' => get_string('work:qr', self::COMPONENT),
                'url' => (new \moodle_url('/local/oksigeniaaty/centreqr.php'))->out(false)];
        }

        return [[
            'key' => self::COMPONENT,
            'title' => get_string('work:title', self::COMPONENT),
            'url' => (new \moodle_url('/local/oksigeniaaty/sendapp.php'))->out(false),
            'status' => get_string('work:status' . ($people === 1 ? ':one' : ''), self::COMPONENT, $people),
            'alerts' => [],
            'actions' => $actions,
        ]];
    }

    /**
     * How many people have the app on a phone, kept for a minute.
     *
     * @return int
     */
    private static function kept_people(): int {
        $cache = \cache::make(self::COMPONENT, 'work');
        $people = $cache->get('people');
        if ($people === false) {
            $people = self::people();
            $cache->set('people', $people);
        }

        return (int) $people;
    }

    /**
     * How many people have the app the site has set on a phone: Aty, or Moodle's own.
     *
     * @return int
     */
    private static function people(): int {
        global $DB;

        $package = get_config('tool_mobile', 'androidappid') ?: 'com.moodle.moodlemobile';

        return (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT d.userid)
               FROM {user_devices} d
               JOIN {user} u ON u.id = d.userid
              WHERE d.appid = :appid AND u.deleted = 0 AND u.suspended = 0",
            ['appid' => $package]
        );
    }
}
