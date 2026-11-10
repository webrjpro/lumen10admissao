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
namespace mod_lumen10admissao;
defined('MOODLE_INTERNAL') || die();

/** Independent admissions. Existing dossier components are queried only for CPF conflicts. */
final class workflow {
    public const COMPONENT = 'mod_lumen10admissao';
    public const TABLE = 'lumen10admissao_req';

    public static function config(string $name, $default = null) {
        $value = get_config(self::COMPONENT, $name);
        return $value === false ? $default : $value;
    }

    public static function locked(array $keys, callable $callback) {
        $factory = \core\lock\lock_config::get_lock_factory(self::COMPONENT);
        $locks = [];
        sort($keys, SORT_STRING);
        try {
            foreach (array_unique($keys) as $key) {
                $lock = $factory->get_lock(hash('sha256', $key), 10);
                if (!$lock) { throw new \moodle_exception('error_public', self::COMPONENT); }
                $locks[] = $lock;
            }
            return self::atomic($callback);
        } finally {
            foreach (array_reverse($locks) as $lock) { $lock->release(); }
        }
    }

    public static function cpf(string $value): string {
        $value = preg_replace('/\D/', '', $value);
        if (strlen($value) !== 11 || preg_match('/^(\d)\1{10}$/', $value)) {
            throw new \moodle_exception('error_cpf', self::COMPONENT);
        }
        for ($length = 9; $length < 11; $length++) {
            $sum = 0;
            for ($i = 0; $i < $length; $i++) { $sum += (int)$value[$i] * ($length + 1 - $i); }
            $digit = (11 - $sum % 11) % 11;
            if ($digit > 9) { $digit = 0; }
            if ((int)$value[$length] !== $digit) { throw new \moodle_exception('error_cpf', self::COMPONENT); }
        }
        return $value;
    }

    public static function atomic(callable $callback) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        try {
            $result = $callback();
            $transaction->allow_commit();
            return $result;
        } catch (\Throwable $error) {
            $transaction->rollback($error instanceof \Exception ? $error : new \coding_exception('Admissions transaction failed'));
        }
    }

    /** Native course module is the boundary: one public URL and one analyst panel per activity. */
    public static function activity(int $id, bool $public = false): array {
        global $DB;
        $activity = $DB->get_record('lumen10admissao', ['id' => $id], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('lumen10admissao', $id, 0, false, MUST_EXIST);
        $reception = self::course((int)$activity->course);
        $target = self::course((int)$activity->targetcourseid);
        if ($activity->course != $cm->course || $activity->receptioncourseid != $reception->id ||
                $activity->categoryid != $reception->category || $target->category != $reception->category) {
            throw new \moodle_exception('error_category', self::COMPONENT);
        }
        if ($public) {
            if (!$cm->visible || !$target->visible || !$reception->visible || !self::category((int)$reception->category)->visible) {
                throw new \moodle_exception('error_disabled', self::COMPONENT);
            }
            if (!self::config('enabled', 1) || !$activity->enabled) {
                throw new \moodle_exception('error_paused', self::COMPONENT);
            }
            $now = time();
            if ($activity->closedate && $activity->closedate <= $now) {
                throw new \moodle_exception('error_closed', self::COMPONENT, '',
                    userdate($activity->closedate, get_string('strftimedatetime', 'langconfig'), \core_date::get_server_timezone()));
            }
            if ($activity->opendate && $activity->opendate > $now) {
                throw new \moodle_exception('error_notopen', self::COMPONENT, '',
                    userdate($activity->opendate, get_string('strftimedatetime', 'langconfig'), \core_date::get_server_timezone()));
            }
        }
        return [$activity, $cm, $reception, $target];
    }

    public static function by_cmid(int $id, bool $public = false): array {
        $cm = get_coursemodule_from_id('lumen10admissao', $id, 0, false, MUST_EXIST);
        return self::activity((int)$cm->instance, $public);
    }

    public static function assert_scope(\stdClass $request, string $capability = 'review'): void {
        [$activity, $cm, $reception] = self::activity((int)$request->admissaoid);
        if ($request->categoryid != $reception->category || $request->receptioncourseid != $reception->id) {
            throw new \moodle_exception('error_category', self::COMPONENT);
        }
        require_capability('mod/lumen10admissao:' . $capability, \context_module::instance($cm->id));
    }

    public static function request(int $id, bool $review = true): \stdClass {
        global $DB;
        $request = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        if ($review) { self::assert_scope($request); }
        return $request;
    }

    public static function reception_url(\stdClass $request): \moodle_url {
        global $DB;
        if (self::course((int)$request->receptioncourseid)->category != $request->categoryid) {
            throw new \moodle_exception('error_category', self::COMPONENT);
        }
        if ($DB->record_exists('config_plugins', ['plugin' => 'local_lumen10_prontuario', 'name' => 'version'])) {
            return new \moodle_url('/local/lumen10_prontuario/index.php', ['categoryid' => $request->categoryid]);
        }
        return new \moodle_url('/course/view.php', ['id' => $request->receptioncourseid]);
    }

    public static function requested_name(\stdClass $request): string {
        return $request->courseid == $request->receptioncourseid
            ? self::category((int)$request->categoryid)->name : self::course((int)$request->courseid)->fullname;
    }

    /** Read the configured category name without requiring public applicants to browse the course catalogue.
     * Public admission visibility is checked separately in activity(); this does not list category courses.
     */
    public static function category(int $id): \core_course_category {
        return \core_course_category::get($id, MUST_EXIST, true);
    }

    public static function course(int $id): \stdClass {
        global $DB;
        $course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
        if ($course->id <= SITEID) { throw new \moodle_exception('error_course', self::COMPONENT); }
        return $course;
    }

    public static function validate(\stdClass $input, bool $public = true): \stdClass {
        $out = new \stdClass();
        if (isset($input->fullname)) {
            $parts = preg_split('/\s+/u', trim(clean_param($input->fullname, PARAM_NOTAGS)), 2);
            $input->firstname = $parts[0] ?? '';
            $input->lastname = $parts[1] ?? '-';
        }
        foreach (['firstname', 'lastname'] as $field) {
            $out->$field = trim(clean_param($input->$field ?? '', PARAM_NOTAGS));
            if ($out->$field === '' || \core_text::strlen($out->$field) > 100 || preg_match('/[\r\n]/', $out->$field)) {
                throw new \moodle_exception('error_invalid', self::COMPONENT);
            }
        }
        $out->email = \core_text::strtolower(trim($input->email ?? ''));
        if (!validate_email($out->email) || strlen($out->email) > 100 || preg_match('/[\r\n]/', $out->email)) {
            throw new \moodle_exception('error_invalid', self::COMPONENT);
        }
        $out->cpf = self::cpf($input->cpf ?? '');
        // Public registration is deliberately small; remaining contact/address data belongs to Secretaria.
        $out->phone = '';
        $visual = $input->visualimpairment ?? 0;
        if (!in_array($visual, ['0', '1', 0, 1], true)) {
            throw new \moodle_exception('error_invalid', self::COMPONENT);
        }
        $out->visualimpairment = (int)$visual;
        foreach (['socialname' => 100, 'mothername' => 200, 'fathername' => 200] as $field => $max) {
            $out->$field = trim(clean_param($input->$field ?? '', PARAM_NOTAGS));
            if (\core_text::strlen($out->$field) > $max || preg_match('/[\r\n]/', $out->$field)) {
                throw new \moodle_exception('error_invalid', self::COMPONENT);
            }
        }
        if ($out->mothername === '' && $out->fathername === '') { throw new \moodle_exception('error_filiation', self::COMPONENT); }
        $out->admissaoid = (int)($input->admissaoid ?? 0);
        [$activity] = self::activity($out->admissaoid, $public);
        $out->courseid = $public ? (int)$activity->targetcourseid : (int)$input->courseid;
        $out->categoryid = (int)$activity->categoryid;
        $out->receptioncourseid = (int)$activity->course;
        if (self::course($out->courseid)->category != $out->categoryid) {
            throw new \moodle_exception('error_category', self::COMPONENT);
        }
        if (empty($input->consent)) { throw new \moodle_exception('error_consent', self::COMPONENT); }
        return $out;
    }

    /** Persistent rate limit: global, caller IP and destination mailbox. No raw IP is stored. */
    public static function throttle(string $email): bool {
        global $DB;
        $salt = (string)self::config('ratesalt', '');
        if (!$salt) {
            $salt = self::locked(['rate-salt'], function() {
                $salt = (string)self::config('ratesalt', '');
                if (!$salt) { $salt = bin2hex(random_bytes(32)); set_config('ratesalt', $salt, self::COMPONENT); }
                return $salt;
            });
        }
        $limits = ['global' => 200, 'ip:' . getremoteaddr() => 10, 'mail:' . $email => 3];
        foreach ($limits as $scope => $limit) {
            $key = hash_hmac('sha256', $scope, $salt);
            $ok = self::locked(['rate:' . $key], function() use ($key, $limit, $DB) {
                $row = $DB->get_record('lumen10admissao_rate', ['ratekey' => $key]);
                if (!$row) {
                    $row = (object)['ratekey' => $key, 'hits' => 0, 'windowstart' => time()];
                    $row->id = $DB->insert_record('lumen10admissao_rate', $row);
                } else if ($row->windowstart < time() - HOURSECS) {
                    $row->hits = 0; $row->windowstart = time();
                }
                if ($row->hits >= $limit) { return false; }
                $row->hits++; $DB->update_record('lumen10admissao_rate', $row);
                return true;
            });
            if (!$ok) { return false; }
        }
        return true;
    }

    public static function submit(\stdClass $input): ?\stdClass {
        global $DB;
        $data = self::validate($input);
        if (!empty($input->website)) { return null; }
        if (!self::throttle($data->email)) { throw new \moodle_exception('error_public', self::COMPONENT); }
        $uploads = $input->uploads ?? [];
        if (!isset($uploads['rg'], $uploads['certidao'])) { throw new \moodle_exception('error_documents', self::COMPONENT); }
        if ($data->visualimpairment && !isset($uploads['laudo'])) {
            throw new \moodle_exception('error_visualreport', self::COMPONENT);
        }
        if (!$data->visualimpairment) { unset($uploads['laudo']); }
        $key = hash('sha256', $data->email . '|' . $data->admissaoid);
        return self::locked(['application:' . $key, 'upload-budget'], function() use ($key, $data, $uploads, $input, $DB) {
            $request = $DB->get_record(self::TABLE, ['activekey' => $key]);
            if (!$request) {
                $request = $data;
                $request->activekey = $key;
                $request->protocol = 'ADM-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(8)));
                $request->status = 'pending';
                $request->timecreated = $request->timemodified = $request->consenttime = time();
                $request->id = $DB->insert_record(self::TABLE, $request);
                documents::store($request, $uploads, $input->certtype ?? 'nascimento');
                self::audit($request, 'submitted');
            }
            // A submission goes directly to the analyst. Only a decision sends a bearer link.
            if ($request->status === 'awaitingconfirmation') { mail::queue($request->id, 'confirm'); }
            else if ($request->status === 'correction') { mail::queue($request->id, 'correction'); }
            return $DB->get_record(self::TABLE, ['id' => $request->id], '*', MUST_EXIST);
        });
    }

    public static function audit(\stdClass $request, string $action, string $note = '', int $actor = 0): void {
        global $DB, $USER;
        $actor = $actor ?: (isloggedin() && !isguestuser() ? (int)$USER->id : 0);
        $DB->insert_record('lumen10admissao_log', (object)['requestid' => $request->id, 'actorid' => $actor,
            'action' => $action, 'note' => $note, 'timecreated' => time()]);
        \mod_lumen10admissao\event\application_updated::create([
            'context' => \context_module::instance(get_coursemodule_from_instance('lumen10admissao', $request->admissaoid)->id),
            'objectid' => $request->id,
            'relateduserid' => (int)($request->userid ?? 0), 'other' => ['action' => $action],
        ])->trigger();
    }

    public static function token_request(string $token, string $purpose): \stdClass {
        global $DB;
        if (!in_array($purpose, ['confirm', 'access']) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new \moodle_exception('error_token', self::COMPONENT);
        }
        $field = $purpose . 'hash';
        $request = $DB->get_record(self::TABLE, [$field => hash('sha256', $token)]);
        if (!$request || $request->{$purpose . 'expires'} <= time() ||
                ($purpose === 'confirm' && $request->status !== 'awaitingconfirmation') ||
                ($purpose === 'access' && !in_array($request->status, ['correction', 'pending']))) {
            throw new \moodle_exception('error_token', self::COMPONENT);
        }
        return $request;
    }

    /** Tokens are consumed by POST endpoints, not by GET or link scanners. */
    public static function consume(string $token, string $purpose): \stdClass {
        global $DB;
        $initial = self::token_request($token, $purpose);
        return self::locked(['request:' . $initial->id, 'identity-approval'], function() use ($token, $purpose, $DB) {
            $request = self::token_request($token, $purpose);
            $request->{$purpose . 'hash'} = null;
            $request->{$purpose . 'expires'} = 0;
            if ($purpose === 'confirm') {
                $request->timeverified = time();
                self::complete($request);
                self::audit($request, 'confirmed');
            } else {
                $request->timeverified = time();
                $identity = self::identity($request);
                if (!$identity['conflict'] && $identity['user']) { $request->userid = $identity['user']->id; }
                self::audit($request, 'correction_accessed');
            }
            $request->timemodified = time();
            $DB->update_record(self::TABLE, $request);
            if ($purpose === 'confirm' && get_config('local_lumen10_prontuario', 'version') &&
                    class_exists(\local_lumen10_prontuario\admission_documents::class)) {
                \local_lumen10_prontuario\admission_documents::archive($request);
            }
            return $request;
        });
    }

    public static function session_grant(\stdClass $request): void {
        global $SESSION;
        if (!isset($SESSION->lumen10_admissao)) { $SESSION->lumen10_admissao = []; }
        $SESSION->lumen10_admissao[$request->id] = time() + DAYSECS;
    }

    public static function session_request(int $id): \stdClass {
        global $DB, $SESSION;
        if (empty($SESSION->lumen10_admissao[$id]) || $SESSION->lumen10_admissao[$id] < time()) {
            throw new \moodle_exception('error_token', self::COMPONENT);
        }
        return $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    public static function update_applicant(int $id, \stdClass $input): \stdClass {
        global $DB;
        $current = self::session_request($id);
        // E-mail and requested course are immutable under a session granted by that mailbox.
        $input->email = $current->email; $input->courseid = $current->courseid; $input->admissaoid = $current->admissaoid;
        $input->consent = 1;
        $data = self::validate($input, false);
        return self::locked(['request:' . $id, 'upload-budget'], function() use ($id, $data, $input, $DB) {
            $request = self::session_request($id);
            if ($request->status !== 'correction') { throw new \moodle_exception('error_state', self::COMPONENT); }
            foreach (['firstname', 'lastname', 'cpf', 'socialname', 'mothername', 'fathername', 'visualimpairment'] as $field) { $request->$field = $data->$field; }
            if (empty($request->visualimpairment)) { unset($input->uploads['laudo']); }
            if (!empty($input->uploads)) { documents::store($request, $input->uploads, $input->certtype ?? 'nascimento'); }
            documents::required($request);
            $request->status = 'pending'; $request->reason = ''; $request->timemodified = time();
            $request->confirmhash = $request->accesshash = null;
            $request->confirmexpires = $request->accessexpires = 0;
            $DB->update_record(self::TABLE, $request);
            self::audit($request, 'resubmitted');
            return $request;
        });
    }

    /** Match verified mailbox and CPF without changing any existing account or dossier. */
    public static function identity(\stdClass $request): array {
        global $DB, $CFG;
        $users = $DB->get_records_sql('SELECT * FROM {user} WHERE deleted=0 AND mnethostid=:host AND ' .
            $DB->sql_equal('email', ':email', false, true), ['host' => $CFG->mnet_localhost_id, 'email' => $request->email]);
        $user = count($users) === 1 ? reset($users) : null;
        $cpfusers = [];
        $knownconflict = false;
        foreach ([['lumen10admissao_req', 'cpf'], ['local_lumen10_prontuario_dados', 'cpf'],
                ['lumen10prontuario_dados', 'cpf'], ['local_lumen10_arquivados_docs', 'alunocpf']] as [$table, $field]) {
            if (!$DB->get_manager()->table_exists($table)) { continue; }
            $columns = $DB->get_columns($table);
            if (!isset($columns[$field], $columns['userid'])) { continue; }
            $normal = "REPLACE(REPLACE(REPLACE($field,'.',''),'-',''),' ','')";
            foreach ($DB->get_records_sql("SELECT DISTINCT userid FROM {{$table}} WHERE userid>0 AND $normal=:cpf",
                    ['cpf' => $request->cpf]) as $row) { $cpfusers[(int)$row->userid] = true; }
            if ($user && $table !== self::TABLE && $DB->record_exists_select($table,
                    "userid=:uid AND $normal<>:cpf AND $normal<>:blank", ['uid' => $user->id, 'cpf' => $request->cpf, 'blank' => ''])) {
                $knownconflict = true;
            }
        }
        foreach ($DB->get_records('user_info_field', ['shortname' => 'cpf']) as $field) {
            foreach ($DB->get_records_sql("SELECT userid FROM {user_info_data} WHERE fieldid=:field
                    AND REPLACE(REPLACE(REPLACE(data,'.',''),'-',''),' ','')=:cpf",
                    ['field' => $field->id, 'cpf' => $request->cpf]) as $row) { $cpfusers[(int)$row->userid] = true; }
            if ($user && $DB->record_exists_select('user_info_data', "userid=:uid AND fieldid=:field
                    AND REPLACE(REPLACE(REPLACE(data,'.',''),'-',''),' ','')<>:cpf AND data<>:blank",
                    ['uid' => $user->id, 'field' => $field->id, 'cpf' => $request->cpf, 'blank' => ''])) { $knownconflict = true; }
        }
        $conflict = $knownconflict || count($users) > 1 || (bool)array_diff(array_keys($cpfusers), $user ? [(int)$user->id] : []);
        // Also detect a contradictory CPF already associated with that verified email in this module.
        if ($user && $DB->record_exists_select(self::TABLE, 'userid=:uid AND cpf<>:cpf AND cpf<>:blank AND status=:status',
                ['uid' => $user->id, 'cpf' => $request->cpf, 'blank' => '', 'status' => 'approved'])) { $conflict = true; }
        return ['user' => $user, 'conflict' => $conflict, 'cpfusers' => array_keys($cpfusers),
            'enrolment' => $user ? self::enrolment((int)$user->id, (int)$request->courseid) : 'none'];
    }

    public static function enrolment(int $userid, int $courseid): string {
        global $DB;
        require_once($GLOBALS['CFG']->libdir . '/enrollib.php');
        if (is_enrolled(\context_course::instance($courseid), $userid, '', true)) { return 'active'; }
        return $DB->record_exists_sql('SELECT ue.id FROM {user_enrolments} ue JOIN {enrol} e ON e.id=ue.enrolid
            WHERE ue.userid=:userid AND e.courseid=:courseid', ['userid' => $userid, 'courseid' => $courseid]) ? 'inactive' : 'none';
    }

    public static function enrol_plan(int $userid, int $courseid, int $authorizer): array {
        global $DB, $CFG;
        require_once($CFG->libdir . '/enrollib.php');
        self::course($courseid);
        $state = $userid ? self::enrolment($userid, $courseid) : 'none';
        if ($state === 'active') { return ['state' => 'existing']; }
        if ($state !== 'none') { throw new \moodle_exception('error_enrol', self::COMPONENT); }
        $context = \context_course::instance($courseid);
        if (!has_capability('enrol/manual:enrol', $context, $authorizer)) {
            throw new \moodle_exception('error_manual', self::COMPONENT);
        }
        $roleid = (int)self::config('studentrole', 0);
        if (!$roleid) { $roles = get_archetype_roles('student'); $roleid = $roles ? (int)key($roles) : 0; }
        $role = $DB->get_record('role', ['id' => $roleid, 'archetype' => 'student']);
        if (!$role || !isset(get_assignable_roles($context, ROLENAME_ALIAS, false, $authorizer)[$roleid]) || !enrol_is_enabled('manual')) {
            throw new \moodle_exception('error_manual', self::COMPONENT);
        }
        $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid, 'status' => ENROL_INSTANCE_ENABLED]);
        if (!$instance) { throw new \moodle_exception('error_manual', self::COMPONENT); }
        return ['state' => 'enrolled', 'instance' => $instance, 'roleid' => $roleid];
    }

    public static function enrol(int $userid, int $courseid, int $authorizer = 0): string {
        global $USER;
        $plan = self::enrol_plan($userid, $courseid, $authorizer ?: (int)$USER->id);
        if ($plan['state'] === 'existing') { return 'existing'; }
        enrol_get_plugin('manual')->enrol_user($plan['instance'], $userid, $plan['roleid'], time(), 0, ENROL_USER_ACTIVE);
        return 'enrolled';
    }

    /** Recheck the recorded institutional authorization without granting the applicant any staff capability. */
    private static function eligibility(\stdClass $request, int $authorizer): array {
        global $DB, $CFG;
        documents::required($request);
        [$activity, $cm] = self::activity((int)$request->admissaoid);
        if (!$authorizer || !$DB->record_exists('user', ['id' => $authorizer, 'deleted' => 0, 'suspended' => 0]) ||
                !has_capability('mod/lumen10admissao:approve', \context_module::instance($cm->id), $authorizer)) {
            throw new \moodle_exception('error_authorizer', self::COMPONENT);
        }
        if (self::course((int)$request->courseid)->category != $request->categoryid ||
                self::course((int)$request->receptioncourseid)->category != $request->categoryid) {
            throw new \moodle_exception('error_category', self::COMPONENT);
        }
        $identity = self::identity($request);
        if ($identity['conflict']) { throw new \moodle_exception('error_conflict', self::COMPONENT); }
        $user = $identity['user'];
        if ($user && ($user->suspended || !$user->confirmed || isguestuser($user) || is_siteadmin($user) ||
                has_capability('moodle/site:config', \context_system::instance(), $user))) {
            throw new \moodle_exception('error_account', self::COMPONENT);
        }
        if (!$user && !is_enabled_auth('manual')) { throw new \moodle_exception('error_account', self::COMPONENT); }
        if (!$user) { mail::temporary_password((object)['id' => -99, 'firstname' => $request->firstname]); }
        self::enrol_plan($user ? (int)$user->id : 0, (int)$request->receptioncourseid, $authorizer);
        if ($request->courseid != $request->receptioncourseid && $request->enrolrequested) {
            self::enrol_plan($user ? (int)$user->id : 0, (int)$request->courseid, $authorizer);
        }
        if (declaration::enabled($request)) { declaration::check_configuration(); }
        return $identity;
    }

    /** Called only by consumption of the post-approval confirmation token, under both locks and transaction. */
    private static function complete(\stdClass $request): void {
        global $DB, $CFG;
        $identity = self::eligibility($request, (int)$request->reviewerid);
        $user = $identity['user'];
        if (!$user) {
            require_once($CFG->dirroot . '/user/lib.php');
            $user = (object)['auth' => 'manual', 'confirmed' => 1, 'suspended' => 0, 'mnethostid' => $CFG->mnet_localhost_id,
                'username' => self::new_username($request),
                'firstname' => $request->firstname, 'lastname' => $request->lastname, 'email' => $request->email,
                'alternatename' => $request->socialname, 'country' => 'BR', 'lang' => current_language(),
                'password' => hash_internal_user_password(bin2hex(random_bytes(32)))];
            $user->id = user_create_user($user, false);
            $request->newaccount = 1;
            set_user_preference('auth_forcepasswordchange', 1, $user->id);
        }
        $request->userid = (int)$user->id;
        $registration = self::registration($request->userid);
        if ($request->newaccount) { $DB->set_field('user', 'idnumber', $registration->number, ['id' => $user->id]); }
        $state = self::enrol($request->userid, (int)$request->receptioncourseid, (int)$request->reviewerid);
        if ($request->courseid == $request->receptioncourseid) { $request->enrolstatus = $state; }
        else if ($identity['enrolment'] === 'active') { $request->enrolstatus = 'existing'; }
        else if ($request->enrolrequested) {
            $request->enrolstatus = self::enrol($request->userid, (int)$request->courseid, (int)$request->reviewerid);
        }
        $request->status = 'approved'; $request->activekey = null; $request->timecompleted = time();
        $request->accesshash = $request->confirmhash = null;
        $request->accessexpires = $request->confirmexpires = 0;
        mail::queue($request->id, 'approved');
    }

    /** First and last name; caller holds the global identity lock and native uniqueness remains authoritative. */
    public static function new_username(\stdClass $request): string {
        global $DB, $CFG;
        $first = preg_split('/\s+/u', trim($request->firstname))[0];
        $parts = preg_split('/\s+/u', trim($request->lastname)); $last = end($parts);
        $first = preg_replace('/[^a-z0-9]/', '', \core_text::strtolower(\core_text::specialtoascii($first))) ?: 'aluno';
        $last = preg_replace('/[^a-z0-9]/', '', \core_text::strtolower(\core_text::specialtoascii($last)));
        $base = substr($first, 0, 49) . ($last !== '' ? '.' . substr($last, 0, 49) : '');
        for ($attempt = 0; $attempt < 10000; $attempt++) {
            $suffix = $attempt ? (string)($attempt + 1) : '';
            $username = substr($base, 0, 100 - strlen($suffix)) . $suffix;
            if (!$DB->record_exists('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id])) { return $username; }
        }
        throw new \moodle_exception('error_account', self::COMPONENT);
    }

    /** Global per-person number. Call under identity-approval lock in the same approval transaction. */
    public static function registration(int $userid): \stdClass {
        global $DB;
        $row = $DB->get_record('lumen10admissao_reg', ['userid' => $userid]);
        if ($row) {
            $current = $DB->get_field('user', 'idnumber', ['id' => $userid]);
            if (trim((string)$current) === '') {
                $DB->set_field('user', 'idnumber', $row->number, ['id' => $userid]);
            }
            return $row;
        }
        $current = trim((string)$DB->get_field('user', 'idnumber', ['id' => $userid]));
        if ($current !== '' && preg_match('/^\d{4}-\d{8}$/', $current) &&
                !$DB->record_exists('lumen10admissao_reg', ['number' => $current])) {
            $row = (object)['number' => $current, 'userid' => $userid, 'timecreated' => time(), 'credentialsent' => 0];
            $row->id = $DB->insert_record('lumen10admissao_reg', $row);
            return $row;
        }
        $year = userdate(time(), '%Y', \core_date::get_server_timezone());
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $number = $year . '-' . sprintf('%08d', random_int(0, 99999999));
            if ($DB->record_exists('lumen10admissao_reg', ['number' => $number]) ||
                    $DB->record_exists('user', ['idnumber' => $number])) { continue; }
            $row = (object)['number' => $number, 'userid' => $userid, 'timecreated' => time(), 'credentialsent' => 0];
            $row->id = $DB->insert_record('lumen10admissao_reg', $row);
            if ($current === '') { $DB->set_field('user', 'idnumber', $row->number, ['id' => $userid]); }
            return $row;
        }
        throw new \moodle_exception('error_registration', self::COMPONENT);
    }

    public static function decide(int $id, string $action, string $reason = '', bool $enrol = false): \stdClass {
        global $DB, $CFG, $USER;
        if (!in_array($action, ['approve', 'correction', 'reject', 'enrol'])) { throw new \moodle_exception('error_invalid', self::COMPONENT); }
        $reason = trim(clean_param($reason, PARAM_NOTAGS));
        if (\core_text::strlen($reason) > 1000 || (in_array($action, ['correction', 'reject']) && $reason === '')) {
            throw new \moodle_exception('error_invalid', self::COMPONENT);
        }
        $initial = self::request($id);
        self::assert_scope($initial, 'approve');
        return self::locked(['request:' . $id, 'identity-approval'],
            function() use ($id, $action, $reason, $enrol, $DB, $CFG, $USER) {
                $request = self::request($id);
                self::assert_scope($request, 'approve');
                if (in_array($request->status, ['approved', 'awaitingconfirmation']) && $action === 'approve') { return $request; }
                if (($action === 'enrol' && $request->status !== 'approved') ||
                        ($action !== 'enrol' && $request->status !== 'pending')) {
                    throw new \moodle_exception('error_state', self::COMPONENT);
                }
                if ($action === 'approve') {
                    $request->enrolrequested = $enrol ? 1 : 0;
                    self::eligibility($request, (int)$USER->id);
                    $request->status = 'awaitingconfirmation';
                    $request->accesshash = $request->confirmhash = null;
                    $request->accessexpires = $request->confirmexpires = 0;
                    mail::queue($request->id, 'confirm');
                } else if ($action === 'enrol') {
                    if ($request->courseid == $request->receptioncourseid) { throw new \moodle_exception('error_state', self::COMPONENT); }
                    if (self::course((int)$request->courseid)->category != $request->categoryid) {
                        throw new \moodle_exception('error_category', self::COMPONENT);
                    }
                    $user = $DB->get_record('user', ['id' => $request->userid, 'deleted' => 0, 'suspended' => 0], '*', MUST_EXIST);
                    $request->enrolstatus = self::enrol((int)$user->id, (int)$request->courseid);
                    mail::queue($request->id, 'enrolled');
                } else {
                    $request->status = $action === 'reject' ? 'rejected' : 'correction';
                    $request->confirmhash = $request->accesshash = null;
                    $request->confirmexpires = $request->accessexpires = 0;
                    if ($action === 'reject') { $request->activekey = null; }
                    mail::queue($request->id, $request->status);
                }
                $request->reason = $reason; $request->reviewerid = $USER->id; $request->timemodified = time();
                $DB->update_record(self::TABLE, $request);
                self::audit($request, $action, $reason, $USER->id);
                    return $request;
            });
    }

    public static function withdraw(int $id): void {
        global $DB;
        self::locked(['request:' . $id], function() use ($id, $DB) {
            $request = self::session_request($id);
            if (!in_array($request->status, ['pending', 'correction', 'awaitingconfirmation'])) {
                throw new \moodle_exception('error_state', self::COMPONENT);
            }
            $request->status = 'withdrawn';
            documents::erase($request);
            foreach (['firstname', 'lastname', 'socialname', 'mothername', 'fathername', 'email', 'cpf', 'phone', 'reason'] as $field) { $request->$field = ''; }
            $request->activekey = $request->accesshash = $request->confirmhash = null;
            $request->userid = $request->reviewerid = 0;
            $request->timemodified = time();
            $DB->update_record(self::TABLE, $request);
            $DB->delete_records('lumen10admissao_mail', ['requestid' => $id]);
            $DB->set_field('lumen10admissao_log', 'note', '', ['requestid' => $id]);
            self::audit($request, 'withdrawn');
        });
    }
    public static function delete_request(int $id): void {
        $initial = self::request($id); self::assert_scope($initial, 'delete');
        self::locked(['request:' . $id], function() use ($id) {
            $request = self::request($id); self::assert_scope($request, 'delete');
            self::audit($request, 'deleted', get_string('manualdelete', 'mod_lumen10admissao'));
            \mod_lumen10admissao\privacy\provider::erase_locked($id);
        });
    }
}
