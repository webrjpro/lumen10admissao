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

final class declaration_test extends \advanced_testcase {
    private function fixture(bool $enrolcourse = false): array {
        global $DB;
        $this->resetAfterTest(); $this->setAdminUser(); update_capabilities(workflow::COMPONENT);
        $category = $this->getDataGenerator()->create_category();
        $secretaria = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $a = $this->getDataGenerator()->create_module('lumen10admissao', ['course' => $secretaria->id, 'targetcourseid' => $course->id, 'enabled' => 1]);
        $r = workflow::submit((object)['admissaoid' => $a->id, 'fullname' => 'Aluno Sintético Teste', 'email' => 'declaration@example.invalid',
            'cpf' => '52998224725', 'mothername' => 'Responsável sintético', 'consent' => 1,
            'uploads' => ['rg' => ['filename' => 'rg.pdf', 'content' => "%PDF-1.4\n%%EOF\n"],
                'certidao' => ['filename' => 'certidao.pdf', 'content' => "%PDF-1.4\n%%EOF\n"]]]);
        $r = workflow::decide($r->id, 'approve', '', $enrolcourse);
        $token = bin2hex(random_bytes(32));
        $DB->update_record(workflow::TABLE, (object)['id' => $r->id, 'confirmhash' => hash('sha256', $token), 'confirmexpires' => time() + 600]);
        $r = workflow::consume($token, 'confirm');
        return [$r, $secretaria, $course, $a];
    }
    private function issue($r): \stored_file {
        return workflow::locked(['request:' . $r->id], fn() => declaration::issue_locked($r));
    }
    private function signing_bundle(): string {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $csr = openssl_csr_new(['commonName' => 'TEST ONLY - Synthetic Institution', 'organizationName' => 'Synthetic Test'], $key, ['digest_alg' => 'sha256']);
        $cert = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);
        openssl_pkcs12_export($cert, $out, $key, 'synthetic-test-only');
        get_file_storage()->create_file_from_string(['contextid' => \context_system::instance()->id, 'component' => workflow::COMPONENT,
            'filearea' => 'declp12', 'itemid' => 0, 'filepath' => '/', 'filename' => 'test.p12'], $out);
        set_config('declarationcertpassword', \core\encryption::encrypt('synthetic-test-only'), workflow::COMPONENT);
        set_config('declarationdigital', 1, workflow::COMPONENT);
        return $out;
    }
    public function test_issued_pdf_is_immutable_and_qr_checks_live_global_and_enrolment_suspension(): void {
        global $DB;
        [$r, $secretaria] = $this->fixture(); $pdf = $this->issue($r);
        $this->assertStringStartsWith('%PDF-', $pdf->get_content());
        $row = $DB->get_record('lumen10admissao_decl', ['requestid' => $r->id], '*', MUST_EXIST);
        $this->assertEquals(hash('sha256', $pdf->get_content()), $row->sha256);
        $this->assertEquals('active', declaration::verification($row->code)['status']);
        $DB->set_field('user', 'suspended', 1, ['id' => $r->userid]);
        $this->assertEquals('suspended', declaration::verification($row->code)['status']);
        $DB->set_field('user', 'suspended', 0, ['id' => $r->userid]);
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, ['userid' => $r->userid]);
        $this->assertEquals('suspended', declaration::verification($row->code)['status']);
        $DB->set_field('user_enrolments', 'status', ENROL_USER_ACTIVE, ['userid' => $r->userid]);
        $this->assertEquals('active', declaration::verification($row->code)['status']);
        $DB->set_field('user_enrolments', 'timestart', time() - 100, ['userid' => $r->userid]);
        $DB->set_field('user_enrolments', 'timeend', time() - 10, ['userid' => $r->userid]);
        $this->assertEquals('suspended', declaration::verification($row->code)['status']);
        $DB->set_field('user_enrolments', 'timeend', 0, ['userid' => $r->userid]);
        $DB->delete_records('user_enrolments', ['userid' => $r->userid]);
        $this->assertEquals('closed', declaration::verification($row->code)['status']);
        set_config('declarationtext', 'Texto novo para novas emissões.', workflow::COMPONENT);
        $this->assertEquals($pdf->get_contenthash(), $this->issue($r)->get_contenthash());
        $this->assertEquals(1, $DB->count_records('lumen10admissao_decl', ['requestid' => $r->id]));
    }
    public function test_qr_also_checks_requested_discipline_when_it_was_enrolled(): void {
        global $DB;
        [$r, $secretaria, $course] = $this->fixture(true); $this->issue($r);
        $row = $DB->get_record('lumen10admissao_decl', ['requestid' => $r->id]);
        $this->assertEquals('active', declaration::verification($row->code)['status']);
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, ['userid' => $r->userid, 'enrolid' => $instance->id]);
        $this->assertEquals('active', workflow::enrolment($r->userid, $secretaria->id));
        $this->assertEquals('suspended', declaration::verification($row->code)['status']);
    }
    public function test_delete_is_scoped_and_preserves_account_number_and_native_enrolments(): void {
        global $DB;
        [$r] = $this->fixture(); $this->issue($r);
        $code = $DB->get_field('lumen10admissao_decl', 'code', ['requestid' => $r->id]);
        $number = $DB->get_field('lumen10admissao_reg', 'number', ['userid' => $r->userid]);
        $enrolments = $DB->get_records('user_enrolments', ['userid' => $r->userid]);
        $this->setUser($this->getDataGenerator()->create_user());
        try { workflow::delete_request($r->id); $this->fail('Unprivileged deletion accepted'); }
        catch (\core\exception\required_capability_exception $e) { $this->assertTrue($DB->record_exists(workflow::TABLE, ['id' => $r->id])); }
        $this->setAdminUser(); workflow::delete_request($r->id);
        $this->assertFalse($DB->record_exists(workflow::TABLE, ['id' => $r->id]));
        $this->assertNull(declaration::verification($code));
        $this->assertNull(declaration::file($r));
        $this->assertCount(0, documents::files($r));
        $this->assertTrue($DB->record_exists('user', ['id' => $r->userid, 'deleted' => 0]));
        $this->assertEquals($number, $DB->get_field('lumen10admissao_reg', 'number', ['userid' => $r->userid]));
        $this->assertEquals($enrolments, $DB->get_records('user_enrolments', ['userid' => $r->userid]));
    }
    public function test_certificate_configuration_fails_closed_and_password_is_never_exposed_in_settings(): void {
        [$r] = $this->fixture(); set_config('declarationdigital', 1, workflow::COMPONENT);
        try { $this->issue($r); $this->fail('Digital signature accepted without certificate'); }
        catch (\moodle_exception $e) { $this->assertEquals('error_declarationconfig', $e->errorcode); }
        set_config('declarationdigital', 0, workflow::COMPONENT); $this->signing_bundle();
        $setting = new admin_secret(workflow::COMPONENT . '/declarationcertpassword', 'Password', '', '');
        $display = $setting->get_setting();
        $this->assertNotEquals('synthetic-test-only', $display);
        $this->assertNotEquals(get_config(workflow::COMPONENT, 'declarationcertpassword'), $display);
        $this->assertEquals('', $setting->write_setting($display));
        $this->assertEquals('synthetic-test-only', \core\encryption::decrypt(get_config(workflow::COMPONENT, 'declarationcertpassword')));
        $pdf = $this->issue($r); $content = $pdf->get_content();
        $this->assertStringContainsString('/ByteRange', $content); $this->assertStringContainsString('/adbe.pkcs7.detached', $content);
        $this->assertStringNotContainsString('synthetic-test-only', $content);
        $this->assertNotNull(declaration::signing());
        set_config('declarationsignatory', str_repeat('A', 121), workflow::COMPONENT);
        $this->expectException(\moodle_exception::class); declaration::check_configuration();
    }
    public function test_wrong_certificate_password_does_not_fall_back_to_unsigned_pdf(): void {
        [$r] = $this->fixture(); $this->signing_bundle();
        set_config('declarationcertpassword', \core\encryption::encrypt('wrong-password'), workflow::COMPONENT);
        $this->expectException(\moodle_exception::class); $this->issue($r);
    }
    public function test_confirmation_rechecks_reviewer_authorization_before_creating_account(): void {
        global $DB;
        [$done, $secretaria] = $this->fixture();
        $r = workflow::submit((object)['admissaoid' => $done->admissaoid, 'fullname' => 'Candidato sintético',
            'email' => 'authorization@example.invalid', 'cpf' => '11144477735', 'mothername' => 'Responsável', 'consent' => 1,
            'uploads' => ['rg' => ['filename' => 'rg.pdf', 'content' => "%PDF-1.4\n%%EOF\n"],
                'certidao' => ['filename' => 'certidao.pdf', 'content' => "%PDF-1.4\n%%EOF\n"]]]);
        $reviewer = $this->getDataGenerator()->create_user();
        $role = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $context = \context_coursecat::instance($secretaria->category);
        role_assign($role->id, $reviewer->id, $context->id);
        $this->setUser($reviewer); workflow::decide($r->id, 'approve');
        $token = bin2hex(random_bytes(32));
        $DB->update_record(workflow::TABLE, (object)['id' => $r->id, 'confirmhash' => hash('sha256', $token), 'confirmexpires' => time() + 600]);
        role_unassign($role->id, $reviewer->id, $context->id); $this->setGuestUser();
        try { workflow::consume($token, 'confirm'); $this->fail('Revoked authorization accepted'); }
        catch (\moodle_exception $e) { $this->assertEquals('error_authorizer', $e->errorcode); }
        $this->setAdminUser();
        $this->assertEquals('awaitingconfirmation', workflow::request($r->id)->status);
        $this->assertFalse($DB->record_exists('user', ['email' => 'authorization@example.invalid']));
    }
    public function test_disabled_declaration_sends_no_pdf_and_erasure_invalidates_public_verification(): void {
        global $DB;
        [$r] = $this->fixture(); set_config('declarationenabled', 0, workflow::COMPONENT);
        $this->assertNull(workflow::locked(['request:' . $r->id], fn() => declaration::issue_locked($r)));
        set_config('declarationenabled', 1, workflow::COMPONENT); $this->issue($r);
        $code = $DB->get_field('lumen10admissao_decl', 'code', ['requestid' => $r->id]);
        $this->assertNull(declaration::verification(str_repeat('A', 31)));
        $this->assertNull(declaration::verification(str_repeat('A', 32)));
        \mod_lumen10admissao\privacy\provider::erase_request($r->id);
        $this->assertNull(declaration::verification($code)); $this->assertNull(declaration::file($r));
    }
    public function test_pending_cannot_edit_but_correction_can_resubmit_once_and_needs_new_approval(): void {
        global $DB;
        [$r] = $this->fixture();
        // A separate pending request, not the already completed matrícula.
        $activity = $DB->get_record('lumen10admissao', ['id' => $r->admissaoid]);
        $input = (object)['admissaoid' => $activity->id, 'fullname' => 'Outro Sintético', 'email' => 'pending@example.invalid',
            'cpf' => '11144477735', 'mothername' => 'Responsável', 'consent' => 1,
            'uploads' => ['rg' => ['filename' => 'rg.pdf', 'content' => "%PDF-1.4\n%%EOF\n"],
                'certidao' => ['filename' => 'certidao.pdf', 'content' => "%PDF-1.4\n%%EOF\n"]]];
        $pending = workflow::submit($input); workflow::session_grant($pending);
        try { workflow::update_applicant($pending->id, $input); $this->fail('Pending fields editable'); }
        catch (\moodle_exception $e) { $this->assertEquals('error_state', $e->errorcode); }
        workflow::decide($pending->id, 'correction', 'Confira a filiação.');
        unset($input->uploads); $input->mothername = 'Filiação corrigida';
        $pending = workflow::update_applicant($pending->id, $input);
        $this->assertEquals('pending', $pending->status); $this->assertEquals('', $pending->reason);
        $this->assertCount(2, documents::files($pending));
        $pending = workflow::decide($pending->id, 'approve');
        $this->assertEquals('awaitingconfirmation', $pending->status); $this->assertEquals(0, $pending->userid);
    }
}
