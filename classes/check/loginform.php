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

namespace local_oksigeniaaty\check;

use core\check\check;
use core\check\result;

/**
 * Whether the app offers the username and password form when the web does.
 *
 * The app follows Moodle's "Display manual login form" (showloginform), which the site sends it in its public config.
 * Some themes ignore that setting and show the form on the web through their own switch: Boost Union does. With the
 * core setting off and the theme's switch on, people with a manual account log in on the web and cannot in the app,
 * which only offers the identity providers.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class loginform extends check {
    /**
     * The check's name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('check:loginform', 'local_oksigeniaaty');
    }

    /**
     * Where the core setting is.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/settings.php', ['section' => 'manageauths']),
            get_string('authsettings', 'admin')
        );
    }

    /**
     * Compare what the web's login page shows with what the app is told.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        // The same value tool_mobile sends to the app.
        if ((int) get_config('core', 'showloginform') !== 0) {
            return new result(result::OK, get_string('check:loginform_same', 'local_oksigeniaaty'));
        }

        $theme = self::form_theme($CFG->theme);
        if ($theme === null) {
            return new result(result::OK, get_string('check:loginform_hidden', 'local_oksigeniaaty'));
        }

        $sm = get_string_manager();
        return new result(result::WARNING, get_string('check:loginform_differs', 'local_oksigeniaaty', [
            'theme' => $sm->string_exists('pluginname', 'theme_' . $theme) ? get_string('pluginname', 'theme_' . $theme) : $theme,
            'setting' => get_string('showloginform', 'core_auth'),
        ]));
    }

    /**
     * The theme, among the given one and its parents, that shows the login form on its own switch, if it is on.
     *
     * @param string $theme The site's theme.
     * @return string|null The name of the theme that shows the form, or null when the web follows the core setting.
     */
    public static function form_theme(string $theme): ?string {
        $chain = [$theme];
        if (\core_component::get_component_directory('theme_' . $theme)) {
            $chain = array_merge($chain, \theme_config::load($theme)->parents);
        }

        // Boost Union does not read showloginform: its "Local login" (on unless set to "no") is the only switch.
        if (in_array('boost_union', $chain, true)) {
            $locallogin = get_config('theme_boost_union', 'loginlocalloginenable');
            if (($locallogin ?: 'yes') === 'yes') {
                return 'boost_union';
            }
        }

        return null;
    }
}
