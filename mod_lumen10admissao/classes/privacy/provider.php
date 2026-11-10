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
namespace mod_lumen10admissao\privacy;
defined('MOODLE_INTERNAL') || die();
use mod_lumen10admissao\workflow;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\{contextlist, approved_contextlist, userlist, approved_userlist, writer};

final class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider, \core_privacy\local\request\core_userlist_provider {
    public static function get_metadata(collection $items): collection {
        $items->add_database_table('lumen10admissao', [
            'usermodified' => 'privacy:metadata:field:usermodified',
            'timecreated' => 'privacy:metadata:field:timecreated',
            'timemodified' => 'privacy:metadata:field:timemodified',
        ], 'privacy:metadata:logs');
        $items->add_database_table('lumen10admissao_req', [
            'admissaoid' => 'privacy:metadata:field:admissaoid',
            'protocol' => 'privacy:metadata:field:protocol',
            'activekey' => 'privacy:metadata:field:activekey',
            'firstname' => 'privacy:metadata:field:firstname',
            'lastname' => 'privacy:metadata:field:lastname',
            'socialname' => 'privacy:metadata:field:socialname',
            'mothername' => 'privacy:metadata:field:mothername',
            'fathername' => 'privacy:metadata:field:fathername',
            'email' => 'privacy:metadata:field:email',
            'cpf' => 'privacy:metadata:field:cpf',
            'phone' => 'privacy:metadata:field:phone',
            'visualimpairment' => 'privacy:metadata:field:visualimpairment',
            'courseid' => 'privacy:metadata:field:courseid',
            'categoryid' => 'privacy:metadata:field:categoryid',
            'receptioncourseid' => 'privacy:metadata:field:receptioncourseid',
            'userid' => 'privacy:metadata:field:userid',
            'newaccount' => 'privacy:metadata:field:newaccount',
            'status' => 'privacy:metadata:field:status',
            'enrolstatus' => 'privacy:metadata:field:enrolstatus',
            'reviewerid' => 'privacy:metadata:field:reviewerid',
            'reason' => 'privacy:metadata:field:reason',
            'confirmhash' => 'privacy:metadata:field:confirmhash',
            'accessexpires' => 'privacy:metadata:field:accessexpires',
            'confirmexpires' => 'privacy:metadata:field:confirmexpires',
            'accesshash' => 'privacy:metadata:field:accesshash',
            'timeverified' => 'privacy:metadata:field:timeverified',
            'consenttime' => 'privacy:metadata:field:consenttime',
            'timecreated' => 'privacy:metadata:field:timecreated',
            'timemodified' => 'privacy:metadata:field:timemodified',
            'enrolrequested' => 'privacy:metadata:field:enrolrequested',
            'timecompleted' => 'privacy:metadata:field:timecompleted',
        ], 'privacy:metadata:requests');
        $items->add_database_table('lumen10admissao_log', [
            'requestid' => 'privacy:metadata:field:requestid',
            'actorid' => 'privacy:metadata:field:actorid',
            'action' => 'privacy:metadata:field:action',
            'note' => 'privacy:metadata:field:note',
            'timecreated' => 'privacy:metadata:field:timecreated',
        ], 'privacy:metadata:logs');
        $items->add_database_table('lumen10admissao_mail', [
            'requestid' => 'privacy:metadata:field:requestid',
            'kind' => 'privacy:metadata:field:kind',
            'attempts' => 'privacy:metadata:field:attempts',
            'nextattempt' => 'privacy:metadata:field:nextattempt',
            'timesent' => 'privacy:metadata:field:timesent',
            'errorcode' => 'privacy:metadata:field:errorcode',
            'timecreated' => 'privacy:metadata:field:timecreated',
        ], 'privacy:metadata:mail');
        $items->add_database_table('lumen10admissao_reg', [
            'userid' => 'privacy:metadata:field:userid',
            'number' => 'privacy:metadata:field:number',
            'credentialsent' => 'privacy:metadata:field:credentialsent',
            'timecreated' => 'privacy:metadata:field:timecreated',
        ], 'privacy:metadata:registration');
        $items->add_database_table('lumen10admissao_rate', [
            'ratekey' => 'privacy:metadata:field:ratekey',
            'hits' => 'privacy:metadata:field:hits',
            'windowstart' => 'privacy:metadata:field:windowstart',
        ], 'privacy:metadata:rate');
        $items->add_database_table('lumen10admissao_decl', [
            'requestid' => 'privacy:metadata:field:requestid',
            'code' => 'privacy:metadata:field:code',
            'studentname' => 'privacy:metadata:field:studentname',
            'registration' => 'privacy:metadata:field:registration',
            'institution' => 'privacy:metadata:field:institution',
            'classname' => 'privacy:metadata:field:classname',
            'sha256' => 'privacy:metadata:field:sha256',
            'signaturetype' => 'privacy:metadata:field:signaturetype',
            'timecreated' => 'privacy:metadata:field:timecreated',
        ], 'privacy:metadata:declaration');
        $items->add_subsystem_link('core_files', [], 'privacy:metadata:documents');
        $items->add_external_location_link('smtp', ['email' => 'privacy:metadata:email', 'message' => 'privacy:metadata:externalmail'], 'privacy:metadata:externalmail');
        return $items;
    }
    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $list->add_from_sql('SELECT DISTINCT c.id FROM {context} c JOIN {course_modules} cm ON cm.id=c.instanceid
            JOIN {modules} m ON m.id=cm.module AND m.name=:module JOIN {lumen10admissao} a ON a.id=cm.instance
            LEFT JOIN {lumen10admissao_req} r ON r.admissaoid=a.id LEFT JOIN {lumen10admissao_log} l ON l.requestid=r.id
            WHERE c.contextlevel=:level AND (r.userid=:owner OR r.reviewerid=:reviewer OR l.actorid=:actor OR a.usermodified=:modifier)',
            ['module' => 'lumen10admissao', 'level' => CONTEXT_MODULE, 'owner' => $userid, 'reviewer' => $userid, 'actor' => $userid, 'modifier' => $userid]);
        global $DB;
        if ($DB->record_exists('lumen10admissao_reg', ['userid' => $userid])) { $list->add_system_context(); }
        return $list;
    }
    private static function instance(\context $context): int {
        if ($context->contextlevel !== CONTEXT_MODULE) { return 0; }
        $cm = get_coursemodule_from_id('lumen10admissao', $context->instanceid);
        return $cm ? (int)$cm->instance : 0;
    }
    public static function get_users_in_context(userlist $list) {
        $context = $list->get_context();
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $list->add_from_sql('userid', 'SELECT userid FROM {lumen10admissao_reg} WHERE userid IS NOT NULL'); return;
        }
        if (!$id = self::instance($context)) { return; }
        $list->add_from_sql('userid', 'SELECT userid FROM {lumen10admissao_req} WHERE admissaoid=:id AND userid>0', ['id' => $id]);
        $list->add_from_sql('reviewerid', 'SELECT reviewerid FROM {lumen10admissao_req} WHERE admissaoid=:id AND reviewerid>0', ['id' => $id]);
        $list->add_from_sql('actorid', 'SELECT l.actorid FROM {lumen10admissao_log} l JOIN {lumen10admissao_req} r ON r.id=l.requestid WHERE r.admissaoid=:id AND l.actorid>0', ['id' => $id]);
        $list->add_from_sql('usermodified', 'SELECT usermodified FROM {lumen10admissao} WHERE id=:id AND usermodified>0', ['id' => $id]);
    }
    public static function export_user_data(approved_contextlist $list) {
        global $DB;
        $uid = (int)$list->get_user()->id;
        foreach ($list->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_SYSTEM) {
                if ($row = $DB->get_record('lumen10admissao_reg', ['userid' => $uid])) {
                    writer::with_context($context)->export_data([get_string('privacyregistration', 'mod_lumen10admissao')], (object)['matricula' => $row->number, 'timecreated' => $row->timecreated]);
                }
                continue;
            }
            if (!$id = self::instance($context)) { continue; }
            foreach ($DB->get_records(workflow::TABLE, ['admissaoid' => $id, 'userid' => $uid]) as $r) {
                $data = clone $r;
                unset($data->confirmhash, $data->accesshash, $data->activekey);
                $data->notifications = array_values($DB->get_records('lumen10admissao_mail', ['requestid' => $r->id]));
                $data->history = array_values($DB->get_records('lumen10admissao_log', ['requestid' => $r->id]));
                writer::with_context($context)->export_data([get_string('privacyapplications', 'mod_lumen10admissao'), (string)$r->id], $data);
                foreach (array_merge(\mod_lumen10admissao\documents::KINDS, ['declaracao']) as $kind) { writer::with_context($context)->export_area_files([get_string('privacyapplications', 'mod_lumen10admissao'), (string)$r->id, $kind], workflow::COMPONENT, $kind, $r->id); }
                if ($declaration = $DB->get_record('lumen10admissao_decl', ['requestid' => $r->id])) {
                    writer::with_context($context)->export_data([get_string('privacyapplications', 'mod_lumen10admissao'), (string)$r->id, get_string('privacydeclaration', 'mod_lumen10admissao')], $declaration);
                }
            }
            // Analyst export contains the analyst's actions, never another applicant's identity or reasons.
            $actions = $DB->get_records_sql('SELECT l.id,l.requestid,l.action,l.timecreated FROM {lumen10admissao_log} l
                JOIN {lumen10admissao_req} r ON r.id=l.requestid WHERE r.admissaoid=:id AND l.actorid=:uid', ['id' => $id, 'uid' => $uid]);
            writer::with_context($context)->export_data([get_string('privacyactions', 'mod_lumen10admissao')], (object)['actions' => array_values($actions)]);
        }
    }
    public static function erase_request(int $id): void {
        workflow::locked(['request:' . $id], function() use ($id) { self::erase_locked($id); });
    }
    public static function erase_locked(int $id): void {
        global $DB;
        if ($request = $DB->get_record(workflow::TABLE, ['id' => $id])) { \mod_lumen10admissao\documents::erase($request); }
        foreach (['lumen10admissao_mail', 'lumen10admissao_log', 'lumen10admissao_decl'] as $table) { $DB->delete_records($table, ['requestid' => $id]); }
        $DB->delete_records(workflow::TABLE, ['id' => $id]);
    }
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $DB->execute('UPDATE {lumen10admissao_reg} SET userid=NULL,credentialsent=0,timecreated=0'); return;
        }
        if (!$id = self::instance($context)) { return; }
        foreach ($DB->get_records(workflow::TABLE, ['admissaoid' => $id], '', 'id') as $r) { self::erase_request($r->id); }
        $DB->set_field('lumen10admissao', 'usermodified', 0, ['id' => $id]);
    }
    public static function delete_data_for_user(approved_contextlist $list) {
        foreach ($list->get_contexts() as $context) { self::delete_users($context, [(int)$list->get_user()->id]); }
    }
    public static function delete_data_for_users(approved_userlist $list) { self::delete_users($list->get_context(), $list->get_userids()); }
    private static function delete_users(\context $context, array $users): void {
        global $DB;
        if (!$users) { return; }
        [$in, $params] = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED, 'user');
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $DB->execute('UPDATE {lumen10admissao_reg} SET userid=NULL,credentialsent=0,timecreated=0 WHERE userid ' . $in, $params); return;
        }
        if (!$id = self::instance($context)) { return; }
        foreach ($DB->get_records_select(workflow::TABLE, 'admissaoid=:activity AND userid ' . $in, $params + ['activity' => $id], '', 'id') as $r) { self::erase_request($r->id); }
        $DB->execute('UPDATE {lumen10admissao_req} SET reviewerid=0 WHERE admissaoid=:activity AND reviewerid ' . $in, $params + ['activity' => $id]);
        $DB->execute('UPDATE {lumen10admissao_log} SET actorid=0 WHERE actorid ' . $in . ' AND requestid IN
            (SELECT id FROM {lumen10admissao_req} WHERE admissaoid=:activity)', $params + ['activity' => $id]);
        $DB->execute('UPDATE {lumen10admissao} SET usermodified=0 WHERE id=:activity AND usermodified ' . $in, $params + ['activity' => $id]);
    }
}
