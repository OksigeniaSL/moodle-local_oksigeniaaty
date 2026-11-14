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

$string['appnotallowed'] = 'Your organisation\'s plan does not include the app. You can keep using it in your browser.';
$string['appnotallowed:browser'] = 'Open in the browser';
$string['cachedef_sitelist'] = 'The list of the Aty app\'s sites, for an hour';
$string['cachedef_work'] = 'How many people have the app, for «Your work»';
$string['calendarapp:address'] = 'If your calendar app asks for the address, copy this one:';
$string['calendarapp:disabled'] = 'Your site does not have calendar export turned on.';
$string['calendarapp:google'] = 'Add to Google Calendar';
$string['calendarapp:intro'] = 'Add your {$a} agenda to your phone\'s calendar: due dates, classes and events, kept up to date on their own.';
$string['calendarapp:other'] = 'Add to another calendar app';
$string['calendarapp:private'] = 'This address is yours alone: do not share it.';
$string['calendarapp:title'] = 'Your agenda on your phone';
$string['centreqr:alt'] = 'QR code of this site for the Aty app';
$string['centreqr:download'] = 'Download (SVG)';
$string['centreqr:heading'] = 'Site QR code';
$string['centreqr:hint'] = 'The SVG prints sharp at any size; printed, make it at least 3 cm wide. The logo is the site\'s compact logo, or else its full logo (Appearance > Logos); with neither, the code has no logo.';
$string['centreqr:intro'] = 'The code of {$a}, in the site\'s colour and with its logo, for posters, letters and welcome packs. Scanned with the Aty app it opens this site\'s sign-in; with a phone camera, the site in the browser.';
$string['centreqr:menu'] = 'Oksigenia Aty: site QR code';
$string['channels:app'] = 'This app';
$string['channels:appdetail'] = 'Here, on your phone';
$string['channels:connect'] = 'Connect';
$string['channels:connectname'] = 'Connect {$a}';
$string['channels:email'] = 'Email';
$string['channels:emailoff'] = 'Switched off in your profile';
$string['channels:intro'] = 'These are the places where {$a} can reach you. Connect the ones you use.';
$string['channels:on'] = 'You get notices here';
$string['channels:preferences'] = 'Choose which notices go where';
$string['channels:title'] = 'Where to get your notices';
$string['check:appready'] = 'Aty app';
$string['check:appready_androidappid'] = 'The app\'s Android identifier is not «com.oksigenia.aty»: people are not offered the right app.';
$string['check:appready_autologin'] = 'The minimum time between automatic sign-ins is over 10 seconds: opening a second web page from the app asks to sign in again.';
$string['check:appready_css'] = 'The app\'s CSS is not this plugin\'s: the app does not wear the centre\'s brand.';
$string['check:appready_familyapp'] = 'The app for families is on, but something it needs is missing: web service authentication, the URL scheme or signing in through the app.';
$string['check:appready_listunknown'] = 'Oksigenia\'s list of sites could not be read to check that this one is on it.';
$string['check:appready_missing'] = 'The Aty app lacks something on this site to work in full.';
$string['check:appready_mobileservice'] = 'Web services for mobile devices are off.';
$string['check:appready_mycourses'] = 'The site\'s «My courses» page has no block, so the app has no tab for the courses (its home lists them instead). Open «My courses» as an administrator and turn editing on to add the «Course overview» block.';
$string['check:appready_notlisted'] = 'This site is not on Oksigenia\'s list of sites: the app does not open it. Ask Oksigenia to add it.';
$string['check:appready_official'] = 'This site uses Moodle\'s own app: it is not on Aty\'s list and does not ask for Aty.';
$string['check:appready_ok'] = 'This site is ready for the Aty app.';
$string['check:appready_parentrole'] = 'There are families, but Family has no parent role: adults do not see their children in Moodle.';
$string['check:appready_push'] = 'Settings of the push bridge are missing (address, site name or key): no notices reach the phone.';
$string['check:appready_pushapp'] = 'The push bridge\'s mobile app name is not «com.oksigenia.aty»: Moodle finds no phones and tells nobody.';
$string['check:appready_scheme'] = 'The app\'s URL scheme is not «oksigeniaaty»: links and the «Open in the app» bar open Moodle\'s own app.';
$string['check:appready_settings'] = 'Mobile app settings';
$string['check:appready_typeoflogin'] = 'The type of login in the app is not «Via the app»: passwordless links fail.';
$string['check:appready_warnings'] = 'The Aty app works on this site, but something fails without a word.';
$string['check:loginform'] = 'Login form in the app';
$string['check:loginform_differs'] = 'The web\'s login page shows the username and password form, because the theme {$a->theme} has its own switch for it and ignores "{$a->setting}", which is off. The app follows "{$a->setting}" and hides the form: people with a manual account log in on the web but not in the app. Turn "{$a->setting}" on or, if the form should not be shown, hide the local login in the theme too.';
$string['check:loginform_hidden'] = 'The username and password form is hidden on the web and in the app.';
$string['check:loginform_same'] = 'The app shows the username and password form.';
$string['compose:confirm'] = 'Publish this? It will reach everyone in the course.';
$string['compose:course'] = 'Course';
$string['compose:empty'] = 'You cannot write to any course.';
$string['compose:intro'] = 'It goes to the course\'s announcements and reaches everyone in it.';
$string['compose:message'] = 'Message';
$string['compose:publish'] = 'Publish';
$string['compose:sent'] = 'Published. It goes out in a moment.';
$string['compose:subject'] = 'Title';
$string['compose:title'] = 'Write to a course';
$string['event:linkissued'] = 'Sign-in link to the app issued';
$string['event:pincleared'] = 'App PIN cleared by the school';
$string['event:pinfailed'] = 'Wrong app PIN';
$string['event:pinlocked'] = 'App PIN locked';
$string['expiredkey'] = 'This sign-in link has expired. Ask for a new one.';
$string['family:add'] = 'Add with another code';
$string['family:addbutton'] = 'Add';
$string['family:added'] = 'Added. Pull down on the home screen to see them.';
$string['family:addintro'] = 'Type the code on the letter from the centre.';
$string['family:code'] = 'Code';
$string['family:grades'] = 'Latest grades';
$string['family:heading'] = 'Your family';
$string['family:linkgone'] = 'This link no longer works. Ask the centre for a new code.';
$string['family:linktitle'] = 'The app';
$string['family:linkused'] = 'This link has already taken you into the app. To use the app on another phone, ask the centre for a new code.';
$string['family:nosurname'] = '(family)';
$string['family:notavailable'] = 'This site has no family records.';
$string['family:nothing'] = 'Nothing to show yet.';
$string['family:notyours'] = 'You cannot see this person.';
$string['family:openapp'] = 'Open in the app';
$string['family:openappnote'] = 'On your phone. You will enter without a password and create a PIN for next time.';
$string['family:toomany'] = 'Too many tries. Wait a few minutes and try again.';
$string['forgotpin:emailbody'] = 'Hello {$a->name},

Use this link to reset your Aty PIN and set a new one. It works once and expires in 30 minutes:

{$a->link}

If you did not ask for this, you can ignore this message.';
$string['forgotpin:emailbutton'] = 'Set a new PIN';
$string['forgotpin:emailheading'] = 'Set a new PIN';
$string['forgotpin:emaillead'] = 'Open this e-mail on your phone and press the button. The app will ask you for a new PIN.';
$string['forgotpin:emailonce'] = 'The link works once and expires in 30 minutes.';
$string['forgotpin:emailsubject'] = 'Reset your Aty PIN';
$string['forgotpin:emailwhy'] = 'If you did not ask for this, ignore this e-mail: your PIN stays as it is.';
$string['forgotpin:heading'] = 'Forgot your Aty PIN?';
$string['forgotpin:intro'] = 'Enter your username or email and we will email you a link to reset your PIN.';
$string['forgotpin:link'] = 'Forgot your PIN?';
$string['forgotpin:search'] = 'Username or email';
$string['forgotpin:sent'] = 'If that account exists and uses Aty, we have emailed a link to reset the PIN. It works once and expires in 30 minutes.';
$string['forgotpin:submit'] = 'Send reset link';
$string['go:openbtn'] = 'Open in the app';
$string['go:title'] = 'Open in the app';
$string['go:web'] = 'Continue in the browser';
$string['home:day:and'] = '{$a->first} and {$a->last}';
$string['home:day:close'] = '{$a} activities that close';
$string['home:day:close1'] = 'an activity that closes';
$string['home:day:dateformat'] = '%e %B';
$string['home:day:due'] = '{$a} things due';
$string['home:day:due1'] = 'something due';
$string['home:day:event'] = '{$a} events';
$string['home:day:event1'] = 'an event';
$string['home:day:grading'] = 'You have {$a} submissions to grade.';
$string['home:day:grading1'] = 'You have a submission to grade.';
$string['home:day:live'] = '{$a} live classes';
$string['home:day:live1'] = 'a live class';
$string['home:day:next'] = 'Nothing for today. Next up: {$a}.';
$string['home:day:nexttomorrow'] = 'Nothing for today. Next up: tomorrow.';
$string['home:day:nothing'] = 'Nothing for today.';
$string['home:day:today'] = 'Today you have {$a}.';
$string['home:day:todo'] = 'You have things to do: {$a}.';
$string['home:day:todo1'] = 'You have something to do: {$a}.';
$string['home:day:todomany'] = 'You have {$a} things to do.';
$string['home:day:unread'] = '{$a} unread conversations';
$string['home:day:unread1'] = 'an unread conversation';
$string['home:event:close'] = 'Closes';
$string['home:event:due'] = 'Due';
$string['home:event:expectcompletionon'] = 'To complete';
$string['home:event:gradingdue'] = 'To grade';
$string['home:event:open'] = 'Opens';
$string['home:greeting'] = 'Hi, {$a}';
$string['home:hello'] = 'Hi';
$string['home:inminutes'] = 'in {$a} min';
$string['home:join'] = 'Join';
$string['home:kind:close'] = 'Closes';
$string['home:kind:due'] = 'Due';
$string['home:messages'] = 'Messages';
$string['home:mycourses'] = 'Your courses';
$string['home:now'] = 'now';
$string['home:opensoutside'] = 'Opens outside the app';
$string['home:placeholdernames'] = 'student, pupil, learner, teacher, parent, family, user, guest, demo, test';
$string['home:seeall'] = 'See all';
$string['home:shortcuts'] = 'Also';
$string['home:submission'] = 'submission';
$string['home:submissions'] = 'submissions';
$string['home:tab'] = 'Home';
$string['home:today'] = 'Today';
$string['home:tograde'] = 'To grade';
$string['home:unread'] = 'unread';
$string['home:upcoming'] = 'Upcoming';
$string['invalidkey'] = 'This sign-in link is not valid.';
$string['issued:body'] = 'Hello, {$a->firstname}:

To enter the {$a->site} app without a password, open this link on your phone. It works once, until {$a->expires}:

{$a->url}

If you do not have the app yet, the link takes you to it. After that, you enter with your PIN.';
$string['issued:button'] = 'Open the app';
$string['issued:heading'] = 'You can now use the {$a->site} app';
$string['issued:hello'] = 'Hello, {$a->firstname}:';
$string['issued:lead'] = 'Open this e-mail on your phone and press the button. You will enter without a password and only have to create a PIN for next time.';
$string['issued:noapp'] = 'If you do not have the app yet, the button takes you to it.';
$string['issued:once'] = 'The link works once, until {$a->expires}. It is yours alone: do not forward it.';
$string['issued:subject'] = 'Your way into the {$a->site} app';
$string['issued:why'] = 'Sent by {$a->site}. If you were not expecting it, you can ignore this e-mail.';
$string['issuenotallowed'] = 'A sign-in link cannot be issued for this account: administrator, suspended or deleted accounts always sign in with their password.';
$string['mobilenotenabled'] = 'Mobile access is not enabled on this site.';
$string['mylink:expires'] = 'This link works once and expires in 30 minutes. Reload this page to get a new one.';
$string['mylink:heading'] = 'Open in the Aty app';
$string['mylink:intro'] = 'Tap the button on your phone to open the Aty app already signed in. No password needed.';
$string['mylink:nav'] = 'Open in the Aty app';
$string['mylink:qralt'] = 'QR code to sign in to the Aty app';
$string['mylink:qrhint'] = 'Or scan this code with the Aty app on your phone.';
$string['noios'] = 'On iPhone and iPad, for now you go on using the platform in the browser: the app is for Android.';
$string['oksigeniaaty:issuelink'] = 'Issue an Aty sign-in link or QR for another user';
$string['open:android'] = 'Get it on Google Play';
$string['open:expired'] = 'This sign-in link has expired or has already been used. Ask your school or organisation for a new one.';
$string['open:gotosite'] = 'Go to the platform';
$string['open:ios'] = 'Download on the App Store';
$string['open:noapp'] = 'Do not have the app yet?';
$string['open:openbtn'] = 'Open in Aty';
$string['open:opening'] = 'Opening the app...';
$string['open:title'] = 'Sign in to Aty';
$string['openinapp:hide'] = 'Hide for 30 days';
$string['openinapp:label'] = 'App';
$string['openinapp:open'] = 'Open';
$string['openinapp:text'] = 'Open this page in the app';
$string['pinbiometric'] = 'Confirm your identity';
$string['pinbiometricask'] = 'Use your fingerprint or face to sign in next time?';
$string['pincancel'] = 'Cancel';
$string['pinconfirm'] = 'Type it again to make sure it is right.';
$string['pininvalid'] = 'The PIN must be 4 to 8 digits.';
$string['pinlocked'] = 'Too many wrong PINs. Wait a few minutes and try again, or ask for a new sign-in link.';
$string['pinmismatch'] = 'They do not match. Type the same PIN again.';
$string['pinnotsaved'] = 'The PIN could not be saved. You are signed in anyway; you can create it the next time you sign in with a link or a code.';
$string['pinokconfirm'] = 'Confirm PIN';
$string['pinokenter'] = 'Sign in';
$string['pinoksetup'] = 'Create PIN';
$string['pinsetup'] = 'Set a 4 to 8 digit PIN to sign in faster next time. You can skip this and set it later.';
$string['pinskip'] = 'Skip';
$string['pintitleconfirm'] = 'Repeat your PIN';
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
$string['selfservicenotforadmins'] = 'For security, administrator accounts cannot sign in to the app without a password. In the app, sign in with your username and password.';
$string['sendapp:again'] = 'Send again';
$string['sendapp:alldone'] = 'Everyone listed has entered the app or has a link on its way.';
$string['sendapp:ask'] = 'Who are you sending it to?';
$string['sendapp:badgedone'] = 'all in';
$string['sendapp:badgewaiting'] = 'to go';
$string['sendapp:badgewaitingone'] = 'to go';
$string['sendapp:blockedadmin'] = 'Administrator: signs in with a password';
$string['sendapp:blockednoemail'] = 'No e-mail address';
$string['sendapp:chosendetail'] = '{$a->area} · {$a->people} people';
$string['sendapp:chosendetailone'] = '{$a->area} · 1 person';
$string['sendapp:clearpin'] = 'Clear the PIN';
$string['sendapp:cohortdetail'] = 'Cohort · {$a} people';
$string['sendapp:cohortdetailone'] = 'Cohort · 1 person';
$string['sendapp:cohorts'] = 'Cohorts';
$string['sendapp:confirm'] = 'An e-mail with their own link will go to {$a} people who have not entered the app yet. Each link works once, for 7 days. Send them?';
$string['sendapp:confirmone'] = 'An e-mail with their own link will go to the person who has not entered the app yet. The link works once, for 7 days. Send it?';
$string['sendapp:countblocked'] = 'cannot get a link';
$string['sendapp:countblockedone'] = 'cannot get a link';
$string['sendapp:countinapp'] = 'in the app';
$string['sendapp:countsent'] = 'with a link';
$string['sendapp:countwaiting'] = 'waiting';
$string['sendapp:countwaitingone'] = 'waiting';
$string['sendapp:coursedetail'] = '{$a->area} · {$a->people} people · {$a->inapp} in the app';
$string['sendapp:coursedetailone'] = '{$a->area} · 1 person · {$a->inapp} in the app';
$string['sendapp:courses'] = 'Courses';
$string['sendapp:failed'] = 'Could not be sent: {$a}.';
$string['sendapp:fixemail'] = 'Add an e-mail';
$string['sendapp:group'] = 'Group';
$string['sendapp:groupblocked'] = 'Cannot be sent a link';
$string['sendapp:groupinapp'] = 'Already in the app';
$string['sendapp:groupsent'] = 'Link on its way';
$string['sendapp:groupwaiting'] = 'Still to go';
$string['sendapp:heading'] = 'Send the app';
$string['sendapp:intro'] = 'Send people an e-mail with their own link: they open it on their phone and enter the app without a password. Only they receive the link; you never see it.';
$string['sendapp:menu'] = 'Oksigenia Aty: send the app';
$string['sendapp:nobody'] = 'Nobody here. Try another choice.';
$string['sendapp:noresults'] = 'Nothing matches “{$a}”. Try part of the name, or the course’s.';
$string['sendapp:notsent'] = 'It could not be sent. If it was sent to them less than an hour ago, wait a while.';
$string['sendapp:other'] = 'Choose another';
$string['sendapp:people'] = 'People';
$string['sendapp:pincleared'] = 'PIN cleared: the next time they sign in with a link, they will set a new one.';
$string['sendapp:pinnotcleared'] = 'No PIN was cleared.';
$string['sendapp:queued'] = '{$a} e-mails are on their way. They take a few minutes; this list updates as they go.';
$string['sendapp:results'] = 'Matching “{$a}”';
$string['sendapp:running'] = 'Courses under way';
$string['sendapp:runninghint'] = 'Those with the most people without the app come first.';
$string['sendapp:searchhint'] = 'A course, a person or a cohort: part of the name is enough.';
$string['sendapp:searchhintcohorts'] = 'A course or a cohort: part of the name is enough.';
$string['sendapp:searchhintcourses'] = 'A course: part of the name is enough.';
$string['sendapp:searchhintpeople'] = 'A course or a person: part of the name is enough.';
$string['sendapp:seeall'] = 'See the whole list ({$a})';
$string['sendapp:send'] = 'Send';
$string['sendapp:sendall'] = 'Send to the {$a} still waiting';
$string['sendapp:sendallone'] = 'Send to the one still waiting';
$string['sendapp:sentmany'] = 'Sent: {$a}.';
$string['sendapp:sentone'] = 'Sent.';
$string['sendapp:stateexpired'] = 'Link expired on {$a}';
$string['sendapp:stateinapp'] = 'In the app';
$string['sendapp:statesent'] = 'Link sent, works until {$a}';
$string['sendapp:stateuntil'] = 'Valid until {$a}';
$string['sendapp:statewaiting'] = 'No link yet';
$string['sendapp:task'] = 'Send the app to a group of people';
$string['sendapp:truncated'] = 'Showing the first {$a} people.';
$string['sendapp:wholecourse'] = 'The whole course';
$string['sendapp:yes'] = 'Yes, send them';
$string['sendapp:yesone'] = 'Yes, send it';
$string['settings:androidstoreurl'] = 'Google Play link';
$string['settings:androidstoreurl_desc'] = 'The app Google Play page, shown on the sign-in bridge when the app is not installed. Leave empty to hide it.';
$string['settings:brandcolour'] = 'Brand colour';
$string['settings:brandcolour_desc'] = 'The main colour of the site, used for bars, buttons and accents in the app.';
$string['settings:content'] = 'Content in the app';
$string['settings:content_desc'] = 'What the app can open on the phone itself, without the site in a frame.';
$string['settings:design'] = 'App design';
$string['settings:design_desc'] = 'How this site looks in the app: the home tab and the brand. Each hosted site keeps its own values; the one app dresses itself with them.';
$string['settings:extracss'] = 'Extra app CSS';
$string['settings:extracss_desc'] = 'CSS served to the app after the brand skin, for sites without Boost Union. On a Boost Union site its "Additional CSS for Mobile app" box is used instead and this is ignored. Dark mode targets the <code>.dark</code> class on the root; load fonts with absolute https url().';
$string['settings:family'] = 'Families';
$string['settings:family_desc'] = 'For sites with Oksigenia Family: what an adult sees in the app about the children in their family.';
$string['settings:familyagenda'] = 'What comes next';
$string['settings:familyagenda_desc'] = 'The dates in the child\'s courses and groups, for the next two weeks. Not their own calendar entries.';
$string['settings:familyapp'] = 'Families enter the app';
$string['settings:familyapp_desc'] = 'Whoever claims a child\'s code gets a button to open the app, and enters without a password with an account made for them, which cannot sign in on the web. Turning this on also turns on the site\'s web service authentication, which those accounts need. It only works with the Aty app on the site: web services for mobile devices, the URL scheme «oksigeniaaty» and signing in through the app itself (mobile app settings).';
$string['settings:familygrades'] = 'Latest grades';
$string['settings:familygrades_desc'] = 'The child\'s latest grades in activities, leaving out what the course hides. Off until the centre decides to show them.';
$string['settings:h5pinapp'] = 'H5P content plays in the app';
$string['settings:h5pinapp_desc'] = 'The app plays an H5P on the phone, offline too, only when its download is allowed. Without it, the app shows the site\'s page in a frame, and on an iPhone that frame shows the login page instead of the content. On, download is allowed for every H5P whose action bar is hidden: without the bar there is no download button on the site, so nothing changes for the people who use it. Content that shows its action bar is left as its author set it. A task checks every ten minutes for new content.';
$string['settings:homelogourl'] = 'Home logo';
$string['settings:homelogourl_desc'] = 'A white logo shown over the brand header on the home tab. Leave empty to show only the heading.';
$string['settings:homeshortcuts'] = 'Home shortcuts';
$string['settings:homeshortcuts_desc'] = 'Shortcuts shown on the home tab, one per line as "Label | https://url | fas-icon". The icon is an optional solid Font Awesome name the app ships (for example fas-calendar); lines without a valid https link are ignored.';
$string['settings:iosstoreurl'] = 'App Store link';
$string['settings:iosstoreurl_desc'] = 'The app App Store page, shown on the sign-in bridge when the app is not installed. Leave empty to hide it.';
$string['settings:openinappbar'] = 'Offer to open pages in the app';
$string['settings:openinappbar_desc'] = 'On Android phones, a slim bar at the foot of every page offers to open that same page in the app the site uses (Aty, or Moodle\'s own app, as set in the mobile app settings). Each person can hide it for 30 days. Turn it on once the people of the site know they have the app.';
$string['settings:selfservicecohorts'] = 'Self-service sign-in cohorts';
$string['settings:selfservicecohorts_desc'] = 'Only members of the chosen cohorts can get their own sign-in link or QR ("Open in the Aty app"). Leave everything unselected to allow everyone. Staff-issued links are not affected.';
$string['settings:signin'] = 'App sign-in';
$string['settings:signin_desc'] = 'Links and QR codes that sign a user into the app without a password. Each key works once: the one a person gets on the web expires in 30 minutes, and the one the centre sends, in 7 days. The token is revocable from Moodle.';
$string['signinfailed'] = 'Could not sign in. The link may have expired or already been used.';
$string['task:h5pinapp'] = 'Make H5P content playable in the app';
$string['work:qr'] = 'Site QR code';
$string['work:status'] = '{$a} people with the app';
$string['work:status:one'] = '{$a} person with the app';
$string['work:title'] = 'Mobile app';
