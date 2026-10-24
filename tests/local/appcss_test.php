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

/**
 * Tests for the app CSS generator.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oksigeniaaty\local;

/**
 * @covers \local_oksigeniaaty\local\appcss
 */
#[\PHPUnit\Framework\Attributes\CoversClass(appcss::class)]
final class appcss_test extends \basic_testcase {
    /**
     * A brand colour yields a light and a dark block with the brand as the primary.
     */
    public function test_generate_brand(): void {
        $css = appcss::generate('#07316b');

        // Light block: the brand as primary, with its RGB and a readable (white) contrast.
        $this->assertStringContainsString(':root {', $css);
        $this->assertStringContainsString('--primary: #07316b;', $css);
        $this->assertStringContainsString('--ion-color-primary-rgb: 7, 49, 107;', $css);
        $this->assertStringContainsString('--primary-contrast: #ffffff;', $css);
        $this->assertStringContainsString('--core-header-toolbar-background: #07316b;', $css);

        // A dark block exists and gives a different primary.
        $this->assertStringContainsString(':root.dark', $css);
        $this->assertMatchesRegularExpression('/:root\.dark,\s*\n:root \.dark \{/', $css);
    }

    /**
     * A light brand gets a black contrast.
     */
    public function test_light_brand_gets_dark_contrast(): void {
        $css = appcss::generate('#ffff00');
        $this->assertStringContainsString('--primary-contrast: #000000;', $css);
    }

    /**
     * A three-digit hex is accepted.
     */
    public function test_short_hex(): void {
        $css = appcss::generate('#fff');
        $this->assertStringContainsString('--primary: #ffffff;', $css);
    }

    /**
     * A malformed colour yields a comment, not broken CSS.
     */
    public function test_invalid_colour(): void {
        $css = appcss::generate('not-a-colour');
        $this->assertStringContainsString('no valid brand colour', $css);
        $this->assertStringNotContainsString('--primary:', $css);
    }
}
