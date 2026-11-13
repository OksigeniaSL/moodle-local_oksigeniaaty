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
 * The pages of the site that work better in the phone's own browser than inside the app.
 *
 * Inside the app a page lives in its in-app browser, which has no share sheet, cannot save a file made on the page, does
 * not hand a calendar file or a WhatsApp or Telegram link to its app, and has less memory for a long video. A plugin
 * whose pages need that says which through `<component>_oksigeniaaty_browser_pages(): string[]` in its lib.php: paths
 * from the site's root, a page ('/local/example/share.php') or a whole folder ('/local/example/public/'). On those pages
 * the "open in the app" bar does not show, and a row, a tile or a shortcut of the app's home that leads to one opens it
 * in the phone's browser.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class browserpages {
    /** @var string[]|null The paths the plugins gave, once per request. */
    private static ?array $paths = null;

    /**
     * The paths the plugins ask to open in the phone's browser. A plugin that fails or gives something else adds nothing.
     *
     * @return string[]
     */
    public static function paths(): array {
        if (self::$paths !== null) {
            return self::$paths;
        }

        $paths = [];
        foreach (get_plugins_with_function('oksigeniaaty_browser_pages') as $plugins) {
            foreach ($plugins as $function) {
                try {
                    $given = $function();
                } catch (\Throwable $e) {
                    debugging($function . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                    continue;
                }
                foreach (is_array($given) ? $given : [] as $path) {
                    if (is_string($path) && preg_match('#^/[^\s?\#]+$#', $path)) {
                        $paths[] = $path;
                    }
                }
            }
        }

        return self::$paths = array_values(array_unique($paths));
    }

    /**
     * Whether an address is one of those pages: one of this site's, whose path is a given page or lies in a given folder.
     *
     * @param string $url The address, full or from the site's root.
     * @param string[]|null $paths The paths to check against; the plugins' when null.
     * @return bool
     */
    public static function matches(string $url, ?array $paths = null): bool {
        global $CFG;

        $target = \local_oksigeniaaty\api::local_target($url);
        if ($target === null) {
            return false;
        }
        $path = (string) parse_url($target, PHP_URL_PATH);
        $root = rtrim((string) parse_url($CFG->wwwroot, PHP_URL_PATH), '/');
        if ($root !== '') {
            if (strpos($path, $root . '/') !== 0) {
                return false;
            }
            $path = substr($path, strlen($root));
        }

        foreach ($paths ?? self::paths() as $given) {
            if ($path === $given || (substr($given, -1) === '/' && strpos($path, $given) === 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Forget the paths read in this request, for the tests.
     *
     * @return void
     */
    public static function reset(): void {
        self::$paths = null;
    }
}
