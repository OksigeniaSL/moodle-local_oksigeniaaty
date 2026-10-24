# Changelog

All notable changes to `local_oksigeniaaty` are listed here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the plugin follows Moodle's date-based versioning.

## [Unreleased]

## [0.2.0] - 2026-10-24

The per-site design works in the app: brand and home from the settings.

### Added

- The remote CSS the app loads (`mobilecssurl`) is generated from the brand colour, for light and dark mode, and
  served from `styles_app.php` (brand primary, shade, tint, contrast and the header toolbar). The plugin points
  `mobilecssurl` at it on install and when the colour changes, unless the site already set its own app CSS.
- Home shortcuts: a list of "Label | https://url" links shown as items on the home tab.
- PHPUnit tests for the CSS generator.

## [0.1.0] - 2026-10-24

Skeleton of the plugin.

### Added

- Plugin scaffold: `version.php`, language packs (en, es), null privacy provider and settings page.
- Per-site design settings: brand colour, home heading and home welcome.
- A home tab for the app (`CoreMainMenuHomeDelegate`) that renders the configured heading and welcome; it works in
  the official Moodle app too.
- Moodle Plugin CI, secret scan and the no-AI-signature check.
