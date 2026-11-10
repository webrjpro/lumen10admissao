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
$id = required_param('id', PARAM_INT); $request = workflow::request($id, false);
$scope = workflow::activity((int)$request->admissaoid); [$activity, $cm, $course] = $scope;
require_login($course, false, $cm); $request = workflow::request($id);
$scope[3] = workflow::course((int)$request->courseid);
$context = context_module::instance($cm->id);
$url = new moodle_url('/mod/lumen10admissao/analisar.php', ['id' => $id]); page::setup($url, $scope);
$back = new moodle_url('/mod/lumen10admissao/view.php', ['id' => $cm->id]);
$form = null;
if (has_capability('mod/lumen10admissao:approve', $context) && !in_array($request->status, ['withdrawn', 'expired'])) {
    $form = new \mod_lumen10admissao\form\review($url, ['request' => $request, 'activity' => $activity]);
    if ($form->is_cancelled()) { redirect($back); }
    if ($data = $form->get_data()) {
        if ($data->decision === 'resend') { \mod_lumen10admissao\mail::resend($id); }
        else { workflow::decide($id, $data->decision, $data->reason ?? '', !empty($data->enrol)); }
        $sent = \mod_lumen10admissao\mail::dispatch_request($id);
        redirect($url, $sent ? get_string('sentnotice', 'mod_lumen10admissao') : get_string('queuednotice', 'mod_lumen10admissao'));
    }
}
page::start(get_string('reviewapplication', 'mod_lumen10admissao'), $scope); page::data($request);
\mod_lumen10admissao\documents::render($request);
\mod_lumen10admissao\declaration::render_link($request);
$identity = workflow::identity($request);
if ($identity['conflict']) { echo $OUTPUT->notification(get_string('error_conflict', workflow::COMPONENT), 'warning'); }
if ($identity['user']) {
    $enrolstates = ['active' => get_string('enrolactive', 'mod_lumen10admissao'), 'inactive' => get_string('enrolinactive', 'mod_lumen10admissao'), 'none' => get_string('enrolnone', 'mod_lumen10admissao')];
    echo $OUTPUT->notification(get_string('existingaccountnotice', workflow::COMPONENT, (object)['name' => s(fullname($identity['user'])), 'username' => s($identity['user']->username), 'status' => s($enrolstates[$identity['enrolment']])]), 'info');
}
if ($request->reason) { echo $OUTPUT->notification(s($request->reason), 'info'); }
if ($request->status === 'awaitingconfirmation') { echo $OUTPUT->notification(get_string('awaitingnotice', 'mod_lumen10admissao'), 'info'); }
if ($form) { $form->display(); }
$jobs = new html_table(); $jobs->head = [get_string('notification', 'mod_lumen10admissao'), get_string('delivery', 'mod_lumen10admissao'), get_string('attempts', 'mod_lumen10admissao')];
$mailnames = ['confirm' => get_string('mail_confirm', 'mod_lumen10admissao'), 'access' => get_string('mail_access', 'mod_lumen10admissao'), 'correction' => get_string('mail_correction', 'mod_lumen10admissao'), 'rejected' => get_string('mail_rejected', 'mod_lumen10admissao'), 'approved' => get_string('mail_approved', 'mod_lumen10admissao'), 'enrolled' => get_string('mail_enrolled', 'mod_lumen10admissao')];
foreach ($DB->get_records('lumen10admissao_mail', ['requestid' => $id], 'id DESC') as $job) {
    $jobs->data[] = [s($mailnames[$job->kind] ?? $job->kind), $job->timesent ? ($job->errorcode === 'obsolete' ? get_string('notapplicable', 'mod_lumen10admissao') : userdate($job->timesent)) : get_string('awaitingdelivery', 'mod_lumen10admissao'), (int)$job->attempts];
}
echo $OUTPUT->heading(get_string('notifications', 'mod_lumen10admissao'), 3); echo html_writer::table($jobs);
$history = new html_table(); $history->head = [get_string('date', 'mod_lumen10admissao'), get_string('action', 'mod_lumen10admissao'), get_string('author', 'mod_lumen10admissao'), get_string('reasonshort', 'mod_lumen10admissao')];
$actions = ['submitted' => get_string('audit_submitted', 'mod_lumen10admissao'), 'confirmed' => get_string('audit_confirmed', 'mod_lumen10admissao'), 'resubmitted' => get_string('audit_resubmitted', 'mod_lumen10admissao'), 'approve' => get_string('audit_approve', 'mod_lumen10admissao'),
    'correction_accessed' => get_string('audit_correction_accessed', 'mod_lumen10admissao'), 'declaration_issued' => get_string('audit_declaration_issued', 'mod_lumen10admissao'), 'declaration_viewed' => get_string('audit_declaration_viewed', 'mod_lumen10admissao'),
    'correction' => get_string('audit_correction', 'mod_lumen10admissao'), 'reject' => get_string('audit_reject', 'mod_lumen10admissao'), 'enrol' => get_string('audit_enrol', 'mod_lumen10admissao'), 'withdrawn' => get_string('audit_withdrawn', 'mod_lumen10admissao'),
    'notification_requested' => get_string('audit_notification_requested', 'mod_lumen10admissao'), 'document_viewed' => get_string('audit_document_viewed', 'mod_lumen10admissao')];
$entries = $DB->get_records('lumen10admissao_log', ['requestid' => $id], 'id DESC');
$actorids = array_values(array_unique(array_filter(array_column($entries, 'actorid'))));
$actors = $actorids ? $DB->get_records_list('user', 'id', $actorids, '', 'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename') : [];
foreach ($entries as $entry) {
    $actor = $actors[$entry->actorid] ?? false;
    $history->data[] = [userdate($entry->timecreated), s($actions[$entry->action] ?? $entry->action), s($actor ? fullname($actor) : get_string('applicantsystem', 'mod_lumen10admissao')), s($entry->note ?? '')];
}
echo $OUTPUT->heading(get_string('history', 'mod_lumen10admissao'), 3); echo html_writer::table($history);
echo html_writer::link($back, get_string('backapplications', 'mod_lumen10admissao'), ['class' => 'btn btn-secondary']); page::end();
