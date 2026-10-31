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

namespace local_oksigeniaaty;

/**
 * Hook callbacks for local_oksigeniaaty.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /** The cookie that hides the "open in the app" bar on this browser. */
    public const BAR_COOKIE = 'local_oksigeniaaty_bar';

    /** Page layouts where the bar never goes (no room, or not a page a person reads). */
    private const BAR_SKIP_LAYOUTS = ['embedded', 'popup', 'frametop', 'print', 'redirect', 'maintenance', 'secure'];

    /**
     * Add "Open in the Aty app" (the user's own sign-in QR and button) to the user menu.
     *
     * The profile page also links to it, but a theme or a custom profile page may not show that section; the user
     * menu is on every page. Only for signed-in users who may use self-service.
     *
     * @param \core_user\hook\extend_user_menu $hook The hook.
     * @return void
     */
    public static function extend_user_menu($hook): void {
        global $CFG, $USER;

        if (!isloggedin() || isguestuser()) {
            return;
        }
        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');
        if (!local_oksigeniaaty_user_can_selfservice((int) $USER->id)) {
            return;
        }

        $url = new \moodle_url('/local/oksigeniaaty/mylink.php');
        $title = get_string('mylink:nav', 'local_oksigeniaaty');
        if (method_exists($hook, 'add_menu_item')) {
            // Moodle 5.3 and later.
            $hook->add_menu_item(new \core_user\output\user_action_menu\link($url, $title));
        } else {
            // Moodle 4.5 to 5.2 take the menu item as a plain object.
            $hook->add_navitem((object) ['itemtype' => 'link', 'url' => $url, 'title' => $title]);
        }
    }

    /**
     * Offer, on Android phones, to open the page being read in the site's app (Aty, or Moodle's own).
     *
     * Every link that ends up in the phone's browser (an e-mail, a WhatsApp notice, a link someone shares) can then go
     * on in the app, on that same page.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook The hook.
     * @return void
     */
    public static function before_standard_top_of_body_html($hook): void {
        $html = self::openinapp_bar();
        if ($html !== '') {
            $hook->add_html($html);
        }
    }

    /**
     * The "open this page in the app" bar, or '' where it does not apply.
     *
     * Only when the site turns it on, on Android (there is no Aty for iPhone yet), outside the app itself (its in-app
     * browser says so in its user agent) and until the person hides it. It opens whichever app the site uses (the URL
     * scheme and Android package set in the site's mobile app settings: Aty's, or Moodle's own) with Chrome's intent
     * link, which goes to that app's store page when it is not installed.
     *
     * @return string The HTML.
     */
    public static function openinapp_bar(): string {
        global $CFG, $OUTPUT, $PAGE;

        if (
            during_initial_install() || empty($CFG->enablemobilewebservice)
            || !get_config('local_oksigeniaaty', 'openinappbar')
        ) {
            return '';
        }
        $useragent = (string) \core_useragent::get_user_agent_string();
        if (stripos($useragent, 'Android') === false || \core_useragent::is_moodle_app()) {
            return '';
        }
        if (!empty($_COOKIE[self::BAR_COOKIE]) || in_array($PAGE->pagelayout, self::BAR_SKIP_LAYOUTS, true)) {
            return '';
        }
        $pageurl = $PAGE->has_set_url() ? $PAGE->url : new \moodle_url('/');
        if (strpos($pageurl->get_path(), '/local/oksigeniaaty/') !== false) {
            // The plugin's own pages are already about the app.
            return '';
        }
        // Signing in or out makes no sense in the app, which has its own way in: those pages open the site's home there.
        $applink = strpos($pageurl->get_path(), '/login/') !== false ? new \moodle_url('/') : $pageurl;

        $scheme = get_config('tool_mobile', 'forcedurlscheme') ?: 'moodlemobile';
        $package = get_config('tool_mobile', 'androidappid') ?: 'com.moodle.moodlemobile';
        $intent = 'intent://link=' . preg_replace('/#.*$/', '', $applink->out(false))
            . '#Intent;scheme=' . $scheme . ';package=' . $package . ';end';

        $hideurl = new \moodle_url('/local/oksigeniaaty/bar.php', [
            'sesskey' => sesskey(),
            'returnurl' => $pageurl->out_as_local_url(false),
        ]);

        return $OUTPUT->render_from_template('local_oksigeniaaty/openinapp_bar', [
            'intenturl' => $intent,
            'hideurl' => $hideurl->out(false),
        ]);
    }
}
