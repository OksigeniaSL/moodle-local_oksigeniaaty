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
 * Since when is what the app shows.
 *
 * The app keeps each page: with a connection, some minutes; without one, or when the site does not answer, whatever it
 * kept last, however old, and an app left open shows the page it had. Nothing in it said so, and «Nothing for today»
 * could be another day's. So each page carries the moment it stops being recent and a line that says when it was made:
 * the template reads the phone's clock and shows the line from then on, with the page itself kept.
 *
 * The line names the day and the time, never «today» or «two hours ago»: a page kept for days would say it wrong.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class freshness {
    /** @var int For how long a page is recent. Longer than the 20 minutes the app keeps it while there is a connection. */
    private const RECENT = 30 * MINSECS;

    /**
     * @var string The same for every page and user: an app that comes back to a page no longer recent asks for it again, if
     * there is a connection, so that the line is only seen when there is no way to know. With the app's own refresh, that
     * gives back the page it kept if the site does not answer. Never with updateCachedContent(): its request takes nothing
     * from the cache, and any other refresh asked meanwhile waits for that same request and is left with an empty page.
     */
    public const JS = <<<'EOF'
var atyPage = this;
var atyResume = this.CorePlatform && this.CorePlatform.resume;
if (atyResume && this.refreshContent) {
    var atyAsking = false;
    var atySubscription = atyResume.subscribe(function () {
        var due = Number(atyPage.CONTENT_OTHERDATA && atyPage.CONTENT_OTHERDATA.atydue);
        if (atyAsking || !due || Date.now() / 1000 < due || !atyPage.CoreNetwork.isOnline()) {
            return;
        }
        atyAsking = true;
        atyPage.refreshContent(false).catch(function () {
            return undefined;
        }).then(function () {
            atyAsking = false;
            return undefined;
        });
    });
    this.ngOnDestroy = function () {
        atySubscription.unsubscribe();
    };
}
EOF;

    /**
     * What the line needs (mobile_since), already inert.
     *
     * @param int $now When the page is being made.
     * @return array due (when it stops being recent), text and refresh.
     */
    public static function line(int $now): array {
        return template::inert([
            'due' => self::due($now),
            'text' => get_string(
                'since',
                'local_oksigeniaaty',
                trim(userdate($now, get_string('since:date', 'local_oksigeniaaty')))
            ),
            'refresh' => get_string('since:refresh', 'local_oksigeniaaty'),
        ]);
    }

    /**
     * When a page made now stops being recent: past a while, or as soon as the user's day changes, because a page says
     * «today».
     *
     * @param int $now When the page is being made.
     * @return int
     */
    public static function due(int $now): int {
        $tomorrow = (new \DateTime('@' . $now))->setTimezone(\core_date::get_user_timezone_object())->modify('tomorrow');

        return min($now + self::RECENT, $tomorrow->getTimestamp());
    }
}
