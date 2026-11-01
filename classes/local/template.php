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
 * The app compiles what a plugin's mobile method returns as an Angular template, and Mustache escapes neither braces nor
 * at signs. A course or an activity named with «{{ … }}» or «@let» would run in the app of whoever sees it, with their
 * session, and an unfinished «@if» would stop the whole page from compiling.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template {
    /** @var string[] What must not reach a template inside an attribute: there, an at sign means nothing. */
    private const BRACES = ['{' => '｛', '}' => '｝'];

    /** @var string[] What must not reach a template as text. */
    private const TEXT = self::BRACES + ['@' => '＠'];

    /**
     * Texts that cannot run: each brace and at sign becomes its full-width look-alike, which reads the same.
     *
     * The values under a key ending in «url» are addresses that only go in attributes, where an at sign has to stay.
     *
     * @param mixed $data A text, or an array of them at any depth; anything else stays as it is.
     * @param bool $attribute Whether all of it only goes in attributes.
     * @return mixed
     */
    public static function inert($data, bool $attribute = false) {
        if (is_array($data)) {
            $inert = [];
            foreach ($data as $key => $value) {
                $inert[$key] = self::inert($value, $attribute || (is_string($key) && str_ends_with($key, 'url')));
            }
            return $inert;
        }

        return is_string($data) ? strtr($data, $attribute ? self::BRACES : self::TEXT) : $data;
    }
}
