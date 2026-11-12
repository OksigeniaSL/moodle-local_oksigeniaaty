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
 * Lets the app play the site's H5P content on the phone itself.
 *
 * The app plays an H5P from its package, which Moodle only serves while "Allow download" is on for that content. With it
 * off, the app shows the content of the site in a frame instead: that works on Android, but on an iPhone the frame does
 * not carry the session and shows the login page. So, for content whose action bar is hidden, download is turned on:
 * without the bar there is no download button on the web, so nothing changes for the people of the site, and the app
 * can play it, offline too. Content that shows its action bar is left as it is, since there turning download on would
 * add a button that the teacher did not choose.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class h5pinapp {
    /** The bit of displayoptions that hides the action bar (H5PCore::DISABLE_FRAME). */
    public const HIDE_BAR = 1;

    /** The bit of displayoptions that turns download off (H5PCore::DISABLE_DOWNLOAD). */
    public const NO_DOWNLOAD = 2;

    /**
     * Whether the site wants it (on unless turned off).
     *
     * @return bool
     */
    public static function enabled(): bool {
        return (string) get_config('local_oksigeniaaty', 'h5pinapp') !== '0';
    }

    /**
     * Turn download on for every H5P with its action bar hidden: the deployed content, and the H5P activities, whose own
     * setting Moodle copies onto the content when a teacher opens it.
     *
     * @return int How many deployed contents changed.
     */
    public static function apply(): int {
        global $DB;

        $both = self::HIDE_BAR | self::NO_DOWNLOAD;
        $where = $DB->sql_bitand('displayoptions', $both) . ' = :both';

        $changed = $DB->count_records_select('h5p', $where, ['both' => $both]);
        foreach (['h5p', 'h5pactivity'] as $table) {
            $DB->execute(
                "UPDATE {{$table}} SET displayoptions = displayoptions - :download WHERE $where",
                ['download' => self::NO_DOWNLOAD, 'both' => $both]
            );
        }

        return $changed;
    }

    /**
     * Delete the exported package of every H5P whose filtered content Moodle has emptied: it does so when one of the
     * content's libraries gets a new version, or the content changes. The web makes the package again on the next view,
     * but the apps ask for the package itself and would go on playing the old one, with the old library. Without it,
     * the app asks Moodle for the package, and Moodle makes it then.
     *
     * @return int How many packages were deleted.
     */
    public static function refresh_exports(): int {
        global $DB;

        // The export of content 51 is "<slug>-51.h5p": the dash keeps 51 from matching 151.
        $named = $DB->sql_like('f.filename', $DB->sql_concat("'%-'", 'h.id', "'.h5p'"));
        $empty = 'h.filtered IS NULL OR ' . $DB->sql_isempty('h5p', 'h.filtered', true, true);
        $sql = "SELECT f.id
                  FROM {files} f
                  JOIN {h5p} h ON $named
                 WHERE f.component = :component AND f.filearea = :filearea AND f.filename <> '.' AND ($empty)";

        $fs = get_file_storage();
        $deleted = 0;
        foreach ($DB->get_fieldset_sql($sql, ['component' => 'core_h5p', 'filearea' => 'export']) as $fileid) {
            if ($file = $fs->get_file_by_id((int) $fileid)) {
                $file->delete();
                $deleted++;
            }
        }

        return $deleted;
    }
}
