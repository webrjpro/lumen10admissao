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
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_login($course);

$context = context_course::instance($course->id);
$title = get_string('modulenameplural', 'mod_lumen10admissao');
$PAGE->set_url('/mod/lumen10admissao/index.php', ['id' => $course->id]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add($title);

$links = lumen10admissao_get_course_index($course);
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_lumen10admissao/index', [
    'title' => $title,
    'links' => $links,
    'haslinks' => !empty($links),
]);
echo $OUTPUT->footer();
