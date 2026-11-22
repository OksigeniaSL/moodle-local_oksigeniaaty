# Changelog

All notable changes to `local_oksigeniaaty` are listed here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the plugin follows Moodle's date-based versioning.

## [Unreleased]

### Fixed (0.5.96-dev)

- Right after installing 0.5.95, `login/token.php` failed for everyone for up to a minute on a site whose PHP cache
  revalidates files every 60 seconds (`opcache.revalidate_freq`): the hook was seen before the new class. The hook now
  checks the class is there, and `token.php` goes on as before until it is.

### Security (0.5.95-dev)

- `login/token.php` no longer gives an app token for the password alone to whoever the site asks for a second factor:
  Moodle's `token.php` never goes through `tool_mfa`. The check comes after the password, with the factors that ask
  nothing of the person (role, authentication type, cohort, IP range, no setup, grace period): a wrong password, a
  locked or suspended account are left to `token.php` as before, everyone the site lets in without a second factor keeps
  the app's form, and the sign-in links do not change. On by default; `mfagate` turns it off. Something unforeseen in
  the check lets `token.php` go on, so it never closes the app's sign-in for everyone.
- The refusal says where to go instead, in words that today's app shows as they are, and carries a launch address
  (`oksigeniaatylaunch`). The app, from its next version, opens it in its own browser and the person only gives the
  second factor: a one-time key of five minutes, bound to the network address, does the password step. `launch.php`
  hands the token back through the app's scheme as tool_mobile's `launch.php` does, but whatever the site's type of
  login, and only to the scheme the site forces (`forcedurlscheme`). The address goes only where that scheme is Aty's;
  elsewhere, the words alone.
- Each refusal goes to the logs (`app_signin_needs_factor`).

### Fixed (0.5.94-dev)

- Oksigenia Family's pages opened from the home (a request for consent) have their style: the home's sheet is served
  with Family's own after it (`styles_home.php`, `appcss::family()`), since the app loads a plugin's sheet only with a handler of it that is on for the
  person, and Family's menu entries are off for a family's adults.

### Changed (0.5.93-dev, the design of the 21 November review)

- «Your work» with a single door that has something waiting: the door opens on the home with its alerts inside, instead
  of the list and the door saying the same twice (`yourwork::shape()` gives `single`). With two doors or more, as before.
- «Add with another code»: scanning the letter's QR comes first and big, with the letter drawn; below, «No camera? Type
  the code», a wide field that shows its shape (XXXX-XXXX); «Add» lights up with the eight letters and digits.
- The PIN pad's new words come from the server, as the rest (`pinhint`, `pinshuffle`, `pinshufflehelp`, in
  `token.php` and in `renew`): «Better with 6 digits or more» while creating it, and «Shuffle the keys» named, with what
  it is for. An app that does not know them keeps its pad as it was.

### Changed (0.5.92-dev)

- A sign-in link opened on a phone that already holds other accounts asks with the person's name as their language says
  it («Tomás Medina Hernández», not the site's «Medina Hernández Tomás» of its lists) and with the centre's name instead
  of its address. `token.php` gives `sitename` too; the app uses it from its next version, and an older one keeps the
  address.
- «Forgot my PIN»'s notice in the app carries the plugin's icon instead of the app's generic pencil.

### Added (0.5.91-dev)

- `\local_oksigeniaaty\api::account_reassigned($userid)`, for a script of the server when the centre gives an account to
  another person: it takes off the account everything of its former owner in Aty. Its PIN; every key into the app issued
  for it (self-service, sent by the centre, PIN reset, and the app's short keys to its web pages); its place in a family,
  when it is an account Aty made for an adult of one; and the app's sessions, the tokens of the mobile service and the
  phones registered for notices. Moodle's own (password, web sessions, profile) stays the script's.

### Changed (0.5.91-dev, capabilities)

- `local/oksigeniaaty:issuelink` (send people their way into the app, «Send the app») is no longer given to the
  manager's role by default: no role has it, and the centre gives it to the roles it trusts. Only for new installations,
  and for a role reset to its defaults: on a site that has the plugin already, the managers keep it until it is taken
  away from them.

### Security (0.5.91-dev)

- «Forgot my PIN» for an account without a mailbox: the notice to the phone no longer carries in its link the reset key,
  which signs in without the PIN, so the push bridge, Firebase and, with Oksigenia Marandu, WhatsApp or Telegram no
  longer see it. The notice leads to a new page, `newpin.php`, which hands the key the account asked for in the last half
  hour to the account's own session, in the app or on the web, and from there to the app, to set a new PIN.
- «Send the app»: «Clear the PIN» is only for whoever e-mail does not reach, as it said, now checked on the server and
  not only in the list. In a course of separate groups, whoever may not see all the groups sees, sends to and clears
  only the people of their own groups, as on the course's page of participants, and the course's card counts only them.
- The home of a family's own account: a plugin's tile that leads to a page of the site no longer shows, as the site's
  shortcuts already did not.

### Fixed (0.5.91-dev)

- «Forgot my PIN» finds an e-mail whatever case it was saved in, on PostgreSQL too («Ana@Example.org»); two accounts
  whose addresses differ only in case find nobody, as two with the same address.

### Changed (0.5.91-dev)

- «Your work» has a hard limit. On the home, six urgent at most, and the others up to three in all, then a line that
  says how many more there are and opens «See all», with the edge of danger when urgent ones are among them. In «See
  all», fifty, then a line with how many more, to the dashboard of the web, where the block has them all.
- A door of «Your work» shows a dot, not a number: the block counts lines of alerts, and «4 pupils without a menu» is
  one line. A ring; filled in the colour of danger, and bigger, when something is urgent; and it says which to screen
  readers.
- An urgent alert of «Your work» and an urgent line of a child on the family's home say «Urgent:» to screen readers,
  not only with their colour.
- The family's home: the «+» says «Add a child» in words, next to «Invite another adult», and the heading is «Your
  children», as everywhere else in the app.

### Security (0.5.90-dev)

- A teacher who is also a parent gets the way into the app with their own account only signed in to it here. Before,
  the link of the family plugin's confirmation by email, which can be opened again for two days from a history or a
  log, gave each time a new key of a day to that real account. Now it is half an hour, as «Open in the app».
- A plugin's block on a child's page: braces written as named references («&lcub;», «&lbrace;») no longer reach the
  app's compiler as an expression, and every at sign is written as a reference, so an address such as
  «info@school.example» no longer leaves the page blank.

### Fixed (0.5.89-dev)

- «Your work»: its doors, shortcuts and alerts open their page of the web in the app's window, signed in, and no longer
  through a plugin's handler of links. Those handlers are for the notices to readers and families: Ayvurenda's took the
  library's «Catalogue» shortcut to the reader's page of the library in the app (seen on the phone).

### Changed (0.5.88-dev)

- «Your work» on the app's home, after a design review:
  - every urgent thing shows, however many, and the others up to three;
  - the doors with something urgent go first, then those with something waiting, then the rest, each group in the
    block's order;
  - a service's name that says twice what its logo says keeps its second part («Library · Library of the school» is
    «Library of the school»);
  - the count of a door is an outline, and filled in the colour of danger when one is urgent: not by the colour alone;
  - a door's line of state on the home is never cut with «…»: the whole parts between «·» that fit, and the whole line
    behind «See all»;
  - the lines go without the full stop some plugins put;
  - in dark, the logos' plate is an off white.

### Added (0.5.87-dev)

- «Your work» on the app's home, for whoever runs something (the office, the canteen, the library, a families' inbox):
  the panel of the dashboard block Oksigenia Tembiapo, where it is installed and enabled, with its order and its hidden
  services. The first three things waiting to be sorted out, the urgent first and each with its service's logo, open
  where they are sorted out; below, a door into each service, two in a row, with how many things of it are
  waiting. «See all» opens the whole panel with each service's shortcuts, only when it has more than the home. A plugin
  takes part through the block's `<component>_oksigenia_work()`, and its tile in "Also" gives way to its door. The line
  under the greeting counts what is waiting among the day's things.

### Added (0.5.86-dev)

- «Invite another adult» on a family's home, next to «+», where Oksigenia Family 0.3.27 has it: the other adult of the
  household (a partner without an account of the site, a grandparent) scans a QR on this phone and joins the same family,
  with all its children.

### Added (0.5.85-dev)

- From the pilot with families in a school:
  - Adding another child in the app scans the QR on the letter with the app's own scanner, on a phone; the code can
    still be typed.
  - A family's home has a card with where the centre's notices reach them (the app, e-mail, WhatsApp, Telegram), with
    «Connect» where they are not yet; it goes once everything is connected. Connecting no longer warns of leaving the
    app.
  - A teacher who is also a parent goes into the app with their own account once Oksigenia Family says it is proven
    theirs (signed in, or the link sent to its address), and only if they may have a sign-in link of their own.
  - The sign-in page, on Android, opens the app or, where it is not installed, Google Play, which hands the sign-in on
    to the app it installs (read from Aty 5.2.4). On an iPhone, the steps in their order (install, then open) and
    Safari's banner of the app, with nothing opened by itself.

### Fixed (0.5.85-dev)

- A family's account no longer takes an address another account of the site already has: two accounts with one
  address broke «Forgotten your password?» for both.

### Fixed (0.5.84-dev)

- The brand's sheet also sets Ionic's own contrast, shade and tint of the primary colour. The app builds those once for
  its own blue, so with a light brand a checkbox, a pressed button or a plugin's sheet that read them got white ink and
  a blue shade (seen in Meals 0.106.0's sheet).

### Changed (0.5.83-dev)

- An older reader's spines, one per line, have a thin line between books and none under them.

### Fixed (0.5.82-dev)

- Seen on the phone with Ayvurenda 1.0.3:
  - An older reader's shelf, without covers, put three books side by side with their words in a narrow column. Now
    each spine has its own line, with its words across.
  - The blocks of a child's page go in the design's order: a plugin's own piece first (the canteen's day), then
    shelves and rows, and the agenda last.
  - On a child's card, what is urgent comes first, whichever plugin it is from.

### Changed (0.5.81-dev)

- A family's own account keeps on its home only the shortcuts that lead outside the site (the school's website): it
  cannot enter the web, so «My badges» and the like led it to a sign-in page.

### Fixed (0.5.80-dev)

- Rows inside any block's body are lines of it, also in a plugin's own body. A test checks a family's own account:
  a greeting in a line, no band, and a card for each child.

### Fixed (0.5.79-dev)

- Seen on the phone: the child's page said «Home» in its bar, and now says the child's name.
- A plugin's block without an icon of its own takes its tile's or its line's (the canteen's cutlery), not a generic one.
- In dark mode, the rows inside a block were boxed one by one, like cards inside the card. They are now lines of it.

### Changed (0.5.78-dev)

- On the library's shelf, a book waiting to be picked up has a striped spine: a row's `pickup` (asked by Ayvurenda).

### Changed (0.5.77-dev)

- What a family sees, with its new design (15 November 2026):
  - **The home:** each child is a card with their initial, name and group, and a line for each thing they have
    today, with its plugin's icon («A · Has eaten it all», «A book is due on Friday» in red). A family's own account
    greets in a line, low, with the day, and «To do» comes before the children.
  - **The child's page:** their head, then what asks for something to do with its own mark, then a block for each
    thing in the same frame with its way to all of it.
    - The library is a shelf: a young reader's covers, an older one's plain spines.
    - The agenda is the week as a strip of five days, with a dot on the days with something, red when something is due.
  - **What asks for something to do** has its own look everywhere: the brand's edge and one word over its name.
- **The cards' contract** (`_oksigeniaaty_child_cards`) takes:
  - `facet`, the child's line of today, with the canteen's menu letter and colour;
  - `block`, a plugin's own body for its block, rendered on the server; Aty takes away the braces and the at signs the
    app would run;
  - a row's `cover`, a book's cover as a data: URL, and the card's `layout` 'shelf' for plain spines.

### Changed (0.5.76-dev)

- A child's page in the family's app shows each card's «See all», as the home does: the canteen's leads to the family's
  page of the canteen (the month, the allergens, the recipes and the ideas for dinner), inside the app.

### Changed (0.5.75-dev)

- In the family's app, a child is named by first name and surnames on their page and their initial, whatever order the
  site uses for its lists: a site that lists by surname showed «ESO 1º A Alumno» at the top of the child's page.

### Added (0.5.74-dev)

- `child.php?id=<the child's user id>`: the address of a child's page, for notices about a child (the canteen's, first).
  In Aty, from the next version of the app, it opens the child's page for an adult of their family; in a browser it
  leads to the person's home.

### Changed (0.5.73-dev)

- From the laptop to the phone without a hitch (asked by a school): on a computer, «Open in the Aty app» shows the QR
  first, to scan with the phone's camera, and no button, which would look for the app on the computer; on the phone,
  the button; on a tablet, both. The sign-in bridge offers only the store of the phone (Google Play on Android, the App
  Store on an iPhone or iPad) and both when it cannot be told. The text for a site without the App Store no longer says
  the app is only for Android.

### Added (0.5.72-dev)

- «Forgot my PIN» also finds the account by mobile number, with Oksigenia Family, when the centre or WhatsApp vouched for
  it; a number shared by two accounts finds nobody, as an e-mail shared by two already did. An account with no real
  mailbox, such as an adult who entered with a child's code, gets the link as a notice to the phone (the app, and
  WhatsApp or Telegram where Oksigenia Marandu is set up) instead of an e-mail that could not arrive. New notice:
  «pinreset».

### Changed (0.5.71-dev)

- The texts people meet name the centre in one word, as the app does: «tu centro» / «your organisation», no longer
  «sitio», «centro u organización» or «school or organisation» (expired sign-in link, no mobile access, no family
  records).

### Changed (0.5.70-dev)

- «Open in the Aty app» shows its QR only on a computer or a tablet, to scan with the phone: on the phone itself, which
  cannot scan its own screen, it shows just the button.
- The centre QR page names the site by its name instead of its web address.
- The sign-in bridge lists the App Store before Google Play, with text links that do not imitate the stores' badges
  («Available on the App Store», «Available on Google Play»), as Apple's marketing rules ask.

### Changed (0.5.69-dev)

- The status check «Aty app» warns when the iOS app identifier in the mobile app settings is not Aty's (6833200207),
  as it already did for Android's: on iPhones, Safari's banner and Moodle's download page would offer Moodle's own app.

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

- «Send the app», redone. One search for a course, a person or a cohort takes the place of the tabs and the
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
  label; with no children yet, it keeps its text.
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
