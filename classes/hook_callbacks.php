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
 * Hook callbacks for local_oksigeniaaty.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Add "Open in the Aty app" (the user's own sign-in QR and button) to the user menu.
     *
     * The profile page also links to it, but a theme or a custom profile page may not show that section; the user
     * menu is on every page. Only for signed-in users who may use self-service.
     *
     * @param \core_user\hook\extend_user_menu $hook The hook.
     * @return void
     */
    public static function extend_user_menu($hook): void {
        global $CFG, $USER;

        if (!isloggedin() || isguestuser()) {
            return;
        }
        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');
        if (!local_oksigeniaaty_user_can_selfservice((int) $USER->id)) {
            return;
        }

        $url = new \moodle_url('/local/oksigeniaaty/mylink.php');
        $title = get_string('mylink:nav', 'local_oksigeniaaty');
        if (method_exists($hook, 'add_menu_item')) {
            // Moodle 5.3 and later.
            $hook->add_menu_item(new \core_user\output\user_action_menu\link($url, $title));
        } else {
            // Moodle 4.5 to 5.2 take the menu item as a plain object.
            $hook->add_navitem((object) ['itemtype' => 'link', 'url' => $url, 'title' => $title]);
        }
    }
}
