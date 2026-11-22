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
 * Sign in to the app through the site, with its second factor: the app opens this page in its own browser, the person
 * signs in as on the web (multi-factor authentication included) and the page hands the app its token through the app's
 * URL scheme, in the same form as tool_mobile's launch.php.
 *
 * Unlike tool_mobile's page, it works whatever the site's type of login in the app, so the app's own form and the
 * sign-in links stay for everyone else. It arrives with a key when token.php has just checked the password
 * (local_oksigeniaaty\local\mfagate): the key does the password step, so the person only gives the second factor.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_oksigeniaaty\local\mfagate;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/authlib.php');

$serviceshortname = required_param('service', PARAM_ALPHANUMEXT);
$passport = required_param('passport', PARAM_RAW);
$urlscheme = optional_param('urlscheme', '', PARAM_NOTAGS);
$key = optional_param('k', '', PARAM_ALPHANUM);

if (empty($CFG->enablewebservices) || empty($CFG->enablemobilewebservice)) {
    throw new moodle_exception('mobilenotenabled', 'local_oksigeniaaty');
}
$service = $DB->get_record('external_services', ['shortname' => $serviceshortname, 'enabled' => 1]);
if (!$service || $serviceshortname !== MOODLE_OFFICIAL_MOBILE_SERVICE) {
    throw new moodle_exception('servicenotavailable', 'webservice');
}
// The token only goes to the app the site names: with any scheme the address asks for, a page could hand a signed-in
// person's token to another app on their phone.
$scheme = (string) get_config('tool_mobile', 'forcedurlscheme');
if ($scheme === '' || !preg_match('/^[a-zA-Z][a-zA-Z0-9-\+\.]*$/', $scheme)) {
    throw new moodle_exception('launch:noscheme', 'local_oksigeniaaty');
}

$params = ['service' => $serviceshortname, 'passport' => $passport, 'urlscheme' => $urlscheme];
$self = new moodle_url('/local/oksigeniaaty/launch.php', $params);

if ($key !== '') {
    $userid = mfagate::spend_key($key);
    if ($userid && (!isloggedin() || isguestuser() || (int) $USER->id !== $userid)) {
        $user = get_complete_user_data('id', $userid);
        if ($user && empty($user->suspended) && !login_is_lockedout($user) && is_enabled_auth($user->auth)) {
            if (isloggedin() && !isguestuser()) {
                require_logout();
            }
            complete_user_login($user);
            // Multi-factor authentication checks the session as soon as the next page starts, and comes back to where it
            // was told: the sign-in page tells it, so this page does too.
            $SESSION->wantsurl = $self->out(false);
        }
    }
    // On without the key in the address; a key that no longer works just leaves the usual sign-in.
    redirect($self);
}

$PAGE->set_url($self);
require_login(0, false);
core_user::require_active_user($USER);
if (isguestuser() || \core\session\manager::is_loggedinas()) {
    throw new moodle_exception('signinfailed', 'local_oksigeniaaty');
}

$timenow = time();
$token = \core_external\util::generate_token_for_current_user($service);
$privatetoken = $token->privatetoken;
\core_external\util::log_token_request($token);

// As tool_mobile: the private token only for a sign-in just made or a token just created, over https, and never for an
// administrator.
if (empty($SESSION->justloggedin) && $token->timecreated < $timenow) {
    $privatetoken = null;
}
$apptoken = md5($CFG->wwwroot . $passport) . ':::' . $token->token;
if ($privatetoken && is_https() && !has_capability('moodle/site:config', context_system::instance())) {
    $apptoken .= ':::' . $privatetoken;
}
$location = $scheme . '://token=' . base64_encode($apptoken);

if (core_useragent::is_ios()) {
    // On iOS, another app opens only from a tap, which the page makes itself.
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('embedded');
    $PAGE->set_title(get_string('clickheretolaunchtheapp', 'tool_mobile'));
    echo $OUTPUT->header();
    $link = html_writer::link($location, get_string('clickheretolaunchtheapp', 'tool_mobile'), ['id' => 'launchapp']);
    echo $OUTPUT->box($link, 'generalbox centerpara');
    echo html_writer::script("window.onload = function() { document.getElementById('launchapp').click(); };");
    echo $OUTPUT->footer();
    exit;
}

header('Location: ' . $location);
exit;
