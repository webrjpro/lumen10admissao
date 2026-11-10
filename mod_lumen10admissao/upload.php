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
require_once($GLOBALS['CFG']->libdir . '/form/text.php');

/** Multipart input for applicants without a Moodle account. Avoids the legacy QuickForm_file PHP 8 signature. */
class MoodleQuickForm_lumen10admissao_upload extends MoodleQuickForm_text {
    public function __construct($name = null, $label = null, $attributes = null) {
        parent::__construct($name, $label, $attributes);
        $this->_type = 'file'; $this->updateAttributes(['type' => 'file']);
    }
    public function setValue($value) { return null; }
    public function getValue() { return $_FILES[$this->getName()] ?? null; }
    public function isUploadedFile() {
        $file = $_FILES[$this->getName()] ?? null;
        return $file && $file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name']);
    }
    public function onQuickFormEvent($event, $arg, &$caller) {
        if ($event === 'updateValue') {
            $caller->updateAttributes(['enctype' => 'multipart/form-data']);
            $caller->setMaxFileSize(10 * 1024 * 1024);
            return true;
        }
        return parent::onQuickFormEvent($event, $arg, $caller);
    }
}
