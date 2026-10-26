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

## Privacy

The plugin stores a hash of each user's app PIN (never the PIN itself), with its failed attempts, and uses Moodle's one-time user keys for the sign-in links. Both are declared to Moodle's privacy API, which exports and deletes them with the rest of the user's data.

## Licence

GNU GPL v3 or later. See [LICENSE](LICENSE).

Copyright © Oksigenia SL.
