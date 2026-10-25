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
 * One-time, short-lived keys that let the Aty app sign a user in without their password.
 *
 * A key is created for a user (by themselves, or by trusted staff with local/oksigeniaaty:issuelink), travels in the
 * sign-in link or QR, and is exchanged once for an app token by token.php. It is single use and expires, so an
 * intercepted key is only useful briefly and only once.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class loginkey {

    /** @var string The user-key script name that scopes these keys. */
    const SCRIPT = 'local_oksigeniaaty/login';

    /** @var int How long a key is valid, in seconds (30 minutes). */
    const TTL = 1800;

    /**
     * Create a one-time sign-in key for a user.
     *
     * @param int $userid The user the key signs in.
     * @return string The key value to put in the link or QR.
     */
    public static function create(int $userid): string {
        return create_user_key(self::SCRIPT, $userid, 0, null, time() + self::TTL);
    }

    /**
     * Validate a key and consume it (single use), returning the user id it signs in.
     *
     * The key record is deleted before the expiry is checked, so a key cannot be used twice even if it was already
     * expired.
     *
     * @param string $keyvalue The key value from the link or QR.
     * @return int The user id.
     * @throws \moodle_exception When the key is unknown or expired.
     */
    public static function consume(string $keyvalue): int {
        global $DB;

        $key = $DB->get_record('user_private_key', ['script' => self::SCRIPT, 'value' => $keyvalue]);
        if (!$key) {
            throw new \moodle_exception('invalidkey', 'local_oksigeniaaty');
        }

        // Single use: remove it now, whatever happens next.
        $DB->delete_records('user_private_key', ['id' => $key->id]);

        if (!empty($key->validuntil) && $key->validuntil < time()) {
            throw new \moodle_exception('expiredkey', 'local_oksigeniaaty');
        }

        return (int) $key->userid;
    }

    /**
     * Whether a key exists and has not expired, without consuming it (for a friendly landing page).
     *
     * @param string $keyvalue The key value.
     * @return bool
     */
    public static function is_live(string $keyvalue): bool {
        global $DB;

        $key = $DB->get_record('user_private_key', ['script' => self::SCRIPT, 'value' => $keyvalue]);

        return $key && (empty($key->validuntil) || $key->validuntil >= time());
    }
}
