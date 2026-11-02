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

use local_oksigeniaaty\local\linkmail;
use local_oksigeniaaty\local\loginkey;

/**
 * What other plugins use from Aty: the sign-in link a school sends to someone (at registration, by e-mail), so they
 * enter the app without a password and then use their PIN.
 *
 * The link opens the app on any phone that has it, and invites to install it where it does not. It works once, for
 * a few days, and never for administrator accounts, which always sign in with their password. Calling plugins check
 * their own permissions before they send one; each link is logged with who issued it.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    /** @var string The Android package of Aty, whose links carry a mark of their own. */
    private const ATY_PACKAGE = 'com.oksigenia.aty';

    /**
     * A one-time sign-in link to the app for a user.
     *
     * @param int $userid The user.
     * @param int $days How many days it works.
     * @return array ['url' => the link, 'expires' => unix time].
     * @throws \moodle_exception For administrator accounts, or users who cannot sign in.
     */
    public static function issue_link(int $userid, int $days = loginkey::ISSUED_DAYS): array {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');
        $user = \core_user::get_user($userid, '*', MUST_EXIST);
        if ($user->deleted || $user->suspended || isguestuser($user) || local_oksigeniaaty_is_admin_account($userid)) {
            throw new \moodle_exception('issuenotallowed', 'local_oksigeniaaty');
        }

        $key = loginkey::create_issued($userid, $days);
        $expires = time() + max(1, $days) * DAYSECS;
        event\link_issued::create([
            'context' => \context_system::instance(),
            'userid' => (int) ($USER->id ?? 0),
            'relateduserid' => $userid,
            'other' => ['expires' => $expires],
        ])->trigger();

        return [
            'url' => (new \moodle_url('/local/oksigeniaaty/open.php', ['key' => $key]))->out(false),
            'expires' => $expires,
        ];
    }

    /**
     * The address of a page of this site for a message to someone: it opens in the app if they have used it on a phone, so
     * a button in an e-mail does not leave them in the browser, asked to sign in again. Otherwise, the page itself. Any
     * other address stays as it is. It works with whichever app the site has set. With Aty it opens in their account, if there
     * are several on the phone: the address says which one as the app knows it, a hash of the site and the username, as Moodle's
     * own notices do.
     *
     * @param int $userid Who the message is for.
     * @param string $url The page.
     * @return string The address to use.
     */
    public static function app_link(int $userid, string $url): string {
        global $CFG, $DB;

        $package = get_config('tool_mobile', 'androidappid') ?: 'com.moodle.moodlemobile';
        if (self::local_target($url) === null || !$DB->record_exists('user_devices', ['userid' => $userid, 'appid' => $package])) {
            return $url;
        }

        $username = $DB->get_field('user', 'username', ['id' => $userid], MUST_EXIST);

        return (new \moodle_url('/local/oksigeniaaty/go.php', [
            'to' => $url,
            'for' => md5($CFG->wwwroot . $username),
        ]))->out(false);
    }

    /**
     * An address of this site, in full; or null for anything else, so the page in between cannot lead outside it.
     *
     * @param string $url The address, full or from the site's root.
     * @return string|null
     */
    public static function local_target(string $url): ?string {
        global $CFG;

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            $url = $CFG->wwwroot . $url;
        }

        return str_starts_with($url, $CFG->wwwroot . '/') && !preg_match('/[\s\x00-\x1f]/', $url) ? $url : null;
    }

    /**
     * What opens a page in the site's app on Android, or goes on to the page where the app is not installed.
     *
     * For Aty, the link carries a mark that the app takes off: the account, and something new each time. Android hands the
     * link that opened the app over again when it brings the app back after closing it, and the mark tells that from a tap.
     *
     * @param string $target The page, in full.
     * @param string $account The account it is for, as the app knows it; empty if unknown.
     * @return string
     */
    public static function app_intent(string $target, string $account = ''): string {
        $scheme = get_config('tool_mobile', 'forcedurlscheme') ?: 'moodlemobile';
        $package = get_config('tool_mobile', 'androidappid') ?: 'com.moodle.moodlemobile';

        $link = $target;
        if ($package === self::ATY_PACKAGE) {
            $marked = new \moodle_url($target);
            $marked->param('atylink', $account . '.' . random_string(12));
            $link = $marked->out(false);
        }

        return 'intent://link=' . rawurlencode($link) . '#Intent;scheme=' . $scheme . ';package=' . $package
            . ';S.browser_fallback_url=' . rawurlencode($target) . ';end';
    }

    /**
     * Send a user their sign-in link to the app by e-mail.
     *
     * @param int $userid The user.
     * @param int $days How many days it works.
     * @return bool Whether the e-mail was sent.
     * @throws \moodle_exception For administrator accounts, or users who cannot sign in.
     */
    public static function send_link(int $userid, int $days = loginkey::ISSUED_DAYS): bool {
        $link = self::issue_link($userid, $days);
        $user = \core_user::get_user($userid, '*', MUST_EXIST);
        $component = 'local_oksigeniaaty';
        $lang = force_current_language($user->lang ?? '');
        $a = (object) [
            'firstname' => $user->firstname,
            'site' => linkmail::centre($userid),
            'url' => $link['url'],
            'expires' => userdate(
                $link['expires'],
                get_string('strftimedatetime', 'langconfig'),
                \core_date::get_user_timezone($user)
            ),
        ];
        $subject = get_string('issued:subject', $component, $a);
        $body = get_string('issued:body', $component, $a);
        $html = linkmail::html($userid, [
            'hello' => get_string('issued:hello', $component, $a),
            'heading' => get_string('issued:heading', $component, $a),
            'lead' => [get_string('issued:lead', $component)],
            'button' => ['url' => $link['url'], 'text' => get_string('issued:button', $component)],
            'after' => [get_string('issued:noapp', $component), get_string('issued:once', $component, $a)],
            'why' => get_string('issued:why', $component, $a),
        ]);
        force_current_language($lang);

        return email_to_user($user, linkmail::from($userid), $subject, $body, $html);
    }
}
