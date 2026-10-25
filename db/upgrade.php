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

/**
 * Upgrade steps for local_oksigeniaaty.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Run the local_oksigeniaaty upgrade steps.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_local_oksigeniaaty_upgrade($oldversion): bool {
    global $CFG;

    require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

    if ($oldversion < 2026102500) {
        // The stylesheet now also carries the client's app CSS, so take over mobilecssurl (including from Boost Union)
        // and move the cache key to a content hash.
        local_oksigeniaaty_refresh_appcss();
        upgrade_plugin_savepoint(true, 2026102500, 'local', 'oksigeniaaty');
    }

    return true;
}
