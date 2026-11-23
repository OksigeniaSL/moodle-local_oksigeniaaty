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
 * «Propose an improvement» of Oksigenia Marandu on the screens where the centre's staff work with the app's tools: the
 * person marks on a picture of what they see what they would change, and the people the site chose hear of it.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class proposal {
    /**
     * The button for a screen, with what it needs loaded on the page: called before the page's header. Empty on a site
     * without Marandu (or with one before 0.61.1) and for an account that may not propose.
     *
     * @param string $screen The screen, as this plugin names it (its title).
     * @return string Its HTML, in its own line.
     */
    public static function button(string $screen): string {
        $marandu = '\\local_oksigeniamarandu\\api';
        if (!class_exists($marandu) || !method_exists($marandu, 'proposal_button')) {
            return '';
        }
        $button = (string) $marandu::proposal_button('local_oksigeniaaty', $screen);

        return $button === '' ? '' : \html_writer::div($button, 'local-oksigeniaaty-propose');
    }
}
