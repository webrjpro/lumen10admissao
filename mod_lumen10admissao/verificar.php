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
use mod_lumen10admissao\{workflow, declaration};
$code = strtoupper(optional_param('code', '', PARAM_ALPHANUM));
$result = declaration::verification($code);
$PAGE->set_url('/mod/lumen10admissao/verificar.php', ['code' => $code]);
$PAGE->set_context(context_system::instance()); $PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('verifyenrolment', 'mod_lumen10admissao')); $PAGE->set_heading(get_string('verifyenrolment', 'mod_lumen10admissao'));
$PAGE->requires->js_call_amd('mod_lumen10admissao/form', 'init');
header('Cache-Control: no-store, private, max-age=0'); header('Referrer-Policy: no-referrer');
if (!$result) { http_response_code(404); }
echo $OUTPUT->header(); echo html_writer::start_div('l10adm');
echo $OUTPUT->heading(get_string('verificationheading', 'mod_lumen10admissao'), 2);
if (!$result) {
    echo $OUTPUT->notification(get_string('declarationnotfound', 'mod_lumen10admissao'), 'warning');
} else {
    $row = $result['record'];
    echo $OUTPUT->notification(get_string('currentstatus', workflow::COMPONENT, s($result['label'])), $result['status'] === 'active' ? 'success' : 'warning');
    $table = new html_table(); $table->attributes['class'] = 'generaltable l10adm-details';
    foreach ([get_string('issuer', 'mod_lumen10admissao') => $row->institution, get_string('student', 'mod_lumen10admissao') => $row->studentname, get_string('registration', 'mod_lumen10admissao') => $row->registration,
        get_string('category', 'mod_lumen10admissao') => $row->classname, get_string('verifiedscope', 'mod_lumen10admissao') => $result['scope'], get_string('accountstatus', 'mod_lumen10admissao') => $result['account'],
        get_string('enrolmentstatus', 'mod_lumen10admissao') => $result['enrolment'], get_string('issued', 'mod_lumen10admissao') => userdate($row->timecreated),
        get_string('checked', 'mod_lumen10admissao') => userdate(time()), get_string('declarationcode', 'mod_lumen10admissao') => $row->code] as $name => $value) {
        $table->data[] = [s($name), s($value)];
    }
    echo html_writer::table($table);
    echo html_writer::tag('p', get_string('verificationnotice', 'mod_lumen10admissao'));
    echo html_writer::tag('p', get_string('verifyfileintro', 'mod_lumen10admissao'));
    echo html_writer::tag('label', get_string('verifyfile', 'mod_lumen10admissao'), ['for' => 'l10adm-verify-file']);
    echo html_writer::empty_tag('input', ['type' => 'file', 'id' => 'l10adm-verify-file', 'accept' => '.pdf,application/pdf',
        'data-l10verifyhash' => $row->sha256]);
    echo html_writer::tag('p', '', ['id' => 'l10adm-verify-result', 'role' => 'status', 'aria-live' => 'polite']);
}
echo html_writer::end_div(); echo $OUTPUT->footer();
