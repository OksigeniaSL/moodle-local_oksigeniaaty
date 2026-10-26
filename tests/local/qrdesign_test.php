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
 * Tests for the designer QR codes.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oksigeniaaty\local;

/**
 * Tests for the designer QR codes.
 *
 * Whether a real reader decodes them is checked in CI: with LOCAL_OKSIGENIAATY_QR_SAMPLES set to a folder, the samples
 * test writes each SVG and its expected text there, and the workflow rasterises them and reads them with zbar.
 *
 * @covers \local_oksigeniaaty\local\qrdesign
 */
#[\PHPUnit\Framework\Attributes\CoversClass(qrdesign::class)]
final class qrdesign_test extends \advanced_testcase {
    /** @var string A short link, like a site address: a small code with room for the logo. */
    const SHORT = 'https://aula.ofilingua.com';

    /** @var string A long link, like a personal sign-in link: a code with an alignment pattern in the middle. */
    const LONG = 'https://aula.ofilingua.com/local/oksigeniaaty/open.php?key=0123456789abcdef0123456789abcdef';

    /**
     * Every dot of the drawing is a dark module, and every dark module outside the eyes and the logo pad has its dot.
     */
    public function test_draw_matches_matrix(): void {
        foreach ([self::SHORT, self::LONG] as $text) {
            $matrix = qrdesign::matrix($text);
            $n = count($matrix);
            $svg = qrdesign::draw($matrix, '#0a66c2', self::logo());
            $dots = self::dots($svg);
            $pad = self::pad($svg);

            foreach (array_keys($dots) as $key) {
                [$y, $x] = explode(',', $key);
                $this->assertNotEmpty($matrix[$y][$x], "Dot on a light module at $x,$y");
            }

            $eyes = array_merge(
                array_map(fn($c) => [$c[0], $c[1], 7], [[0, 0], [$n - 7, 0], [0, $n - 7]]),
                array_map(fn($c) => [$c[0], $c[1], 5], qrdesign::alignments($n))
            );
            if ($pad !== null) {
                $eyes[] = $pad;
            }
            for ($y = 0; $y < $n; $y++) {
                for ($x = 0; $x < $n; $x++) {
                    if (!empty($matrix[$y][$x]) && !self::inside($x, $y, $eyes)) {
                        $this->assertArrayHasKey("$y,$x", $dots, "Dark module without dot at $x,$y");
                    }
                }
            }

            // Three finder patterns plus the alignment patterns, each a ring and a centre (three subpaths).
            preg_match('/<path fill-rule="evenodd" d="([^"]+)"/', $svg, $path);
            $this->assertSame(3 * (3 + count(qrdesign::alignments($n))), substr_count($path[1], 'M'));
        }
    }

    /**
     * The alignment table matches what the encoder draws: each computed position holds a real alignment pattern.
     */
    public function test_alignment_table_matches_encoder(): void {
        // From version 1 (no alignment pattern) to version 16 (four centres per row, thirteen patterns).
        foreach ([7, 14, 26, 42, 60, 84, 120, 160, 200, 250] as $length) {
            $matrix = qrdesign::matrix(str_repeat('a', $length));
            $n = count($matrix);
            $alignments = qrdesign::alignments($n);
            $version = intdiv($n - 17, 4);
            $side = count(qrdesign::ALIGNMENT[$version] ?? []);
            $this->assertCount($side > 0 ? $side * $side - 3 : 0, $alignments, "Version $version");

            foreach ($alignments as [$ax, $ay]) {
                for ($j = 0; $j < 5; $j++) {
                    for ($i = 0; $i < 5; $i++) {
                        $dark = $i === 0 || $i === 4 || $j === 0 || $j === 4 || ($i === 2 && $j === 2);
                        $this->assertSame($dark ? 1 : 0, (int) $matrix[$ay + $j][$ax + $i], "Version $version at $ax,$ay");
                    }
                }
            }
        }
    }

    /**
     * The logo goes in short codes and never covers an alignment pattern: long codes go without it.
     */
    public function test_logo_only_where_it_fits(): void {
        $short = qrdesign::svg(self::SHORT, '#0a66c2', self::logo());
        $this->assertStringContainsString('<image ', $short);

        $long = qrdesign::svg(self::LONG, '#0a66c2', self::logo());
        $this->assertStringNotContainsString('<image ', $long);

        $none = qrdesign::svg(self::SHORT, '#0a66c2');
        $this->assertStringNotContainsString('<image ', $none);

        // The pad of the short code: centred, odd, at most a fifth of the code plus its margin, clear of alignments.
        $matrix = qrdesign::matrix(self::SHORT);
        $n = count($matrix);
        [$x, $y, $side] = self::pad(qrdesign::draw($matrix, '', self::logo()));
        $this->assertSame($x, $y);
        $this->assertSame($n - $side, 2 * $x);
        $this->assertSame(1, $side % 2);
        $this->assertLessThanOrEqual(floor($n * 0.2), $side - 2);
        foreach (qrdesign::alignments($n) as [$ax, $ay]) {
            $apart = $ax >= $x + $side + 1 || $ax + 5 <= $x - 1 || $ay >= $y + $side + 1 || $ay + 5 <= $y - 1;
            $this->assertTrue($apart, "The logo pad touches the alignment pattern at $ax,$ay");
        }
    }

    /**
     * The modules always read well on white: light brand colours are darkened, dark ones kept, invalid ones replaced.
     */
    public function test_ink_reads_on_white(): void {
        $this->assertSame('#0a2e50', qrdesign::ink('#0a2e50'));
        $this->assertSame('#0a66c2', qrdesign::ink('#0a66c2'));
        $this->assertSame(qrdesign::DEFAULTINK, qrdesign::ink(''));
        $this->assertSame(qrdesign::DEFAULTINK, qrdesign::ink('red'));
        $this->assertSame(qrdesign::ink('#000000'), qrdesign::ink('#000'));

        foreach (['#ffff00', '#ffcc00', '#5cd6ff', '#ffffff', '#e0e0e0'] as $light) {
            $ink = qrdesign::ink($light);
            $this->assertNotSame($light, $ink);
            $this->assertGreaterThanOrEqual(qrdesign::MINCONTRAST, self::contrast($ink), $light);
        }

        // The colour reaches the drawing.
        $this->assertStringContainsString('<g fill="#0a66c2">', qrdesign::svg(self::SHORT, '#0a66c2'));
    }

    /**
     * The site logo comes from the compact logo, else the full one, as a data: URI; no logo gives null.
     */
    public function test_sitelogo(): void {
        $this->resetAfterTest();
        $this->assertNull(qrdesign::sitelogo());

        // A 1x1 transparent PNG.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        $fs = get_file_storage();
        $record = [
            'contextid' => \context_system::instance()->id,
            'component' => 'core_admin',
            'filearea' => 'logo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'logo.png',
        ];
        $fs->create_file_from_string($record, $png);
        $this->assertSame('data:image/png;base64,' . base64_encode($png), qrdesign::sitelogo());

        // The compact logo wins over the full one.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"/>';
        $fs->create_file_from_string(['filearea' => 'logocompact', 'filename' => 'compact.svg'] + $record, $svg);
        $this->assertSame('data:image/svg+xml;base64,' . base64_encode($svg), qrdesign::sitelogo());
    }

    /**
     * The samples are valid SVG; in CI they are written out so a real reader can decode them.
     */
    public function test_samples(): void {
        $samples = [
            'site-short' => [self::SHORT, '#0a66c2', self::logo()],
            'site-lightbrand' => ['https://campus.oksigenia.com', '#ffcc00', self::logo()],
            'site-tiny' => ['https://ofi.es', '#2d6a4f', self::logo()],
            'site-nologo' => ['https://formacion.example.org', '#b5179e', null],
            'personal-long' => [self::LONG, '#7b2d8e', self::logo()],
        ];
        $folder = getenv('LOCAL_OKSIGENIAATY_QR_SAMPLES');

        foreach ($samples as $name => [$text, $colour, $logo]) {
            $svg = qrdesign::svg($text, $colour, $logo);
            $this->assertNotFalse(simplexml_load_string($svg), $name);

            if ($folder) {
                check_dir_exists($folder);
                file_put_contents("$folder/$name.svg", $svg);
                file_put_contents("$folder/$name.txt", $text);
            }
        }
    }

    /**
     * A small SVG logo as a data: URI.
     *
     * @return string
     */
    private static function logo(): string {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="100" height="100">'
            . '<circle cx="5" cy="5" r="4.5" fill="#e85d04"/><path d="M3 5h4M5 3v4" stroke="#fff" stroke-width="1.2"/></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * The modules drawn as dots, as "y,x" keys (without the quiet zone).
     *
     * @param string $svg The drawing.
     * @return array "y,x" => true.
     */
    private static function dots(string $svg): array {
        preg_match_all('/<circle cx="([\d.]+)" cy="([\d.]+)" r="0.45"\/>/', $svg, $hits, PREG_SET_ORDER);
        $dots = [];
        foreach ($hits as $hit) {
            $dots[(int) ($hit[2] - qrdesign::QUIET - 0.5) . ',' . (int) ($hit[1] - qrdesign::QUIET - 0.5)] = true;
        }

        return $dots;
    }

    /**
     * The logo pad of a drawing, from the logo image inside it (one module of margin on each side).
     *
     * @param string $svg The drawing.
     * @return array|null [x, y, side] in modules, or null without logo.
     */
    private static function pad(string $svg): ?array {
        if (!preg_match('/<image x="([\d.]+)" y="([\d.]+)" width="([\d.]+)"/', $svg, $m)) {
            return null;
        }

        return [(int) $m[1] - qrdesign::QUIET - 1, (int) $m[2] - qrdesign::QUIET - 1, (int) $m[3] + 2];
    }

    /**
     * Whether a module is inside any of the squares.
     *
     * @param int $x Column.
     * @param int $y Row.
     * @param array $squares A list of [x, y, side].
     * @return bool
     */
    private static function inside(int $x, int $y, array $squares): bool {
        foreach ($squares as [$sx, $sy, $side]) {
            if ($x >= $sx && $x < $sx + $side && $y >= $sy && $y < $sy + $side) {
                return true;
            }
        }

        return false;
    }

    /**
     * The WCAG contrast ratio of a colour against white, worked out here independently of the class.
     *
     * @param string $hex "#rrggbb".
     * @return float
     */
    private static function contrast(string $hex): float {
        $luminance = 0;
        foreach ([0.2126, 0.7152, 0.0722] as $i => $weight) {
            $c = hexdec(substr($hex, 1 + 2 * $i, 2)) / 255;
            $luminance += $weight * ($c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4);
        }

        return 1.05 / ($luminance + 0.05);
    }
}
