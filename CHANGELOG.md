# Changelog

All notable changes to `local_oksigeniaaty` are listed here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the plugin follows Moodle's date-based versioning.

## [Unreleased]

### Added

- A sign-in link a school sends: `\local_oksigeniaaty\api::issue_link($userid, $days)` returns a one-time link to
  the app that lasts days (7 by default) and `api::send_link()` e-mails it, for other plugins such as a library that
  registers its readers. Its own kind of key, so it leaves the user's self-service key alone; never for
  administrator, suspended or deleted accounts; each link logged with who issued it (`link_issued`).
- A user's own brand in a site shared by several schools or libraries: plugins say which with
  `<component>_oksigeniaaty_user_brand($userid)`. The home shows its name, tagline and logo, and the web service
  `local_oksigeniaaty_get_user_style` gives the Aty app its brand colours (light and dark) to put on top of the
  site's sheet.
- "Your agenda on your phone", in the app's user menu: subscribe the phone's calendar to the user's Moodle calendar
  (Moodle's own export feed), with Google Calendar's subscription link, the standard webcal link for other calendar
  apps and the plain address. It keeps up to date on its own and needs no new permission in the app.
- "To grade" on the app's home, for teachers: the assignments with submissions waiting for a grade, the one waiting
  longest first, with Moodle's own count (enrolled students, the teacher's group) and a link to the assignment.
- "Open this page in the app": on Android phones, a slim bar at the foot of every page opens that same page in the
  app the site uses, Aty or Moodle's own (its URL scheme and package from the mobile app settings), or that app's
  store page when it is not installed. Any link that lands in the phone's browser (an e-mail, a WhatsApp notice) goes
  on in the app. Off by default (setting `openinappbar`); each person can hide it for 30 days. Never inside the app
  itself, nor on iPhone yet.
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

- "Open in the Aty app" (the user's own sign-in QR and button) is also in the user menu, so it shows whatever the
  theme or profile page; it used to be only in the profile's Miscellaneous section.

- Setting a PIN asks for it twice: the app's second pad confirms it, with its wording sent by token.php like the
  rest of the pad.

### Fixed

- The token renewal service also says when the current token expires when it asks for the PIN, so an app that
  signed in with a password (and so never learned the expiry) renews in time instead of logging the user out after
  the token's lifetime. PHPUnit tests for the renewal service.
- In dark mode the home hero is light with dark text, and the white logo on it hardly showed: it now takes a dark ink.
- Passwordless sign-in failed for everyone after the administrator check moved to a library function that
  token.php did not load (it answered the generic "link expired or used" error).
- Administrators are no longer offered a passwordless QR they cannot use (the app refuses them by design): it is not in
  their menus, the page tells them to sign in with their password, and so does the app if a link reaches it.
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
