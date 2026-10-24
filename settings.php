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
 * Administration: the design each site shows in the Oksigenia Aty app (and in the official Moodle app).
 *
 * These settings feed the per-site home tab and the remote CSS. Nothing here is tied to a single client: each hosted
 * Moodle keeps its own values, and the one app dresses itself with them.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_oksigeniaaty',
        new lang_string('pluginname', 'local_oksigeniaaty')
    );
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'local_oksigeniaaty/designintro',
            new lang_string('settings:design', 'local_oksigeniaaty'),
            new lang_string('settings:design_desc', 'local_oksigeniaaty')
        ));

        // The brand colour of the site, used for bars, buttons and accents in the app.
        // When it changes, the app's remote CSS (mobilecssurl) is regenerated with a fresh version.
        $brandcolour = new admin_setting_configcolourpicker(
            'local_oksigeniaaty/brandcolour',
            new lang_string('settings:brandcolour', 'local_oksigeniaaty'),
            new lang_string('settings:brandcolour_desc', 'local_oksigeniaaty'),
            '#0a66c2'
        );
        $brandcolour->set_updatedcallback('local_oksigeniaaty_refresh_appcss');
        $settings->add($brandcolour);

        // The heading shown at the top of the home tab.
        $settings->add(new admin_setting_configtext(
            'local_oksigeniaaty/homeheading',
            new lang_string('settings:homeheading', 'local_oksigeniaaty'),
            new lang_string('settings:homeheading_desc', 'local_oksigeniaaty'),
            '',
            PARAM_TEXT
        ));

        // A short welcome shown under the heading.
        $settings->add(new admin_setting_configtextarea(
            'local_oksigeniaaty/homeintro',
            new lang_string('settings:homeintro', 'local_oksigeniaaty'),
            new lang_string('settings:homeintro_desc', 'local_oksigeniaaty'),
            '',
            PARAM_TEXT
        ));

        // The home shortcuts, one "Label | https://url" per line.
        $settings->add(new admin_setting_configtextarea(
            'local_oksigeniaaty/homeshortcuts',
            new lang_string('settings:homeshortcuts', 'local_oksigeniaaty'),
            new lang_string('settings:homeshortcuts_desc', 'local_oksigeniaaty'),
            '',
            PARAM_RAW
        ));
    }
}
