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
class restore_lumen10admissao_activity_structure_step extends restore_activity_structure_step {
    protected function define_structure() { return $this->prepare_activity_structure([new restore_path_element('lumen10admissao', '/activity/lumen10admissao')]); }
    protected function process_lumen10admissao($data) {
        global $DB, $USER;
        $data = (object)$data; $data->course = $this->get_courseid();
        $data->receptioncourseid = $data->course; $data->categoryid = $DB->get_field('course', 'category', ['id' => $data->course], MUST_EXIST);
        $data->enabled = 0; $data->targetcourseid = 0; $data->usermodified = $USER->id;
        $this->apply_activity_instance($DB->insert_record('lumen10admissao', $data));
    }
    protected function after_execute() { $this->add_related_files('mod_lumen10admissao', 'intro', null); }
}
