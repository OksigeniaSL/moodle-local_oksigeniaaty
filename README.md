# Oksigenia Aty (local_oksigeniaaty)

Per-site home screen and design for the Oksigenia Aty mobile app.

This Moodle plugin gives each hosted site its own home tab and its own look in the app: a heading, a welcome and, in later versions, the shortcuts, cards and the brand colour that feed the app's remote CSS. It is set from the site's own administration, so the design of each site changes without a new app build. It works in the official Moodle app too, which is why it is built and tried before the app itself.

Oksigenia Aty is one app, published by Oksigenia SL, for the learning sites hosted by Oksigenia. The app is a separate project; this plugin is the Moodle side of it.

## Requirements

- Moodle 4.5 or later (supported: 4.5 and 5.3).

## Install

Copy the plugin into `local/oksigeniaaty` of your Moodle and finish the upgrade from *Site administration*, or install the ZIP from *Site administration ▸ Plugins ▸ Install plugins*.

## Settings

*Site administration ▸ Plugins ▸ Local plugins ▸ Oksigenia Aty*: the brand colour, the home tab (heading, welcome, logo and shortcuts), extra app CSS, the store links and which cohorts may sign in to the app without a password.

*Site administration ▸ Plugins ▸ Local plugins ▸ Oksigenia Aty: site QR code*: the site's QR code in its brand colour and with its logo, to download as SVG for posters and letters. Each user also finds their own one-time sign-in link and QR under *Open in the Aty app*.

*H5P content plays in the app* (on by default): allows download for every H5P whose action bar is hidden, so the app can play it on the phone, offline and on iPhones too. Without the bar no download button shows on the site.

## For other plugins

A plugin can take part in the app's home, in the official Moodle app and in Oksigenia Aty, with functions in its `lib.php`:

- `<component>_oksigeniaaty_home_cards(int $userid): array` adds a section of rows and a tile for that user, opening a
  page of the plugin in the app or an address. The fields are described in `classes/local/homecards.php`. A plugin the
  site turns off in the app (*Site administration ▸ Mobile app ▸ Mobile features*) shows no cards either. The home shows
  two rows of a card at most and, optionally:
  - on the card, `seeall` (`label` and where it opens): its own "See all", next to its heading;
  - on a row, `actionlabel` ("Sign", "Answer"): a button that opens the same as the row. A row with one is something to
    do, and the home shows it first, under "Today";
  - on a row, `kind` (`due` or `close`): its date is a deadline, and the row says "Due" or "Closes" next to its hour;
  - on a row, `time` (true): its date has an hour that matters, shown big when the row is today's.

  Without them a card shows as before. The tile goes among the shortcuts, in "Also".
- `<component>_oksigeniaaty_browser_pages(): array` lists the plugin's pages that work better in the phone's own browser
  than inside the app (sharing, saving a file made on the page, a calendar file, a long video): paths from the site's
  root, a page or a folder ending in `/`. On them the "open in the app" bar does not show, and the home's rows, tiles
  and shortcuts that lead to them open them in the phone's browser.
- `<component>_oksigeniaaty_user_brand(int $userid): ?array` gives a user their own name, colour, logo or picture, and tagline, where
  several schools or libraries share a site.

And `\local_oksigeniaaty\api::issue_link($userid, $days)` and `api::send_link($userid, $days)` make and send a one-time
sign-in link to the app, for a plugin that registers people who have no password yet.

With Oksigenia Tembiapo («Your work» on the dashboard), whoever may send the app gets a door to it with how many people
have it, and the site's QR code at hand for whoever runs the site.

## Privacy

The plugin stores a hash of each user's app PIN (never the PIN itself), with its failed attempts, and uses Moodle's one-time user keys for the sign-in links. Both are declared to Moodle's privacy API, which exports and deletes them with the rest of the user's data.

## Licence

GNU GPL v3 or later. See [LICENSE](LICENSE).

Copyright © Oksigenia SL.
