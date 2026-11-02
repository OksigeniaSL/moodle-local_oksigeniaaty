<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_oksigeniaaty\local;

use local_oksigeniaaty\output\mobile;

/**
 * "Where to get your notices".
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(channels::class)]
final class channels_test extends \advanced_testcase {
    /**
     * The names of the rows of a person.
     *
     * @param \stdClass $user The person.
     * @return string[]
     */
    private function names(\stdClass $user): array {
        return array_column(channels::for_user($user), 'name');
    }

    /**
     * The app shows only where push is set up, and the email says when the person has it off.
     */
    public function test_app_and_email(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['email' => 'ana@example.com']);
        $app = get_string('channels:app', 'local_oksigeniaaty');

        $CFG->airnotifieraccesskey = '';
        $this->assertNotContains($app, $this->names($user));

        $CFG->airnotifieraccesskey = 'key';
        $DB->set_field('message_processors', 'enabled', 1, ['name' => 'airnotifier']);
        $rows = channels::for_user($user);
        $this->assertSame($app, $rows[0]['name']);
        $this->assertTrue($rows[0]['on']);
        $this->assertSame('ana@example.com', $rows[1]['detail']);
        $this->assertTrue($rows[1]['on']);

        $user->emailstop = 1;
        $rows = channels::for_user($user);
        $this->assertSame(get_string('channels:emailoff', 'local_oksigeniaaty'), $rows[1]['detail']);
        $this->assertFalse($rows[1]['on']);
    }

    /**
     * An account of a family has no email row when its address is the invented one.
     */
    public function test_family_account_with_an_invented_address(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->airnotifieraccesskey = '';
        $user = $this->getDataGenerator()->create_user(['auth' => 'webservice', 'username' => 'atyfamily-x',
            'email' => 'atyfamily-x@invalid.invalid']);
        $this->assertSame([], channels::for_user($user));
    }

    /**
     * The page: what is on says so, what can be connected has its button, and no text can run as a template.
     */
    public function test_template(): void {
        global $OUTPUT;
        $this->resetAfterTest();
        $html = $OUTPUT->render_from_template('local_oksigeniaaty/mobile_channels', [
            'intro' => 'Intro',
            'rows' => [
                ['name' => 'This app', 'icon' => 'fas-mobile-screen', 'detail' => 'Here', 'on' => true],
                ['name' => 'WhatsApp', 'icon' => 'fab-whatsapp', 'url' => 'https://wa.me/1', 'connect' => 'Connect WhatsApp'],
            ],
            'prefsurl' => 'https://example.com/message/notificationpreferences.php',
        ]);
        $this->assertSame(1, substr_count($html, 'fas-circle-check'));
        $this->assertStringContainsString('core-link href="https://wa.me/1" capture="false"', $html);
        $this->assertStringContainsString('aria-label="Connect WhatsApp"', $html);
        $this->assertStringContainsString('notificationpreferences.php" capture="true"', $html);

        $this->setUser($this->getDataGenerator()->create_user(['email' => 'ana@example.com']));
        set_config('shortname', '', 'local_oksigeniaaty');
        $site = get_site();
        $site->shortname = '{{7*7}}';
        $GLOBALS['DB']->update_record('course', $site);
        $page = mobile::mobile_channels([])['templates'][0]['html'];
        $this->assertStringNotContainsString('{{7*7}}', $page);
        $this->assertStringContainsString('ana@example.com', $page);
    }

    /**
     * Without a messaging channel, the page does not show.
     */
    public function test_disabled_without_a_channel(): void {
        if (class_exists(\local_oksigeniamarandu\api::class)) {
            $this->markTestSkipped('This site has Oksigenia Marandu.');
        }
        $this->assertTrue(mobile::channels_init([])['disabled']);
    }
}
