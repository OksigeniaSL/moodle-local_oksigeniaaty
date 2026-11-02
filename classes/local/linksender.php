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

use local_oksigeniaaty\api;
use local_oksigeniaaty\task\send_links;

/**
 * Sends the app to people: a few at once, and a whole class through the site's scheduled tasks, so that the page does
 * not hang on the mail server.
 *
 * Whoever sends only ever starts the e-mail. The link goes to each person's own address and is never shown to the sender.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class linksender {
    /** @var int Up to this many people are sent the app at once; more, through a task. */
    public const AT_ONCE = 10;

    /** @var int How many people a task takes. */
    private const PER_TASK = 200;

    /** @var int How long before the same person can be sent another link, in seconds. */
    public const COOLDOWN = 60;

    /**
     * Send the app to some people.
     *
     * @param int[] $userids Who.
     * @return array ['sent' => how many have gone, 'failed' => how many could not go, 'queued' => how many go by task].
     */
    public static function send(array $userids): array {
        global $USER;

        if (count($userids) <= self::AT_ONCE) {
            return self::send_now($userids) + ['queued' => 0];
        }

        foreach (array_chunk($userids, self::PER_TASK) as $chunk) {
            $task = new send_links();
            $task->set_custom_data(['userids' => $chunk]);
            // So the log says who asked for each link.
            $task->set_userid((int) $USER->id);
            \core\task\manager::queue_adhoc_task($task);
        }

        return ['sent' => 0, 'failed' => 0, 'queued' => count($userids)];
    }

    /**
     * Send the app to some people, now.
     *
     * @param int[] $userids Who.
     * @return array ['sent', 'failed'].
     */
    public static function send_now(array $userids): array {
        $sent = 0;
        $failed = 0;
        foreach ($userids as $userid) {
            try {
                api::send_link((int) $userid) ? $sent++ : $failed++;
            } catch (\moodle_exception $e) {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Whether a person was sent a link just now, so that a double click does not send two.
     *
     * @param int $userid The person.
     * @return bool
     */
    public static function just_sent(int $userid): bool {
        global $DB;

        return $DB->record_exists_select(
            'user_private_key',
            'script = :script AND userid = :userid AND timecreated > :since',
            ['script' => loginkey::ISSUED_SCRIPT, 'userid' => $userid, 'since' => time() - self::COOLDOWN]
        );
    }
}
