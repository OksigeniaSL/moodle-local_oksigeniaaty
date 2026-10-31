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
 * Tests for the app CSS generator.
 *
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

        // The home layout CSS ships with the sheet.
        $this->assertStringContainsString('.aty-hero', $css);
        // The white hero logo turns dark on the light dark-mode hero.
        $this->assertStringContainsString(':root.dark .aty-hero-logo', $css);
        $this->assertStringContainsString('.aty-tile', $css);
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
     * The sheet dresses the app's own screens, and its links read on white even with a light brand.
     */
    public function test_screens_and_readable_links(): void {
        $css = appcss::generate('#07316b');
        $this->assertMatchesRegularExpression('/--aty-canvas: #[0-9a-f]{6};/', $css);
        $this->assertStringContainsString('ion-item.addon-notification-item', $css);
        $this->assertStringContainsString('addon-calendar-calendar', $css);

        $css = appcss::generate('#ffd400');
        $this->assertSame(1, preg_match('/--core-link-color: #([0-9a-f]{6});/', $css, $match));
        $this->assertGreaterThanOrEqual(4.5, self::contrast_on_white($match[1]));
    }

    /**
     * The WCAG contrast ratio of a colour on white.
     *
     * @param string $hex Six hex digits.
     * @return float
     */
    private static function contrast_on_white(string $hex): float {
        $luminance = 0.0;
        foreach ([0.2126, 0.7152, 0.0722] as $i => $weight) {
            $channel = hexdec(substr($hex, $i * 2, 2)) / 255;
            $channel = $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
            $luminance += $weight * $channel;
        }
        return 1.05 / ($luminance + 0.05);
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
