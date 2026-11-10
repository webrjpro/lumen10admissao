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
require_once(__DIR__ . '/../../config.php');
use mod_lumen10admissao\workflow;
$id = required_param('id', PARAM_INT); $kind = required_param('kind', PARAM_ALPHA);
if (!in_array($kind, \mod_lumen10admissao\documents::KINDS)) { throw new moodle_exception('error_documents', workflow::COMPONENT); }
$r = workflow::request($id, false);
[$activity, $cm, $course] = workflow::activity((int)$r->admissaoid);
$context = context_module::instance($cm->id);
$permitted = isloggedin() && !isguestuser() && ((int)$r->userid === (int)$USER->id || has_capability('mod/lumen10admissao:review', $context));
if (!$permitted) { workflow::session_request($id); }
if ($permitted && (int)$r->userid !== (int)$USER->id) { workflow::assert_scope($r); }
$files = \mod_lumen10admissao\documents::files($r);
if (!isset($files[$kind])) { send_file_not_found(); }
workflow::audit($r, 'document_viewed', $kind);
header('Cache-Control: no-store, private'); header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff'); header('Content-Security-Policy: default-src \'none\'; frame-ancestors \'self\'');
header('X-Frame-Options: SAMEORIGIN');
\core\session\manager::write_close();
send_stored_file($files[$kind], 0, 0, false, ['cacheability' => 'private', 'dontdie' => false]);
