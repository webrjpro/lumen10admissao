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
namespace mod_lumen10admissao;
defined('MOODLE_INTERNAL') || die();
final class page {
    public static function setup(\moodle_url $url, array $scope, bool $public = false): void {
        global $PAGE;
        [$activity, $cm, $reception] = $scope;
        $PAGE->set_url($url); $PAGE->set_context(\context_module::instance($cm->id));
        $PAGE->set_course($reception);
        if (!$public) { $PAGE->set_cm($cm, $reception); }
        $PAGE->set_pagelayout($public ? 'login' : 'incourse');
        $PAGE->set_title($activity->name); $PAGE->set_heading($activity->name);
        $PAGE->requires->js_call_amd('mod_lumen10admissao/form', 'init');
        header('Cache-Control: no-store, private'); header('Referrer-Policy: no-referrer');
    }
    public static function start(string $title, array $scope): void {
        global $OUTPUT;
        [$activity, $cm, $reception, $target] = $scope;
        echo $OUTPUT->header(); echo \html_writer::start_div('l10adm');
        echo $OUTPUT->heading($title, 2);
        $category = workflow::category((int)$reception->category);
        $names = (object)['category' => $category->name, 'reception' => $reception->fullname, 'course' => $target->fullname];
        $description = get_string($target->id == $reception->id ? 'scope_reception' : 'scope_course', workflow::COMPONENT, $names);
        echo $OUTPUT->render_from_template('mod_lumen10admissao/scope', ['description' => $description]);
    }
    public static function end(): void { global $OUTPUT; echo \html_writer::end_div(); echo $OUTPUT->footer(); }
    public static function status(\stdClass $r): string { return get_string('status_' . $r->status, workflow::COMPONENT); }
    public static function data(\stdClass $r): void {
        global $DB, $OUTPUT;
        $reg = $r->userid ? $DB->get_record('lumen10admissao_reg', ['userid' => $r->userid]) : false;
        $rows = [];
        foreach ([get_string('protocol', 'mod_lumen10admissao') => $r->protocol, get_string('status', 'mod_lumen10admissao') => self::status($r),
                get_string('fullname', 'mod_lumen10admissao') => $r->firstname . ($r->lastname === '-' ? '' : ' ' . $r->lastname),
                get_string('socialnamelabel', 'mod_lumen10admissao') => $r->socialname, get_string('cpf', 'mod_lumen10admissao') => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $r->cpf),
                get_string('visualdeclared', 'mod_lumen10admissao') => !empty($r->visualimpairment) ? get_string('yes', 'mod_lumen10admissao') : get_string('no', 'mod_lumen10admissao'),
                get_string('motherlabel', 'mod_lumen10admissao') => $r->mothername, get_string('fatherlabel', 'mod_lumen10admissao') => $r->fathername,
                get_string('email', 'mod_lumen10admissao') => $r->email, get_string('registration', 'mod_lumen10admissao') => $reg ? $reg->number : get_string('registrationpending', 'mod_lumen10admissao'),
                ($r->courseid == $r->receptioncourseid ? get_string('receptionenrol', 'mod_lumen10admissao') : get_string('courseenrol', 'mod_lumen10admissao')) => get_string('enrol_' . $r->enrolstatus, workflow::COMPONENT)] as $key => $value) {
            $rows[] = ['label' => $key, 'value' => $value !== '' ? $value : '—'];
        }
        echo $OUTPUT->render_from_template('mod_lumen10admissao/details', ['rows' => $rows]);
    }
}
