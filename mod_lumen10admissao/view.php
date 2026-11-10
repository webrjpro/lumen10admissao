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
require_once(__DIR__ . '/../../config.php');
use mod_lumen10admissao\workflow;
use mod_lumen10admissao\page;
require_once(__DIR__ . '/lib.php');
$id = required_param('id', PARAM_INT);
$initialcm = get_coursemodule_from_id('lumen10admissao', $id, 0, false, MUST_EXIST);
$initial = $DB->get_record('lumen10admissao', ['id' => $initialcm->instance], '*', MUST_EXIST);
if (!$initial->targetcourseid) {
    require_login($initialcm->course, false, $initialcm);
    $initialcontext = context_module::instance($id);
    require_capability('mod/lumen10admissao:view', $initialcontext);
    lumen10admissao_view($initial, get_course($initialcm->course), $initialcm, $initialcontext);
    $PAGE->set_url('/mod/lumen10admissao/view.php', ['id' => $id]); $PAGE->set_cm($initialcm);
    $PAGE->set_title($initial->name); $PAGE->set_heading($initial->name);
    echo $OUTPUT->header(); echo $OUTPUT->notification(get_string('restorednotice', 'mod_lumen10admissao'), 'info');
    if (has_capability('moodle/course:manageactivities', context_module::instance($id))) {
        echo html_writer::link(new moodle_url('/course/modedit.php', ['update' => $id]), get_string('configure', 'mod_lumen10admissao'), ['class' => 'btn btn-primary']);
    }
    echo $OUTPUT->footer(); exit;
}
$scope = workflow::by_cmid($id); [$activity, $cm, $course] = $scope;
require_login($course, false, $cm); $context = context_module::instance($cm->id);
require_capability('mod/lumen10admissao:view', $context);
$url = new moodle_url('/mod/lumen10admissao/view.php', ['id' => $id]); page::setup($url, $scope);
lumen10admissao_view($activity, $course, $cm, $context);
page::start($activity->name, $scope);
$review = has_capability('mod/lumen10admissao:review', $context);
if ($review) {
    $signup = new moodle_url('/mod/lumen10admissao/cadastro.php', ['id' => $cm->id]);
    echo html_writer::tag('p', $activity->enabled ? get_string('enablednotice', 'mod_lumen10admissao') : get_string('disablednotice', 'mod_lumen10admissao'));
    echo html_writer::start_div('l10adm-link');
    echo html_writer::empty_tag('input', ['id' => 'l10adm-signup', 'class' => 'form-control', 'value' => $signup->out(false), 'readonly' => 'readonly', 'aria-label' => get_string('publiclink', 'mod_lumen10admissao')]);
    echo html_writer::tag('button', get_string('copylink', 'mod_lumen10admissao'), ['type' => 'button', 'class' => 'btn btn-primary l10adm-copy', 'data-target' => 'l10adm-signup']);
    echo html_writer::end_div();
    if (has_capability('moodle/course:manageactivities', $context)) {
        echo html_writer::link(new moodle_url('/course/modedit.php', ['update' => $cm->id]), get_string('configure', 'mod_lumen10admissao'), ['class' => 'btn btn-outline-secondary mb-3']);
    }
    $status = optional_param('status', '', PARAM_ALPHA); $search = optional_param('search', '', PARAM_NOTAGS);
    $pageno = max(0, optional_param('page', 0, PARAM_INT));
    $statuses = ['pending', 'correction', 'awaitingconfirmation', 'rejected', 'approved', 'withdrawn'];
    if ($status && !in_array($status, $statuses)) { $status = ''; }
    echo html_writer::start_tag('form', ['method' => 'get', 'action' => $url, 'class' => 'l10adm-link']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
    $options = ['' => get_string('allstatuses', 'mod_lumen10admissao')]; foreach ($statuses as $value) { $options[$value] = get_string('status_' . $value, workflow::COMPONENT); }
    echo html_writer::select($options, 'status', $status, false, ['class' => 'form-select', 'aria-label' => get_string('filterstatus', 'mod_lumen10admissao')]);
    echo html_writer::empty_tag('input', ['type' => 'search', 'name' => 'search', 'value' => $search, 'maxlength' => 100, 'class' => 'form-control', 'placeholder' => get_string('searchplaceholder', 'mod_lumen10admissao'), 'aria-label' => get_string('searchapplication', 'mod_lumen10admissao')]);
    echo html_writer::tag('button', get_string('filter', 'mod_lumen10admissao'), ['type' => 'submit', 'class' => 'btn btn-secondary']); echo html_writer::end_tag('form');
    $where = 'admissaoid=:instance'; $params = ['instance' => $activity->id];
    if ($status) { $where .= ' AND status=:status'; $params['status'] = $status; }
    if ($search) {
        $terms = []; foreach (['firstname', 'lastname', 'email', 'protocol'] as $i => $field) {
            $terms[] = $DB->sql_like($field, ':search' . $i, false); $params['search' . $i] = '%' . $DB->sql_like_escape($search) . '%';
        }
        $where .= ' AND (' . implode(' OR ', $terms) . ')';
    }
    $total = $DB->count_records_select(workflow::TABLE, $where, $params);
    $records = $DB->get_records_select(workflow::TABLE, $where, $params, 'timecreated DESC,id DESC', '*', $pageno * 30, 30);
    $table = new html_table(); $table->head = [get_string('protocol', 'mod_lumen10admissao'), get_string('student', 'mod_lumen10admissao'), get_string('email', 'mod_lumen10admissao'), get_string('status', 'mod_lumen10admissao'), get_string('received', 'mod_lumen10admissao'), get_string('actions', 'mod_lumen10admissao')];
    foreach ($records as $r) {
        $actions = html_writer::link(new moodle_url('/mod/lumen10admissao/analisar.php', ['id' => $r->id]), get_string('review', 'mod_lumen10admissao'), ['class' => 'btn btn-sm btn-outline-primary']);
        if (has_capability('mod/lumen10admissao:delete', $context)) {
            $actions = html_writer::link(new moodle_url('/mod/lumen10admissao/excluir.php', ['id' => $r->id]), get_string('delete', 'mod_lumen10admissao'), ['class' => 'btn btn-sm btn-outline-danger']) . ' ' . $actions;
        }
        $table->data[] = [s($r->protocol), s($r->firstname . ' ' . $r->lastname), s($r->email), s(page::status($r)),
            userdate($r->timecreated), html_writer::div($actions, 'd-flex gap-2')];
    }
    echo html_writer::div(html_writer::table($table), 'l10adm-table');
    echo $OUTPUT->paging_bar($total, $pageno, 30, new moodle_url($url, ['status' => $status, 'search' => $search]));
    if (!$total) { echo html_writer::tag('p', get_string('emptyapplications', 'mod_lumen10admissao')); }
} else {
    $records = $DB->get_records(workflow::TABLE, ['admissaoid' => $activity->id, 'userid' => $USER->id], 'id DESC');
    if (!$records) { echo html_writer::tag('p', get_string('noownapplications', 'mod_lumen10admissao')); }
    foreach ($records as $r) {
        page::data($r);
        if ($r->status === 'approved') {
            echo html_writer::link(workflow::reception_url($r), get_string('completerecord', 'mod_lumen10admissao'), ['class' => 'btn btn-primary']);
            \mod_lumen10admissao\declaration::render_link($r);
        }
    }
}
page::end();
