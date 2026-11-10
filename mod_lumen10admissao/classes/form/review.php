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
namespace mod_lumen10admissao\form;
defined('MOODLE_INTERNAL') || die();
require_once($GLOBALS['CFG']->libdir . '/formslib.php');
final class review extends \moodleform {
    public function definition() {
        $m = $this->_form; $r = $this->_customdata['request'];
        $choices = ['resend' => get_string('resendnotification', 'mod_lumen10admissao')];
        if ($r->status === 'pending') {
            $choices = ['approve' => get_string('approveconfirmation', 'mod_lumen10admissao'), 'correction' => get_string('requestcorrection', 'mod_lumen10admissao'), 'reject' => get_string('reject', 'mod_lumen10admissao')] + $choices;
            if ($r->courseid != $r->receptioncourseid) {
                $m->addElement('advcheckbox', 'enrol', get_string('alsoenrol', 'mod_lumen10admissao'));
                $m->setDefault('enrol', $this->_customdata['activity']->autoenrol);
            }
        } else if ($r->status === 'approved' && $r->courseid != $r->receptioncourseid && $r->enrolstatus === 'notrequested') { $choices = ['enrol' => get_string('releaseenrol', 'mod_lumen10admissao')] + $choices; }
        $m->addElement('select', 'decision', get_string('action', 'mod_lumen10admissao'), $choices); $m->setType('decision', PARAM_ALPHA);
        $m->addElement('textarea', 'reason', get_string('reason', 'mod_lumen10admissao'), ['rows' => 3, 'maxlength' => 1000]);
        $m->setType('reason', PARAM_NOTAGS);
        $this->add_action_buttons(true, get_string('confirmaction', 'mod_lumen10admissao'));
    }
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (in_array($data['decision'] ?? '', ['correction', 'reject']) && trim($data['reason'] ?? '') === '') {
            $errors['reason'] = get_string('required');
        }
        return $errors;
    }
}
