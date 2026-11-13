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

        // Light only: a page canvas with a hint of the brand behind white cards, and links in a shade of the brand that
        // reads on white (the app's own dark theme keeps its canvas, and its selector wins over :root).
        $canvas = self::fromhsl($h, min($s, 0.2), 0.955);
        $surface = ":root {\n"
            . '    --aty-canvas: ' . self::tohex($canvas) . ";\n"
            . "    --ion-background-color: var(--aty-canvas);\n"
            . '    --ion-background-color-rgb: ' . self::rgbcsv($canvas) . ";\n"
            . '    --core-link-color: ' . qrdesign::ink(self::tohex($brand)) . ";\n"
            . "}\n";

        return self::block(':root', $light)
            . self::block(":root.dark,\n:root .dark", $dark)
            . $surface
            . self::homecss()
            . self::screenscss();
    }

    /**
     * The sheet a plugin handler brings to the app: the home's layout, with the brand's colours only inside the home. The
     * official app does not apply the site's mobile CSS but loads this one, and the rest of that app keeps its own look.
     *
     * @param string $brandhex The site brand colour, e.g. "#07316b".
     * @return string The CSS.
     */
    public static function homesheet(string $brandhex): string {
        return self::homevars($brandhex, '.aty-home', ":root.dark .aty-home,\n:root .dark .aty-home") . self::homecss();
    }

    /**
     * The sheet of a user's own brand, on top of the site's: the variables at the root and, because the home's own sheet
     * sets the site's on the home itself, where nothing inherited can beat it, again on the home. There its selectors are
     * more specific than that sheet's, so it does not matter which of the two loads last.
     *
     * @param string $brandhex The brand colour, e.g. "#07316b".
     * @return string The CSS, or '' for a malformed colour.
     */
    public static function usersheet(string $brandhex): string {
        return self::brandvars($brandhex) . self::homevars(
            $brandhex,
            ':root .aty-home.aty-home',
            ":root.dark .aty-home.aty-home,\n:root .dark .aty-home.aty-home"
        );
    }

    /**
     * The brand variables of a colour, set on the home instead of at the root.
     *
     * @param string $brandhex The brand colour, e.g. "#07316b".
     * @param string $light The selector for the light ones.
     * @param string $dark The selector for the dark ones.
     * @return string The CSS, or '' for a malformed colour.
     */
    private static function homevars(string $brandhex, string $light, string $dark): string {
        $vars = self::brandvars($brandhex);
        // Without the light canvas, which would win over the app's dark theme once it is inside the home.
        $canvas = strpos($vars, ":root {\n    --aty-canvas");
        $vars = $canvas === false ? $vars : substr($vars, 0, $canvas);

        return strtr($vars, [":root.dark,\n:root .dark" => $dark, ':root {' => $light . ' {']);
    }

    /**
     * Only the brand variables of a colour (light, dark and the light canvas), with no layout: a user's own brand put on
     * top of the site's sheet, such as a library's in a shared site.
     *
     * @param string $brandhex The brand colour, e.g. "#07316b".
     * @return string The CSS, or '' for a malformed colour.
     */
    public static function brandvars(string $brandhex): string {
        if (self::parse($brandhex) === null) {
            return '';
        }
        $sheet = self::generate($brandhex);

        return substr($sheet, 0, strpos($sheet, self::homecss()));
    }

    /**
     * The static layout of the home tab: the low band of the brand, "Today" with what is about to start and its "Join",
     * the deadlines that say what they are, the plugins' cards with their "See all" and the shortcuts as chips.
     *
     * It is the same for every client and only reads the brand variables above and the app's own (Ionic's and Aty's), so it
     * follows light and dark on its own. Design: the home of 13 November 2026, on the common per-client template
     * (agents/arte/docs/aty-app-plantilla.md).
     *
     * @return string
     */
    private static function homecss(): string {
        return <<<CSS

.aty-home { padding: 12px 16px 24px;
    --aty-card-shadow: 0 1px 2px rgba(0, 0, 0, .06), 0 8px 18px -12px rgba(0, 0, 0, .35); }
:root.dark .aty-home, :root .dark .aty-home { --aty-card-shadow: 0 0 0 1px rgba(255, 255, 255, .08); }
.aty-section { margin: 22px 4px 6px; font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    color: var(--ion-color-medium); }
.aty-agenda { display: flex; flex-direction: column; gap: 8px; }
.aty-event { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 14px;
    background: var(--ion-item-background, var(--ion-background-color)); color: var(--ion-text-color);
    text-decoration: none; box-shadow: var(--aty-card-shadow); }
:root.dark .aty-event, :root .dark .aty-event { box-shadow: none; outline: 1px solid rgba(255, 255, 255, .08); }
.aty-event-date { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center;
    width: 46px; height: 46px; border-radius: 12px; background: rgba(var(--ion-color-primary-rgb), .12);
    color: var(--primary); line-height: 1; }
.aty-event-day { font-size: 1.25rem; font-weight: 800; }
.aty-event-month { margin-top: 2px; font-size: .62rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    opacity: .9; }
.aty-calendarapp-intro { margin: 0 0 8px; line-height: 1.45; }
.aty-calendarapp-note { margin: 18px 0 6px; font-size: .85rem; color: var(--ion-color-medium); }
.aty-calendarapp-address { margin: 0; padding: 10px 12px; border-radius: 10px; font-size: .78rem; word-break: break-all;
    user-select: text; -webkit-user-select: text; background: rgba(var(--ion-color-primary-rgb), .08); }
.aty-grading .aty-event-date { width: auto; min-width: 46px; padding: 0 8px; }
.aty-grading .aty-event-month { letter-spacing: .02em; }
button.aty-event { width: 100%; border: 0; font: inherit; text-align: start; }
.aty-urgent .aty-event-date { background: rgba(var(--ion-color-danger-rgb), .14); color: var(--ion-color-danger); }
.aty-event-body { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.aty-event-name { font-weight: 700; line-height: 1.25; }
.aty-event-context { font-size: .8rem; color: var(--ion-color-medium); }
.aty-event-time { flex: 0 0 auto; font-size: .82rem; font-weight: 700; color: var(--ion-color-medium); }

/* The band of the brand: the greeting and the day in a line, the logo small. Low, so that "Today" is on the first screen. */
.aty-franja { display: flex; align-items: center; gap: 12px; margin: 0 0 6px; padding: 12px 14px; border-radius: 16px;
    background: var(--primary); color: var(--primary-contrast); }
.aty-franja-logo { flex: 0 0 auto; display: block; width: auto; height: 30px; }
.aty-franja-texto { min-width: 0; }
.aty-franja-hola { margin: 0; font-size: 1.05rem; font-weight: 800; line-height: 1.2; }
.aty-franja-dia { margin: 2px 0 0; font-size: .85rem; line-height: 1.3; opacity: .9; }
/* In dark the brand does not shout: a tint of it on the dark canvas, its colour as an edge, the ink of the app. */
:root.dark .aty-franja, :root .dark .aty-franja { background: rgba(var(--ion-color-primary-rgb), .16);
    color: var(--ion-text-color); box-shadow: inset 4px 0 0 var(--primary); }
:root.dark .aty-franja-logo, :root .dark .aty-franja-logo { filter: brightness(0) invert(1); opacity: .85; }

/* The children of a family: small pills under the band, one tap each. */
.aty-hijos { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0 0; }
.aty-hijo { display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 4px 14px 4px 4px;
    border: 0; border-radius: 999px; background: var(--ion-item-background, var(--ion-background-color));
    color: var(--ion-text-color); font: inherit; font-weight: 700; box-shadow: var(--aty-card-shadow); }
.aty-hijo-inicial { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 50%;
    background: rgba(var(--ion-color-primary-rgb), .14); color: var(--primary); font-weight: 800; }
.aty-hijo-mas { padding-left: 14px; color: var(--primary); }
.aty-hijo-solo { justify-content: center; width: 40px; padding: 0; }

/* Today: the hour big where the date was (it is today); what is about to start, with "Join". */
.aty-event-hora { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center;
    min-width: 54px; height: 46px; padding: 0 6px; border-radius: 12px; background: rgba(var(--ion-color-primary-rgb), .12);
    color: var(--primary); line-height: 1; }
.aty-event-hora b { font-size: 1.05rem; font-weight: 800; }
.aty-event-hora small { margin-top: 3px; font-size: .62rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.aty-ahora { flex-wrap: wrap; box-shadow: 0 0 0 2px var(--primary), var(--aty-card-shadow); }
.aty-ahora .aty-event-body { flex: 1 1 0; }
.aty-ahora .aty-event-hora { background: var(--primary); color: var(--primary-contrast); }
/* "Join", under the class and as wide as the card: the one thing to do, for a thumb. */
.aty-entrar { flex: 1 1 100%; display: flex; align-items: center; justify-content: center; min-height: 48px;
    border-radius: 999px; background: var(--primary); color: var(--primary-contrast); font-size: 1.05rem; font-weight: 800;
    text-decoration: none; }
/* A deadline of today: its hour where the date was, with what it is under it, in the colour of what is due. */
.aty-hora-plazo { background: rgba(var(--ion-color-danger-rgb), .12); color: var(--ion-color-danger); }

/* A deadline says what it is: "Due" or "Closes", over its hour; a class keeps its bare hour. */
.aty-plazo { flex: 0 0 auto; display: flex; flex-direction: column; align-items: flex-end; line-height: 1.1;
    color: var(--ion-color-medium); }
.aty-plazo small { font-size: .62rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.aty-plazo b { font-size: .9rem; font-weight: 800; color: var(--ion-text-color); }

/* A row of another plugin with something to do: its button at hand (sign, answer). */
.aty-hacer { flex: 0 0 auto; display: inline-flex; align-items: center; min-height: 40px; padding: 0 16px;
    border: 2px solid var(--primary); border-radius: 999px; color: var(--primary); font-weight: 800; text-decoration: none; }
button.aty-hacer { background: transparent; font-family: inherit; font-size: inherit; }
.aty-urgent .aty-hacer { border-color: var(--ion-color-danger); color: var(--ion-color-danger); }

/* A card of another plugin: two rows on the home, and its own "See all". */
.aty-seccion-cab { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin: 22px 4px 6px; }
.aty-seccion-cab .aty-section { margin: 0; }
.aty-ver { flex: 0 0 auto; color: var(--primary); font-size: .85rem; font-weight: 700; text-decoration: none; }
button.aty-ver { padding: 0; border: 0; background: transparent; font-family: inherit; }

/* What the tab bar does not have: chips, small, in one or two rows. */
.aty-atajos { display: flex; flex-wrap: wrap; gap: 8px; }
.aty-atajo { display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 14px 0 10px;
    border-radius: 999px; background: var(--ion-item-background, var(--ion-background-color)); color: var(--ion-text-color);
    font-weight: 700; text-decoration: none; box-shadow: var(--aty-card-shadow); }
button.aty-atajo { border: 0; font-family: inherit; font-size: inherit; }
.aty-atajo .aty-atajo-icono { color: var(--primary); font-size: 18px; }
.aty-atajo .aty-atajo-fuera { color: var(--ion-color-medium); font-size: 12px; }
CSS;
    }

    /**
     * The app's own screens dressed in the brand: notices, the calendar, the course list, the tab bar and dialogs.
     *
     * Same for every client, it only reads the brand variables, so it follows light and dark on its own. It targets the
     * app's stable classes; where a screen's own styles win on specificity, the rule says so with !important.
     *
     * @return string
     */
    private static function screenscss(): string {
        return <<<CSS

:root, :root.dark, :root .dark { --aty-card-shadow: 0 1px 2px rgba(0, 0, 0, .06), 0 8px 18px -12px rgba(0, 0, 0, .35);
    --core-bottom-tabs-color-selected: var(--primary); --core-tab-color-active: var(--primary);
    --addon-calendar-today-border-color: var(--primary); --info: var(--primary);
    --ion-color-info-rgb: var(--ion-color-primary-rgb); --ion-color-info-contrast: var(--primary-contrast);
    --ion-color-info-shade: var(--primary-shade); --ion-color-info-tint: var(--primary-tint); }
:root.dark, :root .dark { --aty-card-shadow: 0 0 0 1px rgba(255, 255, 255, .08); }
ion-header.collapsible-header-collapsed { --core-header-toolbar-background: var(--aty-toolbar) !important;
    --core-header-toolbar-color: var(--aty-toolbar-color) !important; --core-header-buttons-color: var(--aty-toolbar-color) !important; }
:root ion-action-sheet { --background: var(--ion-item-background); }
ion-accordion.core-course-module-list-wrapper { border: 0 !important; background: var(--ion-item-background) !important;
    box-shadow: var(--aty-card-shadow); }
ion-tab-button.tab-selected ion-icon.core-tab-icon { padding: 4px 20px; border-radius: 16px;
    background: rgba(var(--ion-color-primary-rgb), .14); }
ion-item.addon-notification-item { --background: var(--ion-item-background); --inner-border-width: 0; --border-width: 0;
    margin: 10px 14px; border-radius: 16px; box-shadow: var(--aty-card-shadow); }
ion-item.addon-notification-item:has(ion-note) { --border-width: 0 0 0 4px; --border-color: var(--primary); }
ion-item.addon-notification-item:has(ion-note) .item-heading { font-weight: 700 !important; }
ion-item.addon-notification-item .core-avatar-extra-img { border-radius: 50%; background: var(--ion-item-background);
    box-shadow: 0 0 0 2px var(--ion-item-background); }
addon-calendar-calendar .addon-calendar-months { width: calc(100% - 24px) !important; margin: 4px 12px 12px;
    padding: 4px 6px 12px !important; border-radius: 20px; background: var(--ion-item-background) !important;
    box-shadow: var(--aty-card-shadow); }
addon-calendar-calendar .addon-calendar-day { min-height: 58px !important; padding: 0 !important; border: 0 !important; }
addon-calendar-calendar .addon-calendar-day.dayblank { background: transparent !important; }
addon-calendar-calendar .addon-calendar-weekday { padding: 12px 0 6px; font-size: .72rem; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase; color: var(--ion-color-medium); }
addon-calendar-calendar .addon-calendar-day-number span[aria-hidden] { width: 36px !important; height: 36px !important;
    margin: 6px auto 0 !important; border: 0 !important; border-radius: 50% !important; line-height: 36px !important; }
addon-calendar-calendar .addon-calendar-day.hasevents .addon-calendar-day-number span[aria-hidden] {
    background: rgba(var(--ion-color-primary-rgb), .12); color: var(--primary); font-weight: 800 !important; }
addon-calendar-calendar .addon-calendar-day.today .addon-calendar-day-number span[aria-hidden] {
    background: var(--primary); color: var(--primary-contrast); font-weight: 800 !important; }
addon-calendar-calendar .calendar_event_type { background-color: var(--primary) !important; }
addon-calendar-calendar .calendar_event_site { background-color: var(--primary-shade) !important; }
addon-calendar-calendar .calendar_event_user { background-color: var(--ion-color-medium) !important; }
addon-calendar-calendar .addon-calendar-period { font-weight: 800; text-transform: capitalize; }
addon-calendar-calendar .addon-calendar-navigation ion-button { --color: var(--primary); }
addon-calendar-event-card ion-card, ion-card.core-course-list-item, ion-card.core-course-list-card {
    border: 0 !important; border-radius: 18px !important; box-shadow: var(--aty-card-shadow) !important; }
addon-calendar-event-card ion-card ion-item { --background: var(--ion-item-background); }
ion-card.core-course-list-card .item-heading, ion-card.core-course-list-item .item-heading { font-weight: 700 !important; }
:root { --core-progressbar-background: rgba(var(--ion-color-primary-rgb), .16);
    --core-search-box-background: var(--ion-item-background); --ion-searchbar-background: var(--ion-item-background);
    --core-combobox-background: var(--ion-item-background); --core-input-background: var(--ion-item-background); }
page-core-courses-my ion-item.divider { --background: transparent !important; }
page-core-courses-my ion-item.divider h2 { font-weight: 800; }
page-core-courses-my ion-list, ion-card.addon-block-myoverview { --background: transparent; background: transparent !important;
    box-shadow: none !important; }
ion-chip.core-course-category { --background: rgba(var(--ion-color-primary-rgb), .12); --color: var(--primary);
    background: rgba(var(--ion-color-primary-rgb), .12) !important; color: var(--primary) !important; font-weight: 600; }
:root ion-alert.md, :root ion-alert.ios { --background: var(--ion-item-background); }
page-core-login-credentials, page-core-login-forgotten-password, page-core-login-email-signup,
page-core-login-change-password { --core-login-brand: var(--primary) !important;
    --core-login-brand-contrast: var(--primary-contrast) !important; }
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
            . "    --aty-toolbar: {$v['toolbarbg']};\n"
            . "    --aty-toolbar-color: {$v['toolbarcolor']};\n"
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
