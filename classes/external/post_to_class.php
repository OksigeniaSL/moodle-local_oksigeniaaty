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

namespace local_oksigeniaaty\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forum/externallib.php');

/**
 * Web service: a few plain words to everyone in a course, from the phone. They go to the course's announcements, with
 * Moodle's own rules on who may post, and leave at once rather than after the time for editing, as this has no editing.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_to_class extends external_api {
    /**
     * The announcements forum, a title and the words.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'forumid' => new external_value(PARAM_INT, 'The course\'s announcements forum'),
            'subject' => new external_value(PARAM_TEXT, 'The title'),
            'message' => new external_value(PARAM_RAW, 'The message, in plain text'),
        ]);
    }

    /**
     * Publish it.
     *
     * @param int $forumid The forum.
     * @param string $subject The title.
     * @param string $message The message, in plain text.
     * @return array ['discussionid' => int]
     */
    public static function execute(int $forumid, string $subject, string $message): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'forumid' => $forumid,
            'subject' => $subject,
            'message' => $message,
        ]);
        $forum = $DB->get_record('forum', ['id' => $params['forumid'], 'type' => 'news'], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('forum', $forum->id, $forum->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        $subject = trim($params['subject']);
        $message = trim($params['message']);
        if ($subject === '' || $message === '') {
            throw new \invalid_parameter_exception('A title and a message are needed.');
        }

        // Whether this person may post here, and all the rest, is the forum's own decision.
        $result = \mod_forum_external::add_discussion($forum->id, $subject, nl2br(s($message)));
        if (has_capability('mod/forum:canmailnow', $context)) {
            $DB->set_field('forum_posts', 'mailnow', 1, ['discussion' => $result['discussionid']]);
        }

        return ['discussionid' => (int) $result['discussionid']];
    }

    /**
     * The return description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'discussionid' => new external_value(PARAM_INT, 'The new discussion'),
        ]);
    }
}
