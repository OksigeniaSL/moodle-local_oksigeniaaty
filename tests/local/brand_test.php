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
 * Tests for a user's own brand in a shared site.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_oksigeniaaty\local\brand
 * @covers \local_oksigeniaaty\external\get_user_style
 */
#[\PHPUnit\Framework\Attributes\CoversClass(brand::class)]
final class brand_test extends \advanced_testcase {
    /**
     * What a plugin returns is checked: a name and a valid colour, or nothing.
     */
    public function test_clean(): void {
        $this->assertNull(brand::clean(null));
        $this->assertNull(brand::clean(['name' => 'Biblioteca Central', 'colour' => 'red']));
        $this->assertNull(brand::clean(['colour' => '#8e2a3c']));

        $brand = brand::clean(['name' => 'Biblioteca Central', 'colour' => '#8e2a3c', 'tagline' => 'Lee, cuenta, vuelve']);
        $this->assertSame('Biblioteca Central', $brand['name']);
        $this->assertSame('#8e2a3c', $brand['colour']);
        $this->assertSame('', $brand['logo']);
        $this->assertSame('Lee, cuenta, vuelve', $brand['tagline']);
    }

    /**
     * The brand alone: its colours in light and dark, without the layout the site's sheet already brings.
     */
    public function test_brandvars(): void {
        $css = appcss::brandvars('#8e2a3c');
        $this->assertStringContainsString('--primary: #8e2a3c;', $css);
        $this->assertStringContainsString(':root.dark', $css);
        $this->assertStringNotContainsString('.aty-hero', $css);
        $this->assertSame('', appcss::brandvars('not a colour'));
    }

    /**
     * A logo that is a file of the site goes through tokenpluginfile.php with the user's files key; any other stays.
     */
    public function test_logo_for_app(): void {
        global $CFG;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $url = brand::logo_for_app($CFG->wwwroot . '/pluginfile.php/5/local_oksigeniaayvurenda/logo/0/logo.png', (int) $user->id);
        $key = get_user_key('core_files', (int) $user->id);
        $this->assertSame($CFG->wwwroot . '/tokenpluginfile.php/' . $key . '/5/local_oksigeniaayvurenda/logo/0/logo.png', $url);
        $this->assertSame('https://example.org/logo.png', brand::logo_for_app('https://example.org/logo.png', (int) $user->id));
    }

    /**
     * With no plugin giving the user a brand, the web service gives nothing and the site's look stays.
     */
    public function test_user_style_without_brand(): void {
        $this->resetAfterTest();

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame(['css' => ''], \local_oksigeniaaty\external\get_user_style::execute());
    }
}
