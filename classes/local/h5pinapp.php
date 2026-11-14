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

    /** @var string The table of what apply() changed. */
    private const CHANGED = 'local_oksigeniaaty_h5pdl';

    /**
     * Turn download on for every H5P with its action bar hidden: the deployed content, and the H5P activities, whose own
     * setting Moodle copies onto the content when a teacher opens it. What it changes is kept, for undo().
     *
     * @return int How many deployed contents changed.
     */
    public static function apply(): int {
        global $DB;

        $both = self::HIDE_BAR | self::NO_DOWNLOAD;
        $where = $DB->sql_bitand('displayoptions', $both) . ' = :both';

        $changed = 0;
        foreach (['h5p', 'h5pactivity'] as $table) {
            $ids = $DB->get_fieldset_select($table, 'id', $where, ['both' => $both]);
            foreach (array_chunk($ids, 1000) as $chunk) {
                [$in, $params] = $DB->get_in_or_equal($chunk, SQL_PARAMS_NAMED);
                $params += ['download' => self::NO_DOWNLOAD, 'both' => $both];
                $DB->execute("UPDATE {{$table}} SET displayoptions = displayoptions - :download WHERE id $in AND $where", $params);

                $known = $DB->get_fieldset_select(
                    self::CHANGED,
                    'itemid',
                    "tablename = :tablename AND itemid $in",
                    $params + ['tablename' => $table]
                );
                $now = time();
                $new = array_map(
                    fn($id) => ['tablename' => $table, 'itemid' => (int) $id, 'timecreated' => $now],
                    array_diff($chunk, $known)
                );
                if ($new) {
                    $DB->insert_records(self::CHANGED, $new);
                }
            }
            if ($table === 'h5p') {
                $changed = count($ids);
            }
        }

        return $changed;
    }

    /**
     * Take as changed by apply() every H5P with its action bar hidden and download on, for the sites where it ran before
     * it kept what it changed. Without the bar, download shows nowhere on the web, so an author's own choice looks the
     * same, and turning it off again changes nothing they see.
     *
     * @return void
     */
    public static function adopt(): void {
        global $DB;

        $where = $DB->sql_bitand('displayoptions', self::HIDE_BAR | self::NO_DOWNLOAD) . ' = :hidden';
        $now = time();
        foreach (['h5p', 'h5pactivity'] as $table) {
            $ids = $DB->get_fieldset_select($table, 'id', $where, ['hidden' => self::HIDE_BAR]);
            $known = $DB->get_fieldset_select(self::CHANGED, 'itemid', 'tablename = :tablename', ['tablename' => $table]);
            $new = array_map(
                fn($id) => ['tablename' => $table, 'itemid' => (int) $id, 'timecreated' => $now],
                array_diff($ids, $known)
            );
            if ($new) {
                $DB->insert_records(self::CHANGED, $new);
            }
        }
    }

    /**
     * Turn download off again where apply() turned it on, for a site that turns the setting off: only where the action
     * bar is still hidden and download still on, as apply() left it. What a teacher has changed since stays.
     *
     * @return int How many deployed contents changed.
     */
    public static function undo(): int {
        global $DB;

        $mask = self::HIDE_BAR | self::NO_DOWNLOAD;
        $restored = 0;
        foreach (['h5p', 'h5pactivity'] as $table) {
            $ids = $DB->get_fieldset_select(self::CHANGED, 'itemid', 'tablename = :tablename', ['tablename' => $table]);
            foreach (array_chunk($ids, 1000) as $chunk) {
                [$in, $params] = $DB->get_in_or_equal($chunk, SQL_PARAMS_NAMED);
                $where = "id $in AND " . $DB->sql_bitand('displayoptions', $mask) . ' = :hidden';
                $params += ['hidden' => self::HIDE_BAR, 'download' => self::NO_DOWNLOAD];
                if ($table === 'h5p') {
                    $restored += $DB->count_records_select($table, $where, $params);
                }
                $DB->execute("UPDATE {{$table}} SET displayoptions = displayoptions + :download WHERE $where", $params);
            }
        }
        $DB->delete_records(self::CHANGED);

        return $restored;
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

        $empty = 'filtered IS NULL OR ' . $DB->sql_isempty('h5p', 'filtered', true, true);
        $stale = array_flip(array_map('intval', $DB->get_fieldset_select('h5p', 'id', $empty)));
        if (!$stale) {
            return 0;
        }

        // The export of content 51 is "<slug>-51.h5p".
        $deleted = 0;
        $files = get_file_storage()->get_area_files(\context_system::instance()->id, 'core_h5p', 'export', false, 'id', false);
        foreach ($files as $file) {
            if (preg_match('/-(\d+)\.h5p$/', $file->get_filename(), $m) && isset($stale[(int) $m[1]])) {
                $file->delete();
                $deleted++;
            }
        }

        return $deleted;
    }
}
