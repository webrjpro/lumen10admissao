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
use mod_lumen10admissao\{workflow, page};
$id = required_param('id', PARAM_INT); $request = workflow::request($id, false);
$scope = workflow::activity((int)$request->admissaoid); [$activity, $cm, $course] = $scope;
require_login($course, false, $cm); workflow::assert_scope($request, 'delete');
$url = new moodle_url('/mod/lumen10admissao/excluir.php', ['id' => $id]);
$back = new moodle_url('/mod/lumen10admissao/view.php', ['id' => $cm->id]);
page::setup($url, $scope);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && optional_param('confirm', 0, PARAM_BOOL)) {
    require_sesskey(); workflow::delete_request($id); redirect($back, get_string('deletednotice', 'mod_lumen10admissao'));
}
page::start(get_string('deleteapplication', 'mod_lumen10admissao'), $scope);
$name = s(trim($request->firstname . ' ' . $request->lastname));
$message = get_string('deletequestion', workflow::COMPONENT, (object)['name' => $name, 'protocol' => s($request->protocol)]) . ' ' . get_string('deletewarning', workflow::COMPONENT);
$yes = new single_button(new moodle_url($url, ['confirm' => 1]), get_string('confirmdelete', 'mod_lumen10admissao'), 'post');
echo $OUTPUT->confirm($message, $yes, $back); page::end();
