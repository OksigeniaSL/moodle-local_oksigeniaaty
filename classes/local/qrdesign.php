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
 * Designer QR codes in the site's brand.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_oksigeniaaty\local;

/**
 * Renders a QR code as a self-contained SVG in the site's brand.
 *
 * The format, shared with the oksigenia.com demo so both show the same code: error correction H; round modules (90 %
 * of a module) in the brand colour on white, darkened when the colour is too light to read well; the three finder
 * patterns ("eyes") and the alignment patterns drawn solid as rounded squares (radius 30 % of their side), because
 * readers miss alignment patterns drawn as loose dots; the logo in the middle, at most 20 % of the width, on a white
 * rounded pad with one module of margin, and only where it covers no alignment pattern (long codes have one in the
 * middle, so they go without logo); a quiet zone of four modules.
 */
class qrdesign {

    /** @var int Modules of white around the code. */
    const QUIET = 4;

    /** @var float Minimum contrast of the modules against white (WCAG ratio). */
    const MINCONTRAST = 4.5;

    /** @var string The colour used when the site has no valid brand colour (Refugio navy). */
    const DEFAULTINK = '#0a2e50';

    /** @var int Largest logo file embedded, in bytes. */
    const MAXLOGOBYTES = 204800;

    /** @var array Centres of the alignment patterns by QR version (ISO/IEC 18004, annex E). */
    const ALIGNMENT = [
        2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34],
        7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50],
        11 => [6, 30, 54], 12 => [6, 32, 58], 13 => [6, 34, 62],
        14 => [6, 26, 46, 66], 15 => [6, 26, 48, 70], 16 => [6, 26, 50, 74], 17 => [6, 30, 54, 78],
        18 => [6, 30, 56, 82], 19 => [6, 30, 58, 86], 20 => [6, 34, 62, 90],
        21 => [6, 28, 50, 72, 94], 22 => [6, 26, 50, 74, 98], 23 => [6, 30, 54, 78, 102],
        24 => [6, 28, 54, 80, 106], 25 => [6, 32, 58, 84, 110], 26 => [6, 30, 58, 86, 114],
        27 => [6, 34, 62, 90, 118],
        28 => [6, 26, 50, 74, 98, 122], 29 => [6, 30, 54, 78, 102, 126], 30 => [6, 26, 52, 78, 104, 130],
        31 => [6, 30, 56, 82, 108, 134], 32 => [6, 34, 60, 86, 112, 138], 33 => [6, 30, 58, 86, 114, 142],
        34 => [6, 34, 62, 90, 118, 146],
        35 => [6, 30, 54, 78, 102, 126, 150], 36 => [6, 24, 50, 76, 102, 128, 154],
        37 => [6, 28, 54, 80, 106, 132, 158], 38 => [6, 32, 58, 84, 110, 136, 162],
        39 => [6, 26, 54, 82, 110, 138, 166], 40 => [6, 30, 58, 86, 114, 142, 170],
    ];

    /**
     * The QR code as a self-contained SVG.
     *
     * @param string $text What the code holds (a URL).
     * @param string $colour The brand colour (#rrggbb); anything invalid falls back to the default navy.
     * @param string|null $logo The logo as a data: URI, or null for none.
     * @return string The SVG markup.
     */
    public static function svg(string $text, string $colour = '', ?string $logo = null): string {
        return self::draw(self::matrix($text), $colour, $logo);
    }

    /**
     * Draw a module matrix as the SVG (apart from svg() so a test can check the drawing against the same matrix: the
     * encoder picks the mask at random, so two encodings of the same text may differ).
     *
     * @param array $matrix Rows of 0 or 1, n by n, as matrix() returns them.
     * @param string $colour The brand colour (#rrggbb); anything invalid falls back to the default navy.
     * @param string|null $logo The logo as a data: URI, or null for none.
     * @return string The SVG markup.
     */
    public static function draw(array $matrix, string $colour = '', ?string $logo = null): string {
        $n = count($matrix);
        $q = self::QUIET;
        $total = $n + 2 * $q;
        $reserved = array_fill(0, $n, array_fill(0, $n, false));
        $shapes = [];

        // The three finder patterns, drawn solid as rounded squares.
        foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$x, $y]) {
            self::reserve($reserved, $x, $y, 7);
            $shapes[] = self::eye($x + $q, $y + $q, 7);
        }

        // The alignment patterns, solid too: drawn as loose dots, readers miss them on bigger renders.
        $alignments = self::alignments($n);
        foreach ($alignments as [$x, $y]) {
            self::reserve($reserved, $x, $y, 5);
            $shapes[] = self::eye($x + $q, $y + $q, 5);
        }

        // The logo pad, centred, only where it covers no alignment pattern.
        $pad = $logo !== null ? self::logopad($n, $alignments) : null;
        if ($pad !== null) {
            self::reserve($reserved, $pad, $pad, $n - 2 * $pad);
        }

        // Every other dark module, as a round dot.
        $dots = '';
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if (!empty($matrix[$y][$x]) && !$reserved[$y][$x]) {
                    $dots .= '<circle cx="' . ($x + $q + 0.5) . '" cy="' . ($y + $q + 0.5) . '" r="0.45"/>';
                }
            }
        }

        $ink = self::ink($colour);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" '
            . 'viewBox="0 0 ' . $total . ' ' . $total . '" width="' . ($total * 10) . '" height="' . ($total * 10) . '">'
            . '<rect width="' . $total . '" height="' . $total . '" fill="#ffffff"/>'
            . '<g fill="' . $ink . '">'
            . '<path fill-rule="evenodd" d="' . implode(' ', $shapes) . '"/>'
            . $dots
            . '</g>';

        if ($pad !== null) {
            $side = $n - 2 * $pad;
            $svg .= '<path fill="#ffffff" d="' . self::rounded($pad + $q, $pad + $q, $side, 1) . '"/>';
            $href = s($logo);
            $svg .= '<image x="' . ($pad + $q + 1) . '" y="' . ($pad + $q + 1) . '" width="' . ($side - 2) . '" height="'
                . ($side - 2) . '" preserveAspectRatio="xMidYMid meet" href="' . $href . '" xlink:href="' . $href . '"/>';
        }

        return $svg . '</svg>';
    }

    /**
     * The module matrix of a QR code with error correction H.
     *
     * @param string $text What the code holds.
     * @return array Rows of 0 or 1 (1 is dark), n by n, without the quiet zone.
     */
    public static function matrix(string $text): array {
        global $CFG;
        require_once($CFG->libdir . '/tcpdf/tcpdf_barcodes_2d.php');

        $barcode = new \TCPDF2DBarcode($text, 'QRCODE,H');
        $array = $barcode->getBarcodeArray();
        if (!is_array($array) || empty($array['bcode']) || $array['num_rows'] !== $array['num_cols']) {
            throw new \coding_exception('The QR code could not be encoded.');
        }

        return $array['bcode'];
    }

    /**
     * The colour of the modules: the brand colour, darkened until it reads well on white.
     *
     * @param string $colour A colour like "#0a2e50"; anything invalid gives the default navy.
     * @return string The colour as "#rrggbb".
     */
    public static function ink(string $colour): string {
        $rgb = self::parse($colour) ?? self::parse(self::DEFAULTINK);
        while (self::contrast($rgb) < self::MINCONTRAST) {
            $rgb = [(int) floor($rgb[0] * 0.9), (int) floor($rgb[1] * 0.9), (int) floor($rgb[2] * 0.9)];
        }

        return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * The site's logo as a data: URI (the compact logo, else the full one), or null when there is none or it is too big.
     *
     * @return string|null The data: URI, or null.
     */
    public static function sitelogo(): ?string {
        $fs = get_file_storage();
        $context = \context_system::instance();
        foreach (['logocompact', 'logo'] as $area) {
            $files = $fs->get_area_files($context->id, 'core_admin', $area, 0, 'itemid', false);
            foreach ($files as $file) {
                $mimetype = $file->get_mimetype();
                if (!in_array($mimetype, ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp'], true)) {
                    continue;
                }
                if ($file->get_filesize() > self::MAXLOGOBYTES) {
                    continue;
                }

                return 'data:' . $mimetype . ';base64,' . base64_encode($file->get_content());
            }
        }

        return null;
    }

    /**
     * The top-left corners of the alignment patterns of a code of this size (none for version 1).
     *
     * @param int $n The width of the code in modules.
     * @return array A list of [x, y].
     */
    public static function alignments(int $n): array {
        $centres = self::ALIGNMENT[intdiv($n - 17, 4)] ?? [];
        $last = end($centres);
        $corners = [];
        foreach ($centres as $cy) {
            foreach ($centres as $cx) {
                // The three corners taken by the finder patterns have no alignment pattern.
                if (($cx === 6 && $cy === 6) || ($cx === 6 && $cy === $last) || ($cx === $last && $cy === 6)) {
                    continue;
                }
                $corners[] = [$cx - 2, $cy - 2];
            }
        }

        return $corners;
    }

    /**
     * Where the logo pad starts (it is centred and square), or null when no pad fits without covering an alignment
     * pattern. The logo is at most 20 % of the width; the pad adds one module of margin around it.
     *
     * @param int $n The width of the code in modules.
     * @param array $alignments The top-left corners of the alignment patterns.
     * @return int|null The first module of the pad on both axes, or null for no logo.
     */
    private static function logopad(int $n, array $alignments): ?int {
        $side = (int) floor($n * 0.2) + 2;
        if ($side % 2 === 0) {
            // The code has an odd width: an odd pad centres on the module grid.
            $side--;
        }
        for (; $side - 2 >= 3; $side -= 2) {
            $start = intdiv($n - $side, 2);
            $clear = true;
            foreach ($alignments as [$x, $y]) {
                // Keep one module of white between the pad and any alignment pattern.
                if ($x < $start + $side + 1 && $x + 5 > $start - 1 && $y < $start + $side + 1 && $y + 5 > $start - 1) {
                    $clear = false;
                    break;
                }
            }
            if ($clear) {
                return $start;
            }
        }

        return null;
    }

    /**
     * An eye-like pattern: a ring and a centre, as rounded squares (finder patterns are 7, alignment patterns 5).
     *
     * @param float $x Left edge.
     * @param float $y Top edge.
     * @param int $side Size in modules.
     * @return string Path data, to draw with the even-odd rule.
     */
    private static function eye(float $x, float $y, int $side): string {
        return self::rounded($x, $y, $side, 0.3 * $side)
            . self::rounded($x + 1, $y + 1, $side - 2, 0.3 * ($side - 2))
            . self::rounded($x + 2, $y + 2, $side - 4, 0.3 * ($side - 4));
    }

    /**
     * Path data of a rounded square.
     *
     * @param float $x Left edge.
     * @param float $y Top edge.
     * @param float $w Side.
     * @param float $r Corner radius.
     * @return string Path data.
     */
    private static function rounded(float $x, float $y, float $w, float $r): string {
        $r = round($r, 3);
        $f = fn(float $v): string => (string) round($v, 3);

        return 'M' . $f($x + $r) . ' ' . $f($y) . 'H' . $f($x + $w - $r)
            . 'A' . $r . ' ' . $r . ' 0 0 1 ' . $f($x + $w) . ' ' . $f($y + $r) . 'V' . $f($y + $w - $r)
            . 'A' . $r . ' ' . $r . ' 0 0 1 ' . $f($x + $w - $r) . ' ' . $f($y + $w) . 'H' . $f($x + $r)
            . 'A' . $r . ' ' . $r . ' 0 0 1 ' . $f($x) . ' ' . $f($y + $w - $r) . 'V' . $f($y + $r)
            . 'A' . $r . ' ' . $r . ' 0 0 1 ' . $f($x + $r) . ' ' . $f($y) . 'Z';
    }

    /**
     * Mark a square of modules as drawn by something other than a dot.
     *
     * @param array $reserved The n by n reservation grid, changed in place.
     * @param int $x Left module.
     * @param int $y Top module.
     * @param int $side Size in modules.
     */
    private static function reserve(array &$reserved, int $x, int $y, int $side): void {
        $n = count($reserved);
        for ($j = max(0, $y); $j < min($n, $y + $side); $j++) {
            for ($i = max(0, $x); $i < min($n, $x + $side); $i++) {
                $reserved[$j][$i] = true;
            }
        }
    }

    /**
     * Parse "#rrggbb" or "#rgb" into [r, g, b], or null if it is not a valid colour.
     *
     * @param string $hex The colour.
     * @return array|null [r, g, b], each 0 to 255, or null.
     */
    private static function parse(string $hex): ?array {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return null;
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /**
     * The WCAG contrast ratio of a colour against white.
     *
     * @param array $rgb [r, g, b].
     * @return float The ratio, from 1 to 21.
     */
    private static function contrast(array $rgb): float {
        $channel = function (int $c): float {
            $c = $c / 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };
        $luminance = 0.2126 * $channel($rgb[0]) + 0.7152 * $channel($rgb[1]) + 0.0722 * $channel($rgb[2]);

        return 1.05 / ($luminance + 0.05);
    }
}
