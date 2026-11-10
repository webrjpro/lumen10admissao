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

final class credentials_test extends \advanced_testcase {
    private function pending(): \stdClass {
        global $CFG;
        $this->resetAfterTest(); $this->setAdminUser();
        $CFG->passwordpolicy = 1; $CFG->minpasswordlength = 8;
        $CFG->minpassworddigits = $CFG->minpasswordupper = $CFG->minpasswordlower = $CFG->minpasswordnonalphanum = 1;
        $CFG->maxconsecutiveidentchars = 0;
        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $activity = $this->getDataGenerator()->create_module('lumen10admissao', ['course' => $course->id, 'targetcourseid' => $course->id, 'enabled' => 1]);
        return workflow::submit((object)['admissaoid' => $activity->id, 'fullname' => 'cARLOS Antonio de Oliveira PIQUET',
            'email' => 'credentials@example.com', 'cpf' => '52998224725', 'mothername' => 'Responsável sintético', 'consent' => 1,
            'uploads' => ['rg' => ['filename' => 'rg.pdf', 'content' => "%PDF-1.4\n%%EOF\n"],
                'certidao' => ['filename' => 'certidao.pdf', 'content' => "%PDF-1.4\n%%EOF\n"]]]);
    }
    private function confirm(\stdClass $r): \stdClass {
        global $DB;
        workflow::decide($r->id, 'approve'); $token = bin2hex(random_bytes(32));
        $DB->update_record(workflow::TABLE, (object)['id' => $r->id, 'confirmhash' => hash('sha256', $token), 'confirmexpires' => time() + 600]);
        $this->setGuestUser(); return workflow::consume($token, 'confirm');
    }
    public function test_new_account_sends_random_password_with_forced_change(): void {
        global $DB, $CFG;
        $r = $this->confirm($this->pending()); $user = $DB->get_record('user', ['id' => $r->userid], '*', MUST_EXIST);
        $this->assertEquals('carlos.piquet', $user->username);
        $CFG->noemailever = false; $sink = $this->redirectEmails();
        $this->assertTrue(mail::dispatch_request($r->id));
        $messages = $sink->get_messages(); $sink->close();
        $this->assertCount(1, $messages);
        $body = end($messages)->body;
        if (str_contains($body, 'Content-Transfer-Encoding: quoted-printable')) { $body = quoted_printable_decode($body); }
        $this->assertSame(1, preg_match('/(?:Senha temporária|Temporary password): ([^\r\n]+)/', $body, $matches));
        $expected = trim($matches[1]);
        $this->assertSame(20, strlen($expected));
        $this->assertNotEquals(userdate($user->timecreated, '%Y', 'America/Sao_Paulo') . '@Carlos', $expected);
        $error = null;
        $this->assertTrue(check_password_policy($expected, $error, $user));
        $this->assertNotEquals($expected, mail::temporary_password($user));
        $user = $DB->get_record('user', ['id' => $r->userid]);
        $this->assertTrue(validate_internal_user_password($user, $expected));
        $this->assertEquals(1, get_user_preferences('auth_forcepasswordchange', 0, $user->id));
        $values = declaration::substitutions($r, '2026-12345678', time());
        $this->assertEquals('529.982.247-25', $values['{cpf}']);
        $this->assertStringNotContainsString('com acesso à Secretaria', $values['{vinculo}']);
        $this->assertStringContainsString('CPF nº {cpf}', declaration::DEFAULTTEXT);
    }
    public function test_username_collision_does_not_reuse_or_change_another_persons_account(): void {
        global $DB;
        $r = $this->pending();
        $other = $this->getDataGenerator()->create_user(['username' => 'carlos.piquet', 'email' => 'other@example.invalid']);
        $hash = $DB->get_field('user', 'password', ['id' => $other->id]);
        $r = $this->confirm($r);
        $this->assertNotEquals($other->id, $r->userid);
        $this->assertEquals('carlos.piquet2', $DB->get_field('user', 'username', ['id' => $r->userid]));
        $this->assertEquals($hash, $DB->get_field('user', 'password', ['id' => $other->id]));
        $this->assertEquals('jose.silva', workflow::new_username((object)['firstname' => 'José Maria', 'lastname' => 'da Silva']));
    }
    public function test_incompatible_password_policy_blocks_before_creating_account_or_enrolment(): void {
        global $DB, $CFG;
        $r = $this->pending(); $before = $DB->count_records('user'); $CFG->minpasswordlength = 30;
        try { workflow::decide($r->id, 'approve'); $this->fail('Password policy bypassed'); }
        catch (\moodle_exception $e) { $this->assertEquals('error_credentials', $e->errorcode); }
        $this->assertEquals('pending', workflow::request($r->id)->status);
        $this->assertEquals($before, $DB->count_records('user'));
    }

    public function test_same_person_receives_independent_strong_temporary_passwords(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->passwordpolicy = 1; $CFG->minpasswordlength = 8;
        $CFG->minpassworddigits = $CFG->minpasswordupper = $CFG->minpasswordlower = $CFG->minpasswordnonalphanum = 1;
        $CFG->maxconsecutiveidentchars = 0;
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Ana', 'lastname' => 'Teste']);
        $seen = [];
        for ($i = 0; $i < 30; $i++) {
            $password = mail::temporary_password($user); $error = null;
            $this->assertSame(20, strlen($password));
            $this->assertTrue(check_password_policy($password, $error, $user));
            $this->assertArrayNotHasKey($password, $seen);
            $seen[$password] = true;
        }
    }
}
