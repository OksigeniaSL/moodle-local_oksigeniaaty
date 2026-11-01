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
     * other address stays as it is. It works with whichever app the site has set.
     *
     * @param int $userid Who the message is for.
     * @param string $url The page.
     * @return string The address to use.
     */
    public static function app_link(int $userid, string $url): string {
        global $DB;

        $package = get_config('tool_mobile', 'androidappid') ?: 'com.moodle.moodlemobile';
        if (self::local_target($url) === null || !$DB->record_exists('user_devices', ['userid' => $userid, 'appid' => $package])) {
            return $url;
        }

        return (new \moodle_url('/local/oksigeniaaty/go.php', ['to' => $url]))->out(false);
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
     * @param string $target The page, in full.
     * @return string
     */
    public static function app_intent(string $target): string {
        $scheme = get_config('tool_mobile', 'forcedurlscheme') ?: 'moodlemobile';
        $package = get_config('tool_mobile', 'androidappid') ?: 'com.moodle.moodlemobile';

        return 'intent://link=' . rawurlencode($target) . '#Intent;scheme=' . $scheme . ';package=' . $package
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
        $a = (object) [
            'firstname' => $user->firstname,
            'site' => format_string(get_site()->fullname),
            'url' => $link['url'],
            'expires' => userdate($link['expires'], get_string('strftimedatetime', 'langconfig')),
        ];
        $lang = force_current_language($user->lang ?? '');
        $subject = get_string('issued:subject', 'local_oksigeniaaty', $a);
        $body = get_string('issued:body', 'local_oksigeniaaty', $a);
        force_current_language($lang);

        return email_to_user($user, \core_user::get_noreply_user(), $subject, $body);
    }
}
