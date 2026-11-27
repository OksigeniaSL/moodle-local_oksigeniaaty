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

use local_oksigeniaaty\api;

/**
 * The mails in the common look of the organization, by the way of Oksigenia Marandu when it is there.
 *
 * @package    local_oksigeniaaty
 * @category   test
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_oksigeniaaty\local\commonmail
 * @covers     \local_oksigeniaaty\local\linkmail
 */
final class commonmail_test extends \advanced_testcase {
    /**
     * A text becomes a paragraph for each block of lines, and nothing for blank ones.
     */
    public function test_paragraphs(): void {
        $this->assertSame(
            [['paragraph' => 'Uno.'], ['paragraph' => "Dos,\ncon dos líneas."]],
            commonmail::paragraphs("\n Uno.\n\n  \nDos,\ncon dos líneas.\n")
        );
        $this->assertSame([], commonmail::paragraphs("  \n "));
    }

    /**
     * The message of the app as pieces of the common mail: the button after the first paragraphs, the small print last,
     * and no centre beside the name when the site has only one.
     */
    public function test_pieces(): void {
        $this->resetAfterTest();
        $person = $this->getDataGenerator()->create_user(['firstname' => 'Ana']);

        $pieces = linkmail::pieces((int) $person->id, 'Asunto', [
            'hello' => 'Hola, Ana:',
            'heading' => 'Título',
            'lead' => ['Primero.'],
            'button' => ['url' => 'https://example.org/abrir', 'text' => 'Abrir'],
            'after' => ['Después.'],
            'small' => ['Pequeño.'],
            'why' => 'Por esto.',
        ]);

        $this->assertSame([
            ['paragraph' => 'Primero.'],
            ['action' => ['actionurl' => 'https://example.org/abrir', 'actiontext' => 'Abrir']],
            ['paragraph' => 'Después.'],
            ['small' => 'Pequeño.'],
        ], $pieces['blocks']);
        $this->assertSame(
            ['Asunto', 'Título', 'Primero.', 'Hola, Ana:', 'Por esto.', ''],
            [$pieces['subject'], $pieces['heading'], $pieces['preheader'], $pieces['hello'], $pieces['reason'], $pieces['unit']]
        );
    }

    /**
     * With Oksigenia Marandu, «Send the app» goes in the common look, with a text version and the link in both.
     */
    public function test_send_link_in_the_common_look(): void {
        if (!commonmail::available()) {
            $this->markTestSkipped('Oksigenia Marandu 0.70 or later is not installed.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();
        $person = $this->getDataGenerator()->create_user(['firstname' => 'Ana']);
        $sink = $this->redirectEmails();

        $this->assertTrue(api::send_link((int) $person->id));

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertSame($person->email, $messages[0]->to);
        $this->assertStringContainsString('Auto-Submitted: auto-generated', $messages[0]->header);
        $raw = quoted_printable_decode($messages[0]->body);
        $this->assertStringContainsString('text/plain', $raw);
        $this->assertStringContainsString('text/html', $raw);
        $this->assertSame(2, substr_count($raw, 'local/oksigeniaaty/open.php'));
        $this->assertStringContainsString('Ana', $raw);
    }
}
