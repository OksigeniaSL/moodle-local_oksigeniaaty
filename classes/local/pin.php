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
 * The per-user PIN that backs passwordless sign-in: a convenient second factor.
 *
 * A short PIN has little entropy, so it is hashed and, crucially, verification is throttled server-side: after a few
 * wrong tries the account is locked for a growing while. The PIN is set on first use (trust on first use) and then
 * required, and it is what lets the app renew an expiring token without a password.
 *
 * It lives in its own table, never in user preferences, so the hash is not reachable through the web service that
 * returns a user's preferences.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pin {
    /** @var string The table holding the per-user PIN. */
    const TABLE = 'local_oksigeniaaty_pin';

    /** @var int Minimum PIN length. */
    const MINLEN = 4;

    /** @var int Maximum PIN length. */
    const MAXLEN = 8;

    /** @var int Wrong tries before the account is locked. */
    const MAXFAILS = 5;

    /** @var int Base lockout in seconds, which grows with each further lock. */
    const LOCKSECS = 300;

    /**
     * Whether the user has a PIN set.
     *
     * @param int $userid The user id.
     * @return bool
     */
    public static function has_pin(int $userid): bool {
        global $DB;

        return $DB->record_exists(self::TABLE, ['userid' => $userid]);
    }

    /**
     * Set (or change) a user's PIN, clearing any failure count or lock.
     *
     * @param int $userid The user id.
     * @param string $pin The new PIN (digits only).
     * @return void
     * @throws \moodle_exception When the PIN is not the right shape.
     */
    public static function set(int $userid, string $pin): void {
        global $DB;

        if (!preg_match('/^\d{' . self::MINLEN . ',' . self::MAXLEN . '}$/', $pin)) {
            throw new \moodle_exception('pininvalid', 'local_oksigeniaaty');
        }

        $now = time();
        $hash = password_hash($pin, PASSWORD_DEFAULT);
        $record = $DB->get_record(self::TABLE, ['userid' => $userid]);
        if ($record) {
            $record->pinhash = $hash;
            $record->fails = 0;
            $record->lockuntil = 0;
            $record->timemodified = $now;
            $DB->update_record(self::TABLE, $record);
        } else {
            $DB->insert_record(self::TABLE, (object) [
                'userid' => $userid,
                'pinhash' => $hash,
                'fails' => 0,
                'lockuntil' => 0,
                'timemodified' => $now,
            ]);
        }
    }

    /**
     * Whether PIN verification is currently locked after too many wrong tries.
     *
     * @param int $userid The user id.
     * @return bool
     */
    public static function is_locked(int $userid): bool {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['userid' => $userid], 'lockuntil');

        return $record && (int) $record->lockuntil > time();
    }

    /**
     * Verify a PIN, counting failures and locking after too many.
     *
     * A locked account always fails without checking the hash, so the lock cannot be worn down by guessing. Check
     * is_locked() first to tell the user why.
     *
     * @param int $userid The user id.
     * @param string $pin The PIN to check.
     * @return bool True when the PIN is correct.
     */
    public static function verify(int $userid, string $pin): bool {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['userid' => $userid]);
        if (!$record || (int) $record->lockuntil > time()) {
            return false;
        }

        if (password_verify($pin, $record->pinhash)) {
            if ((int) $record->fails !== 0 || (int) $record->lockuntil !== 0) {
                $record->fails = 0;
                $record->lockuntil = 0;
                $record->timemodified = time();
                $DB->update_record(self::TABLE, $record);
            }

            return true;
        }

        // Wrong PIN: count it and, past the limit, lock for a growing while.
        $record->fails = (int) $record->fails + 1;
        if ($record->fails >= self::MAXFAILS) {
            $record->lockuntil = time() + self::LOCKSECS * ($record->fails - self::MAXFAILS + 1);
        }
        $record->timemodified = time();
        $DB->update_record(self::TABLE, $record);

        return false;
    }

    /**
     * Remove a user's PIN (e.g. when they ask to reset it, or their data is deleted).
     *
     * @param int $userid The user id.
     * @return void
     */
    public static function delete(int $userid): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['userid' => $userid]);
    }
}
