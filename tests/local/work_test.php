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

namespace local_oksigeniaaty\local;

/**
 * The door of the app in «Your work»: who gets it and its figure.
 *
 * @package    local_oksigeniaaty
 * @category   test
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_oksigeniaaty\local\work
 */
final class work_test extends \advanced_testcase {
    /**
     * Only whoever may send the app gets the door; it counts the people with the site's app, once each.
     */
    public function test_door(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $teacher = $generator->create_user();
        $manager = $generator->create_user();
        $generator->role_assign($DB->get_field('role', 'id', ['shortname' => 'manager']), $manager->id);
        set_config('androidappid', 'com.oksigenia.aty', 'tool_mobile');

        $this->assertSame([], work::for_user((int) $teacher->id, false));

        $phone = $generator->create_user();
        $gone = $generator->create_user(['suspended' => 1]);
        foreach ([[$phone, 'a1'], [$phone, 'a2'], [$gone, 'a3'], [$teacher, 'o1']] as [$user, $uuid]) {
            $DB->insert_record('user_devices', (object) [
                'userid' => $user->id, 'appid' => $uuid[0] === 'o' ? 'com.moodle.moodlemobile' : 'com.oksigenia.aty',
                'name' => 'Pixel', 'model' => 'Pixel', 'platform' => 'Android', 'version' => '16', 'pushid' => 'push-' . $uuid,
                'uuid' => $uuid, 'timecreated' => time(), 'timemodified' => time(),
            ]);
        }

        $doors = work::for_user((int) $manager->id, false);
        $this->assertCount(1, $doors);
        $this->assertSame('local_oksigeniaaty', $doors[0]['key']);
        $this->assertSame('Mobile app', $doors[0]['title']);
        $this->assertStringContainsString('/local/oksigeniaaty/sendapp.php', $doors[0]['url']);
        $this->assertSame('1 person with the app', $doors[0]['status']);
        $this->assertSame([], $doors[0]['alerts']);
        // A manager does not run the site: no QR code.
        $this->assertSame([], $doors[0]['actions']);

        $this->setAdminUser();
        $doors = work::for_user(2, false);
        $this->assertSame('Site QR code', $doors[0]['actions'][0]['label']);
    }
}
