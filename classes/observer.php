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

namespace local_oksigeniaaty;

/**
 * Event observers for local_oksigeniaaty.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Keep the app's remote CSS pointing at this plugin when the client CSS or mobilecssurl changes.
     *
     * Boost Union points $CFG->mobilecssurl at its own styles.php when its settings are saved; this points it back at
     * the plugin, whose stylesheet serves that CSS too, and refreshes the cache version when the Boost Union app CSS
     * box changes. Reclaiming writes the same value once things settle, so it does not loop.
     *
     * @param \core\event\config_log_created $event The config change event.
     * @return void
     */
    public static function config_log_created(\core\event\config_log_created $event): void {
        global $CFG;

        $other = $event->get_data()['other'] ?? [];
        $name = (string) ($other['name'] ?? '');
        $plugin = (string) ($other['plugin'] ?? '');

        $relevant = ($name === 'mobilecssurl' && ($plugin === '' || $plugin === 'core'))
            || ($plugin === 'theme_boost_union' && $name === 'mobilescss');
        if (!$relevant) {
            return;
        }

        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');
        local_oksigeniaaty_refresh_appcss();
    }
}
