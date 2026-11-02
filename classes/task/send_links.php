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

namespace local_oksigeniaaty\task;

use local_oksigeniaaty\local\linkroster;
use local_oksigeniaaty\local\linksender;

/**
 * Sends the app to a group of people. It looks again at how each one stands, so nobody gets it twice.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_links extends \core\task\adhoc_task {
    /**
     * The task's name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendapp:task', 'local_oksigeniaaty');
    }

    /**
     * Send them.
     */
    public function execute() {
        global $DB;

        $userids = array_map('intval', (array) ($this->get_custom_data()->userids ?? []));
        if (!$userids) {
            return;
        }

        [$in, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $fields = implode(', ', \core_user\fields::get_picture_fields());
        $users = $DB->get_records_select('user', "id $in AND deleted = 0 AND suspended = 0", $params, '', $fields);
        $result = linksender::send_now(linkroster::waiting(linkroster::rows($users)));
        mtrace("Sent: {$result['sent']}. Failed: {$result['failed']}.");
    }
}
