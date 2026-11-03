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
 * The account an adult of a family uses in the app, for those who have none in Moodle.
 *
 * It uses the webservice authentication, which never signs in on the web, and is born when the adult opens the app, not when
 * they claim a code. It goes with them: when they leave the family, it is deleted. Only an account made here can be entered
 * with a link: a real account of the site never is, since holding a child's code says nothing about that person.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class familyaccount {
    /** @var string How the username of these accounts starts. */
    private const PREFIX = 'atyfamily-';

    /** @var int How long the link that the claim page shows works, in days. */
    private const LINK_DAYS = 30;

    /**
     * Whether an account is one made here.
     *
     * @param \stdClass $user The account, with auth and username.
     * @return bool
     */
    public static function is_own(\stdClass $user): bool {
        return $user->auth === 'webservice' && str_starts_with((string) $user->username, self::PREFIX);
    }

    /**
     * Whether this site offers the app to families: the centre has said so, which also turned on the web service
     * authentication these accounts use. Without it, Moodle refuses them, and nothing outside an administrator's hand turns it on.
     *
     * @return bool
     */
    public static function available(): bool {
        global $CFG;

        return class_exists(\local_oksigeniafamily\api::class) && \local_oksigeniafamily\api::is_available()
            && get_config('local_oksigeniaaty', 'familyapp') && is_enabled_auth('webservice')
            && !empty($CFG->enablewebservices) && !empty($CFG->enablemobilewebservice)
            && get_config('tool_mobile', 'forcedurlscheme') === 'oksigeniaaty'
            && (int) get_config('tool_mobile', 'typeoflogin') <= 1;
    }

    /**
     * The address that takes an adult into the app. It says who in a way only this site can read, and it makes nothing yet.
     *
     * @param int $adultid The adult of the family.
     * @return string
     */
    public static function link(int $adultid): string {
        $plain = $adultid . ':' . (time() + self::LINK_DAYS * DAYSECS);
        $sealed = rtrim(strtr(base64_encode(\core\encryption::encrypt($plain)), '+/', '-_'), '=');

        return (new \moodle_url('/local/oksigeniaaty/family.php', ['k' => $sealed]))->out(false);
    }

    /**
     * Who a link is for.
     *
     * @param string $sealed What link() put in the address.
     * @return int|null The adult, or null if it is not valid or has expired.
     */
    public static function read(string $sealed): ?int {
        try {
            $plain = \core\encryption::decrypt(base64_decode(strtr($sealed, '-_', '+/')));
        } catch (\Throwable $e) {
            return null;
        }
        if (!preg_match('/^(\d+):(\d+)$/', $plain, $parts) || (int) $parts[2] < time()) {
            return null;
        }

        return (int) $parts[1];
    }

    /**
     * The account of an adult, made now if they have none. Null when they should not get one this way: they are no longer in a
     * family, or they have a real account of the site, which they sign in to as always.
     *
     * @param int $adultid The adult.
     * @return int|null The account's user id.
     */
    public static function ensure(int $adultid): ?int {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');

        $adult = $DB->get_record('local_oksigeniafamily_adult', ['id' => $adultid, 'status' => 'active']);
        if (!$adult || !\local_oksigeniafamily\api::children_of_adult($adultid)) {
            return null;
        }
        if ($adult->userid) {
            $user = \core_user::get_user((int) $adult->userid);
            return $user && !$user->deleted && self::is_own($user) ? (int) $user->id : null;
        }

        $suffix = strtolower(random_string(20));
        $email = (string) $adult->email;
        $record = (object) [
            'auth' => 'webservice',
            'username' => self::PREFIX . $suffix,
            'password' => AUTH_PASSWORD_NOT_CACHED,
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id,
            'firstname' => (string) $adult->firstname,
            // Moodle sends an account with no surname or no address to fill in its profile, which this one cannot do.
            'lastname' => (string) $adult->lastname !== ''
                ? (string) $adult->lastname
                : get_string('family:nosurname', 'local_oksigeniaaty'),
            // Moodle sends no mail to a .invalid domain. Not with emailstop, which would stop the notices to the phone as well.
            'email' => validate_email($email) ? $email : self::PREFIX . $suffix . '@invalid.invalid',
        ];
        // Moodle 5.3 moved it; 4.5 only has the function.
        $userid = method_exists(\core_user::class, 'create_user')
            ? \core_user::create_user($record, false, false)
            : user_create_user($record, false, false);
        \local_oksigeniafamily\api::set_adult_user($adultid, (int) $userid);

        return (int) $userid;
    }

    /**
     * An adult has left their family: their account goes too, if it is one made here.
     *
     * @param int $userid The account.
     * @return void
     */
    public static function remove(int $userid): void {
        $user = \core_user::get_user($userid);
        if ($user && !$user->deleted && self::is_own($user)) {
            delete_user($user);
        }
    }
}
