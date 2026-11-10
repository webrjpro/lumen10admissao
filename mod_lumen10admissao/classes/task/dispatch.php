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
namespace mod_lumen10admissao\task;
defined('MOODLE_INTERNAL') || die();
final class dispatch extends \core\task\scheduled_task {
    public function get_name(): string { return get_string('taskdispatch', 'mod_lumen10admissao'); }
    public function execute(): void {
        global $DB;
        foreach ($DB->get_records_select('lumen10admissao_mail', 'timesent=0 AND nextattempt<=:now AND attempts<10',
                ['now' => time()], 'id', '*', 0, 100) as $job) { \mod_lumen10admissao\mail::send((int)$job->id); }
        $DB->delete_records_select('lumen10admissao_rate', 'windowstart<:expiry', ['expiry' => time() - DAYSECS]);
        // Institutional retention is explicitly configurable. Disabled until a policy is selected.
        $days = (int)\mod_lumen10admissao\workflow::config('retentiondays', 0);
        if ($days > 0) {
            $rows = $DB->get_records_select('lumen10admissao_req', 'status<>:approved AND timemodified<:expiry',
                ['approved' => 'approved', 'expiry' => time() - $days * DAYSECS], 'id', '*', 0, 100);
            foreach ($rows as $request) {
                \mod_lumen10admissao\workflow::locked(['request:' . $request->id], function() use ($request, $days, $DB) {
                    $current = $DB->get_record('lumen10admissao_req', ['id' => $request->id]);
                    if ($current && $current->status !== 'approved' && $current->timemodified < time() - $days * DAYSECS) {
                        \mod_lumen10admissao\privacy\provider::erase_locked((int)$request->id);
                    }
                });
            }
        }
    }
}
