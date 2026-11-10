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
class backup_lumen10admissao_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure() {
        $element = new backup_nested_element('lumen10admissao', ['id'], ['name', 'intro', 'introformat', 'autoenrol', 'opendate', 'closedate', 'timecreated', 'timemodified']);
        $element->set_source_table('lumen10admissao', ['id' => backup::VAR_ACTIVITYID]);
        $element->annotate_files('mod_lumen10admissao', 'intro', null);
        // Institutional identifiers and pre-account personal data never travel in a course/activity copy.
        return $this->prepare_activity_structure($element);
    }
}
