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
$id = required_param('id', PARAM_INT);
$scope = workflow::by_cmid($id);
$url = new moodle_url('/mod/lumen10admissao/cadastro.php', ['id' => $id]);
page::setup($url, $scope, true);
try {
    workflow::by_cmid($id, true);
} catch (\moodle_exception $error) {
    $titles = ['error_closed' => 'signupclosed', 'error_notopen' => 'signupnotopen',
        'error_paused' => 'signuppaused', 'error_disabled' => 'signupunavailable'];
    if ($error->module !== workflow::COMPONENT || !isset($titles[$error->errorcode])) {
        throw $error;
    }
    $title = get_string($titles[$error->errorcode], workflow::COMPONENT);
    $PAGE->set_title($title);
    page::start($title, $scope);
    echo $OUTPUT->notification(get_string($error->errorcode, workflow::COMPONENT, $error->a), 'info');
    page::end();
    exit;
}
$sessionchanged = $_SERVER['REQUEST_METHOD'] === 'POST' && !confirm_sesskey();
// Reject stale submissions without processing files or creating an application.
// A non-empty AJAX data envelope without the submission marker opens a fresh native form.
$form = new \mod_lumen10admissao\form\application($url, [],
    ajaxformdata: $sessionchanged ? ['lumen_session_recovery' => 1] : null);
$received = false;
$submissionerror = $sessionchanged
    ? get_string('sessionrecovery', 'mod_lumen10admissao') : '';
if ($sessionchanged) {
    $preserved = [];
    foreach (['fullname', 'cpf', 'mothername', 'fathername', 'socialname', 'email', 'emailconfirm', 'certtype', 'website', 'visualimpairment'] as $field) {
        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            $preserved[$field] = clean_param($_POST[$field], PARAM_NOTAGS);
        }
    }
    $form->set_data((object)$preserved);
}
if (!$sessionchanged && ($data = $form->get_data())) {
    try {
        $data->admissaoid = $scope[0]->id;
        $data->uploads = \mod_lumen10admissao\documents::payload($form, true, !empty($data->visualimpairment));
        $request = workflow::submit($data);
        if ($request) { \mod_lumen10admissao\mail::dispatch_request($request->id); }
        $received = true;
    } catch (\moodle_exception $error) {
        if ($error->module !== workflow::COMPONENT || !in_array($error->errorcode,
                ['error_public', 'error_storage', 'error_documents', 'error_visualreport', 'error_filetype', 'error_filesize',
                    'error_invalid', 'error_filiation', 'error_consent', 'error_closed', 'error_notopen',
                    'error_paused', 'error_disabled'])) {
            throw $error;
        }
        $submissionerror = $error->getMessage();
    }
}
page::start(get_string('application', 'mod_lumen10admissao'), $scope);
if ($received) {
    echo $OUTPUT->notification(get_string('receivednotice', 'mod_lumen10admissao'), 'info');
} else {
    if ($submissionerror !== '') { echo $OUTPUT->notification(s($submissionerror), 'error'); }
    echo html_writer::tag('p', get_string('applicationintro', 'mod_lumen10admissao'));
    $form->display();
}
page::end();
