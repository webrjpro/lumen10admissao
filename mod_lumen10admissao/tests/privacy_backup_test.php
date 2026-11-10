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
use mod_lumen10admissao\privacy\provider;
use core_privacy\local\request\{approved_contextlist, writer};

final class privacy_backup_test extends \core_privacy\tests\provider_testcase {
    private function fixture(): array {
        global $DB;
        $this->resetAfterTest(); $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $a = $this->getDataGenerator()->create_module('lumen10admissao', ['course' => $course->id, 'enabled' => 1]);
        $r = workflow::submit((object)['admissaoid' => $a->id, 'fullname' => 'Cadastro Sintético',
            'email' => 'privacy@example.invalid', 'cpf' => '52998224725', 'mothername' => 'Responsável', 'consent' => 1,
            'uploads' => ['rg' => ['filename' => 'rg.pdf', 'content' => "%PDF-1.4\n%%EOF\n"],
                'certidao' => ['filename' => 'certidao.pdf', 'content' => "%PDF-1.4\n%%EOF\n"]]]);
        workflow::decide($r->id, 'approve');
        $token = bin2hex(random_bytes(32));
        $DB->update_record(workflow::TABLE, (object)['id' => $r->id, 'confirmhash' => hash('sha256', $token), 'confirmexpires' => time() + 600]);
        $r = workflow::consume($token, 'confirm');
        $user = $DB->get_record('user', ['id' => $r->userid], '*', MUST_EXIST);
        return [$a, $course, $r, $user, \context_module::instance($a->cmid)];
    }
    public function test_owner_exports_only_approved_context_with_identity_documents(): void {
        [$a, $course, $r, $user, $context] = $this->fixture();
        $ids = array_map('intval', provider::get_contexts_for_userid($user->id)->get_contextids());
        $this->assertContains($context->id, $ids); $this->assertContains(\context_system::instance()->id, $ids);
        provider::export_user_data(new approved_contextlist($user, workflow::COMPONENT, [$context->id]));
        $w = writer::with_context($context); $path = [get_string('privacyapplications', workflow::COMPONENT), (string)$r->id];
        $this->assertEquals($r->cpf, $w->get_data($path)->cpf);
        $this->assertFalse(isset($w->get_data($path)->confirmhash));
        $this->assertCount(1, $w->get_files([...$path, 'rg']));
        $this->assertCount(1, $w->get_files([...$path, 'certidao']));
        $this->assertFalse(writer::with_context(\context_system::instance())->has_any_data());
    }
    public function test_analyst_export_does_not_include_applicant_identity_or_document(): void {
        [$a, $course, $r, $user, $context] = $this->fixture(); $staff = get_admin();
        provider::export_user_data(new approved_contextlist($staff, workflow::COMPONENT, [$context->id]));
        $w = writer::with_context($context);
        $this->assertEmpty((array)$w->get_data([get_string('privacyapplications', workflow::COMPONENT), (string)$r->id]));
        $actions = $w->get_data([get_string('privacyactions', workflow::COMPONENT)])->actions;
        $this->assertNotEmpty($actions);
        foreach ($actions as $action) { $this->assertFalse(isset($action->note)); $this->assertFalse(isset($action->cpf)); }
        $this->assertCount(0, $w->get_files([get_string('privacyapplications', workflow::COMPONENT), (string)$r->id, 'rg']));
    }
    public function test_erasing_analyst_preserves_candidate_and_documents(): void {
        global $DB;
        [$a, $course, $r, $user, $context] = $this->fixture(); $before = documents::files($r);
        provider::delete_data_for_user(new approved_contextlist(get_admin(), workflow::COMPONENT, [$context->id]));
        $this->assertEquals(0, $DB->get_field(workflow::TABLE, 'reviewerid', ['id' => $r->id]));
        $this->assertEquals($r->cpf, $DB->get_field(workflow::TABLE, 'cpf', ['id' => $r->id]));
        $this->assertEquals($before['rg']->get_id(), documents::files($r)['rg']->get_id());
        $this->assertTrue($DB->record_exists('user', ['id' => $user->id]));
    }
    public function test_retention_never_automatically_erases_approved_admission(): void {
        global $DB, $CFG;
        [$a, $course, $r] = $this->fixture(); $CFG->noemailever = true;
        set_config('retentiondays', 1, workflow::COMPONENT);
        $DB->set_field(workflow::TABLE, 'timemodified', time() - 10 * DAYSECS, ['id' => $r->id]);
        (new \mod_lumen10admissao\task\dispatch())->execute();
        $this->assertTrue($DB->record_exists(workflow::TABLE, ['id' => $r->id]));
        $this->assertCount(2, documents::files($r));
    }
    public function test_native_duplicate_copies_configuration_with_closed_registration_and_no_candidates(): void {
        global $DB, $CFG;
        [$a, $course, $r] = $this->fixture(); require_once($CFG->dirroot . '/course/lib.php');
        $users = $DB->count_records('user'); $numbercount = $DB->count_records('lumen10admissao_reg');
        $cm = get_fast_modinfo($course)->get_cm($a->cmid); $copy = method_exists(\core_courseformat\local\cmactions::class, 'duplicate')
            ? (new \core_courseformat\local\cmactions($course))->duplicate($cm->id)
            : duplicate_module($course, $cm);
        $this->assertNotNull($copy);
        $new = $DB->get_record('lumen10admissao', ['id' => $copy->instance], '*', MUST_EXIST);
        $this->assertEquals(0, $new->enabled); $this->assertEquals(0, $new->targetcourseid);
        $this->assertEquals($course->id, $new->receptioncourseid);
        $this->assertEquals(0, $DB->count_records(workflow::TABLE, ['admissaoid' => $new->id]));
        $this->assertEquals($users, $DB->count_records('user')); $this->assertEquals($numbercount, $DB->count_records('lumen10admissao_reg'));
        $this->assertEquals(1, $DB->get_field('lumen10admissao', 'enabled', ['id' => $a->id]));
        $this->assertCount(2, documents::files($r));
    }
}
