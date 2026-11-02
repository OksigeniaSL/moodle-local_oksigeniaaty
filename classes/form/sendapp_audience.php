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

namespace local_oksigeniaaty\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Who to send the app to: a course or one of its groups, a person, or a cohort.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sendapp_audience extends \moodleform {
    /**
     * The form, with only the control of the chosen tab.
     */
    protected function definition() {
        $form = $this->_form;
        $mode = $this->_customdata['mode'];
        $courseid = (int) $this->_customdata['courseid'];
        $component = 'local_oksigeniaaty';

        $form->disable_form_change_checker();
        $form->addElement('hidden', 'mode', $mode);
        $form->setType('mode', PARAM_ALPHA);

        if ($mode === 'search') {
            $form->addElement('text', 'q', get_string('sendapp:search', $component));
            $form->setType('q', PARAM_TEXT);
        } else if ($mode === 'cohort') {
            $form->addElement('cohort', 'cohortid', get_string('sendapp:cohort', $component), [
                'contextid' => \context_system::instance()->id,
                'multiple' => false,
                'noselectionstring' => get_string('sendapp:choose', $component),
            ]);
        } else {
            $form->addElement('course', 'courseid', get_string('sendapp:course', $component), [
                'multiple' => false,
                'noselectionstring' => get_string('sendapp:choose', $component),
            ]);
            $groups = $courseid && $courseid != SITEID ? groups_get_all_groups($courseid) : [];
            if ($groups) {
                $options = [0 => get_string('sendapp:wholecourse', $component)];
                foreach ($groups as $group) {
                    $options[(int) $group->id] = format_string($group->name);
                }
                $form->addElement('select', 'groupid', get_string('sendapp:group', $component), $options);
            }
        }

        $this->add_action_buttons(false, get_string('sendapp:show', $component));
    }

    /**
     * This form only chooses what to show, and goes in the address: without the session key, which has no place there, and
     * without the mark that makes Moodle take it as a submission and ask for that key.
     */
    public function definition_after_data() {
        $this->_form->removeElement('sesskey');
        $this->_form->removeElement('_qf__' . $this->_formname);
    }
}
