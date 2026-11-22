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
 * The app's password form and the site's second factor.
 *
 * Moodle's login/token.php gives an app token for a username and a password alone: it never goes through tool_mfa, so
 * on a site with multi-factor authentication the password would be enough. This closes that door for the people the
 * site asks for a second factor, and only for them, once the password is known to be right: everyone else keeps the
 * app's own form. The refusal carries a launch address that signs the person in on the site with the password step
 * already done, so the app opens it and the person only gives the second factor (launch.php); an older app shows the
 * refusal's words, which say where to go instead.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mfagate {
    /** The user key script of the launch keys (core's user_private_key). */
    public const SCRIPT = 'local_oksigeniaaty/mfalaunch';

    /** How long a launch key lives, in seconds: the app opens it at once. */
    public const KEYLIFE = 300;

    /** The field of token.php's answer that carries the launch address. */
    public const FIELD = 'oksigeniaatylaunch';

    /**
     * Whether the gate is on: the site has multi-factor authentication and has not turned the gate off.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return get_config('local_oksigeniaaty', 'mfagate') !== '0'
            && class_exists(\tool_mfa\manager::class)
            && !empty(get_config('tool_mfa', 'enabled'));
    }

    /**
     * Called as soon as the site is set up, on every request: acts only on login/token.php, and only answers there when
     * the person must give a second factor.
     *
     * @return void
     */
    public static function check_token_request(): void {
        global $SCRIPT;

        if ($SCRIPT !== '/login/token.php' || !isset($_POST['username'], $_POST['password'])) {
            return;
        }
        if (!self::enabled()) {
            return;
        }

        try {
            $answer = self::decide(
                (string) clean_param($_POST['username'], PARAM_USERNAME),
                (string) clean_param($_POST['password'], PARAM_RAW)
            );
        } catch (\Throwable $e) {
            // Something unforeseen must not close the app's sign-in for everyone: token.php goes on as it always has.
            debugging('Second factor gate of the app: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return;
        }
        if ($answer === null) {
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-store');
        echo json_encode($answer);
        exit;
    }

    /**
     * What token.php should answer for these credentials: null to let it go on as usual (a wrong password, a locked or
     * suspended account, someone the site lets in without a second factor), or the refusal.
     *
     * @param string $username What was typed as the username (or the e-mail, where the site allows it).
     * @param string $password The password.
     * @return array|null
     */
    public static function decide(string $username, string $password): ?array {
        global $CFG;
        // So early in the request (login/token.php's setup), authlib is not loaded yet.
        require_once($CFG->libdir . '/authlib.php');

        $user = self::find_user($username);
        if (!$user || !empty($user->suspended) || login_is_lockedout($user) || !is_enabled_auth($user->auth)) {
            return null;
        }
        // The same check authenticate_user_login() makes, without its side effects: a wrong password is left to
        // token.php, which counts it once.
        if (!get_auth_plugin($user->auth)->user_login($user->username, $password)) {
            return null;
        }
        if (!self::needs_factor($user)) {
            return null;
        }

        \local_oksigeniaaty\event\app_signin_needs_factor::create([
            'context' => \context_system::instance(),
            'userid' => $user->id,
            'relateduserid' => $user->id,
        ])->trigger();

        return [
            'error' => self::message($user),
            // The code that today's app answers by showing the words above as they are.
            'errorcode' => 'invalidlogin',
            self::FIELD => self::launch_url((int) $user->id)->out(false),
        ];
    }

    /**
     * Whether the site would ask this person for a second factor: the factors that need nothing from them (role,
     * authentication type, cohort, IP range, no setup, grace period…) do not reach the 100 points.
     *
     * @param \stdClass $user The account.
     * @return bool
     */
    public static function needs_factor(\stdClass $user): bool {
        global $USER;

        if (!self::enabled()) {
            return false;
        }

        // The factors read the current user.
        $previous = $USER;
        \core\session\manager::set_user($user);
        try {
            if (!\tool_mfa\manager::is_ready()) {
                return false;
            }
            $points = 0;
            foreach (\tool_mfa\plugininfo\factor::get_active_user_factor_types($user) as $factor) {
                if ($factor->has_input()) {
                    continue;
                }
                // The grace factor takes whether it may redirect; the others ignore the argument.
                $state = $factor->get_state(false);
                if ($state === \tool_mfa\plugininfo\factor::STATE_FAIL) {
                    return true;
                }
                if ($state === \tool_mfa\plugininfo\factor::STATE_PASS) {
                    $points += (int) $factor->get_weight();
                }
            }
            return $points < 100;
        } finally {
            \core\session\manager::set_user($previous);
        }
    }

    /**
     * The address the app opens to finish the sign-in on the site: it carries a key that does the password step, for five
     * minutes and once, and only from the same network address.
     *
     * @param int $userid The account.
     * @return \moodle_url
     */
    public static function launch_url(int $userid): \moodle_url {
        delete_user_key(self::SCRIPT, $userid);
        $key = create_user_key(self::SCRIPT, $userid, null, getremoteaddr(), time() + self::KEYLIFE);

        return new \moodle_url('/local/oksigeniaaty/launch.php', ['k' => $key]);
    }

    /**
     * The account a launch key is for, spending the key; null if it is not valid any more or comes from elsewhere.
     *
     * @param string $key The key.
     * @return int|null
     */
    public static function spend_key(string $key): ?int {
        try {
            $record = validate_user_key($key, self::SCRIPT, null);
        } catch (\moodle_exception $e) {
            return null;
        }
        delete_user_key(self::SCRIPT, (int) $record->userid);

        return (int) $record->userid;
    }

    /**
     * The words an app that does not open the launch address shows.
     *
     * @param \stdClass $user The account.
     * @return string
     */
    protected static function message(\stdClass $user): string {
        global $CFG;
        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

        $a = (object) [
            'site' => format_string(get_site()->fullname, true, ['context' => \context_system::instance(), 'escape' => false]),
            'menu' => get_string('mylink:nav', 'local_oksigeniaaty'),
        ];
        $id = local_oksigeniaaty_user_can_selfservice((int) $user->id) ? 'mfagate:refused' : 'mfagate:refusednolink';

        return get_string($id, 'local_oksigeniaaty', $a);
    }

    /**
     * The account behind what was typed, as authenticate_user_login() finds it.
     *
     * @param string $username The username, or the e-mail where the site allows signing in with it.
     * @return \stdClass|null
     */
    protected static function find_user(string $username): ?\stdClass {
        global $CFG, $DB;

        $username = trim(\core_text::strtolower($username));
        if ($username === '') {
            return null;
        }
        if (!empty($CFG->authloginviaemail) && ($email = clean_param($username, PARAM_EMAIL))) {
            $select = 'mnethostid = :mnethostid AND LOWER(email) = LOWER(:email) AND deleted = 0';
            $users = $DB->get_records_select('user', $select,
                ['mnethostid' => $CFG->mnet_localhost_id, 'email' => $email], 'id', 'id', 0, 2);
            if (count($users) === 1) {
                return \core_user::get_user(reset($users)->id) ?: null;
            }
        }
        $user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0]);

        return $user ?: null;
    }
}
