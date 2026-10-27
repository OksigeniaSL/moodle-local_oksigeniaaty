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
    global $CFG, $DB;

    require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

    if ($oldversion < 2026102500) {
        // The stylesheet now also carries the client's app CSS, so take over mobilecssurl (including from Boost Union)
        // and move the cache key to a content hash.
        local_oksigeniaaty_refresh_appcss();
        upgrade_plugin_savepoint(true, 2026102500, 'local', 'oksigeniaaty');
    }

    if ($oldversion < 2026102505) {
        // The sign-in PIN gets its own table (so the hash is never reachable through the user-preferences web service).
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_oksigeniaaty_pin');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pinhash', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fails', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lockuntil', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN_UNIQUE, ['userid'], 'user', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026102505, 'local', 'oksigeniaaty');
    }

    if ($oldversion < 2026102517) {
        // The home layout and its CSS changed; the version is now in the cache key, so refresh mobilecssurl (after the
        // savepoint, so it picks up the new version) and the app refetches the stylesheet.
        upgrade_plugin_savepoint(true, 2026102517, 'local', 'oksigeniaaty');
        local_oksigeniaaty_refresh_appcss();
    }

    if ($oldversion < 2026102601) {
        // The home gained an upcoming-events agenda with its own CSS; refresh mobilecssurl after the savepoint so the
        // app refetches the stylesheet with the new version in its cache key.
        upgrade_plugin_savepoint(true, 2026102601, 'local', 'oksigeniaaty');
        local_oksigeniaaty_refresh_appcss();
    }

    // Any release may change the generated CSS: always point mobilecssurl at the new version, so the app and the CDN
    // refetch the stylesheet. (Campus found 0.5.21 left the 0.5.20 URL in place.)
    local_oksigeniaaty_refresh_appcss();

    return true;
}
