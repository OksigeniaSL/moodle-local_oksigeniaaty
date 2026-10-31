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

    // The site's QR code in its brand, to print or download for posters and letters.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_oksigeniaaty_centreqr',
        new lang_string('centreqr:menu', 'local_oksigeniaaty'),
        new moodle_url('/local/oksigeniaaty/centreqr.php'),
        'moodle/site:config'
    ));

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

        // A white logo shown over the brand hero on the home tab.
        $settings->add(new admin_setting_configtext(
            'local_oksigeniaaty/homelogourl',
            new lang_string('settings:homelogourl', 'local_oksigeniaaty'),
            new lang_string('settings:homelogourl_desc', 'local_oksigeniaaty'),
            '',
            PARAM_URL
        ));

        // The home shortcuts, one "Label | https://url | fas-icon" per line.
        $settings->add(new admin_setting_configtextarea(
            'local_oksigeniaaty/homeshortcuts',
            new lang_string('settings:homeshortcuts', 'local_oksigeniaaty'),
            new lang_string('settings:homeshortcuts_desc', 'local_oksigeniaaty'),
            '',
            PARAM_RAW
        ));

        // Extra CSS for the app, served after the brand skin. On a Boost Union site its "Additional CSS for Mobile app"
        // box is used instead and this is ignored; it is here for sites without Boost Union. Regenerates the app CSS.
        $extracss = new admin_setting_configtextarea(
            'local_oksigeniaaty/extracss',
            new lang_string('settings:extracss', 'local_oksigeniaaty'),
            new lang_string('settings:extracss_desc', 'local_oksigeniaaty'),
            '',
            PARAM_RAW
        );
        $extracss->set_updatedcallback('local_oksigeniaaty_refresh_appcss');
        $settings->add($extracss);

        // App sign-in: store links shown on the bridge page when the app is not installed.
        $settings->add(new admin_setting_heading(
            'local_oksigeniaaty/signin',
            new lang_string('settings:signin', 'local_oksigeniaaty'),
            new lang_string('settings:signin_desc', 'local_oksigeniaaty')
        ));

        $settings->add(new admin_setting_configtext(
            'local_oksigeniaaty/androidstoreurl',
            new lang_string('settings:androidstoreurl', 'local_oksigeniaaty'),
            new lang_string('settings:androidstoreurl_desc', 'local_oksigeniaaty'),
            'https://play.google.com/store/apps/details?id=com.oksigenia.aty',
            PARAM_URL
        ));

        // On Android phones, a bar on every page offers to open that page in the app. Off until the app is announced.
        $settings->add(new admin_setting_configcheckbox(
            'local_oksigeniaaty/openinappbar',
            new lang_string('settings:openinappbar', 'local_oksigeniaaty'),
            new lang_string('settings:openinappbar_desc', 'local_oksigeniaaty'),
            0
        ));

        $settings->add(new admin_setting_configtext(
            'local_oksigeniaaty/iosstoreurl',
            new lang_string('settings:iosstoreurl', 'local_oksigeniaaty'),
            new lang_string('settings:iosstoreurl_desc', 'local_oksigeniaaty'),
            '',
            PARAM_URL
        ));

        // Which cohorts may use passwordless self-service sign-in. Nothing selected means everyone.
        $cohorts = $DB->get_records_menu('cohort', null, 'name ASC', 'id, name');
        $settings->add(new admin_setting_configmultiselect(
            'local_oksigeniaaty/selfservicecohorts',
            new lang_string('settings:selfservicecohorts', 'local_oksigeniaaty'),
            new lang_string('settings:selfservicecohorts_desc', 'local_oksigeniaaty'),
            [],
            $cohorts
        ));
    }
}
