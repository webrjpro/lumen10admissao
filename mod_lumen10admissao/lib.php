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
defined('MOODLE_INTERNAL') || die();

function lumen10admissao_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_INTRO => true, FEATURE_SHOW_DESCRIPTION => true, FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_GRADE_HAS_GRADE => false, FEATURE_GROUPS => false, FEATURE_GROUPINGS => false,
        FEATURE_COMPLETION_TRACKS_VIEWS => false, FEATURE_MOD_PURPOSE => MOD_PURPOSE_ADMINISTRATION,
        default => null,
    };
}

function lumen10admissao_record($data): stdClass {
    global $USER;
    $reception = \mod_lumen10admissao\workflow::course((int)$data->course);
    $target = \mod_lumen10admissao\workflow::course((int)$data->targetcourseid);
    if ($target->category != $reception->category ||
            (!empty($data->opendate) && !empty($data->closedate) && $data->closedate <= $data->opendate)) {
        throw new moodle_exception('error_category', 'mod_lumen10admissao');
    }
    return (object)['course' => $reception->id, 'name' => $data->name,
        'intro' => $data->intro ?? '', 'introformat' => (int)($data->introformat ?? FORMAT_HTML),
        'targetcourseid' => $target->id, 'receptioncourseid' => $reception->id, 'categoryid' => $reception->category,
        'enabled' => !empty($data->enabled) ? 1 : 0, 'autoenrol' => !empty($data->autoenrol) ? 1 : 0,
        'opendate' => (int)($data->opendate ?? 0), 'closedate' => (int)($data->closedate ?? 0),
        'usermodified' => $USER->id, 'timemodified' => time()];
}

function lumen10admissao_add_instance($data, $mform = null) {
    global $DB;
    $record = lumen10admissao_record($data);
    $record->timecreated = time();
    return $DB->insert_record('lumen10admissao', $record);
}

function lumen10admissao_update_instance($data, $mform = null) {
    global $DB;
    $record = lumen10admissao_record($data);
    $record->id = $data->instance;
    return $DB->update_record('lumen10admissao', $record);
}

function lumen10admissao_delete_instance($id) {
    global $DB;
    if (!$DB->record_exists('lumen10admissao', ['id' => $id])) { return false; }
    $transaction = $DB->start_delegated_transaction();
    foreach ($DB->get_records('lumen10admissao_req', ['admissaoid' => $id], '', 'id') as $request) {
        \mod_lumen10admissao\privacy\provider::erase_request((int)$request->id);
    }
    $DB->delete_records('lumen10admissao', ['id' => $id]);
    $transaction->allow_commit();
    return true;
}

function lumen10admissao_get_coursemodule_info($cm) {
    global $DB;
    if (!$instance = $DB->get_record('lumen10admissao', ['id' => $cm->instance])) { return null; }
    $info = new cached_cm_info(); $info->name = $instance->name;
    return $info;
}

/** Only intro files follow native course permissions; identity documents use documento.php exclusively. */
function lumen10admissao_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel === CONTEXT_SYSTEM && in_array($filearea, ['decllogo', 'declwatermark', 'declsignature'])) {
        require_login(); require_capability('moodle/site:config', $context);
        $itemid = (int)array_shift($args); $filename = array_pop($args); $filepath = '/' . implode('/', $args) . '/';
        if ($filepath === '//') { $filepath = '/'; }
        $file = get_file_storage()->get_file($context->id, 'mod_lumen10admissao', $filearea, $itemid, $filepath, $filename);
        if (!$file || $file->is_directory()) { return false; }
        send_stored_file($file, 0, 0, $forcedownload, $options);
    }
    if ($context->contextlevel !== CONTEXT_MODULE || $filearea !== 'intro') { return false; }
    require_login($course, false, $cm);
    $filename = array_pop($args); $filepath = '/' . implode('/', $args) . '/';
    if ($filepath === '//') { $filepath = '/'; }
    $file = get_file_storage()->get_file($context->id, 'mod_lumen10admissao', 'intro', 0, $filepath, $filename);
    if (!$file || $file->is_directory()) { return false; }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/** Record an authenticated activity view through Moodle's event API. */
function lumen10admissao_view(stdClass $activity, stdClass $course, stdClass $cm, context_module $context): void {
    $event = \mod_lumen10admissao\event\course_module_viewed::create([
        'objectid' => $activity->id,
        'context' => $context,
    ]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('lumen10admissao', $activity);
    $event->trigger();
}
