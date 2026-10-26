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
 * There are two kinds, under two scripts: an ordinary sign-in key, and a PIN-reset key (only ever created by
 * forgotpin.php and emailed to the user's registered address). Keeping them apart is what lets "forgot my PIN" exist
 * without weakening ordinary links: an intercepted sign-in link can never reset the PIN. Both are single use and
 * expire, so an intercepted key is only useful briefly and only once.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class loginkey {
    /** @var string The user-key script for ordinary sign-in keys. */
    const SCRIPT = 'local_oksigeniaaty/login';

    /** @var string The user-key script for PIN-reset keys (emailed, and they clear the PIN when used). */
    const RESET_SCRIPT = 'local_oksigeniaaty/resetpin';

    /** @var int How long a key is valid, in seconds (30 minutes). */
    const TTL = 1800;

    /**
     * Create a one-time sign-in key for a user, replacing any previous one.
     *
     * Only one key is valid per user at a time, so prefetching or reloading the self-service page cannot pile keys up,
     * and expired keys do not accumulate.
     *
     * @param int $userid The user the key signs in.
     * @return string The key value to put in the link or QR.
     */
    public static function create(int $userid): string {
        delete_user_key(self::SCRIPT, $userid);

        return create_user_key(self::SCRIPT, $userid, 0, null, time() + self::TTL);
    }

    /**
     * Create a one-time PIN-reset key for a user, replacing any previous one. Using it clears the PIN.
     *
     * @param int $userid The user the key is for.
     * @return string The key value to put in the emailed link.
     */
    public static function create_reset(int $userid): string {
        delete_user_key(self::RESET_SCRIPT, $userid);

        return create_user_key(self::RESET_SCRIPT, $userid, 0, null, time() + self::TTL);
    }

    /**
     * When the user's current reset key was created, or null if there is none, so a resend can be put on a cooldown.
     *
     * @param int $userid The user id.
     * @return int|null The unix time the reset key was created, or null.
     */
    public static function last_reset_request(int $userid): ?int {
        global $DB;

        $key = $DB->get_record('user_private_key', ['script' => self::RESET_SCRIPT, 'userid' => $userid], 'timecreated');

        return $key ? (int) $key->timecreated : null;
    }

    /**
     * Validate a key and consume it (single use), returning the user id it belongs to.
     *
     * The key is claimed atomically with a private nonce, so two concurrent requests with the same key cannot both
     * succeed; the record is then deleted before the expiry is checked, so a key cannot be used twice. Both kinds of
     * key are tried, so one consume call serves sign-in and reset.
     *
     * @param string $keyvalue The key value from the link or QR.
     * @return int The user id.
     * @throws \moodle_exception When the key is unknown or expired.
     */
    public static function consume(string $keyvalue): int {
        global $DB;

        foreach ([self::SCRIPT, self::RESET_SCRIPT] as $script) {
            $nonce = random_string(20);
            $DB->execute(
                'UPDATE {user_private_key} SET iprestriction = ? WHERE script = ? AND value = ? AND iprestriction IS NULL',
                [$nonce, $script, $keyvalue]
            );

            $key = $DB->get_record(
                'user_private_key',
                ['script' => $script, 'value' => $keyvalue, 'iprestriction' => $nonce]
            );
            if (!$key) {
                continue;
            }

            // We own it; remove it so it cannot be used again.
            $DB->delete_records('user_private_key', ['id' => $key->id]);

            if (!empty($key->validuntil) && $key->validuntil < time()) {
                throw new \moodle_exception('expiredkey', 'local_oksigeniaaty');
            }

            return (int) $key->userid;
        }

        throw new \moodle_exception('invalidkey', 'local_oksigeniaaty');
    }

    /**
     * The key's owner and whether it is a PIN-reset key, without consuming it, or null when it is unknown or expired.
     *
     * token.php peeks before it consumes, so a wrong PIN can be reported without burning the key, and so it knows
     * whether to clear the PIN. Only consume() claims the key atomically; peek() never changes it.
     *
     * @param string $keyvalue The key value.
     * @return array|null ['userid' => int, 'reset' => bool], or null when there is no live key.
     */
    public static function peek(string $keyvalue): ?array {
        global $DB;

        foreach ([self::SCRIPT => false, self::RESET_SCRIPT => true] as $script => $reset) {
            $key = $DB->get_record('user_private_key', ['script' => $script, 'value' => $keyvalue]);
            if ($key && (empty($key->validuntil) || $key->validuntil >= time())) {
                return ['userid' => (int) $key->userid, 'reset' => $reset];
            }
        }

        return null;
    }

    /**
     * Whether a key exists and has not expired, without consuming it (for a friendly landing page).
     *
     * @param string $keyvalue The key value.
     * @return bool
     */
    public static function is_live(string $keyvalue): bool {
        return self::peek($keyvalue) !== null;
    }
}
