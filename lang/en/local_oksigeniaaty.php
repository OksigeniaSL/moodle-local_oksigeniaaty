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
 * Strings for local_oksigeniaaty, language 'en'.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['expiredkey'] = 'This sign-in link has expired. Ask for a new one.';
$string['forgotpin:emailbody'] = 'Hello {$a->name},

Use this link to reset your Aty PIN and set a new one. It works once and expires in 30 minutes:

{$a->link}

If you did not ask for this, you can ignore this message.';
$string['forgotpin:emailsubject'] = 'Reset your Aty PIN';
$string['forgotpin:heading'] = 'Forgot your Aty PIN?';
$string['forgotpin:intro'] = 'Enter your username or email and we will email you a link to reset your PIN.';
$string['forgotpin:link'] = 'Forgot your PIN?';
$string['forgotpin:search'] = 'Username or email';
$string['forgotpin:sent'] = 'If that account exists and uses Aty, we have emailed a link to reset the PIN. It works once and expires in 30 minutes.';
$string['forgotpin:submit'] = 'Send reset link';
$string['home:opensoutside'] = 'Opens outside the app';
$string['home:shortcuts'] = 'Shortcuts';
$string['invalidkey'] = 'This sign-in link is not valid.';
$string['mobilenotenabled'] = 'Mobile access is not enabled on this site.';
$string['mylink:expires'] = 'This link works once and expires in 30 minutes. Reload this page to get a new one.';
$string['mylink:heading'] = 'Open in the Aty app';
$string['mylink:intro'] = 'Tap the button on your phone to open the Aty app already signed in. No password needed.';
$string['mylink:nav'] = 'Open in the Aty app';
$string['mylink:qralt'] = 'QR code to sign in to the Aty app';
$string['mylink:qrhint'] = 'Or scan this code with the Aty app on your phone.';
$string['oksigeniaaty:issuelink'] = 'Issue an Aty sign-in link or QR for another user';
$string['open:android'] = 'Get it on Google Play';
$string['open:expired'] = 'This sign-in link has expired or has already been used. Ask your school or organisation for a new one.';
$string['open:ios'] = 'Download on the App Store';
$string['open:noapp'] = 'Do not have the app yet?';
$string['open:openbtn'] = 'Open in Aty';
$string['open:opening'] = 'Opening the app...';
$string['open:title'] = 'Sign in to Aty';
$string['pincancel'] = 'Cancel';
$string['pininvalid'] = 'The PIN must be 4 to 8 digits.';
$string['pinlocked'] = 'Too many wrong PINs. Wait a few minutes and try again, or ask for a new sign-in link.';
$string['pinokenter'] = 'Sign in';
$string['pinoksetup'] = 'Create PIN';
$string['pinsetup'] = 'Set a 4 to 8 digit PIN to sign in faster next time. You can skip this and set it later.';
$string['pinskip'] = 'Skip';
$string['pintitleenter'] = 'Enter your PIN';
$string['pintitlesetup'] = 'Create your PIN';
$string['pinwrong'] = 'That PIN is not correct.';
$string['pluginname'] = 'Oksigenia Aty';
$string['privacy:metadata:loginkey'] = 'The one-time keys issued to sign in to the app without a password.';
$string['privacy:metadata:loginkey:timecreated'] = 'When the sign-in key was created.';
$string['privacy:metadata:loginkey:userid'] = 'The user the sign-in key belongs to.';
$string['privacy:metadata:loginkey:validuntil'] = 'When the sign-in key expires.';
$string['privacy:metadata:pin'] = 'The sign-in PIN that protects passwordless access to the app.';
$string['privacy:metadata:pin:fails'] = 'The number of consecutive wrong PIN attempts.';
$string['privacy:metadata:pin:lockuntil'] = 'The time until which PIN checks are locked after too many wrong attempts.';
$string['privacy:metadata:pin:pinhash'] = 'The PIN, stored only as a secure hash.';
$string['privacy:metadata:pin:timemodified'] = 'When the PIN was last set or changed.';
$string['privacy:metadata:pin:userid'] = 'The user the PIN belongs to.';
$string['resetpin:confirm'] = 'Reset your PIN? The next time you open the Aty app you will set a new one.';
$string['resetpin:done'] = 'Your Aty PIN has been reset.';
$string['resetpin:heading'] = 'Reset your Aty PIN';
$string['resetpin:nav'] = 'Reset my Aty PIN';
$string['resetpin:nopin'] = 'You do not have an Aty PIN set.';
$string['selfservicenotallowed'] = 'Passwordless sign-in is not available for your account. Please sign in with your usual details.';
$string['settings:androidstoreurl'] = 'Google Play link';
$string['settings:androidstoreurl_desc'] = 'The app Google Play page, shown on the sign-in bridge when the app is not installed. Leave empty to hide it.';
$string['settings:brandcolour'] = 'Brand colour';
$string['settings:brandcolour_desc'] = 'The main colour of the site, used for bars, buttons and accents in the app.';
$string['settings:design'] = 'App design';
$string['settings:design_desc'] = 'How this site looks in the app: the home tab and the brand. Each hosted site keeps its own values; the one app dresses itself with them.';
$string['settings:extracss'] = 'Extra app CSS';
$string['settings:extracss_desc'] = 'CSS served to the app after the brand skin, for sites without Boost Union. On a Boost Union site its "Additional CSS for Mobile app" box is used instead and this is ignored. Dark mode targets the <code>.dark</code> class on the root; load fonts with absolute https url().';
$string['settings:homeheading'] = 'Home heading';
$string['settings:homeheading_desc'] = 'The title shown at the top of the home tab. Left empty, the site name is used.';
$string['settings:homeintro'] = 'Home welcome';
$string['settings:homeintro_desc'] = 'A short welcome shown under the heading on the home tab.';
$string['settings:homelogourl'] = 'Home logo';
$string['settings:homelogourl_desc'] = 'A white logo shown over the brand header on the home tab. Leave empty to show only the heading.';
$string['settings:homeshortcuts'] = 'Home shortcuts';
$string['settings:homeshortcuts_desc'] = 'Shortcuts shown on the home tab, one per line as "Label | https://url | fas-icon". The icon is an optional solid Font Awesome name the app ships (for example fas-calendar); lines without a valid https link are ignored.';
$string['settings:iosstoreurl'] = 'App Store link';
$string['settings:iosstoreurl_desc'] = 'The app App Store page, shown on the sign-in bridge when the app is not installed. Leave empty to hide it.';
$string['settings:selfservicecohorts'] = 'Self-service sign-in cohorts';
$string['settings:selfservicecohorts_desc'] = 'Only members of the chosen cohorts can get their own sign-in link or QR ("Open in the Aty app"). Leave everything unselected to allow everyone. Staff-issued links are not affected.';
$string['settings:signin'] = 'App sign-in';
$string['settings:signin_desc'] = 'Links and QR codes that sign a user into the app without a password. The key is single use and expires in 30 minutes; the token is revocable from Moodle.';
$string['signinfailed'] = 'Could not sign in. The link may have expired or already been used.';
