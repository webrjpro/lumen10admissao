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
require_login(); require_capability('moodle/site:config', context_system::instance());
$PAGE->set_url('/mod/lumen10admissao/certificado.php'); $PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('certificatepagetitle', 'mod_lumen10admissao')); $PAGE->set_heading(get_string('certificatepagetitle', 'mod_lumen10admissao'));
$PAGE->set_pagelayout('admin');
$check = \mod_lumen10admissao\integrity::verify();
echo $OUTPUT->header(); echo $OUTPUT->heading(get_string('certificateheading', 'mod_lumen10admissao'), 2);
echo $OUTPUT->notification($check['signature'] && $check['files'] ? get_string('certificatevalid', 'mod_lumen10admissao') : get_string('certificateinvalid', 'mod_lumen10admissao'),
    $check['signature'] && $check['files'] ? 'success' : 'warning');
if ($check['metadata']) {
    $data = $check['metadata']; $table = new html_table();
    $table->data = [[get_string('component', 'mod_lumen10admissao'), s($data['component'])], [get_string('version', 'mod_lumen10admissao'), s($data['release'])], [get_string('owner', 'mod_lumen10admissao'), s($data['owner'])],
        [get_string('technicalissue', 'mod_lumen10admissao'), s($data['issued_at'])], [get_string('codelicence', 'mod_lumen10admissao'), s($data['license'])], [get_string('checkedfiles', 'mod_lumen10admissao'), count($data['files'])]];
    echo html_writer::table($table);
}
echo html_writer::tag('p', get_string('copyrightnotice', 'mod_lumen10admissao'));
echo html_writer::tag('p', get_string('certificatelimitations', 'mod_lumen10admissao'));
echo $OUTPUT->footer();
