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
     * Turned off, download goes off again where it was turned on, unless a teacher has shown the bar since; content it
     * did not change stays as it is.
     */
    public function test_undo(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/local/oksigeniaaty/lib.php');

        $ids = $this->contents([15, 3, 14, 13]);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course->id, 'displayoptions' => 15]);

        $this->assertSame(2, h5pinapp::apply());
        $this->assertSame(0, h5pinapp::apply());
        $this->assertSame(3, $DB->count_records('local_oksigeniaaty_h5pdl'));
        // Since then, a teacher shows the bar of the second one.
        $DB->set_field('h5p', 'displayoptions', 0, ['id' => $ids[3]]);

        set_config('h5pinapp', '0', 'local_oksigeniaaty');
        local_oksigeniaaty_h5pinapp_changed();
        $this->assertEquals(15, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[15]]));
        $this->assertEquals(0, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[3]]));
        $this->assertEquals(14, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[14]]));
        $this->assertEquals(13, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[13]]));
        $this->assertEquals(15, $DB->get_field('h5pactivity', 'displayoptions', ['id' => $activity->id]));
        $this->assertSame(0, $DB->count_records('local_oksigeniaaty_h5pdl'));

        // On again, the same as the first time.
        set_config('h5pinapp', '1', 'local_oksigeniaaty');
        local_oksigeniaaty_h5pinapp_changed();
        $this->assertEquals(13, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[15]]));
        $this->assertEquals(13, $DB->get_field('h5pactivity', 'displayoptions', ['id' => $activity->id]));
    }

    /**
     * On a site where it ran before it kept what it changed, everything with the bar hidden and download on is taken as
     * its own, and undone with the rest.
     */
    public function test_adopt(): void {
        global $DB;
        $this->resetAfterTest();

        $ids = $this->contents([13, 1, 15, 12]);
        h5pinapp::adopt();
        h5pinapp::adopt();
        $this->assertSame(2, $DB->count_records('local_oksigeniaaty_h5pdl'));
        $this->assertSame(2, h5pinapp::undo());
        $this->assertEquals(15, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[13]]));
        $this->assertEquals(3, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[1]]));
        $this->assertEquals(15, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[15]]));
        $this->assertEquals(12, $DB->get_field('h5p', 'displayoptions', ['id' => $ids[12]]));
    }

    /**
     * After a library update Moodle empties the content's filtered parameters, and the exported package, made with the
     * old library, goes: the app then asks for a new one. Content whose parameters are filtered keeps its package.
     */
    public function test_refresh_exports_after_a_library_update(): void {
        global $DB;
        $this->resetAfterTest();

        $ids = $this->contents([13, 15]);
        $fs = get_file_storage();
        $context = \context_system::instance();
        foreach ($ids as $id) {
            $fs->create_file_from_string([
                'contextid' => $context->id, 'component' => 'core_h5p', 'filearea' => 'export', 'itemid' => 0,
                'filepath' => '/', 'filename' => "example-$id.h5p",
            ], 'package');
        }
        $DB->set_field('h5p', 'filtered', '', ['id' => $ids[13]]);
        $DB->set_field('h5p', 'filtered', '{"words":[]}', ['id' => $ids[15]]);

        $this->assertSame(1, h5pinapp::refresh_exports());
        $this->assertFalse($fs->file_exists($context->id, 'core_h5p', 'export', 0, '/', "example-{$ids[13]}.h5p"));
        $this->assertTrue($fs->file_exists($context->id, 'core_h5p', 'export', 0, '/', "example-{$ids[15]}.h5p"));
        $this->assertSame(0, h5pinapp::refresh_exports());
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
