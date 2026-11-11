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

use local_oksigeniaaty\local\h5pinapp;

/**
 * Keeps new and edited H5P content playable in the app (see h5pinapp): a teacher's new activity, or an embed whose
 * settings Moodle copies back when a teacher opens it.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class h5p_in_app extends \core\task\scheduled_task {
    /**
     * The task's name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:h5pinapp', 'local_oksigeniaaty');
    }

    /**
     * Turn download on where it is invisible.
     */
    public function execute() {
        if (!h5pinapp::enabled()) {
            return;
        }

        mtrace('H5P content made playable in the app: ' . h5pinapp::apply() . '.');
    }
}
