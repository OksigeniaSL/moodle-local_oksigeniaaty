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
 * Who a site can send the app to, and how each of them stands: already in the app, with a link on its way, or waiting.
 *
 * It keeps nothing of its own. A person is in the app when they have a token of the mobile service, and has a link when
 * their sign-in key of this kind is there; using the key removes it.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class linkroster {
    /** @var string Has no working link and is not in the app. */
    public const WAITING = 'waiting';

    /** @var string Has a link that still works. */
    public const SENT = 'sent';

    /** @var string Has already entered the app. */
    public const INAPP = 'inapp';

    /** @var string Cannot get a link: an administrator account, or no address to send it to. */
    public const BLOCKED = 'blocked';

    /** @var int The most people one list holds. */
    public const MAX = 2000;

    /**
     * The active people whose name, username or e-mail contain a text.
     *
     * @param string $text The text.
     * @return \stdClass[] Users by id.
     */
    public static function search(string $text): array {
        global $DB;

        $text = trim($text);
        if ($text === '') {
            return [];
        }
        [$where, $params] = users_search_sql($text, 'u', USER_SEARCH_CONTAINS, ['email', 'username']);

        return $DB->get_records_sql(self::select() . " WHERE $where AND " . self::active(), $params, 0, self::MAX);
    }

    /**
     * The people actively enrolled in a course, or in one of its groups.
     *
     * @param int $courseid The course.
     * @param int $groupid The group, or 0 for the whole course.
     * @return \stdClass[] Users by id.
     */
    public static function course(int $courseid, int $groupid = 0): array {
        global $DB;

        $context = \context_course::instance($courseid);
        [$enrolled, $params] = get_enrolled_sql($context, '', $groupid, true);

        return $DB->get_records_sql(
            self::select() . " JOIN ($enrolled) je ON je.id = u.id WHERE " . self::active(),
            $params,
            0,
            self::MAX
        );
    }

    /**
     * The people in a cohort.
     *
     * @param int $cohortid The cohort.
     * @return \stdClass[] Users by id.
     */
    public static function cohort(int $cohortid): array {
        global $DB;

        return $DB->get_records_sql(
            self::select() . " JOIN {cohort_members} cm ON cm.userid = u.id AND cm.cohortid = :cohortid WHERE " . self::active(),
            ['cohortid' => $cohortid],
            0,
            self::MAX
        );
    }

    /**
     * How each person stands, those still waiting first.
     *
     * @param \stdClass[] $users The people.
     * @return array[] A list of ['user', 'state', 'until' (the link's end, 0 if none), 'why' for a blocked one].
     */
    public static function rows(array $users): array {
        $users = array_filter($users, fn($user) => !isguestuser($user));
        if (!$users) {
            return [];
        }
        $ids = array_map('intval', array_keys($users));
        $admins = self::admins();
        $inapp = self::in_app($ids);
        $keys = self::keys($ids);
        $now = time();

        $rows = [];
        foreach ($users as $user) {
            $id = (int) $user->id;
            $until = $keys[$id] ?? 0;
            $why = '';
            if (isset($admins[$id])) {
                $state = self::BLOCKED;
                $why = 'admin';
            } else if (!validate_email((string) $user->email)) {
                $state = self::BLOCKED;
                $why = 'noemail';
            } else if (isset($inapp[$id])) {
                $state = self::INAPP;
            } else if ($until > $now) {
                $state = self::SENT;
            } else {
                $state = self::WAITING;
            }
            $rows[] = ['user' => $user, 'state' => $state, 'until' => $until, 'why' => $why];
        }

        $order = [self::WAITING => 0, self::SENT => 1, self::INAPP => 2, self::BLOCKED => 3];
        usort($rows, fn($a, $b) => [$order[$a['state']], $a['user']->lastname, $a['user']->firstname, $a['user']->id]
            <=> [$order[$b['state']], $b['user']->lastname, $b['user']->firstname, $b['user']->id]);

        return $rows;
    }

    /**
     * How many people are in each state.
     *
     * @param array[] $rows From rows().
     * @return int[] By state.
     */
    public static function counts(array $rows): array {
        $counts = [self::WAITING => 0, self::SENT => 0, self::INAPP => 0, self::BLOCKED => 0];
        foreach ($rows as $row) {
            $counts[$row['state']]++;
        }

        return $counts;
    }

    /**
     * Who can be sent a link now, out of a list of people: the ones who have none and are not in the app.
     *
     * @param array[] $rows From rows().
     * @return int[] User ids.
     */
    public static function waiting(array $rows): array {
        $rows = array_filter($rows, fn($row) => $row['state'] === self::WAITING);

        return array_values(array_map(fn($row) => (int) $row['user']->id, $rows));
    }

    /**
     * The start of the query, with the fields a list needs.
     *
     * @return string
     */
    private static function select(): string {
        $fields = array_map(fn($field) => 'u.' . $field, \core_user\fields::get_picture_fields());

        return 'SELECT ' . implode(', ', $fields) . ' FROM {user} u';
    }

    /**
     * The accounts that can sign in at all.
     *
     * @return string
     */
    private static function active(): string {
        global $CFG;

        return 'u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1 AND u.mnethostid = ' . (int) $CFG->mnet_localhost_id;
    }

    /**
     * The administrator accounts, which sign in with their password. All at once, not person by person.
     *
     * @return array User ids as keys.
     */
    private static function admins(): array {
        $admins = array_flip(array_map('intval', array_keys(get_admins())));
        $configurers = get_users_by_capability(\context_system::instance(), 'moodle/site:config', 'u.id');

        return $admins + array_flip(array_map('intval', array_keys($configurers)));
    }

    /**
     * Who among some people has a working token of the mobile service.
     *
     * @param int[] $ids User ids.
     * @return array User ids as keys.
     */
    private static function in_app(array $ids): array {
        global $DB;

        [$in, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $params += ['service' => MOODLE_OFFICIAL_MOBILE_SERVICE, 'permanent' => EXTERNAL_TOKEN_PERMANENT, 'now' => time()];
        $sql = "SELECT DISTINCT t.userid
                  FROM {external_tokens} t
                  JOIN {external_services} s ON s.id = t.externalserviceid
                 WHERE s.shortname = :service
                   AND t.tokentype = :permanent
                   AND (t.validuntil = 0 OR t.validuntil IS NULL OR t.validuntil > :now)
                   AND t.userid $in";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Until when the link of each of some people works.
     *
     * @param int[] $ids User ids.
     * @return int[] Unix times by user id.
     */
    private static function keys(array $ids): array {
        global $DB;

        [$in, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $params['script'] = loginkey::ISSUED_SCRIPT;

        return array_map('intval', $DB->get_records_sql_menu(
            "SELECT userid, MAX(validuntil) FROM {user_private_key} WHERE script = :script AND userid $in GROUP BY userid",
            $params
        ));
    }
}
