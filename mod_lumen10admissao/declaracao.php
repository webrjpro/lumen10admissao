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
use mod_lumen10admissao\{workflow, declaration};
$id = required_param('id', PARAM_INT);
$request = workflow::request($id, false); declaration::authorized($request);
$file = declaration::file($request);
if (!$file || $request->status !== 'approved') { throw new moodle_exception('error_state', workflow::COMPONENT); }
workflow::audit($request, 'declaration_viewed');
header('Cache-Control: no-store, private'); header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'self'");
\core\session\manager::write_close(); send_stored_file($file, 0, 0, false);
