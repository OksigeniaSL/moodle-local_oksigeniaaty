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

/**
 * Callbacks and helpers for local_oksigeniaaty.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The URL of this plugin's app stylesheet (without the cache-busting version).
 *
 * @return string
 */
function local_oksigeniaaty_cssurl(): string {
    global $CFG;

    return $CFG->wwwroot . '/local/oksigeniaaty/styles_app.php';
}

/**
 * The client's own extra app CSS, served after the brand skin so the site keeps its course styles.
 *
 * It is the Boost Union "Additional CSS for Mobile app" setting (theme_boost_union/mobilescss) when that theme is
 * installed and the box is filled, otherwise this plugin's own "extracss" setting. Empty when neither is set.
 *
 * @return string
 */
function local_oksigeniaaty_client_css(): string {
    global $CFG;

    if (file_exists($CFG->dirroot . '/theme/boost_union/version.php')) {
        $boostunion = (string) get_config('theme_boost_union', 'mobilescss');
        if (trim($boostunion) !== '') {
            return $boostunion;
        }
    }

    return (string) get_config('local_oksigeniaaty', 'extracss');
}

/**
 * A short content hash for the app CSS, so mobilecssurl's ?v= changes only when the design or the client CSS changes.
 *
 * @return string
 */
function local_oksigeniaaty_appcss_version(): string {
    $brand = (string) get_config('local_oksigeniaaty', 'brandcolour');
    // The plugin version is in the hash too, so a release that only changes the generated CSS still busts the cache
    // (the app and Cloudflare refetch) without needing a brand or client-CSS change.
    $version = (string) get_config('local_oksigeniaaty', 'version');

    return substr(sha1($version . "\n" . $brand . "\n" . local_oksigeniaaty_client_css()), 0, 12);
}

/**
 * Point the app's remote CSS (mobilecssurl) at this plugin, which serves the brand skin plus the client's own app CSS.
 *
 * Runs when a design setting changes, at install, and from the observer when Boost Union rewrites mobilecssurl. The
 * plugin takes over from an empty value, from its own URL (to refresh the version) and from Boost Union's styles.php,
 * because its stylesheet now carries that CSS too; any other deliberate value is left alone. The ?v= is a content hash,
 * so the app refetches only when something changed.
 *
 * @return void
 */
function local_oksigeniaaty_refresh_appcss(): void {
    $target = local_oksigeniaaty_cssurl() . '?v=' . local_oksigeniaaty_appcss_version();
    $current = (string) get_config('core', 'mobilecssurl');

    $manageable = $current === ''
        || strpos($current, '/local/oksigeniaaty/styles_app.php') !== false
        || strpos($current, '/theme/boost_union/mobile/styles.php') !== false;

    if ($manageable && $current !== $target) {
        set_config('mobilecssurl', $target);
    }
}

/**
 * The home shortcuts, parsed from the setting (one "Label | https://url | fas-icon" per line).
 *
 * The icon is optional (a default is used). A shortcut is "external" when its URL does not point at this site, so the
 * app marks the ones that leave it.
 *
 * @return array[] A list of ['label', 'url', 'icon', 'external']; malformed lines are skipped.
 */
function local_oksigeniaaty_get_shortcuts(): array {
    global $CFG;

    $raw = (string) get_config('local_oksigeniaaty', 'homeshortcuts');
    $shortcuts = [];
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '|') === false) {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        $label = $parts[0] ?? '';
        $url = $parts[1] ?? '';
        $icon = $parts[2] ?? '';
        if ($label === '' || !preg_match('#^https?://#i', $url)) {
            continue;
        }
        // Only solid Font Awesome names the app ships (fas-*); anything else falls back to a neutral icon.
        if (!preg_match('/^fas-[a-z0-9-]+$/', $icon)) {
            $icon = 'fas-circle-chevron-right';
        }
        $shortcuts[] = [
            'label' => $label,
            'url' => $url,
            'icon' => $icon,
            'external' => strpos($url, $CFG->wwwroot) !== 0,
        ];
    }
    return $shortcuts;
}

/**
 * Whether a user may use passwordless self-service sign-in (get their own link or QR).
 *
 * Gated by the selfservicecohorts setting: empty means everyone, otherwise only members of the listed cohorts. This
 * lets a site open self-service to, say, staff and older students only. Staff-issued links are not affected.
 *
 * @param int $userid The user id.
 * @return bool
 */
function local_oksigeniaaty_user_can_selfservice(int $userid): bool {
    global $DB;

    $setting = trim((string) get_config('local_oksigeniaaty', 'selfservicecohorts'));
    if ($setting === '') {
        return true;
    }

    $cohortids = array_values(array_filter(array_map('intval', explode(',', $setting))));
    if (empty($cohortids)) {
        return true;
    }

    [$insql, $params] = $DB->get_in_or_equal($cohortids, SQL_PARAMS_NAMED);
    $params['userid'] = $userid;

    return $DB->record_exists_select('cohort_members', "cohortid $insql AND userid = :userid", $params);
}

/**
 * Add a link to a user's own Aty sign-in page on their profile (self-service).
 *
 * @param \core_user\output\myprofile\tree $tree The profile tree.
 * @param stdClass $user The user whose profile is shown.
 * @param bool $iscurrentuser Whether it is the current user's own profile.
 * @param stdClass $course The course, if any.
 * @return void
 */
function local_oksigeniaaty_myprofile_navigation(
    \core_user\output\myprofile\tree $tree,
    $user,
    $iscurrentuser,
    $course
): void {
    if (!$iscurrentuser) {
        return;
    }

    $userid = (int) $user->id;

    if (local_oksigeniaaty_user_can_selfservice($userid)) {
        $tree->add_node(new \core_user\output\myprofile\node(
            'miscellaneous',
            'oksigeniaatylink',
            get_string('mylink:nav', 'local_oksigeniaaty'),
            null,
            new moodle_url('/local/oksigeniaaty/mylink.php')
        ));
    }

    // Let a user who set a PIN clear it (the secure "forgot my PIN" path, since this needs their web password).
    if (\local_oksigeniaaty\local\pin::has_pin($userid)) {
        $tree->add_node(new \core_user\output\myprofile\node(
            'miscellaneous',
            'oksigeniaatyresetpin',
            get_string('resetpin:nav', 'local_oksigeniaaty'),
            null,
            new moodle_url('/local/oksigeniaaty/resetpin.php')
        ));
    }
}
