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

use core_text;
use core_user;
use moodle_url;
use stdClass;

/**
 * «Forgot my PIN»: who is asking, found by what they remember, and how their reset link reaches them.
 *
 * People rarely remember a username. They may give it, their e-mail or, with Oksigenia Family, their mobile number,
 * when the centre or WhatsApp vouched for it. The link goes by e-mail to a real mailbox; an account without one, such as
 * an adult who entered with a child's code, gets a notice to the phone (the app, and WhatsApp or Telegram where Oksigenia
 * Marandu is set up) that leads to the link without carrying it.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pinreset {
    /**
     * The one active, confirmed, local account that a username, an e-mail or a mobile number points to. Two accounts
     * with the same e-mail or the same number point to nobody: the link must never reach someone else.
     *
     * @param string $search What the person typed.
     * @return stdClass|null The user record, or null.
     */
    public static function find_user(string $search): ?stdClass {
        global $CFG, $DB;

        $search = trim($search);
        if ($search === '') {
            return null;
        }
        $base = ['mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0, 'suspended' => 0, 'confirmed' => 1];

        $user = $DB->get_record('user', ['username' => core_text::strtolower($search)] + $base);
        if ($user) {
            return $user;
        }
        if (validate_email($search)) {
            // Whatever case it was saved in: PostgreSQL compares as written, and «Ana@Example.org» is the same mailbox.
            $matches = $DB->get_records_select(
                'user',
                'LOWER(email) = LOWER(:email) AND mnethostid = :host AND deleted = 0 AND suspended = 0 AND confirmed = 1',
                ['email' => $search, 'host' => $base['mnethostid']]
            );
            return count($matches) === 1 ? reset($matches) : null;
        }

        $userids = self::userids_by_phone($search);
        if (count($userids) !== 1) {
            return null;
        }
        return $DB->get_record('user', ['id' => reset($userids)] + $base) ?: null;
    }

    /**
     * The accounts of the adults whose mobile number, known to be theirs, is the one typed (Oksigenia Family).
     *
     * @param string $typed The number as typed.
     * @return int[] Distinct user ids; none without Family or when it is not a number.
     */
    public static function userids_by_phone(string $typed): array {
        if (
            !class_exists('\local_oksigeniafamily\api') || !class_exists('\local_oksigeniafamily\local\phone')
            || preg_match('/^[\d\s()+.\-]{6,}$/', $typed) !== 1
        ) {
            return [];
        }
        $prefix = (string) get_config('local_oksigeniafamily', 'phoneprefix');
        $phone = \local_oksigeniafamily\local\phone::normalise($typed, $prefix);
        if ($phone === '') {
            return [];
        }
        $userids = [];
        foreach (\local_oksigeniafamily\api::adults_by_phone($phone) as $adult) {
            if (!empty($adult->userid)) {
                $userids[(int) $adult->userid] = (int) $adult->userid;
            }
        }
        return array_values($userids);
    }

    /**
     * Whether e-mail reaches the person: an address that exists, not a placeholder such as the «.invalid» ones of the
     * accounts made for a child's code.
     *
     * @param stdClass $user The user record.
     * @return bool
     */
    public static function has_mailbox(stdClass $user): bool {
        $email = trim((string) ($user->email ?? ''));
        return $email !== '' && validate_email($email) && !preg_match('/\.invalid$/i', $email);
    }

    /**
     * Send the way to a new PIN: the reset link by e-mail to a real mailbox; otherwise a notice to the phone.
     *
     * The notice carries no key. It travels through the push bridge and Firebase and, with Oksigenia Marandu, through
     * WhatsApp or Telegram, and a reset key signs in without the PIN. So it only says where to go: newpin.php, which hands
     * the key to the account's own session, in the app or on the web.
     *
     * @param stdClass $user The user record.
     * @param moodle_url $openurl The sign-in bridge with the reset key, for the e-mail.
     * @return string 'email' or 'notice', for the record.
     */
    public static function send(stdClass $user, moodle_url $openurl): string {
        $lang = force_current_language($user->lang ?? '');
        $link = $openurl->out(false);
        $subject = get_string('forgotpin:emailsubject', 'local_oksigeniaaty');

        if (self::has_mailbox($user)) {
            $data = (object) ['name' => fullname($user), 'link' => $link];
            $bodytext = get_string('forgotpin:emailbody', 'local_oksigeniaaty', $data);
            $html = linkmail::html((int) $user->id, [
                'hello' => get_string('issued:hello', 'local_oksigeniaaty', (object) ['firstname' => $user->firstname]),
                'heading' => get_string('forgotpin:emailheading', 'local_oksigeniaaty'),
                'lead' => [get_string('forgotpin:emaillead', 'local_oksigeniaaty')],
                'button' => ['url' => $link, 'text' => get_string('forgotpin:emailbutton', 'local_oksigeniaaty')],
                'after' => [get_string('forgotpin:emailonce', 'local_oksigeniaaty')],
                'why' => get_string('forgotpin:emailwhy', 'local_oksigeniaaty'),
            ]);
            force_current_language($lang);
            email_to_user($user, core_user::get_support_user(), $subject, $bodytext, $html);
            return 'email';
        }

        $message = new \core\message\message();
        $message->component = 'local_oksigeniaaty';
        $message->name = 'pinreset';
        $message->userfrom = core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = get_string('forgotpin:notice', 'local_oksigeniaaty');
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '';
        $message->smallmessage = get_string('forgotpin:noticesmall', 'local_oksigeniaaty');
        $message->notification = 1;
        // From WhatsApp or Telegram, on an Android phone with the app, the page opens in the app, signed in.
        $message->contexturl = \local_oksigeniaaty\api::app_link(
            (int) $user->id,
            (new moodle_url('/local/oksigeniaaty/newpin.php'))->out(false)
        );
        $message->contexturlname = get_string('forgotpin:emailbutton', 'local_oksigeniaaty');
        force_current_language($lang);
        message_send($message);
        return 'notice';
    }
}
