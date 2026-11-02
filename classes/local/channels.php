<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_oksigeniaaty\local;

/**
 * "Where to get your notices": the places a site can reach someone, and how to connect the ones they do not use yet.
 *
 * WhatsApp and Telegram come from Oksigenia Marandu when the site has it. Which notice goes by which of them stays in
 * Moodle's notification preferences, which the app already shows.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class channels {
    /** Icons of the channels that Marandu can offer. */
    private const ICONS = ['whatsapp' => 'fab-whatsapp', 'telegram' => 'fab-telegram'];

    /**
     * Whether there is anything to choose: without a messaging channel, the app and the email need no page of their own.
     *
     * @return bool
     */
    public static function available(): bool {
        return (bool) array_filter(self::site_channels(), fn(array $channel): bool => !empty($channel['ready']));
    }

    /**
     * The channels Marandu has on this site.
     *
     * @return array By channel, with name and ready; empty without Marandu.
     */
    private static function site_channels(): array {
        return class_exists(\local_oksigeniamarandu\api::class) ? \local_oksigeniamarandu\api::channels() : [];
    }

    /**
     * Where a person can get their notices.
     *
     * @param \stdClass $user The person, with id, auth, username, email and emailstop.
     * @return array Rows with name, icon, detail, on (they get notices there) and url (to connect it).
     */
    public static function for_user(\stdClass $user): array {
        global $CFG, $DB;

        $rows = [];
        if (!empty($CFG->airnotifieraccesskey) && $DB->get_field('message_processors', 'enabled', ['name' => 'airnotifier'])) {
            $rows[] = self::row(
                get_string('channels:app', 'local_oksigeniaaty'),
                'fas-mobile-screen',
                get_string('channels:appdetail', 'local_oksigeniaaty'),
                true
            );
        }
        // An account of a family with no address of its own has an invented one, which is nobody's.
        if (!familyaccount::is_own($user) || validate_email((string) $user->email) && !self::invented((string) $user->email)) {
            $rows[] = self::row(
                get_string('channels:email', 'local_oksigeniaaty'),
                'fas-envelope',
                $user->emailstop ? get_string('channels:emailoff', 'local_oksigeniaaty') : (string) $user->email,
                !$user->emailstop
            );
        }
        if (self::available()) {
            foreach (self::marandu_channels((int) $user->id) as $channel => $info) {
                $rows[] = self::row(
                    (string) $info['name'],
                    self::ICONS[$channel] ?? 'fas-comment',
                    (string) ($info['where'] ?? ''),
                    !empty($info['joined']),
                    empty($info['joined']) ? (string) $info['url'] : ''
                );
            }
        }

        return $rows;
    }

    /**
     * Whether an address is one of those made up for an account of a family.
     *
     * @param string $email The address.
     * @return bool
     */
    private static function invented(string $email): bool {
        return str_ends_with($email, '@invalid.invalid');
    }

    /**
     * How a person is on Marandu's channels: as an adult of the family records if they are one, or else as a user.
     *
     * @param int $userid The person.
     * @return array By channel, with name, joined, where and url.
     */
    private static function marandu_channels(int $userid): array {
        global $DB;

        $adultid = 0;
        if (class_exists(\local_oksigeniafamily\api::class)) {
            $adultid = (int) $DB->get_field(
                'local_oksigeniafamily_adult',
                'id',
                ['userid' => $userid, 'status' => 'active'],
                IGNORE_MULTIPLE
            );
        }

        return $adultid ? \local_oksigeniamarandu\api::adult_channels($adultid)
            : \local_oksigeniamarandu\api::user_channels($userid);
    }

    /**
     * One row of the page.
     *
     * @param string $name The channel.
     * @param string $icon Its icon.
     * @param string $detail Where exactly, or why not.
     * @param bool $on Whether they get notices there.
     * @param string $url The link that connects it, if they can.
     * @return array
     */
    private static function row(string $name, string $icon, string $detail, bool $on, string $url = ''): array {
        return [
            'name' => $name,
            'icon' => $icon,
            'detail' => $detail,
            'on' => $on,
            'url' => $url,
            'connect' => $url === '' ? '' : get_string('channels:connectname', 'local_oksigeniaaty', $name),
        ];
    }
}
