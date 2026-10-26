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
 * Generates the remote CSS the app loads (mobilecssurl) from the plugin settings.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oksigeniaaty\local;

/**
 * The brand of a site, turned into the app's CSS variables for light and dark.
 *
 * The variable set matches what the official Moodle app reads and what the design template uses: --primary and its
 * shade, tint and contrast, the RGB forms the app expects, and the header toolbar colours. The dark values are derived
 * from the same brand colour so a site is legible in dark mode without a second setting; the design template may refine
 * them per client.
 */
class appcss {
    /**
     * The full CSS sheet for a brand colour.
     *
     * @param string $brandhex The site brand colour, e.g. "#07316b".
     * @return string The CSS, with a :root block for light and a dark block.
     */
    public static function generate(string $brandhex): string {
        $brand = self::parse($brandhex);
        if ($brand === null) {
            // A malformed colour yields no brand variables, but the home layout still ships (it uses var() fallbacks).
            return "/* local_oksigeniaaty: no valid brand colour set. */\n" . self::homecss();
        }

        [$h, $s, $l] = self::torgbhsl($brand);

        // Light: the brand as the primary, with standard shade/tint and a readable contrast.
        $light = [
            'primary' => self::tohex($brand),
            'primary-rgb' => self::rgbcsv($brand),
            'contrast' => self::contrast($brand) === 'light' ? '#ffffff' : '#000000',
            'contrast-rgb' => self::contrast($brand) === 'light' ? '255, 255, 255' : '0, 0, 0',
            'shade' => self::tohex(self::fromhsl($h, $s, max(0.0, $l - 0.08))),
            'tint' => self::tohex(self::fromhsl($h, $s, min(1.0, $l + 0.08))),
            'toolbarbg' => self::tohex($brand),
            'toolbarcolor' => self::contrast($brand) === 'light' ? '#ffffff' : '#000000',
        ];

        // Dark: a lighter, calmer primary that reads on a dark surface, over a deep brand header.
        $darkprimary = self::fromhsl($h, min($s, 0.6), 0.72);
        $darksurface = self::fromhsl($h, min($s, 0.7), 0.14);
        $dark = [
            'primary' => self::tohex($darkprimary),
            'primary-rgb' => self::rgbcsv($darkprimary),
            'contrast' => self::tohex($darksurface),
            'contrast-rgb' => self::rgbcsv($darksurface),
            'shade' => self::tohex(self::fromhsl($h, min($s, 0.6), 0.64)),
            'tint' => self::tohex(self::fromhsl($h, min($s, 0.6), 0.78)),
            'toolbarbg' => self::tohex($darksurface),
            'toolbarcolor' => self::tohex(self::fromhsl($h, 0.3, 0.92)),
        ];

        return self::block(':root', $light)
            . self::block(":root.dark,\n:root .dark", $dark)
            . self::homecss();
    }

    /**
     * The static layout of the home tab (the hero header and the shortcut mosaic).
     *
     * It is the same for every client and reads the brand variables above, so it follows light and dark on its own.
     * Design: the common per-client template by the art director (agents/arte/docs/aty-app-plantilla.md).
     *
     * @return string
     */
    private static function homecss(): string {
        return <<<CSS

.aty-home { padding: 12px 16px 24px; }
.aty-hero { border-radius: 20px; padding: 20px; background: var(--primary); color: var(--primary-contrast);
    box-shadow: 0 10px 24px -14px rgba(var(--ion-color-primary-rgb), .7); }
.aty-hero-logo { display: block; height: 36px; width: auto; margin: 0 0 14px; }
.aty-hero-title { margin: 0; font-size: 1.5rem; font-weight: 800; line-height: 1.2; }
.aty-hero-intro { margin: 6px 0 0; opacity: .9; }
.aty-section { margin: 22px 4px 6px; font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    color: var(--ion-color-medium); }
.aty-tiles { padding: 0; --ion-grid-column-padding: 6px; }
.aty-tile { position: relative; display: flex; flex-direction: column; gap: 10px; min-height: 104px; margin: 0;
    padding: 14px; border-radius: 16px; background: var(--ion-item-background, var(--ion-background-color));
    color: var(--ion-text-color); text-decoration: none;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .06), 0 8px 18px -12px rgba(0, 0, 0, .35); }
.aty-tile-icon { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 12px; font-size: 20px;
    background: rgba(var(--ion-color-primary-rgb), .12); color: var(--primary); }
.aty-tile-label { font-weight: 700; line-height: 1.25; }
.aty-tile-out { position: absolute; top: 12px; right: 12px; font-size: 12px; color: var(--ion-color-medium); }
:root.dark .aty-tile, :root .dark .aty-tile { box-shadow: none; outline: 1px solid rgba(255, 255, 255, .08); }
.aty-greeting { margin: 2px 4px 12px; font-size: 1.05rem; font-weight: 700; color: var(--ion-text-color); }
.aty-course-card { position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: flex-end;
    min-height: 104px; margin: 0; padding: 14px; border-radius: 16px; text-decoration: none; color: var(--primary-contrast);
    box-shadow: 0 8px 18px -12px rgba(0, 0, 0, .4); }
.aty-course-card::before { content: ''; position: absolute; inset: 0; z-index: 0;
    background: linear-gradient(135deg, var(--primary), var(--primary-shade)); filter: hue-rotate(var(--aty-hue, 0deg)); }
.aty-course-initial { position: relative; z-index: 1; font-size: 2rem; font-weight: 800; line-height: 1; opacity: .85; }
.aty-course-name { position: relative; z-index: 1; margin-top: 8px; font-size: .95rem; font-weight: 700; line-height: 1.25; }
.aty-agenda { display: flex; flex-direction: column; gap: 8px; }
.aty-event { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 14px;
    background: var(--ion-item-background, var(--ion-background-color)); color: var(--ion-text-color);
    text-decoration: none; box-shadow: 0 1px 2px rgba(0, 0, 0, .06), 0 8px 18px -12px rgba(0, 0, 0, .35); }
:root.dark .aty-event, :root .dark .aty-event { box-shadow: none; outline: 1px solid rgba(255, 255, 255, .08); }
.aty-event-date { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center;
    width: 46px; height: 46px; border-radius: 12px; background: rgba(var(--ion-color-primary-rgb), .12);
    color: var(--primary); line-height: 1; }
.aty-event-day { font-size: 1.25rem; font-weight: 800; }
.aty-event-month { margin-top: 2px; font-size: .62rem; font-weight: 700; letter-spacing: .06em; opacity: .9; }
.aty-event-body { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.aty-event-name { font-weight: 700; line-height: 1.25; }
.aty-event-context { font-size: .8rem; color: var(--ion-color-medium); }
.aty-event-time { flex: 0 0 auto; font-size: .82rem; font-weight: 700; color: var(--ion-color-medium); }
CSS;
    }

    /**
     * One CSS block of the app's brand variables.
     *
     * @param string $selector The CSS selector.
     * @param array $v The values (primary, primary-rgb, contrast, contrast-rgb, shade, tint, toolbarbg, toolbarcolor).
     * @return string
     */
    private static function block(string $selector, array $v): string {
        return "$selector {\n"
            . "    --primary: {$v['primary']};\n"
            . "    --ion-color-primary-rgb: {$v['primary-rgb']};\n"
            . "    --primary-contrast: {$v['contrast']};\n"
            . "    --ion-color-primary-contrast-rgb: {$v['contrast-rgb']};\n"
            . "    --primary-shade: {$v['shade']};\n"
            . "    --primary-tint: {$v['tint']};\n"
            . "    --core-header-toolbar-background: {$v['toolbarbg']};\n"
            . "    --core-header-toolbar-color: {$v['toolbarcolor']};\n"
            . "}\n";
    }

    /**
     * Parse a hex colour into [r, g, b], or null if it is not a valid colour.
     *
     * @param string $hex A colour like "#07316b" or "07316b".
     * @return array|null [r, g, b] with each 0-255, or null.
     */
    private static function parse(string $hex): ?array {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return null;
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /**
     * [r, g, b] to "#rrggbb".
     *
     * @param array $rgb [r, g, b].
     * @return string
     */
    private static function tohex(array $rgb): string {
        return sprintf('#%02x%02x%02x', self::clamp($rgb[0]), self::clamp($rgb[1]), self::clamp($rgb[2]));
    }

    /**
     * [r, g, b] to "r, g, b".
     *
     * @param array $rgb [r, g, b].
     * @return string
     */
    private static function rgbcsv(array $rgb): string {
        return self::clamp($rgb[0]) . ', ' . self::clamp($rgb[1]) . ', ' . self::clamp($rgb[2]);
    }

    /**
     * Whether a light or a dark contrast colour reads better on this colour (YIQ).
     *
     * @param array $rgb [r, g, b].
     * @return string 'light' when white reads better, 'dark' when black does.
     */
    private static function contrast(array $rgb): string {
        $yiq = ($rgb[0] * 299 + $rgb[1] * 587 + $rgb[2] * 114) / 1000;
        return $yiq >= 140 ? 'dark' : 'light';
    }

    /**
     * [r, g, b] (0-255) to [h (0-1), s (0-1), l (0-1)].
     *
     * @param array $rgb [r, g, b].
     * @return array [h, s, l].
     */
    private static function torgbhsl(array $rgb): array {
        $r = $rgb[0] / 255;
        $g = $rgb[1] / 255;
        $b = $rgb[2] / 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        if ($max === $min) {
            return [0.0, 0.0, $l];
        }
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        if ($max === $r) {
            $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
        } else if ($max === $g) {
            $h = ($b - $r) / $d + 2;
        } else {
            $h = ($r - $g) / $d + 4;
        }
        return [$h / 6, $s, $l];
    }

    /**
     * [h, s, l] (each 0-1) to [r, g, b] (0-255).
     *
     * @param float $h Hue.
     * @param float $s Saturation.
     * @param float $l Lightness.
     * @return array [r, g, b].
     */
    private static function fromhsl(float $h, float $s, float $l): array {
        if ($s == 0.0) {
            $v = (int) round($l * 255);
            return [$v, $v, $v];
        }
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        return [
            (int) round(self::huetorgb($p, $q, $h + 1 / 3) * 255),
            (int) round(self::huetorgb($p, $q, $h) * 255),
            (int) round(self::huetorgb($p, $q, $h - 1 / 3) * 255),
        ];
    }

    /**
     * One channel of an HSL-to-RGB conversion.
     *
     * @param float $p Helper p.
     * @param float $q Helper q.
     * @param float $t Hue-shifted value.
     * @return float The channel, 0-1.
     */
    private static function huetorgb(float $p, float $q, float $t): float {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }
        return $p;
    }

    /**
     * Keep a channel inside 0-255.
     *
     * @param int|float $value A channel value.
     * @return int
     */
    private static function clamp($value): int {
        return (int) max(0, min(255, round($value)));
    }
}
