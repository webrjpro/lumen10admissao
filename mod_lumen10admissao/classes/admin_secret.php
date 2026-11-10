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
require_once($GLOBALS['CFG']->libdir . '/adminlib.php');

/** Encrypt the institution's certificate password; never render the saved password or ciphertext. */
final class admin_secret extends \admin_setting_configpasswordunmask {
    private const SAVED = 'L10_PASSWORD_ALREADY_SAVED';
    public function get_setting() {
        $value = parent::get_setting();
        return $value === false || $value === null ? $value : ($value === '' ? '' : self::SAVED);
    }
    public function write_setting($data) {
        if ($data === self::SAVED) { return ''; }
        return parent::write_setting(\core\encryption::encrypt((string)$data));
    }
}
