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
 * Whether this site is ready for the Aty app: what has to be set outside the plugin for the app to open it, sign people
 * in without a password and bring them notices. Each missing piece fails without a word somewhere else (the app refuses
 * the site, links open Moodle's own app, notices never arrive), so the site's administrator, the hosting's weekly round
 * and its monitor read it here. No value of a secret is shown.
 *
 * A site that is not on Oksigenia's list and does not ask for Aty uses Moodle's own app: then there is nothing to check.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class appready extends check {
    /** The list of the sites the app opens: SHA-256 of each host. */
    public const LIST_URL = 'https://aravo.oksigenia.com/aty/sitios.json';

    /** The app's identifier in the stores, which Moodle and the push bridge key the devices by. */
    public const APPID = 'com.oksigenia.aty';

    /** The app's URL scheme. */
    public const SCHEME = 'oksigeniaaty';

    /** The most seconds between two automatic sign-ins on the web from the app (Moodle's default is 6 minutes). */
    public const AUTOLOGIN_SECONDS = 10;

    /**
     * The check's name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('check:appready', 'local_oksigeniaaty');
    }

    /**
     * Where most of it is set.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/settings.php', ['section' => 'mobileapp']),
            get_string('check:appready_settings', 'local_oksigeniaaty')
        );
    }

    /**
     * Go through what the app needs.
     *
     * @return result
     */
    public function get_result(): result {
        $listed = self::listed();
        $wantsaty = get_config('tool_mobile', 'forcedurlscheme') === self::SCHEME;
        if ($listed === false && !$wantsaty) {
            return new result(result::NA, get_string('check:appready_official', 'local_oksigeniaaty'));
        }

        [$missing, $warnings] = self::problems($listed);
        $component = 'local_oksigeniaaty';
        $items = array_merge(
            array_map(fn($key) => get_string('check:appready_' . $key, $component), $missing),
            array_map(fn($key) => get_string('check:appready_' . $key, $component), $warnings)
        );
        $details = $items ? \html_writer::alist($items) : '';
        if ($missing) {
            return new result(result::ERROR, get_string('check:appready_missing', $component), $details);
        }
        if ($warnings) {
            return new result(result::WARNING, get_string('check:appready_warnings', $component), $details);
        }

        return new result(result::OK, get_string('check:appready_ok', $component), $details);
    }

    /**
     * What is missing (the app does not work) and what is not as it should be (something fails quietly).
     *
     * @param bool|null $listed Whether the site is on Oksigenia's list; null when the list could not be read.
     * @return array [missing keys, warning keys]
     */
    public static function problems(?bool $listed): array {
        global $CFG, $DB;

        $missing = [];
        $warnings = [];
        if ($listed === false) {
            $missing[] = 'notlisted';
        } else if ($listed === null) {
            $warnings[] = 'listunknown';
        }
        if (!self::mobile_service_works()) {
            $missing[] = 'mobileservice';
        }
        if (get_config('tool_mobile', 'forcedurlscheme') !== self::SCHEME) {
            $missing[] = 'scheme';
        }
        if (get_config('tool_mobile', 'androidappid') !== self::APPID) {
            $missing[] = 'androidappid';
        }
        if ((int) get_config('tool_mobile', 'typeoflogin') > 1) {
            $missing[] = 'typeoflogin';
        }
        if (empty($CFG->airnotifierurl) || empty($CFG->airnotifierappname) || empty($CFG->airnotifieraccesskey)) {
            $missing[] = 'push';
        } else if (($CFG->airnotifiermobileappname ?? '') !== self::APPID) {
            $missing[] = 'pushapp';
        }

        $autologin = get_config('tool_mobile', 'autologinmintimebetweenreq');
        if ($autologin === false || (int) $autologin > self::AUTOLOGIN_SECONDS) {
            $warnings[] = 'autologin';
        }
        if (strpos((string) get_config('core', 'mobilecssurl'), '/local/oksigeniaaty/styles_app.php') === false) {
            $warnings[] = 'css';
        }
        if (get_config('local_oksigeniaaty', 'familyapp') && !\local_oksigeniaaty\local\familyaccount::available()) {
            $warnings[] = 'familyapp';
        }
        if (
            class_exists(\local_oksigeniafamily\api::class)
            && !get_config('local_oksigeniafamily', 'parentroleid')
            && $DB->record_exists('local_oksigeniafamily_family', ['status' => 'active'])
        ) {
            $warnings[] = 'parentrole';
        }

        return [$missing, $warnings];
    }

    /**
     * Whether the app can really use the mobile service, as Moodle's own settings page tells it: the switch can be on
     * (set in config.php or from the command line) with the service off, without REST, or without the right to use REST,
     * and then the app cannot sign in.
     *
     * @return bool
     */
    public static function mobile_service_works(): bool {
        global $CFG;

        if (empty($CFG->enablewebservices) || empty($CFG->enablemobilewebservice)) {
            return false;
        }
        require_once($CFG->libdir . '/adminlib.php');
        $setting = new \admin_setting_enablemobileservice('enablemobilewebservice', '', '', 0);
        $protocols = empty($CFG->webserviceprotocols) ? [] : explode(',', $CFG->webserviceprotocols);

        return (string) $setting->get_setting() === '1' && in_array('rest', $protocols, true);
    }

    /**
     * Whether this site is on the list of the sites the app opens. Read once an hour at most.
     *
     * @return bool|null True or false; null when the list could not be read.
     */
    public static function listed(): ?bool {
        global $CFG;

        $host = strtolower(rtrim((string) parse_url($CFG->wwwroot, PHP_URL_HOST), '.'));
        if ($host === '') {
            return null;
        }
        $cache = \cache::make('local_oksigeniaaty', 'sitelist');
        $sites = $cache->get('sites');
        if ($sites === false) {
            require_once($CFG->libdir . '/filelib.php');
            $curl = new \curl();
            $json = $curl->get(self::LIST_URL, [], ['CURLOPT_TIMEOUT' => 5, 'CURLOPT_CONNECTTIMEOUT' => 3]);
            $list = $curl->get_errno() ? null : json_decode((string) $json, true);
            if (!is_array($list) || !isset($list['sites']) || !is_array($list['sites'])) {
                return null;
            }
            $sites = array_values(array_filter($list['sites'], 'is_string'));
            $cache->set('sites', $sites);
        }

        return in_array(hash('sha256', $host), $sites, true);
    }
}
