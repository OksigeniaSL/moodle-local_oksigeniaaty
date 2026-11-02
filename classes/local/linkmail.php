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
 * The look of the e-mails Aty sends, in the colour and with the name of the person's centre.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class linkmail {
    /**
     * The whole message, ready to send.
     *
     * @param int $userid Who it is for, whose centre's look it takes.
     * @param array $content hello, heading, lead (paragraphs), button (url, text), after (paragraphs), why.
     * @return string
     */
    public static function html(int $userid, array $content): string {
        global $OUTPUT;

        $look = self::look($userid);
        $render = $look + [
            'lang' => str_replace('_', '-', current_language()),
            'preheader' => $content['lead'][0] ?? $content['heading'],
            'hello' => $content['hello'],
            'heading' => $content['heading'],
            'lead' => array_values($content['lead']),
            'buttonurl' => $content['button']['url'],
            'buttontext' => $content['button']['text'],
            'after' => array_values($content['after']),
            'why' => $content['why'],
        ];

        return $OUTPUT->render_from_template('local_oksigeniaaty/email_link', $render);
    }

    /**
     * Who a message comes from: the site's no-reply address, with the name of the person's centre.
     *
     * @param int $userid Who it is for.
     * @return \stdClass
     */
    public static function from(int $userid): \stdClass {
        // A copy: the one Moodle hands out is the same for the whole request, and the next message is someone else's.
        $from = clone \core_user::get_noreply_user();
        $from->firstname = self::look($userid)['name'];
        $from->lastname = '';

        return $from;
    }

    /**
     * The name of a person's centre.
     *
     * @param int $userid The person.
     * @return string
     */
    public static function centre(int $userid): string {
        return self::look($userid)['name'];
    }

    /**
     * What the message takes from the person's centre, or else from the site.
     *
     * @param int $userid The person.
     * @return array ['name', 'logo', 'band', 'ink'].
     */
    private static function look(int $userid): array {
        $brand = brand::for_user($userid);
        $name = $brand !== null ? $brand['name'] : get_site()->fullname;
        $colour = $brand !== null ? $brand['colour'] : (string) get_config('local_oksigeniaaty', 'brandcolour');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colour)) {
            $colour = qrdesign::DEFAULTINK;
        }
        $logo = $brand !== null ? $brand['logo'] : (string) get_config('local_oksigeniaaty', 'homelogourl');

        return [
            'name' => format_string($name, true, ['escape' => false]),
            // Mail programs do not show SVG, and an address that only opens with a session shows nothing.
            'logo' => preg_match('#^https://[^?]+\.(png|jpe?g|gif)(\?.*)?$#i', $logo) ? $logo : '',
            'band' => $colour,
            'ink' => self::ink($colour),
        ];
    }

    /**
     * The text colour that reads on a colour.
     *
     * @param string $colour A colour as #rrggbb.
     * @return string
     */
    private static function ink(string $colour): string {
        [$r, $g, $b] = array_map('hexdec', str_split(substr($colour, 1), 2));
        $channel = function (int $value): float {
            $value /= 255;
            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        };
        $luminance = 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);

        // The one with more contrast: (L1 + 0.05) / (L2 + 0.05) against white and against the dark ink, which meet here.
        return $luminance > 0.179 ? '#14181c' : '#ffffff';
    }
}
