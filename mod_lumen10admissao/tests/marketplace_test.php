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

/** Regression coverage for native viewing, private output and independent installation. */
final class marketplace_test extends \advanced_testcase {
    public function test_view_event_preserves_the_native_context_and_does_not_approve_admission(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/lumen10admissao/lib.php');
        $course = $this->getDataGenerator()->create_course();
        $a = $this->getDataGenerator()->create_module('lumen10admissao', ['course' => $course->id]);
        $cm = get_coursemodule_from_id('lumen10admissao', $a->cmid);
        $sink = $this->redirectEvents();
        lumen10admissao_view($a, $course, $cm, \context_module::instance($cm->id));
        $events = array_values(array_filter($sink->get_events(), fn($e) => $e instanceof event\course_module_viewed));
        $this->assertCount(1, $events);
        $this->assertEquals($a->id, $events[0]->objectid);
        $this->assertEquals(\context_module::instance($cm->id)->id, $events[0]->contextid);
        $this->assertEquals(0, $DB->count_records(workflow::TABLE));
        $this->assertFalse(lumen10admissao_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
    }

    public function test_privacy_metadata_names_real_fields_and_has_english_descriptions(): void {
        global $DB;
        $this->resetAfterTest();
        $items = privacy\provider::get_metadata(new \core_privacy\local\metadata\collection(workflow::COMPONENT));
        $tables = [];
        foreach ($items->get_collection() as $item) {
            if (!$item instanceof \core_privacy\local\metadata\types\database_table) {
                continue;
            }
            $tables[$item->get_name()] = $item->get_privacy_fields();
            $columns = $DB->get_columns($item->get_name());
            foreach ($item->get_privacy_fields() as $field => $key) {
                $this->assertArrayHasKey($field, $columns);
                $this->assertTrue(get_string_manager()->string_exists($key, workflow::COMPONENT));
                $this->assertStringNotContainsString('[[', get_string($key, workflow::COMPONENT));
            }
        }
        $this->assertCount(7, $tables);
        foreach (['phone', 'visualimpairment', 'confirmexpires', 'accessexpires', 'timecompleted', 'consenttime'] as $key) {
            $this->assertArrayHasKey($key, $tables[workflow::TABLE]);
        }
    }

    public function test_independent_reception_does_not_require_the_optional_student_record_plugin(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        unset_config('version', 'local_lumen10_prontuario');
        $course = $this->getDataGenerator()->create_course();
        $a = $this->getDataGenerator()->create_module('lumen10admissao', ['course' => $course->id]);
        $r = (object)['categoryid' => $course->category, 'receptioncourseid' => $course->id];
        $this->assertSame((new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false), workflow::reception_url($r)->out(false));
        $this->assertSame((string)$course->id, (string)workflow::reception_url($r)->get_param('id'));
        $this->assertNotEmpty($a->id);
    }

    public function test_private_details_template_escapes_identity_and_uses_row_headers(): void {
        global $OUTPUT;
        $this->resetAfterTest();
        $html = $OUTPUT->render_from_template('mod_lumen10admissao/details', ['rows' => [
            ['label' => 'Full name', 'value' => '<img src=x onerror=alert(1)>'],
        ]]);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString('scope="row"', $html);
    }

    public function test_public_category_name_does_not_require_guest_course_catalogue_permission(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $a = $this->getDataGenerator()->create_module('lumen10admissao', ['course' => $course->id, 'enabled' => 1]);
        foreach (get_archetype_roles('guest') as $role) {
            assign_capability('moodle/category:viewcourselist', CAP_PROHIBIT, $role->id,
                \context_coursecat::instance($category->id)->id, true);
        }
        $this->setGuestUser();
        $this->assertFalse(has_capability('moodle/category:viewcourselist', \context_coursecat::instance($category->id)));
        $this->assertNotEmpty(workflow::activity($a->id, true));
        $r = (object)['courseid' => $course->id, 'receptioncourseid' => $course->id, 'categoryid' => $category->id];
        $this->assertSame($category->name, workflow::requested_name($r));
    }

    public function test_public_application_is_rejected_when_reception_is_hidden(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->getDataGenerator()->create_category();
        $reception = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $target = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $a = $this->getDataGenerator()->create_module('lumen10admissao', ['course' => $reception->id, 'targetcourseid' => $target->id, 'enabled' => 1]);
        $DB->set_field('course', 'visible', 0, ['id' => $reception->id]);
        $this->setGuestUser();
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('error_disabled', workflow::COMPONENT));
        workflow::activity($a->id, true);
    }
}
