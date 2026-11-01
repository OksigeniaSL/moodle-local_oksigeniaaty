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
 * Data for the templates the app compiles.
 *
 * The app compiles what a plugin's mobile method returns as an Angular template, and Mustache does not escape braces. A
 * course or an activity named with «{{ … }}» would run in the app of whoever sees it, with their session.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template {
    /**
     * Texts that cannot run: each brace becomes its full-width look-alike, which reads the same.
     *
     * @param mixed $data A text, or an array of them at any depth; anything else stays as it is.
     * @return mixed
     */
    public static function inert($data) {
        if (is_array($data)) {
            return array_map([self::class, 'inert'], $data);
        }

        return is_string($data) ? strtr($data, ['{' => '｛', '}' => '｝']) : $data;
    }
}
