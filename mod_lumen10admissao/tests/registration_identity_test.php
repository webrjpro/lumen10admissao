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

#[\PHPUnit\Framework\Attributes\CoversClass(workflow::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(observer::class)]
final class registration_identity_test extends \advanced_testcase {
    public static function identifiers(): array {
        return [['IMPORTADO-001'], ['0'], ['00001']];
    }
    #[\PHPUnit\Framework\Attributes\DataProvider('identifiers')]
    public function test_imported_identifier_survives_creation_event_and_registration_reuse(string $identifier): void {
        global $DB;
        $this->resetAfterTest();$this->setAdminUser();$this->redirectMessages();
        $user=$this->getDataGenerator()->create_user(['idnumber'=>$identifier]);
        $this->assertSame($identifier,$DB->get_field('user','idnumber',['id'=>$user->id]));
        $first=workflow::registration((int)$user->id);$second=workflow::registration((int)$user->id);
        $this->assertEquals($first->id,$second->id);
        $this->assertSame($identifier,$DB->get_field('user','idnumber',['id'=>$user->id]));
        $this->assertEquals(1,$DB->count_records('lumen10admissao_reg',['userid'=>$user->id]));
    }
}
