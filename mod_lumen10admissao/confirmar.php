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
$token = required_param('token', PARAM_RAW_TRIMMED); $purpose = required_param('purpose', PARAM_ALPHA);
$request = workflow::token_request($token, $purpose);
$scope = workflow::activity((int)$request->admissaoid);
$url = new moodle_url('/mod/lumen10admissao/confirmar.php');
page::setup($url, $scope, true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey(); $request = workflow::consume($token, $purpose); workflow::session_grant($request);
    if ($purpose === 'confirm') { \mod_lumen10admissao\mail::dispatch_request($request->id); }
    redirect(new moodle_url('/mod/lumen10admissao/acompanhar.php', ['id' => $request->id]));
}
page::start($purpose === 'confirm' ? get_string('confirmapproved', 'mod_lumen10admissao') : get_string('correctapplication', 'mod_lumen10admissao'), $scope);
echo html_writer::tag('p', $purpose === 'confirm'
    ? get_string('confirmintro', 'mod_lumen10admissao')
    : get_string('correctionintro', 'mod_lumen10admissao'));
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url]);
foreach (['token' => $token, 'purpose' => $purpose, 'sesskey' => sesskey()] as $name => $value) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
}
echo html_writer::tag('button', get_string('confirmcontinue', workflow::COMPONENT), ['class' => 'btn btn-primary', 'type' => 'submit']);
echo html_writer::end_tag('form'); page::end();
