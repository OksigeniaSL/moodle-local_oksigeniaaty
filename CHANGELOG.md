# Changelog

All notable changes to `local_oksigeniaaty` are listed here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the plugin follows Moodle's date-based versioning.

## [Unreleased]

### Changed (0.5.68-dev)

- The App Store link comes set, like Google Play's: Aty is in the App Store since 14 November 2026. On the upgrade, a
  site with it empty gets it, and one with its own address keeps it. iPhones and iPads now get what Android phones
  already had: the App Store on the sign-in bridge and «Open in the Aty app» in the user menu.

### Changed (0.5.67-dev)

- The exchange of a sign-in key for the app's token moves from token.php to `local\tokenexchange`, which returns the
  answer instead of printing it and stopping, so that it has tests: the token once, what stops it (the mobile service
  off, not a POST over HTTPS, an unknown key, the browser sign-in the site asks for, a suspended account), the
  administrator, the PIN with its lock and the key of «Forgot the PIN». token.php reads the request and prints what the
  class answers; the app sees the same.

### Fixed (0.5.66-dev)

- «H5P plays in the app» can be undone: turned off, download goes off again on the H5P content and activities where it
  turned it on, unless a teacher has shown the action bar since. What it changes is kept in a table of its own. On the
  upgrade, everything with the bar hidden and download on is taken as its own, since before this version it kept no
  list (without the bar, download shows nowhere on the web, so an author's own choice looks the same).

### Fixed (0.5.65-dev)

- A site with «My courses» off, or whose «My courses» page has no block, had no list of courses in the app: the app
  shows its tab «My courses» only with the setting on and a block it can draw on that page (Moodle's course overview),
  and since 0.5.60 the home left the courses to that tab. «My courses» is off by default on a new site since Moodle 5.2. Now the home checks it as the app does («My courses» on, not turned off in the app, a block on the page) and,
  without the tab, lists «Your courses» itself: eight at most, each opening its course in the app, and «See all» to the
  site's page in the phone's browser. Seen on campus.oksigenia.com.
- The status check «Aty app» warns of a page without the block (Moodle does not let anyone add one there from the web).

### Fixed (0.5.64-dev)

- The day's line of the home no longer ends «…2027-28?.» when the thing to do is a question: a title that already ends a
  sentence takes no full stop. Seen on the Pixel with Eguahe's card.

### Changed (0.5.63-dev)

- «Send the app», redone (Jujol). One search for a course, a person or a cohort takes the place of the tabs and the
  form. The courses under way that the person may see come as cards, those with most people still without the app
  first, with how many are missing. Once one is chosen: a phone for each person (a bar past 60), «Send to the 6 still
  waiting» with its question in place (no page apart, no JavaScript), and the people in columns by how they stand, those
  already in the app folded. A long list shows its first 48 people and a link to the whole list, in place of the pages.
  «Clear the PIN» stays, small under the name.
- The rights do not change: the whole site's people only for who may see them, by e-mail or username only for who may
  see those, a course's people for who may see its participants, and cohorts for who may see them. The cards are only
  of those courses, chosen before they are counted, so that how many there are says nothing of the others.
- What sends or clears takes a POST with the session key; the same in an address does nothing.

### Changed (0.5.62-dev)

- Managers (who may log in as anyone or assign roles across the site) get no private token with a passwordless link,
  like site admins: a link in the wrong hands would open a web session with all of that. Their app works; a page
  embedded in it may ask them to sign in.
- «Send the app» lets the school clear the PIN of someone in the list who forgot it and cannot get it back by e-mail (no
  address, or an account made for a family); the next link lets them set a new one. It is logged (`pin_cleared`).
- On the home, the pill to add another child's code is a round «+» at the end of the children's row, with its name as
  label; with no children yet, it keeps its text (Jujol).
- The settings «Home heading» and «Home welcome» are gone: the new home greets the person and says their day, and the
  site's name is in the header. What was set stays stored, unused.
- README: what Aty needs outside the plugin, the status check that tells it, and how to bring people to the app.

### Fixed

- The status check «Aty app» (0.5.61-dev) loads Moodle's file library before reading the list of sites: on a site's
  status page it stopped at «Class "curl" not found».

### Security

- From the first full review of Aty (0.5.59-dev). Changing the app's PIN takes the current one: whoever held the token
  alone could put their own and renew with it. The count of wrong PINs can no longer be outrun by tries sent all at
  once (one try at a time per user), and wrong PINs and locks leave a trace in the logs (`pin_failed`, `pin_locked`).
  `set_pin` and `renew` are no longer open to a web page's JavaScript. The family link (`family.php`) opens the app only
  until the account is in it, not for its whole month. «Send the app» searches the whole site only for who may see the
  site's people, by e-mail or username only for who may see those, and sends again to the same person once an hour at
  most. A deleted account takes its PIN with it.

### Fixed

- A child's agenda, in the app of an adult of their family, showed no dates of the child's activities: the calendar
  checked them against the adult, who is in none of the child's courses. It is asked as the child now.
- A card of another plugin that cannot be read (a title that is not text), or a brand that fails, no longer takes the
  whole home, the sign-in style or the mails down; and the strings inside an object among a row's arguments are made
  inert too. The rows of a child's page with an address open inside the app, like the home's.
- «Forgot your PIN?» works for anyone with a PIN, not only the cohorts of self-service: whoever entered with a link
  from the centre had no way back.

### Changed

- The app's home, redone so that whoever opens it has almost nothing to do (0.5.60-dev):
  - a low band of the brand in place of the big card that repeated the header's name: the logo small, «Hi, Lucía» (just
    «Hi» when the first name is empty or a role's or a test account's word, never «Hi, Student») and a line the server
    puts together with what matters most of the day: what there is to do, then today's live classes, deadlines, events
    and unread conversations, then the submissions to grade, and with nothing, what comes next. In dark it is a tint of
    the brand with its colour as an edge, no longer a light card that shouts. The heading, the welcome and the picture
    no longer show on the home;
  - an adult's children as small pills under the band;
  - «Today» first, with the hour big where the date was. What is about to start, from half an hour before until it ends,
    gets «in 12 min» and a «Join» button as wide as the card: a BigBlueButton room the user may join (its own join link,
    until its closing time) and an event whose location is an address (until its end, when it has a duration); without
    a known end, until it starts. A deadline of today says «Due» or «Closes» under its hour, and those to come say it
    over the hour, no longer next to the course;
  - the cards of other plugins show two rows at most, with their own «See all» (or their tile's when rows were left
    out), and a row with something to do goes to «Today» with its button;
  - «Also» (formerly «Shortcuts») keeps only what the tab bar does not have: my courses, the calendar, the notices and the
    messages go, unless the site has turned that part of the app off; the plugins' tiles are chips there. With nothing
    left, the section does not show. The course cards at the end go too: they are the tab «My courses».
  The layout only reads the variables of the brand, Ionic and Aty, in light and dark, and adds no JavaScript to the app.
- `token.php` also says who the link signs in (`fullname`, `username`), so the app can ask before switching accounts.
- The help of the settings, up to date: the links the centre sends last 7 days, the bar of the app is for once people
  know they have it, and the family app says what it needs.

### Added

- A status check «Aty app» (0.5.60-dev), in the site's status checks: what has to be set outside the plugin for the app
  to open the site, sign people in without a password and bring them notices. Missing (the app does not work): not on
  Oksigenia's list of sites, mobile web services off, the URL scheme or the Android identifier not Aty's, a type of
  login other than «Via the app», the push bridge unset or set for another app. Quiet failures: more than 10 seconds
  between automatic sign-ins, an app CSS other than this plugin's, the family app on without what it needs, families
  without a parent role. A site that uses Moodle's own app has nothing to check. No secret is shown.

- New optional fields for the cards of `<component>_oksigeniaaty_home_cards()` (0.5.60-dev), documented in
  `classes/local/homecards.php`; without them a card shows as before. On a row, `actionlabel` (a button that opens the
  same as the row: the row goes to «Today»), `kind` (`due` or `close`, said next to its hour) and `time` (the date has
  an hour that matters, shown big for a row of today). On a card, `seeall`, its own «See all» with a label and where it
  opens.

- Pages that open in the phone's browser rather than inside the app (0.5.58-dev): a plugin lists them through
  `<component>_oksigeniaaty_browser_pages()`, a page or a folder of its own. The app's in-app browser has no share sheet,
  cannot save a file made on the page, keeps calendar files and WhatsApp or Telegram links inside and has little memory
  for a long video. On those pages the "open in the app" bar does not show, and a row, a tile or a shortcut of the home
  that leads to one opens it in the phone's browser. Asked by Oksigenia Eguahe for the pages of its families.

- A site status check, «Login form in the app» (0.5.56-dev), also shown at the top of the plugin's settings when it
  fails. The app follows Moodle's «Display manual login form»; Boost Union ignores it and shows the form on the web
  through its own «Local login». With the first off and the second on, people with a manual account log in on the web
  but the app only offers the identity providers. The check says so and points to the setting.

- H5P content plays in the app on the phone itself, iPhones included (0.5.55-dev, setting «H5P content plays in the
  app», on by default, and the task `h5p_in_app` every ten minutes). Moodle serves an H5P package only while its
  download is allowed, and without the package the app shows the site in a frame, which on an iPhone shows the login
  page instead of the game. For every H5P whose action bar is hidden, download is now allowed: without the bar no
  download button appears on the web. H5P content that shows its bar keeps what its author chose.

### Fixed

- After a new version of an H5P library, the app plays the new version too (0.5.57-dev). Moodle updates the content and
  makes its package again on the next web view, but the apps ask for the package itself and went on playing the old
  one. The task `h5p_in_app` now deletes the package of every H5P whose content Moodle has refreshed, and the app asks
  for a new one, which Moodle makes then. Seen with the letter wheel 1.0.4 on a site that had 1.0.3.

- When the app cannot save the PIN chosen at the first sign-in, it now says so (0.5.54-dev, string `pinnotsaved`
  sent with the rest of the PIN wording); before, the user believed they had a PIN. Seen on an iPhone, where the app
  lost the second pad and saved nothing; the app fix is in Oksigenia Aty.

- A row or a tile of another plugin on the app's home, and a shortcut to a page of the site that the app cannot open by
  itself, open inside the app with the session signed in (0.5.53-dev). They went to the phone's browser, which asked to
  sign in again; seen with the canteen of Oksigenia Meals. Pages the app opens by itself (courses, calendar, badges) and
  addresses outside the site stay as they were.

### Added

- A door in «Your work», the dashboard block of Oksigenia Tembiapo (contract version 2), from
  `local_oksigeniaaty_oksigenia_work()` (0.5.52-dev): for whoever may send the app, how many people have the app the
  site has set, with the site's QR code at hand for whoever runs the site; the figure kept for a minute (cache `work`).
  The emblem of Aty in `pix/icon.svg`.
- A plugin the site turns off in the app (all its handlers among the mobile features disabled under *Site administration ▸
  Mobile app ▸ Mobile features*) shows no cards on the app's home or on a child's page either: one switch hides it from
  the app, say while a library gets ready.
- "Where to get your notices", in the app's user menu: the app, the email and, where the site has Oksigenia Marandu, WhatsApp
  and Telegram, with a button to connect them.
- `<component>_oksigeniaaty_user_allowed(int $userid): bool`: another plugin can close Aty to someone, such as a plan of a shared
  site that does not include the app. They get no link, and the home says so in Aty; the official app stays as it is.
- A row on a child's page can open nothing (`'plain' => true` in `_oksigeniaaty_child_cards`), for what a family may know
  exists but not see.

### Fixed

- A family with no e-mail gets the notices on the phone. Their account had Moodle's «stop e-mail» on, which stops every
  notice and not only mail; its `.invalid` address is what keeps mail from going out. The upgrade turns it off in the
  accounts already made.
- Moodle's sticky footer sits above the "Open in the app" bar instead of under it, whatever its height: on an activity page
  the bar hid the buttons to complete it and to go on to the next one.
- The buttons of Oksigenia Access and of Boost Union, and the ones that open the drawers on a phone, make room for the bar
  and the footer instead of being covered by them, or covering them.

### Security

- The texts of the app's home and of "Your agenda on your phone" cannot run as templates any more. The app compiles
  them as Angular templates and Mustache escapes neither braces nor at signs, so a course or an activity named with
  double braces or `@let` ran in the app of whoever saw it, with their session, and an unfinished `@if` stopped the
  page from compiling. Each brace and at sign in a text now becomes its full-width look-alike; addresses keep theirs.

### Fixed

- In a site shared by several schools or libraries, the home takes the user's own colour again. Its sheet for the
  official app had set the site's on the home, over the user's.
- The site's sign-in screens in the app take the site's brand. The bar already did, but the button kept the app's own
  colour, navy in light and sky blue in dark, next to a site's green or red.
- The app's home reads well in the official Moodle app too. That app does not apply the site's mobile CSS, so the home
  came with no layout and a white logo on white. Its handler now brings a sheet of its own, with the brand's colours
  only inside the home; the rest of the official app keeps its look. Its tab is called "Home", not the plugin's name.

### Added

- A button in an e-mail can open its page in the app, for whoever has used the app on a phone:
  `\local_oksigeniaaty\api::app_link($userid, $url)`. Mail programs open links in the browser, which asked to sign in
  again. It goes through a small page, `go.php`, that opens the app on Android and goes on to the page anywhere else or
  where the app is not installed, and it only leads to pages of the site.
- Other plugins can add to the app's home for a user, with `<component>_oksigeniaaty_home_cards($userid)`: a section
  of rows (a book's return date, an assignment's count) and a tile next to the shortcuts, opening a page of their own
  in the app or an address. A library's reader, with no courses, finds their books and the way in on the home.
- A sign-in link a school sends: `\local_oksigeniaaty\api::issue_link($userid, $days)` returns a one-time link to
  the app that lasts days (7 by default) and `api::send_link()` e-mails it, for other plugins such as a library that
  registers its readers. Its own kind of key, so it leaves the user's self-service key alone; never for
  administrator, suspended or deleted accounts; each link logged with who issued it (`link_issued`).
- A user's own brand in a site shared by several schools or libraries: plugins say which with
  `<component>_oksigeniaaty_user_brand($userid)`. The home shows its name, tagline and logo, or a picture that fills
  its hero, and the web service
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
