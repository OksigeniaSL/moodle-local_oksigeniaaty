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

use local_oksigeniaaty\task\h5p_in_app;

/**
 * H5P content made playable in the app.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(h5pinapp::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(h5p_in_app::class)]
final class h5pinapp_test extends \advanced_testcase {
    /**
     * One deployed H5P content per display option, keyed by that option.
     *
     * @param int[] $options The displayoptions values.
     * @return int[] The content ids, keyed by option.
     */
    private function contents(array $options): array {
        global $DB;

        /** @var \core_h5p_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_h5p');
        $library = $generator->create_library_record('H5P.Example', 'Example', 1, 0);

        $ids = [];
        foreach ($options as $option) {
            $id = $generator->create_h5p_record((int) $library->id);
            $DB->set_field('h5p', 'displayoptions', $option, ['id' => $id]);
            $ids[$option] = $id;
        }

        return $ids;
    }

    /**
     * Download goes on only where the action bar is hidden, and nothing else changes.
     */
    public function test_apply_turns_download_on_only_without_the_bar(): void {
        global $DB;
        $this->resetAfterTest();

        // 15: bar, download, embed and copyright off (the usual case). 3: bar and download off. 14: the bar shown with
        // download off, which the teacher chose. 13 and 8: download already on.
        $ids = $this->contents([15, 3, 14, 13, 8]);

        $this->setAdminUser(); // The H5P activity generator needs a current user.
        $course = $this->getDataGenerator()->create_course();
        $hidden = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course->id, 'displayoptions' => 15]);
        $shown = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course->id, 'displayoptions' => 14]);

        $this->assertSame(2, h5pinapp::apply());

        $this->assertEquals(13, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[15]]));
        $this->assertEquals(1, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[3]]));
        $this->assertEquals(14, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[14]]));
        $this->assertEquals(13, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[13]]));
        $this->assertEquals(8, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[8]]));
        $this->assertEquals(13, $DB->get_field('h5pactivity', 'displayoptions', ['id' => $hidden->id]));
        $this->assertEquals(14, $DB->get_field('h5pactivity', 'displayoptions', ['id' => $shown->id]));

        // Moodle now serves the package: download is on for the content.
        $core = (new \core_h5p\factory())->get_core();
        $this->assertTrue($core->getDisplayOptionsForView(13, $ids[15])[\core_h5p\core::DISPLAY_OPTION_DOWNLOAD]);

        // Run again, nothing is left to change.
        $this->assertSame(0, h5pinapp::apply());
    }

    /**
     * The task leaves everything alone on a site that turned the setting off, and works when it is on or unset.
     */
    public function test_task_follows_the_setting(): void {
        global $DB;
        $this->resetAfterTest();

        $ids = $this->contents([15]);
        $task = new h5p_in_app();

        set_config('h5pinapp', '0', 'local_oksigeniaaty');
        $this->assertFalse(h5pinapp::enabled());
        $task->execute();
        $this->assertEquals(15, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[15]]));

        unset_config('h5pinapp', 'local_oksigeniaaty');
        $this->assertTrue(h5pinapp::enabled());
        $this->expectOutputRegex('/playable in the app: 1\./');
        $task->execute();
        $this->assertEquals(13, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[15]]));
    }
}
