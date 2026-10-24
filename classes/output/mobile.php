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
 * Output for the Moodle app.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oksigeniaaty\output;

/**
 * Builds what the app shows: for now, the per-site home tab.
 */
class mobile {
    /**
     * The home tab of the site, rendered from the plugin settings.
     *
     * The real home (cards, shortcuts and the site CSS) is built from these settings in a later version; this skeleton
     * shows the configured heading and welcome so the handler can be wired and tried in the app.
     *
     * @param array $args The arguments the app sends (site URL, user id, language...).
     * @return array The template, javascript and data the app expects.
     */
    public static function mobile_home(array $args): array {
        global $OUTPUT;

        $config = get_config('local_oksigeniaaty');
        $heading = !empty($config->homeheading) ? $config->homeheading : format_string(get_site()->fullname);
        $intro = !empty($config->homeintro) ? $config->homeintro : '';

        $data = [
            'heading' => $heading,
            'intro' => $intro,
        ];

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('local_oksigeniaaty/mobile_home', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => [],
        ];
    }
}
