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

/** Initial identity documents, private Moodle file areas. No public file URL grants access. */
final class documents {
    public const KINDS = ['rg', 'certidao', 'laudo'];
    public const TYPES = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'webp' => 'image/webp'];
    public static function validate(string $filename, string $content): array {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($content);
        if (!isset(self::TYPES[$extension]) || $mime !== self::TYPES[$extension] || $content === '') {
            throw new \moodle_exception('error_filetype', workflow::COMPONENT);
        }
        $max = $extension === 'pdf' ? 10 * 1024 * 1024 : 5 * 1024 * 1024;
        if (strlen($content) > $max) { throw new \moodle_exception('error_filesize', workflow::COMPONENT); }
        if ($extension !== 'pdf' && !@getimagesizefromstring($content)) { throw new \moodle_exception('error_filetype', workflow::COMPONENT); }
        if ($extension === 'pdf' && !str_starts_with($content, '%PDF-')) { throw new \moodle_exception('error_filetype', workflow::COMPONENT); }
        return [$extension, $mime];
    }
    public static function payload(\moodleform $form, bool $required = true, bool $visual = false): array {
        $result = [];
        foreach (self::KINDS as $kind) {
            if ($kind === 'laudo' && !$visual) { continue; }
            $filename = $form->get_new_filename($kind); $content = $form->get_file_content($kind);
            if (!$filename || $content === false) {
                if ($required) { throw new \moodle_exception($kind === 'laudo' ? 'error_visualreport' : 'error_documents', workflow::COMPONENT); }
                continue;
            }
            self::validate($filename, $content);
            $result[$kind] = ['filename' => $filename, 'content' => $content];
        }
        return $result;
    }
    public static function files(\stdClass $r): array {
        $cm = get_coursemodule_from_instance('lumen10admissao', $r->admissaoid, 0, false, MUST_EXIST);
        $fs = get_file_storage(); $result = [];
        foreach (self::KINDS as $kind) {
            $rows = $fs->get_area_files(\context_module::instance($cm->id)->id, workflow::COMPONENT, $kind, $r->id, 'id', false);
            if ($rows) { $result[$kind] = reset($rows); }
        }
        return $result;
    }
    /** Called inside application transaction and upload-budget lock. Scan before making files accessible. */
    public static function store(\stdClass $r, array $uploads, string $certtype = 'nascimento'): void {
        global $CFG, $DB;
        if (!in_array($certtype, ['nascimento', 'casamento'])) { throw new \moodle_exception('error_documents', workflow::COMPONENT); }
        $cm = get_coursemodule_from_instance('lumen10admissao', $r->admissaoid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id); $fs = get_file_storage();
        $usage = (int)$DB->get_field_sql('SELECT COALESCE(SUM(filesize),0) FROM {files} WHERE component=:component AND filename<>:directory',
            ['component' => workflow::COMPONENT, 'directory' => '.']);
        $existing = self::files($r); $incoming = 0;
        foreach ($uploads as $kind => $upload) {
            if (!in_array($kind, self::KINDS)) { throw new \moodle_exception('error_documents', workflow::COMPONENT); }
            self::validate($upload['filename'], $upload['content']);
            $incoming += strlen($upload['content']) - (isset($existing[$kind]) ? $existing[$kind]->get_filesize() : 0);
        }
        $budget = max(1, (int)workflow::config('uploadbudgetmb', 10240)) * 1024 * 1024;
        $free = disk_free_space($CFG->dataroot);
        if ($usage + $incoming > $budget || $free === false || $free - max(0, $incoming) < 2 * 1024 ** 3) {
            throw new \moodle_exception('error_storage', workflow::COMPONENT);
        }
        foreach ($uploads as $kind => $upload) {
            [$ext, $mime] = self::validate($upload['filename'], $upload['content']);
            $temp = tempnam(make_temp_directory('lumen10admissao'), 'scan');
            if ($temp === false) { throw new \moodle_exception('error_storage', workflow::COMPONENT); }
            try {
                if (file_put_contents($temp, $upload['content']) !== strlen($upload['content'])) { throw new \moodle_exception('error_storage', workflow::COMPONENT); }
                \core\antivirus\manager::scan_file($temp, $upload['filename'], true);
                $fs->delete_area_files($context->id, workflow::COMPONENT, $kind, $r->id);
                $fs->create_file_from_pathname(['contextid' => $context->id, 'component' => workflow::COMPONENT,
                    'filearea' => $kind, 'itemid' => $r->id, 'filepath' => '/', 'filename' => bin2hex(random_bytes(16)) . '.' . $ext,
                    'mimetype' => $mime, 'source' => $kind === 'certidao' ? $certtype : $kind, 'userid' => 0], $temp);
            } finally { if (is_file($temp)) { unlink($temp); } }
        }
    }
    public static function required(\stdClass $r): void {
        $files = self::files($r);
        if (!isset($files['rg'], $files['certidao'])) { throw new \moodle_exception('error_documents', workflow::COMPONENT); }
        if (!empty($r->visualimpairment) && !isset($files['laudo'])) {
            throw new \moodle_exception('error_visualreport', workflow::COMPONENT);
        }
    }
    public static function erase(\stdClass $r): void {
        $cm = get_coursemodule_from_instance('lumen10admissao', $r->admissaoid);
        if (!$cm) { return; }
        foreach (array_merge(self::KINDS, ['declaracao']) as $kind) { get_file_storage()->delete_area_files(\context_module::instance($cm->id)->id, workflow::COMPONENT, $kind, $r->id); }
    }
    public static function render(\stdClass $r): void {
        global $OUTPUT;
        $documents = [];
        foreach (self::files($r) as $kind => $file) {
            $label = get_string($kind === 'laudo' ? 'medicalreport' : ($kind === 'rg' ? 'identitydocument' :
                ($file->get_source() === 'casamento' ? 'cert_marriage' : 'cert_birth')), workflow::COMPONENT);
            $documents[] = ['label' => $label, 'size' => display_size($file->get_filesize()),
                'url' => (new \moodle_url('/mod/lumen10admissao/documento.php', ['id' => $r->id, 'kind' => $kind]))->out(false),
                'mime' => $file->get_mimetype()];
        }
        echo $OUTPUT->render_from_template('mod_lumen10admissao/documents', ['documents' => $documents]);
    }
}
