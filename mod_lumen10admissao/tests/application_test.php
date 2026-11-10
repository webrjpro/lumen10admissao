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
 * Admissions integration.
 * @package mod_lumen10admissao
 * @copyright 2026 Carlos Antonio de Oliveira Piquet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10admissao;
defined('MOODLE_INTERNAL') || die();

#[\PHPUnit\Framework\Attributes\CoversClass(\mod_lumen10admissao\form\application::class)]
final class application_test extends \advanced_testcase {
    private function data(): array {
        return ['fullname' => 'Ana Teste', 'cpf' => '52998224725', 'mothername' => 'Responsável', 'fathername' => '',
            'socialname' => '', 'email' => 'validation@example.invalid', 'emailconfirm' => 'validation@example.invalid', 'consent' => 1,
            'visualimpairment' => '0'];
    }
    private function form(bool $captcha = false, bool $correction = false): array {
        global $CFG;
        $this->resetAfterTest();$this->setAdminUser();
        $CFG->recaptchapublickey = $captcha ? 'isolated-public-key' : '';
        $CFG->recaptchaprivatekey = $captcha ? 'isolated-private-key' : '';
        $form = new \mod_lumen10admissao\form\application(null,
            ['correction' => $correction, 'request' => (object)['email' => 'validation@example.invalid']]);
        $property = new \ReflectionProperty(\moodleform::class, '_form');
        return [$form, $property->getValue($form)];
    }
    public static function captcha_cases(): array {
        return [
            'absent' => [null, true, true, 0],
            'empty' => ['', true, true, 0],
            'whitespace' => ['  ', true, true, 0],
            'malformed' => [['invalid'], true, true, 0],
            'accepted' => ['controlled-token', true, false, 1],
            'refused-string' => ['controlled-token', 'invalid-input-response', true, 1],
            'expired-string' => ['controlled-token', 'timeout-or-duplicate', true, 1],
            'refused-array' => ['controlled-token', ['invalid-input-response'], true, 1],
            'refused-false' => ['controlled-token', false, true, 1],
        ];
    }
    #[\PHPUnit\Framework\Attributes\DataProvider('captcha_cases')]
    public function test_captcha_response_and_renderable_error($token, $verification, bool $error, int $calls): void {
        global $CFG;
        [$form, $quickform] = $this->form(true);
        require_once($CFG->libdir . '/form/recaptcha.php');
        $captcha = $this->getMockBuilder(\MoodleQuickForm_recaptcha::class)->setConstructorArgs(['recaptcha', 'Security'])
            ->onlyMethods(['verify'])->getMock();
        $captcha->expects($this->exactly($calls))->method('verify')->with('controlled-token')
            ->willReturnCallback(function($response) use ($captcha, $verification) {
                if ($verification !== true) { $captcha->updateAttributes(['error_message' => $verification]); }
                return $verification;
            });
        $quickform->removeElement('recaptcha');$quickform->addElement($captcha);
        if ($token !== null) { $quickform->_submitValues['g-recaptcha-response'] = $token; }
        $errors = $form->validation($this->data(), []);
        $this->assertSame($error, isset($errors['recaptcha']));
        $this->assertArrayHasKey('rg', $errors);$this->assertArrayHasKey('certidao', $errors);
        $this->assertIsString($captcha->getAttributes(true));
    }
    public static function oversized_fields(): array {
        return [
            'first-name' => ['fullname', str_repeat('A', 101)],
            'last-name' => ['fullname', 'Ana ' . str_repeat('B', 101)],
            'social-name' => ['socialname', str_repeat('C', 101)],
            'mother' => ['mothername', str_repeat('D', 201)],
            'father' => ['fathername', str_repeat('E', 201)],
            'email' => ['email', str_repeat('a', 60) . '@' . str_repeat('b', 36) . '.com'],
        ];
    }
    #[\PHPUnit\Framework\Attributes\DataProvider('oversized_fields')]
    public function test_oversized_values_are_reported_in_the_form(string $field, string $value): void {
        [$form] = $this->form();$data=$this->data();$data[$field]=$value;
        if ($field === 'email') { $data['emailconfirm']=$value; }
        $errors=$form->validation($data, []);
        $this->assertArrayHasKey($field, $errors, 'Value would reach workflow validation without an inline error');
    }
    public static function failed_uploads(): array {
        return [
            'partial-new' => [UPLOAD_ERR_PARTIAL, false],
            'partial-correction' => [UPLOAD_ERR_PARTIAL, true],
            'too-large-new' => [UPLOAD_ERR_INI_SIZE, false],
            'too-large-correction' => [UPLOAD_ERR_INI_SIZE, true],
        ];
    }
    #[\PHPUnit\Framework\Attributes\DataProvider('failed_uploads')]
    public function test_interrupted_upload_is_reported_instead_of_silently_accepted(int $code, bool $correction): void {
        [$form]=$this->form(false,$correction);$before=$_FILES;
        try {
            $_FILES=['rg'=>['name'=>'rg.pdf','error'=>$code,'tmp_name'=>'']];
            $errors=$form->validation($this->data(),[]);
            $this->assertArrayHasKey('rg',$errors);
        } finally { $_FILES=$before; }
    }
    public function test_unreadable_upload_returns_an_inline_error(): void {
        [$form]=$this->form();$before=$_FILES;
        try {
            $_FILES=['rg'=>['name'=>'rg.pdf','error'=>UPLOAD_ERR_OK,'tmp_name'=>'/not-an-upload/does-not-exist.pdf']];
            $errors=$form->validation($this->data(),[]);$this->assertArrayHasKey('rg',$errors);
        } finally { $_FILES=$before; }
    }
    public function test_visual_choice_and_conditional_report_are_validated_without_voice(): void {
        [$form] = $this->form();
        $data = $this->data();
        $errors = $form->validation($data, []);
        $this->assertArrayNotHasKey('laudo', $errors);
        $data['visualimpairment'] = '';
        $this->assertArrayHasKey('visualimpairment', $form->validation($data, []));
        $data['visualimpairment'] = '1';
        $this->assertArrayHasKey('laudo', $form->validation($data, []));
    }
}
