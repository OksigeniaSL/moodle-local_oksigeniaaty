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
 * Point the app's remote CSS (mobilecssurl) at this plugin's generated stylesheet, with a fresh version.
 *
 * Runs when a design setting changes and at install. The ?v= changes so the app refetches. It writes mobilecssurl only
 * when it is empty or already one of ours, so a site that set its own app CSS is left alone.
 *
 * @return void
 */
function local_oksigeniaaty_refresh_appcss(): void {
    global $CFG;

    $ours = $CFG->wwwroot . '/local/oksigeniaaty/styles_app.php';
    $current = isset($CFG->mobilecssurl) ? (string) $CFG->mobilecssurl : '';

    if ($current === '' || strpos($current, '/local/oksigeniaaty/styles_app.php') !== false) {
        set_config('mobilecssurl', $ours . '?v=' . time());
    }
}

/**
 * The home shortcuts, parsed from the setting (one "Label | https://url" per line).
 *
 * @return array[] A list of ['label' => string, 'url' => string]; malformed lines are skipped.
 */
function local_oksigeniaaty_get_shortcuts(): array {
    $raw = (string) get_config('local_oksigeniaaty', 'homeshortcuts');
    $shortcuts = [];
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '|') === false) {
            continue;
        }
        [$label, $url] = array_map('trim', explode('|', $line, 2));
        if ($label === '' || !preg_match('#^https?://#i', $url)) {
            continue;
        }
        $shortcuts[] = ['label' => $label, 'url' => $url];
    }
    return $shortcuts;
}
