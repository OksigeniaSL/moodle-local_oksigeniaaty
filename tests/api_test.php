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

namespace local_oksigeniaaty;

use local_oksigeniaaty\local\loginkey;

/**
 * Tests for the sign-in link a school sends.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_oksigeniaaty\api
 */
#[\PHPUnit\Framework\Attributes\CoversClass(api::class)]
final class api_test extends \advanced_testcase {
    /**
     * An issued link: it signs the user in, lasts days, is logged with who issued it, and leaves the user's own
     * self-service key alone.
     */
    public function test_issue_link(): void {
        $this->resetAfterTest();

        $reader = $this->getDataGenerator()->create_user();
        $librarian = $this->getDataGenerator()->create_user();
        $own = loginkey::create((int) $reader->id);

        $this->setUser($librarian);
        $sink = $this->redirectEvents();
        $link = api::issue_link((int) $reader->id, 7);
        $events = array_values(array_filter($sink->get_events(), fn($event) => $event instanceof event\link_issued));

        $this->assertMatchesRegularExpression('#/local/oksigeniaaty/open\.php\?key=[0-9a-f]+$#', $link['url']);
        $this->assertGreaterThan(time() + 6 * DAYSECS, $link['expires']);
        parse_str(parse_url($link['url'], PHP_URL_QUERY), $query);
        $this->assertSame((int) $reader->id, loginkey::peek($query['key'])['userid']);
        $this->assertTrue(loginkey::is_live($own));
        $this->assertCount(1, $events);
        $this->assertSame((int) $librarian->id, (int) $events[0]->userid);
        $this->assertSame((int) $reader->id, (int) $events[0]->relateduserid);

        // Used once, it is gone.
        $this->assertSame((int) $reader->id, loginkey::consume($query['key']));
        $this->assertFalse(loginkey::is_live($query['key']));
    }

    /**
     * Never for an administrator, a suspended or a deleted account.
     */
    public function test_issue_link_refused(): void {
        $this->resetAfterTest();

        $suspended = $this->getDataGenerator()->create_user(['suspended' => 1]);
        foreach ([(int) get_admin()->id, (int) $suspended->id] as $userid) {
            try {
                api::issue_link($userid);
                $this->fail('No sign-in link for this account.');
            } catch (\moodle_exception $e) {
                $this->assertSame('issuenotallowed', $e->errorcode);
            }
        }
    }

    /**
     * The link goes by e-mail to the user, with the site's name and the link in it.
     */
    public function test_send_link(): void {
        $this->resetAfterTest();

        $reader = $this->getDataGenerator()->create_user();
        $sink = $this->redirectEmails();
        $this->assertTrue(api::send_link((int) $reader->id));
        $emails = $sink->get_messages();

        $this->assertCount(1, $emails);
        $this->assertSame($reader->email, $emails[0]->to);
        $this->assertStringContainsString('local/oksigeniaaty/open.php', $emails[0]->body);
    }

    /**
     * A page of the site goes through the app's page only for whoever has the site's app on a phone; anything else stays.
     */
    public function test_app_link(): void {
        global $CFG, $DB;
        $this->resetAfterTest();

        set_config('androidappid', 'com.example.app', 'tool_mobile');
        $user = $this->getDataGenerator()->create_user();
        $page = $CFG->wwwroot . '/local/oksigeniaaty/index.php?a=1&b=2';
        $this->assertSame($page, api::app_link($user->id, $page));

        $DB->insert_record('user_devices', (object) [
            'userid' => $user->id, 'appid' => 'com.other.app', 'name' => 'x', 'model' => 'x', 'platform' => 'android',
            'version' => '1', 'pushid' => 'p1', 'uuid' => 'u1', 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $this->assertSame($page, api::app_link($user->id, $page));

        $DB->insert_record('user_devices', (object) [
            'userid' => $user->id, 'appid' => 'com.example.app', 'name' => 'x', 'model' => 'x', 'platform' => 'android',
            'version' => '1', 'pushid' => 'p2', 'uuid' => 'u2', 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $link = api::app_link($user->id, $page);
        $this->assertStringStartsWith($CFG->wwwroot . '/local/oksigeniaaty/go.php?to=', $link);
        parse_str((string) parse_url($link, PHP_URL_QUERY), $params);
        $this->assertSame($page, $params['to']);
        $this->assertSame(md5($CFG->wwwroot . $user->username), $params['for']);

        $this->assertSame('https://example.org/x', api::app_link($user->id, 'https://example.org/x'));
    }

    /**
     * The page in between only ever leads to this site.
     */
    public function test_local_target(): void {
        global $CFG;
        $this->resetAfterTest();

        $this->assertSame($CFG->wwwroot . '/my/', api::local_target('/my/'));
        $this->assertSame($CFG->wwwroot . '/my/', api::local_target($CFG->wwwroot . '/my/'));
        $this->assertNull(api::local_target('https://example.org/my/'));
        $this->assertNull(api::local_target('//example.org/my/'));
        $this->assertNull(api::local_target($CFG->wwwroot . '.example.org/my/'));
        $this->assertNull(api::local_target($CFG->wwwroot . "/my/\r\nX-Evil: 1"));
        $this->assertNull(api::local_target('javascript:alert(1)'));
    }

    /**
     * The intent names the site's app and falls back to the page itself.
     */
    public function test_app_intent(): void {
        $this->resetAfterTest();

        set_config('forcedurlscheme', 'exampleapp', 'tool_mobile');
        set_config('androidappid', 'com.example.app', 'tool_mobile');
        $intent = api::app_intent('https://example.org/a?b=1&c=2');
        $this->assertSame('intent://link=https%3A%2F%2Fexample.org%2Fa%3Fb%3D1%26c%3D2#Intent;scheme=exampleapp;'
            . 'package=com.example.app;S.browser_fallback_url=https%3A%2F%2Fexample.org%2Fa%3Fb%3D1%26c%3D2;end', $intent);
    }

    /**
     * Aty's link says whose account it is and is new each time; where the app is not installed, the page as it was.
     */
    public function test_app_intent_for_aty(): void {
        $this->resetAfterTest();

        set_config('forcedurlscheme', 'oksigeniaaty', 'tool_mobile');
        set_config('androidappid', 'com.oksigenia.aty', 'tool_mobile');
        $page = 'https://example.org/a?b=1';
        $intent = api::app_intent($page, 'abc123');

        $link = rawurlencode($page) . '%26atylink%3Dabc123';
        $this->assertMatchesRegularExpression('/^intent:\/\/link=' . preg_quote($link, '/') . '\.[a-zA-Z0-9]{12}#Intent;/', $intent);
        $this->assertStringEndsWith(';S.browser_fallback_url=' . rawurlencode($page) . ';end', $intent);
        $this->assertNotSame($intent, api::app_intent($page, 'abc123'));
        $this->assertStringContainsString('%3Fatylink%3D.', api::app_intent('https://example.org/a'));
    }
}
