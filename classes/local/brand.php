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

/**
 * A user's own brand, on top of the site's: in a site shared by several schools or libraries (such as ayvurenda.com),
 * each person sees theirs. Other plugins say which, with a function `<component>_oksigeniaaty_user_brand(int $userid)`
 * in their lib.php that returns null or an array with `name`, `colour` (#rrggbb) and, if they have them, `logo` (a URL),
 * `tagline` and `url`.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class brand {
    /**
     * The first brand a plugin gives for a user, or null when none does.
     *
     * @param int $userid The user.
     * @return array|null ['name', 'colour', 'logo', 'tagline', 'url'], or null.
     */
    public static function for_user(int $userid): ?array {
        foreach (get_plugins_with_function('oksigeniaaty_user_brand') as $plugins) {
            foreach ($plugins as $function) {
                $brand = self::clean($function($userid));
                if ($brand !== null) {
                    return $brand;
                }
            }
        }

        return null;
    }

    /**
     * A brand as a plugin returned it, checked: a name and a valid colour, or nothing.
     *
     * @param mixed $brand What the plugin returned.
     * @return array|null
     */
    public static function clean($brand): ?array {
        $colour = is_array($brand) ? (string) ($brand['colour'] ?? '') : '';
        if (!is_array($brand) || empty($brand['name']) || !preg_match('/^#[0-9a-fA-F]{6}$/', $colour)) {
            return null;
        }

        return [
            'name' => (string) $brand['name'],
            'colour' => $colour,
            'logo' => empty($brand['logo']) ? '' : (string) $brand['logo'],
            'tagline' => (string) ($brand['tagline'] ?? ''),
            'url' => (string) ($brand['url'] ?? ''),
        ];
    }

    /**
     * A logo URL the app can show: a file of the site goes through tokenpluginfile.php with the user's own files key,
     * as the app does with any file, since the site may ask for a session to serve it.
     *
     * @param string $url The logo's URL.
     * @param int $userid The user the home is for.
     * @return string
     */
    public static function logo_for_app(string $url, int $userid): string {
        global $CFG;

        $pluginfile = $CFG->wwwroot . '/pluginfile.php/';
        if ($url === '' || strpos($url, $pluginfile) !== 0) {
            return $url;
        }

        return $CFG->wwwroot . '/tokenpluginfile.php/' . get_user_key('core_files', $userid) . '/'
            . substr($url, strlen($pluginfile));
    }
}
