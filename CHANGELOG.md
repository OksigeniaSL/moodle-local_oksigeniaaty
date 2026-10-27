# Changelog

All notable changes to `local_oksigeniaaty` are listed here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the plugin follows Moodle's date-based versioning.

## [Unreleased]

### Added

- Designer QR codes in the site's brand: round modules in the brand colour (darkened when too light to read on
  white), rounded eyes, solid alignment patterns, and the site's logo in the middle when it covers no alignment
  pattern (an SVG logo is nested, so it prints sharp; one with its own styles or scripts goes as an image). Used on the personal "Open in the Aty app" page (Moodle's plain QR stays as the fallback) and on a new
  admin page, "Oksigenia Aty: site QR code", with the site's code to download as SVG for posters and letters.
- The home agenda names an activity's dates by the activity ("Case study") and says what the date is next to the
  course ("Customer care · Due"), instead of Moodle's "Case study is due".
- PHPUnit tests for the QR drawing, and a CI step that reads the sample codes with a real reader (zbar).
- The app stylesheet dresses the app's own screens in the brand too, in light and dark: a page canvas with a hint of
  the brand behind white cards, notices as cards (unread ones with a brand stripe), the month calendar as one card
  with today in the brand, course cards with the progress bar and category in the brand, a pill behind the selected
  tab, course sections as white cards, the brand bar also on pages with a folding header (a course, an activity),
  white dialogs and action sheets, and links in a shade of the brand that reads on white.

### Fixed

- Every upgrade now points `mobilecssurl` at the new version of the app stylesheet, so the app and the CDN refetch it;
  a release that only changed the generated CSS used to leave the previous URL in place.

### Changed

- The home agenda's dates and times are drawn by the app in the phone's time zone, as its calendar does (the server's
  text, in the user's Moodle time zone, only shows until then).
- Home shortcuts to pages of the site itself open the app's own screen (My courses, Notifications, Calendar,
  Messages) instead of the browser; shortcuts that leave the site still open outside.

## [0.3.0] - 2026-10-24

The home design from the art director's common template.

### Added

- The home tab now renders a brand hero (optional white logo, heading, welcome) and the shortcuts as a two-column
  mosaic with a Font Awesome icon each and an "opens outside the app" marker for external links. The layout CSS is
  served as part of the generated app stylesheet, so it follows light and dark on its own.
- Home logo setting, and the shortcut lines now take an optional icon ("Label | https://url | fas-icon").

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
