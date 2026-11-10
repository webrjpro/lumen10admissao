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
namespace mod_lumen10admissao\form;
defined('MOODLE_INTERNAL') || die();
require_once($GLOBALS['CFG']->libdir . '/formslib.php');

final class application extends \moodleform {
    /** Keep native upload checks while allowing scans with the same name in different document fields. */
    public function _validate_files(&$files) {
        foreach (\mod_lumen10admissao\documents::KINDS as $kind) {
            if (!isset($_FILES[$kind]) || !is_array($_FILES[$kind]) || !is_string($_FILES[$kind]['name'] ?? null)) {
                continue;
            }
            $filename = clean_param($_FILES[$kind]['name'], PARAM_FILE);
            if ($filename !== '') {
                $_FILES[$kind]['name'] = $kind . '-' . $filename;
            }
        }
        return parent::_validate_files($files);
    }

    public function definition() {
        $m = $this->_form;
        $d = $this->_customdata;
        $m->addElement('header', 'registration', get_string('registrationdata', 'mod_lumen10admissao'));
        foreach (['fullname' => [get_string('fullname', 'mod_lumen10admissao'), 200], 'cpf' => [get_string('cpf', 'mod_lumen10admissao'), 14],
                'mothername' => [get_string('mothername', 'mod_lumen10admissao'), 200], 'fathername' => [get_string('fathername', 'mod_lumen10admissao'), 200],
                'socialname' => [get_string('socialname', 'mod_lumen10admissao'), 100]] as $name => [$label, $max]) {
            $m->addElement('text', $name, $label, ['maxlength' => $max, 'class' => $name === 'cpf' ? 'l10adm-cpf' : '']);
            $m->setType($name, PARAM_NOTAGS);
            if (in_array($name, ['fullname', 'cpf'])) { $m->addRule($name, null, 'required', null, 'client'); }
        }
        $m->addElement('static', 'parenthint', '', get_string('parenthint', 'mod_lumen10admissao'));
        $m->addElement('header', 'identitydocs', get_string('identitydocs', 'mod_lumen10admissao'));
        $m->addElement('static', 'dochint', '', get_string('documenthint', 'mod_lumen10admissao'));
        $m->addElement('select', 'certtype', get_string('certtype', 'mod_lumen10admissao'), ['nascimento' => get_string('birth', 'mod_lumen10admissao'), 'casamento' => get_string('marriage', 'mod_lumen10admissao')]);
        $m->setType('certtype', PARAM_ALPHA);
        \MoodleQuickForm::registerElementType('lumen10admissao_upload', $GLOBALS['CFG']->dirroot . '/mod/lumen10admissao/upload.php', 'MoodleQuickForm_lumen10admissao_upload');
        foreach (['rg' => get_string('identitydocument', 'mod_lumen10admissao'), 'certidao' => get_string('birthmarriage', 'mod_lumen10admissao')] as $name => $label) {
            $m->addElement('lumen10admissao_upload', $name, $label, ['accept' => '.pdf,.jpg,.jpeg,.png,.webp']);
            $m->setType($name, PARAM_RAW);
            if (empty($d['correction'])) { $m->addRule($name, null, 'required'); }
        }
        $m->setType('MAX_FILE_SIZE', PARAM_INT);
        $m->addElement('header', 'accessibility', get_string('visualheading', 'mod_lumen10admissao'));
        $m->addElement('select', 'visualimpairment', get_string('visualquestion', 'mod_lumen10admissao'), [
            '' => get_string('selectoption', 'mod_lumen10admissao'), '0' => get_string('visualno', 'mod_lumen10admissao'), '1' => get_string('visualyes', 'mod_lumen10admissao')]);
        $m->setType('visualimpairment', PARAM_RAW_TRIMMED);
        $m->addElement('static', 'visualhint', '', get_string('visualhint', 'mod_lumen10admissao'));
        $m->addElement('lumen10admissao_upload', 'laudo', get_string('visualreport', 'mod_lumen10admissao'),
            ['accept' => '.pdf,.jpg,.jpeg,.png,.webp']);
        $m->setType('laudo', PARAM_RAW);
        $m->hideIf('laudo', 'visualimpairment', 'neq', '1');
        if (!empty($d['correction'])) { $m->addElement('static', 'preserve', '', get_string('preservefiles', 'mod_lumen10admissao')); }
        $m->addElement('header', 'contact', get_string('contact', 'mod_lumen10admissao'));
        if (!empty($d['correction'])) {
            $m->addElement('static', 'emaildisplay', get_string('confirmedemail', 'mod_lumen10admissao'), s($d['request']->email));
        } else {
            foreach (['email' => get_string('email', 'mod_lumen10admissao'), 'emailconfirm' => get_string('emailconfirm', 'mod_lumen10admissao')] as $name => $label) {
                $m->addElement('text', $name, $label, ['maxlength' => 100, 'autocomplete' => 'email']);
                $m->setType($name, PARAM_RAW_TRIMMED); $m->addRule($name, null, 'required', null, 'client');
            }
            $m->addElement('text', 'website', get_string('honeypot', 'mod_lumen10admissao'), ['class' => 'l10adm-honeypot', 'tabindex' => -1, 'autocomplete' => 'off']);
            $m->setType('website', PARAM_RAW_TRIMMED);
            if (!empty($GLOBALS['CFG']->recaptchapublickey) && !empty($GLOBALS['CFG']->recaptchaprivatekey)) {
                $m->addElement('recaptcha', 'recaptcha', get_string('recaptcha', 'auth'));
            }
            $notice = (string)\mod_lumen10admissao\workflow::config('privacynotice', get_string('privacynoticefallback', 'mod_lumen10admissao'));
            $m->addElement('static', 'privacy', get_string('datause', 'mod_lumen10admissao'), s($notice));
            $m->addElement('advcheckbox', 'consent', get_string('consent', 'mod_lumen10admissao'));
            $m->addRule('consent', null, 'required', null, 'client');
        }
        foreach (['registration', 'identitydocs', 'accessibility', 'contact'] as $section) { $m->setExpanded($section, true); }
        $this->add_action_buttons(false, !empty($d['correction']) ? get_string('resubmit', 'mod_lumen10admissao') : get_string('submit', 'mod_lumen10admissao'));
    }
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (trim($data['fullname'] ?? '') === '') { $errors['fullname'] = get_string('required'); }
        $parts = preg_split('/\s+/u', trim(clean_param($data['fullname'] ?? '', PARAM_NOTAGS)), 2);
        foreach ([$parts[0] ?? '', $parts[1] ?? '-'] as $part) {
            if (\core_text::strlen($part) > 100) {
                $errors['fullname'] = get_string('error_namelength', 'mod_lumen10admissao');
            }
        }
        foreach (['socialname' => 100, 'mothername' => 200, 'fathername' => 200] as $field => $max) {
            $value = trim(clean_param($data[$field] ?? '', PARAM_NOTAGS));
            if (\core_text::strlen($value) > $max || preg_match('/[\r\n]/', $value)) {
                $errors[$field] = get_string('maximumchars', '', $max);
            }
        }
        try { \mod_lumen10admissao\workflow::cpf($data['cpf'] ?? ''); }
        catch (\moodle_exception $e) { $errors['cpf'] = get_string('error_cpf', 'mod_lumen10admissao'); }
        if (trim($data['mothername'] ?? '') === '' && trim($data['fathername'] ?? '') === '') {
            $errors['mothername'] = get_string('error_filiation', 'mod_lumen10admissao');
        }
        if (!in_array($data['visualimpairment'] ?? null, ['0', '1', 0, 1], true)) {
            $errors['visualimpairment'] = get_string('error_visualchoice', 'mod_lumen10admissao');
        }
        $needsreport = (string)($data['visualimpairment'] ?? '') === '1';
        $existingreport = !empty($this->_customdata['correction']) && !empty($this->_customdata['request']->id) &&
            isset(\mod_lumen10admissao\documents::files($this->_customdata['request'])['laudo']);
        foreach (['rg', 'certidao', 'laudo'] as $kind) {
            if ($kind === 'laudo' && !$needsreport) { continue; }
            $upload = $_FILES[$kind] ?? [];
            $uploaderror = is_array($upload) ? ($upload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
            if ($uploaderror === UPLOAD_ERR_NO_FILE && ($kind === 'laudo' ? $existingreport : !empty($this->_customdata['correction']))) { continue; }
            if ($uploaderror !== UPLOAD_ERR_OK || !is_string($upload['name'] ?? null) ||
                    !is_string($upload['tmp_name'] ?? null) || $upload['tmp_name'] === '') {
                $errors[$kind] = get_string($kind === 'laudo' ? 'error_visualreport' : 'error_documents', 'mod_lumen10admissao');
                continue;
            }
            $content = @file_get_contents($upload['tmp_name']);
            if ($content === false) {
                $errors[$kind] = get_string($kind === 'laudo' ? 'error_visualreport' : 'error_documents', 'mod_lumen10admissao');
                continue;
            }
            try { \mod_lumen10admissao\documents::validate($upload['name'], $content); }
            catch (\moodle_exception $e) { $errors[$kind] = $e->getMessage(); }
        }
        if (empty($this->_customdata['correction'])) {
            if (!validate_email(trim($data['email'] ?? '')) || strlen(trim($data['email'] ?? '')) > 100) {
                $errors['email'] = get_string('invalidemail');
            }
            if (\core_text::strtolower(trim($data['email'] ?? '')) !== \core_text::strtolower(trim($data['emailconfirm'] ?? ''))) {
                $errors['emailconfirm'] = get_string('error_emailmatch', 'mod_lumen10admissao');
            }
            if (empty($data['consent'])) { $errors['consent'] = get_string('error_consent', 'mod_lumen10admissao'); }
            if ($this->_form->elementExists('recaptcha')) {
                $element = $this->_form->getElement('recaptcha');
                $response = $this->_form->_submitValues['g-recaptcha-response'] ?? '';
                if (!is_string($response) || trim($response) === '') {
                    $errors['recaptcha'] = get_string('missingrecaptchachallengefield');
                } else if ($element->verify($response) !== true) {
                    // Error codes can be arrays; keep the element's HTML attributes printable.
                    $errors['recaptcha'] = get_string('incorrectpleasetryagain', 'auth');
                    $element->updateAttributes(['error_message' => $errors['recaptcha']]);
                }
            }
        }
        return $errors;
    }
}
