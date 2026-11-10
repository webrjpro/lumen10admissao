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

/** Course index access boundaries. */
final class index_test extends \advanced_testcase {
    public function test_course_index_excludes_hidden_activities_and_other_courses(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/lumen10admissao/lib.php');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $other = $generator->create_course();
        $visible = $generator->create_module('lumen10admissao', ['course' => $course->id, 'name' => 'Visible admission']);
        $generator->create_module('lumen10admissao', ['course' => $course->id, 'visible' => 0, 'name' => 'Hidden admission']);
        $generator->create_module('lumen10admissao', ['course' => $other->id, 'name' => 'Other course']);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $links = lumen10admissao_get_course_index($course);
        $this->assertCount(1, $links);
        $this->assertEquals('Visible admission', $links[0]['name']);
        $this->assertSame((new \moodle_url('/mod/lumen10admissao/view.php', ['id' => $visible->cmid]))->out(false), $links[0]['url']);
        $this->assertSame(['name', 'url', 'hidden'], array_keys($links[0]));
    }

    public function test_course_index_respects_explicitly_prohibited_activity_view(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/lumen10admissao/lib.php');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $activity = $generator->create_module('lumen10admissao', ['course' => $course->id]);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        assign_capability('mod/lumen10admissao:view', CAP_PROHIBIT, $roleid, \context_module::instance($activity->cmid)->id, true);
        $this->setUser($student);
        $this->assertSame([], lumen10admissao_get_course_index($course));
    }
}
