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

final class workflow_test extends \advanced_testcase {
    private function fixture(): array {
        $this->resetAfterTest(); $this->setAdminUser();
        $category = $this->getDataGenerator()->create_category();
        $secretaria = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $target = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $activity = $this->getDataGenerator()->create_module('lumen10admissao', ['course' => $secretaria->id, 'targetcourseid' => $target->id, 'enabled' => 1]);
        return [$activity, $secretaria, $target, $category];
    }
    private function input($activity, string $email = 'synthetic@example.invalid', string $cpf = '529.982.247-25'): \stdClass {
        return (object)['admissaoid' => $activity->id, 'fullname' => 'Ana Teste Sintético', 'socialname' => 'Ana',
            'mothername' => 'Responsável sintético', 'fathername' => '', 'email' => $email, 'cpf' => $cpf, 'consent' => 1,
            'uploads' => ['rg' => ['filename' => 'rg.pdf', 'content' => "%PDF-1.4\n%%EOF\n"],
                          'certidao' => ['filename' => 'certidao.pdf', 'content' => "%PDF-1.4\n%%EOF\n"]]];
    }
    private function pending($activity, string $email = 'synthetic@example.invalid', string $cpf = '529.982.247-25'): \stdClass {
        return workflow::submit($this->input($activity, $email, $cpf));
    }
    public function test_visual_report_is_required_private_and_removed_by_privacy_erasure(): void {
        global $DB;
        [$a] = $this->fixture();
        $input = $this->input($a); $input->visualimpairment = 1;
        try { workflow::submit($input); $this->fail('Missing medical report accepted'); }
        catch (\moodle_exception $e) { $this->assertSame('error_visualreport', $e->errorcode); }
        $this->assertEquals(0, $DB->count_records(workflow::TABLE));
        $input->uploads['laudo'] = ['filename' => 'laudo.pdf', 'content' => "%PDF-1.4\n%%EOF\n"];
        $request = workflow::submit($input);
        $this->assertEquals(1, $request->visualimpairment);
        $files = documents::files($request);
        $this->assertCount(3, $files);
        $this->assertSame('laudo', $files['laudo']->get_source());
        $this->assertSame('laudo', $files['laudo']->get_filearea());
        $contextid = $files['laudo']->get_contextid();
        $medicalfileid = $files['laudo']->get_id();
        $this->complete($request->id);
        $this->assertFalse($DB->get_manager()->table_exists('local_lumen10_arquivados_docs') &&
            $DB->record_exists('local_lumen10_arquivados_docs', [
            'sourcecomponent' => workflow::COMPONENT, 'sourceid' => $medicalfileid]),
            'Medical report was copied into the general identity archive');
        \mod_lumen10admissao\privacy\provider::erase_request($request->id);
        $this->assertEmpty(get_file_storage()->get_area_files($contextid, workflow::COMPONENT, 'laudo', $request->id, 'id', false));
    }
    public function test_no_visual_impairment_does_not_store_an_unsolicited_report(): void {
        [$a] = $this->fixture(); $input = $this->input($a); $input->visualimpairment = 0;
        $input->uploads['laudo'] = ['filename' => 'laudo.pdf', 'content' => "%PDF-1.4\n%%EOF\n"];
        $request = workflow::submit($input);
        $this->assertEquals(0, $request->visualimpairment);
        $this->assertArrayNotHasKey('laudo', documents::files($request));
    }
    private function complete(int $id, bool $enrol = false): \stdClass {
        global $DB;
        $r = workflow::decide($id, 'approve', '', $enrol);
        $token = bin2hex(random_bytes(32));
        $DB->update_record(workflow::TABLE, (object)['id' => $r->id, 'confirmhash' => hash('sha256', $token), 'confirmexpires' => time() + 600]);
        return workflow::consume($token, 'confirm');
    }
    public function test_public_target_is_bound_to_activity_and_duplicate_does_not_overwrite(): void {
        global $DB;
        [$a, $reception, $target] = $this->fixture();
        $input = $this->input($a); $input->courseid = 1; $input->categoryid = 0;
        $r = workflow::submit($input);
        $input->fullname = 'Outro nome'; $again = workflow::submit($input);
        $this->assertEquals($target->id, $r->courseid); $this->assertEquals($reception->id, $r->receptioncourseid);
        $this->assertEquals($r->id, $again->id); $this->assertEquals('Ana', $again->firstname);
        $this->assertEquals(1, $DB->count_records(workflow::TABLE));
    }
    public function test_rate_limit_reports_failure_instead_of_false_receipt(): void {
        global $DB;
        [$a] = $this->fixture();
        for ($i = 0; $i < 3; $i++) { $this->assertNotNull(workflow::submit($this->input($a))); }
        try { workflow::submit($this->input($a)); $this->fail('Rate limit claimed success'); }
        catch (\moodle_exception $error) { $this->assertSame('error_public', $error->errorcode); }
        $this->assertEquals(1, $DB->count_records(workflow::TABLE));
    }
    public function test_invalid_cpf_and_missing_filiation_are_rejected(): void {
        [$a] = $this->fixture(); $input = $this->input($a); $input->mothername = '';
        try { workflow::validate($input); $this->fail('Missing filiation accepted'); }
        catch (\moodle_exception $e) { $this->assertEquals('error_filiation', $e->errorcode); }
        $input->mothername = 'Responsável'; $input->cpf = '111.111.111-11';
        $this->expectException(\moodle_exception::class); workflow::validate($input);
    }
    public function test_closed_activity_cannot_accept_requests(): void {
        global $DB;
        [$a] = $this->fixture(); $DB->set_field('lumen10admissao', 'enabled', 0, ['id' => $a->id]);
        $this->expectException(\moodle_exception::class); workflow::submit($this->input($a));
    }
    public function test_elapsed_deadline_explains_closure_and_rejects_new_requests(): void {
        global $DB;
        [$a] = $this->fixture();
        $deadline = time() - HOURSECS;
        $DB->set_field('lumen10admissao', 'closedate', $deadline, ['id' => $a->id]);
        try {
            workflow::submit($this->input($a));
            $this->fail('An expired period accepted a new request');
        } catch (\moodle_exception $error) {
            $this->assertEquals('error_closed', $error->errorcode);
            $this->assertEquals(get_string('error_closed', workflow::COMPONENT, $error->a), $error->getMessage());
            $this->assertEquals(userdate($deadline, get_string('strftimedatetime', 'langconfig'), \core_date::get_server_timezone()), $error->a);
        }
        $this->assertEquals(0, $DB->count_records(workflow::TABLE));
        $this->assertNotEmpty(workflow::activity($a->id));
    }
    public function test_future_start_explains_when_registration_will_open(): void {
        global $DB;
        [$a] = $this->fixture();
        $start = time() + DAYSECS;
        $DB->set_field('lumen10admissao', 'opendate', $start, ['id' => $a->id]);
        try {
            workflow::activity($a->id, true);
            $this->fail('A future period was opened early');
        } catch (\moodle_exception $error) {
            $this->assertEquals('error_notopen', $error->errorcode);
            $this->assertEquals(userdate($start, get_string('strftimedatetime', 'langconfig'), \core_date::get_server_timezone()), $error->a);
        }
    }
    public function test_category_registration_enrols_secretaria_without_choosing_a_discipline(): void {
        global $DB;
        [$a, $secretaria, $discipline, $category] = $this->fixture();
        $DB->set_field('lumen10admissao', 'targetcourseid', $secretaria->id, ['id' => $a->id]);
        $r = $this->complete($this->pending($a)->id);
        $this->assertEquals($secretaria->id, $r->courseid); $this->assertEquals('enrolled', $r->enrolstatus);
        $this->assertEquals($category->name, workflow::requested_name($r));
        $this->assertEquals('active', workflow::enrolment($r->userid, $secretaria->id));
        $this->assertEquals('none', workflow::enrolment($r->userid, $discipline->id));
        $this->expectException(\moodle_exception::class); workflow::decide($r->id, 'enrol');
    }
    public function test_direct_analysis_then_approval_confirmation_is_single_use(): void {
        global $DB;
        [$a] = $this->fixture(); $r = workflow::submit($this->input($a));
        $users = $DB->count_records('user'); $enrolments = $DB->count_records('user_enrolments');
        $this->assertEquals('pending', $r->status); $this->assertEquals(0, $DB->count_records('lumen10admissao_mail'));
        $r = workflow::decide($r->id, 'approve');
        $this->assertEquals('awaitingconfirmation', $r->status); $this->assertEquals(0, $r->userid);
        $this->assertEquals($users, $DB->count_records('user')); $this->assertEquals($enrolments, $DB->count_records('user_enrolments'));
        $token = bin2hex(random_bytes(32));
        $DB->update_record(workflow::TABLE, (object)['id' => $r->id, 'confirmhash' => hash('sha256', $token), 'confirmexpires' => time() + 600]);
        $this->setGuestUser();
        $this->assertEquals('approved', workflow::consume($token, 'confirm')->status);
        $this->assertEquals($users + 1, $DB->count_records('user'));
        $this->expectException(\moodle_exception::class); workflow::consume($token, 'confirm');
    }
    public function test_approval_creates_one_account_and_reception_enrolment_only(): void {
        global $DB;
        [$a, $secretaria, $target] = $this->fixture(); $r = $this->pending($a); $before = $DB->count_records('user');
        $approved = $this->complete($r->id);
        $again = workflow::decide($r->id, 'approve');
        $this->assertEquals($before + 1, $DB->count_records('user')); $this->assertEquals($approved->userid, $again->userid);
        $this->assertEquals('active', workflow::enrolment($approved->userid, $secretaria->id));
        $this->assertEquals('none', workflow::enrolment($approved->userid, $target->id));
        $this->assertEquals(1, get_user_preferences('auth_forcepasswordchange', 0, $approved->userid));
        $user = $DB->get_record('user', ['id' => $approved->userid]);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{8}$/', $user->idnumber);
        $this->assertEquals('Ana', $user->alternatename);
        $this->assertEquals(1, $DB->count_records('lumen10admissao_reg', ['userid' => $user->id]));
        $this->assertEquals(1, $DB->count_records('lumen10admissao_mail', ['requestid' => $r->id, 'kind' => 'approved']));
        $this->assertEquals('enrolled', workflow::decide($r->id, 'enrol')->enrolstatus);
        $this->assertEquals('existing', workflow::decide($r->id, 'enrol')->enrolstatus);
    }
    public function test_existing_account_and_enrolment_preserve_password_and_idnumber(): void {
        global $DB;
        [$a, $secretaria, $target] = $this->fixture();
        $user = $this->getDataGenerator()->create_user(['email' => 'synthetic@example.invalid', 'idnumber' => 'IMPORTADO-001']);
        $hash = $DB->get_field('user', 'password', ['id' => $user->id]);
        $this->getDataGenerator()->enrol_user($user->id, $target->id, 'student');
        $before = $DB->count_records('user_enrolments'); $r = $this->pending($a); $result = $this->complete($r->id, true);
        $this->assertEquals($user->id, $result->userid); $this->assertEquals(0, $result->newaccount);
        $this->assertEquals('existing', $result->enrolstatus);
        $this->assertEquals($before + 1, $DB->count_records('user_enrolments'));
        $this->assertEquals($hash, $DB->get_field('user', 'password', ['id' => $user->id]));
        $this->assertEquals('IMPORTADO-001', $DB->get_field('user', 'idnumber', ['id' => $user->id]));
    }
    public function test_reused_account_has_one_global_internal_number_across_turmas(): void {
        global $DB;
        [$a] = $this->fixture(); $first = $this->complete($this->pending($a)->id);
        $firstnumber = $DB->get_field('lumen10admissao_reg', 'number', ['userid' => $first->userid]);
        $category = $this->getDataGenerator()->create_category(); $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $other = $this->getDataGenerator()->create_module('lumen10admissao', ['course' => $course->id, 'enabled' => 1]);
        $second = $this->complete($this->pending($other)->id);
        $this->assertEquals($first->userid, $second->userid);
        $this->assertEquals($firstnumber, $DB->get_field('lumen10admissao_reg', 'number', ['userid' => $second->userid]));
        $this->assertEquals(1, $DB->count_records('lumen10admissao_reg'));
    }
    public function test_failed_reception_enrolment_rolls_back_new_account_and_number(): void {
        global $DB;
        [$a, $secretaria] = $this->fixture(); $r = $this->pending($a);
        $before = $DB->count_records('user'); $DB->delete_records('enrol', ['courseid' => $secretaria->id]);
        try { workflow::decide($r->id, 'approve'); $this->fail('Missing enrolment accepted'); }
        catch (\moodle_exception $e) { $this->assertEquals('error_manual', $e->errorcode); }
        $this->assertEquals($before, $DB->count_records('user'));
        $this->assertEquals(0, $DB->count_records('lumen10admissao_reg'));
        $this->assertEquals('pending', workflow::request($r->id)->status);
    }
    public function test_inactive_enrolment_and_suspended_accounts_are_not_reactivated(): void {
        global $DB;
        [$a, $secretaria, $target] = $this->fixture();
        $user = $this->getDataGenerator()->create_user(['email' => 'synthetic@example.invalid']);
        $this->getDataGenerator()->enrol_user($user->id, $target->id, 'student');
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, ['userid' => $user->id]);
        $r = $this->pending($a);
        try { workflow::decide($r->id, 'approve', '', true); $this->fail('Suspended enrolment accepted'); }
        catch (\moodle_exception $e) { $this->assertEquals('error_enrol', $e->errorcode); }
        $this->assertEquals('none', workflow::enrolment($user->id, $secretaria->id));
        $DB->set_field('user', 'suspended', 1, ['id' => $user->id]);
        $this->expectException(\moodle_exception::class); workflow::decide($r->id, 'approve');
    }
    public function test_cpf_conflict_blocks_different_email_account_creation(): void {
        global $DB;
        [$a] = $this->fixture(); $this->complete($this->pending($a)->id);
        $r = $this->pending($a, 'other@example.invalid'); $before = $DB->count_records('user');
        $this->assertTrue(workflow::identity($r)['conflict']);
        try { workflow::decide($r->id, 'approve'); $this->fail('CPF conflict accepted'); }
        catch (\moodle_exception $e) { $this->assertEquals('error_conflict', $e->errorcode); }
        $this->assertEquals($before, $DB->count_records('user'));
    }
    public function test_correction_preserves_fields_and_immutable_verified_email(): void {
        [$a] = $this->fixture(); $r = $this->pending($a);
        workflow::decide($r->id, 'correction', 'Confira a filiação.'); workflow::session_grant($r);
        $input = $this->input($a, 'attacker@example.invalid'); $input->mothername = 'Nome corrigido';
        $result = workflow::update_applicant($r->id, $input);
        $this->assertEquals('pending', $result->status); $this->assertEquals('synthetic@example.invalid', $result->email);
        $this->assertEquals('Nome corrigido', $result->mothername); $this->assertEquals('Ana', $result->socialname);
    }
    public function test_category_analyst_cannot_read_another_turma(): void {
        [$a, $secretaria, $target, $category] = $this->fixture(); $r = $this->pending($a);
        $othercat = $this->getDataGenerator()->create_category(); $user = $this->getDataGenerator()->create_user();
        $role = $this->getDataGenerator()->create_role();
        assign_capability('mod/lumen10admissao:review', CAP_ALLOW, $role, \context_system::instance()->id);
        role_assign($role, $user->id, \context_coursecat::instance($othercat->id)); $this->setUser($user);
        $this->expectException(\required_capability_exception::class); workflow::request($r->id);
    }
    public function test_existing_profile_cpf_disagreement_blocks_linking_verified_email(): void {
        global $DB;
        [$a] = $this->fixture(); $u = $this->getDataGenerator()->create_user(['email' => 'synthetic@example.invalid']);
        $field = $this->getDataGenerator()->create_custom_profile_field(['shortname' => 'cpf', 'name' => 'CPF', 'datatype' => 'text']);
        $DB->insert_record('user_info_data', (object)['userid' => $u->id, 'fieldid' => $field->id, 'data' => '111.444.777-35']);
        $r = $this->pending($a); $this->assertTrue(workflow::identity($r)['conflict']);
        $this->expectException(\moodle_exception::class); workflow::decide($r->id, 'approve');
    }
    public function test_moved_course_blocks_approval(): void {
        global $DB;
        [$a, $secretaria, $target] = $this->fixture(); $r = $this->pending($a);
        $other = $this->getDataGenerator()->create_category(); $DB->set_field('course', 'category', $other->id, ['id' => $target->id]);
        $this->expectException(\moodle_exception::class); workflow::decide($r->id, 'approve');
    }
    public function test_internal_numbers_unique_and_remain_reserved_after_erasure(): void {
        global $DB;
        $this->fixture(); $numbers = [];
        for ($i = 0; $i < 30; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $reg = workflow::locked(['identity-approval'], fn() => workflow::registration($user->id));
            $this->assertNotContains($reg->number, $numbers); $numbers[] = $reg->number;
            $this->assertEquals($reg->number, workflow::locked(['identity-approval'], fn() => workflow::registration($user->id))->number);
        }
        \mod_lumen10admissao\privacy\provider::delete_data_for_all_users_in_context(\context_system::instance());
        $this->assertEquals(30, $DB->count_records('lumen10admissao_reg'));
        $this->assertEquals(0, $DB->count_records_select('lumen10admissao_reg', 'userid IS NOT NULL'));
        foreach ($numbers as $number) { $this->assertTrue($DB->record_exists('lumen10admissao_reg', ['number' => $number])); }
    }
    public function test_post_approval_confirmation_then_welcome_credentials_and_pdf_once(): void {
        global $CFG, $DB;
        [$a, $secretaria, $target, $category] = $this->fixture(); $CFG->noemailever = false;
        $sink = $this->redirectEmails(); $r = workflow::submit($this->input($a, 'synthetic@example.com'));
        $this->assertTrue(mail::dispatch_request($r->id)); $this->assertCount(0, $sink->get_messages());
        $r = workflow::decide($r->id, 'approve');
        $this->assertTrue(mail::dispatch_request($r->id)); $this->assertCount(1, $sink->get_messages());
        $body = $sink->get_messages()[0]->body;
        if (str_contains($body, 'Content-Transfer-Encoding: quoted-printable')) { $body = quoted_printable_decode($body); }
        $this->assertStringContainsString(explode("\n", get_string('mail_confirm_body', workflow::COMPONENT))[0], $body);
        $this->assertSame(0, preg_match('/(?:Senha temporária|Temporary password): /', $body));
        preg_match('/token=([a-f0-9]{64})/', $body, $match);
        $this->setGuestUser(); $r = workflow::consume($match[1], 'confirm');
        $this->assertEquals('approved', $r->status);
        $this->assertTrue(mail::dispatch_request($r->id)); $this->assertCount(2, $sink->get_messages());
        $body = $sink->get_messages()[1]->body;
        if (str_contains($body, 'Content-Transfer-Encoding: quoted-printable')) { $body = quoted_printable_decode($body); }
        $this->assertStringContainsString(explode('{instituicao}', get_string('welcometext_default', workflow::COMPONENT))[0], $body);
        $this->assertSame(1, preg_match('/(?:Senha temporária|Temporary password): /', $body));
        $this->assertStringContainsString($DB->get_field('lumen10admissao_reg', 'number', ['userid' => $r->userid]), $body);
        $this->assertStringContainsString(workflow::reception_url($r)->out(false), $body);
        preg_match('/(?:Senha temporária|Temporary password): ([^\r\n]+)/', $body, $password);
        $user = $DB->get_record('user', ['id' => $r->userid]);
        $this->assertTrue(validate_internal_user_password($user, $password[1]));
        $this->assertEquals(1, get_user_preferences('auth_forcepasswordchange', 0, $user->id));
        $values = declaration::substitutions($r, 'TEST', time());
        $this->assertEquals($category->name, $values['{curso}']);
        $this->assertEquals(get_string('declarationrelationship', workflow::COMPONENT, $category->name), $values['{vinculo}']);
        $file = declaration::file($r); $this->assertNotNull($file); $this->assertStringStartsWith('%PDF-', $file->get_content());
        $this->assertTrue(mail::dispatch_request($r->id)); $this->assertCount(2, $sink->get_messages());
        $this->assertEquals($file->get_id(), declaration::file($r)->get_id());
        $this->assertGreaterThan(0, $DB->get_field('lumen10admissao_reg', 'credentialsent', ['userid' => $user->id]));
        $sink->close();
    }
    public function test_mail_disabled_keeps_retryable_queue_without_claiming_delivery(): void {
        global $CFG, $DB;
        [$a] = $this->fixture(); $CFG->noemailever = true; $r = workflow::decide(workflow::submit($this->input($a))->id, 'approve');
        $this->assertFalse(mail::dispatch_request($r->id));
        $job = $DB->get_record('lumen10admissao_mail', ['requestid' => $r->id]);
        $this->assertEquals(0, $job->timesent); $this->assertEquals('email_disabled', $job->errorcode);
    }
    public function test_registration_requires_both_documents_and_file_spoofing_is_rejected(): void {
        [$a] = $this->fixture(); $input = $this->input($a); unset($input->uploads['certidao']);
        try { workflow::submit($input); $this->fail('Missing document accepted'); }
        catch (\moodle_exception $e) { $this->assertEquals('error_documents', $e->errorcode); }
        $this->expectException(\moodle_exception::class); documents::validate('rg.pdf', '<html><script>alert(1)</script></html>');
    }
    public function test_large_image_and_renamed_executable_are_rejected(): void {
        $this->fixture();
        try { documents::validate('rg.php', "%PDF-1.4\n%%EOF\n"); $this->fail('Executable extension accepted'); }
        catch (\moodle_exception $e) { $this->assertEquals('error_filetype', $e->errorcode); }
        $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aG1cAAAAASUVORK5CYII=');
        $content .= str_repeat(' ', 5 * 1024 * 1024);
        $this->expectException(\moodle_exception::class); documents::validate('rg.png', $content);
    }
    public function test_upload_budget_failure_rolls_back_entire_registration(): void {
        global $DB;
        [$a] = $this->fixture(); set_config('uploadbudgetmb', 1, workflow::COMPONENT);
        $input = $this->input($a);
        $input->uploads['rg']['content'] .= str_repeat(' ', 1024 * 1024);
        try { workflow::submit($input); $this->fail('Storage budget ignored'); }
        catch (\moodle_exception $e) { $this->assertEquals('error_storage', $e->errorcode); }
        $this->assertEquals(0, $DB->count_records(workflow::TABLE));
        $this->assertEquals(0, $DB->count_records('lumen10admissao_mail'));
    }
    public function test_correction_without_new_documents_preserves_private_files(): void {
        [$a] = $this->fixture(); $r = $this->pending($a);
        $before = documents::files($r); workflow::decide($r->id, 'correction', 'Confira os dados.'); workflow::session_grant($r);
        $input = $this->input($a); unset($input->uploads); $input->mothername = 'Correção de filiação';
        $result = workflow::update_applicant($r->id, $input); $after = documents::files($result);
        $this->assertEquals($before['rg']->get_contenthash(), $after['rg']->get_contenthash());
        $this->assertEquals($before['certidao']->get_id(), $after['certidao']->get_id());
    }
    public function test_privacy_erase_removes_own_files_and_preserves_account_and_reserved_number(): void {
        global $DB;
        [$a] = $this->fixture(); $r = $this->complete($this->pending($a)->id);
        $reg = $DB->get_record('lumen10admissao_reg', ['userid' => $r->userid]);
        $context = \context_module::instance(get_coursemodule_from_instance('lumen10admissao', $a->id)->id);
        \mod_lumen10admissao\privacy\provider::erase_request($r->id);
        $this->assertEquals(0, get_file_storage()->get_area_files($context->id, workflow::COMPONENT, 'rg', $r->id, 'id', false) ? 1 : 0);
        $this->assertTrue($DB->record_exists('user', ['id' => $r->userid]));
        $this->assertTrue($DB->record_exists('lumen10admissao_reg', ['number' => $reg->number]));
    }
    public function test_withdraw_erases_preregistration_and_cannot_touch_approved_account(): void {
        [$a] = $this->fixture(); $r = $this->pending($a); workflow::session_grant($r); workflow::withdraw($r->id);
        $gone = workflow::request($r->id); $this->assertEquals('withdrawn', $gone->status);
        $this->assertEquals('', $gone->cpf); $this->assertEquals('', $gone->mothername);
        $other = $this->complete($this->pending($a)->id); workflow::session_grant($other);
        $this->expectException(\moodle_exception::class); workflow::withdraw($other->id);
    }
}
