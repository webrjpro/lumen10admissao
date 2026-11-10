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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_lumen10admissao
 * @copyright 2026 Carlos Antonio de Oliveira Piquet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');
final class mod_lumen10admissao_mod_form extends moodleform_mod {
    public function definition() {
        global $DB;
        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => 60, 'maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT); $mform->addRule('name', null, 'required', null, 'client');
        $mform->setDefault('name', get_string('modulename', 'mod_lumen10admissao'));
        $this->standard_intro_elements();
        $choices = [0 => get_string('choosecourse', 'mod_lumen10admissao'), $this->_course->id => get_string('categorydestination', 'mod_lumen10admissao')];
        foreach ($DB->get_records('course', ['category' => $this->current->category ?? $this->_course->category], 'fullname', 'id,fullname') as $course) {
            if ($course->id != $this->_course->id) { $choices[$course->id] = $course->fullname; }
        }
        $mform->addElement('select', 'targetcourseid', get_string('destination', 'mod_lumen10admissao'), $choices);
        $mform->setType('targetcourseid', PARAM_INT);
        $mform->setDefault('targetcourseid', $this->_course->id);
        $mform->addElement('static', 'scopehint', get_string('receptioncategory', 'mod_lumen10admissao'), get_string('scopehint', 'mod_lumen10admissao'));
        $mform->addElement('advcheckbox', 'enabled', get_string('openapplications', 'mod_lumen10admissao')); $mform->setDefault('enabled', 0);
        $mform->addElement('date_time_selector', 'opendate', get_string('opendate', 'mod_lumen10admissao'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'closedate', get_string('closedate', 'mod_lumen10admissao'), ['optional' => true]);
        $mform->addElement('advcheckbox', 'autoenrol', get_string('autoenrol', 'mod_lumen10admissao'));
        $mform->setDefault('autoenrol', 0);
        $mform->hideIf('autoenrol', 'targetcourseid', 'eq', $this->_course->id);
        $mform->addElement('static', 'enrolhint', '', get_string('enrolhint', 'mod_lumen10admissao'));
        $mform->addElement('static', 'declarationhint', get_string('declarationwelcome', 'mod_lumen10admissao'),
            get_string('declarationhint', 'mod_lumen10admissao'));
        $this->standard_coursemodule_elements(); $this->add_action_buttons();
    }
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $target = $DB->get_record('course', ['id' => (int)$data['targetcourseid']]);
        if (!$target || $target->category != $this->_course->category) {
            $errors['targetcourseid'] = get_string('error_category', 'mod_lumen10admissao');
        }
        if ($data['opendate'] && $data['closedate'] && $data['closedate'] <= $data['opendate']) {
            $errors['closedate'] = get_string('error_dates', 'mod_lumen10admissao');
        }
        return $errors;
    }
}
