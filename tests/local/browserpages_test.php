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
 * The pages that open in the phone's browser rather than inside the app.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(browserpages::class)]
final class browserpages_test extends \advanced_testcase {
    /**
     * A page matches by its own path, with or without query; a folder matches what lies in it; nothing else does, not
     * another site, not a page whose name only starts the same.
     */
    public function test_matches(): void {
        global $CFG;

        $paths = ['/local/example/f.php', '/local/example/public/'];
        $site = $CFG->wwwroot;

        $this->assertTrue(browserpages::matches($site . '/local/example/f.php', $paths));
        $this->assertTrue(browserpages::matches($site . '/local/example/f.php?k=abc#top', $paths));
        $this->assertTrue(browserpages::matches('/local/example/f.php?k=abc', $paths));
        $this->assertTrue(browserpages::matches($site . '/local/example/public/story.php', $paths));

        $this->assertFalse(browserpages::matches($site . '/local/example/f.php.bak', $paths));
        $this->assertFalse(browserpages::matches($site . '/local/example/index.php', $paths));
        $this->assertFalse(browserpages::matches($site . '/local/example/publicity.php', $paths));
        $this->assertFalse(browserpages::matches('https://elsewhere.example.org/local/example/f.php', $paths));
        $this->assertFalse(browserpages::matches('/local/example/f.php', []));
    }

    /**
     * Without any plugin asking, no page of the site opens in the phone's browser.
     */
    public function test_no_plugin_asks(): void {
        browserpages::reset();
        $this->assertFalse(browserpages::matches('/course/view.php?id=2'));
    }
}
