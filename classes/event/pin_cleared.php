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

namespace local_oksigeniaaty\event;

/**
 * The app's PIN of a user was cleared by the school, from «Send the app», so they can set a new one.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pin_cleared extends \core\event\base {
    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * The event's name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:pincleared', 'local_oksigeniaaty');
    }

    /**
     * What happened, for the logs.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' cleared the Aty PIN of the user with id '{$this->relateduserid}'.";
    }

    /**
     * Check the data the event needs.
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
    }

    /**
     * The mapping of the other data (none needed).
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }
}
