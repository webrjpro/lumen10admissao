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
function xmldb_lumen10admissao_upgrade($oldversion): bool {
    global $DB;
    if ($oldversion < 2026100301) {
        $manager = $DB->get_manager(); $table = new xmldb_table('lumen10admissao_req');
        foreach (['enrolrequested' => 1, 'timecompleted' => 10] as $name => $length) {
            $field = new xmldb_field($name, XMLDB_TYPE_INTEGER, (string)$length, null, XMLDB_NOTNULL, null, '0');
            if (!$manager->field_exists($table, $field)) { $manager->add_field($table, $field); }
        }
        $statusindex = new xmldb_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        if ($manager->index_exists($table, $statusindex)) { $manager->drop_index($table, $statusindex); }
        $manager->change_field_default($table, new xmldb_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending'));
        $manager->add_index($table, $statusindex);
        if (!$manager->table_exists('lumen10admissao_decl')) {
            $structure = new xmldb_file(__DIR__ . '/install.xml');
            if (!$structure->loadXMLStructure()) { throw new coding_exception('Invalid admissions schema'); }
            $manager->create_table($structure->getStructure()->getTable('lumen10admissao_decl'));
        }
        // Legacy pre-analysis confirmations are replaced by direct analysis; approved accounts remain intact.
        $DB->execute("UPDATE {lumen10admissao_req} SET status=:pending,confirmhash=NULL,confirmexpires=0 WHERE status=:old",
            ['pending' => 'pending', 'old' => 'unverified']);
        $DB->execute('UPDATE {lumen10admissao_req} SET timecompleted=timemodified WHERE status=:status AND timecompleted=0', ['status' => 'approved']);
        $DB->execute('UPDATE {lumen10admissao_mail} SET timesent=:now,errorcode=:reason WHERE kind=:kind AND timesent=0',
            ['now' => time(), 'reason' => 'obsolete', 'kind' => 'confirm']);
        upgrade_mod_savepoint(true, 2026100301, 'lumen10admissao');
    }
    if ($oldversion < 2026100400) {
        $previous = "Declaramos, para os devidos fins, que {aluno}, matrícula nº {matricula}, encontra-se matriculado(a) {vinculo}, na modalidade {modalidade}, na {instituicao}.\n\nEsta declaração apresenta a situação da matrícula na data de sua emissão. A situação atual pode ser conferida pelo QR Code.";
        if (get_config('mod_lumen10admissao', 'declarationtext') === $previous) {
            set_config('declarationtext', \mod_lumen10admissao\declaration::DEFAULTTEXT, 'mod_lumen10admissao');
        }
        upgrade_mod_savepoint(true, 2026100400, 'lumen10admissao');
    }
    if ($oldversion < 2026102100) {
        $manager = $DB->get_manager();
        $table = new xmldb_table('lumen10admissao_req');
        $field = new xmldb_field('visualimpairment', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$manager->field_exists($table, $field)) { $manager->add_field($table, $field); }
        upgrade_mod_savepoint(true, 2026102100, 'lumen10admissao');
    }
    return true;
}
