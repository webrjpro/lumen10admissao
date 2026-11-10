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

class observer {
    public static function user_created(\core\event\user_created $event): void {
        global $DB;
        $userid = (int)$event->objectid;
        if ($userid <= 1) { return; }
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user || !empty($user->deleted)) { return; }
        $reg = workflow::registration($userid);
        if (!empty($reg->number) && trim((string)$user->idnumber) === '') {
            $DB->set_field('user', 'idnumber', $reg->number, ['id' => $userid]);
        }
    }

    public static function user_updated(\core\event\user_updated $event): void {
        global $DB;
        $userid = (int)$event->objectid;
        if ($userid <= 1) { return; }
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user || !empty($user->deleted)) { return; }
        if (trim((string)$user->idnumber) === '') {
            $reg = workflow::registration($userid);
            if (!empty($reg->number)) {
                $DB->set_field('user', 'idnumber', $reg->number, ['id' => $userid]);
            }
        }
    }
}
