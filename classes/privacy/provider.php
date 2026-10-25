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
 * Privacy provider.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oksigeniaaty\privacy;

use context;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_oksigeniaaty\local\loginkey;
use local_oksigeniaaty\local\pin;

/**
 * The plugin keeps two bits of personal data: the one-time sign-in keys it issues, and the app PIN. Both are the
 * user's own and live in the user context.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the personal data the plugin stores.
     *
     * @param collection $collection The collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_oksigeniaaty_pin', [
            'userid' => 'privacy:metadata:pin:userid',
            'pinhash' => 'privacy:metadata:pin:pinhash',
            'fails' => 'privacy:metadata:pin:fails',
            'lockuntil' => 'privacy:metadata:pin:lockuntil',
            'timemodified' => 'privacy:metadata:pin:timemodified',
        ], 'privacy:metadata:pin');

        $collection->add_database_table('user_private_key', [
            'userid' => 'privacy:metadata:loginkey:userid',
            'validuntil' => 'privacy:metadata:loginkey:validuntil',
            'timecreated' => 'privacy:metadata:loginkey:timecreated',
        ], 'privacy:metadata:loginkey');

        return $collection;
    }

    /**
     * The contexts (the user's own) that hold data for a user.
     *
     * @param int $userid The user id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        $haspin = $DB->record_exists('local_oksigeniaaty_pin', ['userid' => $userid]);
        $haskey = $DB->record_exists('user_private_key', ['script' => loginkey::SCRIPT, 'userid' => $userid]);
        if ($haspin || $haskey) {
            $contextlist->add_user_context($userid);
        }

        return $contextlist;
    }

    /**
     * The users who have data in a context.
     *
     * @param userlist $userlist The userlist, already scoped to one context.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_user) {
            return;
        }

        $userid = $context->instanceid;
        $haspin = $DB->record_exists('local_oksigeniaaty_pin', ['userid' => $userid]);
        $haskey = $DB->record_exists('user_private_key', ['script' => loginkey::SCRIPT, 'userid' => $userid]);
        if ($haspin || $haskey) {
            $userlist->add_user($userid);
        }
    }

    /**
     * Export a user's data (never the PIN hash nor the key value, which are secrets).
     *
     * @param approved_contextlist $contextlist The approved contexts to export.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_user || $context->instanceid != $user->id) {
                continue;
            }

            $pinrecord = $DB->get_record('local_oksigeniaaty_pin', ['userid' => $user->id]);
            if ($pinrecord) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:metadata:pin', 'local_oksigeniaaty')],
                    (object) [
                        'pinset' => transform::yesno(true),
                        'lockuntil' => $pinrecord->lockuntil ? transform::datetime($pinrecord->lockuntil) : '-',
                        'timemodified' => transform::datetime($pinrecord->timemodified),
                    ]
                );
            }

            $keys = $DB->get_records('user_private_key', ['script' => loginkey::SCRIPT, 'userid' => $user->id]);
            if ($keys) {
                $rows = [];
                foreach ($keys as $key) {
                    $rows[] = (object) [
                        'validuntil' => $key->validuntil ? transform::datetime($key->validuntil) : '-',
                        'timecreated' => transform::datetime($key->timecreated),
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('privacy:metadata:loginkey', 'local_oksigeniaaty')],
                    (object) ['keys' => $rows]
                );
            }
        }
    }

    /**
     * Delete all plugin data in a context.
     *
     * @param context $context The context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        if ($context instanceof context_user) {
            self::purge($context->instanceid);
        }
    }

    /**
     * Delete a user's data in the approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_user && $context->instanceid == $user->id) {
                self::purge($user->id);
            }
        }
    }

    /**
     * Delete data for several users in a context.
     *
     * @param approved_userlist $userlist The approved users, scoped to one context.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_user) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            if ($userid == $context->instanceid) {
                self::purge($userid);
            }
        }
    }

    /**
     * Remove every trace of a user: their PIN and their sign-in keys.
     *
     * @param int $userid The user id.
     * @return void
     */
    protected static function purge(int $userid): void {
        global $DB;

        pin::delete($userid);
        $DB->delete_records('user_private_key', ['script' => loginkey::SCRIPT, 'userid' => $userid]);
    }
}
