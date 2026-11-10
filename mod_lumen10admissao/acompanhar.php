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
$id = required_param('id', PARAM_INT); $request = workflow::session_request($id);
$scope = workflow::activity((int)$request->admissaoid);
$url = new moodle_url('/mod/lumen10admissao/acompanhar.php', ['id' => $id]); page::setup($url, $scope, true);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && optional_param('withdraw', 0, PARAM_BOOL)) {
    require_sesskey(); workflow::withdraw($id); redirect($url, get_string('withdrawnnotice', 'mod_lumen10admissao'));
}
$form = null;
if ($request->status === 'correction') {
    $form = new \mod_lumen10admissao\form\application($url, ['correction' => true, 'request' => $request]);
    if ($data = $form->get_data()) {
        $data->uploads = \mod_lumen10admissao\documents::payload($form, false, !empty($data->visualimpairment));
        workflow::update_applicant($id, $data); redirect($url, get_string('resubmittednotice', 'mod_lumen10admissao'));
    }
    $defaults = clone $request;
    $defaults->fullname = $request->firstname . ($request->lastname === '-' ? '' : ' ' . $request->lastname);
    $currentfiles = \mod_lumen10admissao\documents::files($request);
    if (isset($currentfiles['certidao'])) { $defaults->certtype = $currentfiles['certidao']->get_source(); }
    $form->set_data($defaults);
}
page::start(get_string('trackapplication', 'mod_lumen10admissao'), $scope); page::data($request);
\mod_lumen10admissao\documents::render($request);
if ($request->reason) { echo $OUTPUT->notification(s($request->reason), 'info'); }
if ($form) { $form->display(); }
if (in_array($request->status, ['pending', 'correction', 'awaitingconfirmation'])) {
    echo $OUTPUT->single_button(new moodle_url($url, ['withdraw' => 1]), get_string('withdraw', 'mod_lumen10admissao'), 'post');
}
if ($request->status === 'pending') { echo $OUTPUT->notification(get_string('underreview', 'mod_lumen10admissao'), 'info'); }
if ($request->status === 'approved') {
    echo $OUTPUT->notification(get_string('completednotice', 'mod_lumen10admissao'), 'success');
    echo html_writer::link(new moodle_url('/login/index.php'), get_string('signin', workflow::COMPONENT), ['class' => 'btn btn-primary']);
    \mod_lumen10admissao\declaration::render_link($request);
}
page::end();
