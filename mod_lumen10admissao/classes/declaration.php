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

/** Immutable, private PDFs. A verification code authenticates the issued bytes, never grants document access. */
final class declaration {
    public const DEFAULTTEXT = "Declaramos, para os devidos fins, que {aluno}, CPF nº {cpf}, matrícula nº {matricula}, encontra-se matriculado(a) {vinculo}, na modalidade {modalidade}, na {instituicao}.\n\nEsta declaração apresenta a situação da matrícula na data de sua emissão. A situação atual pode ser conferida pelo QR Code.";
    public static function enabled(\stdClass $r): bool {
        return (bool)workflow::config('declarationenabled', 1);
    }
    public static function institution(): string {
        global $SITE;
        return trim((string)workflow::config('declarationinstitution', '')) ?: $SITE->fullname;
    }
    public static function asset(string $area): ?\stored_file {
        $files = get_file_storage()->get_area_files(\context_system::instance()->id, workflow::COMPONENT, $area, 0, 'id', false);
        return $files ? reset($files) : null;
    }
    private static function image(?\stored_file $file): string {
        if (!$file) { return ''; }
        $content = $file->get_content();
        if ($file->get_filesize() > 2 * 1024 * 1024 ||
                !in_array((new \finfo(FILEINFO_MIME_TYPE))->buffer($content), ['image/jpeg', 'image/png']) ||
                !($size = @getimagesizefromstring($content)) || $size[0] * $size[1] > 16000000) {
            throw new \moodle_exception('error_declarationconfig', workflow::COMPONENT);
        }
        return $content;
    }
    public static function branding(): array {
        $logo = self::asset('decllogo');
        if (!$logo) {
            $files = get_file_storage()->get_area_files(\context_system::instance()->id, 'core_admin', 'logo', 0, 'id', false);
            $logo = $files ? reset($files) : null;
        }
        return ['logo' => self::image($logo), 'watermark' => self::image(self::asset('declwatermark')),
            'signature' => workflow::config('declarationvisual', 1) ? self::image(self::asset('declsignature')) : '',
            'watermarktext' => (string)workflow::config('declarationwatermarktext', self::institution())];
    }
    /** Certificate and key remain private File API content; password is encrypted with Moodle's server key. */
    public static function signing(): ?array {
        if (!workflow::config('declarationdigital', 0)) { return null; }
        try {
            $file = self::asset('declp12');
            if (!$file || $file->get_filesize() > 1024 * 1024 || !function_exists('openssl_pkcs12_read')) { throw new \Exception(); }
            $password = \core\encryption::decrypt((string)workflow::config('declarationcertpassword', ''));
            if (!@openssl_pkcs12_read($file->get_content(), $bundle, $password) || empty($bundle['cert']) || empty($bundle['pkey']) ||
                    !openssl_x509_check_private_key($bundle['cert'], $bundle['pkey'])) { throw new \Exception(); }
            $cert = openssl_x509_parse($bundle['cert']);
            if (!$cert || $cert['validFrom_time_t'] > time() || $cert['validTo_time_t'] < time()) { throw new \Exception(); }
            return $bundle;
        } catch (\Throwable $e) { throw new \moodle_exception('error_declarationconfig', workflow::COMPONENT); }
    }
    public static function check_configuration(): void {
        self::branding(); self::signing();
        $text = (string)workflow::config('declarationtext', get_string('declarationdefault', workflow::COMPONENT));
        if (trim($text) === '' || \core_text::strlen($text) > 8000 || \core_text::strlen(self::institution()) > 255) {
            throw new \moodle_exception('error_declarationconfig', workflow::COMPONENT);
        }
        foreach (['declarationsubtitle' => 160, 'declarationmodality' => 120, 'declarationsignatory' => 120,
                'declarationsignatoryrole' => 160, 'declarationwatermarktext' => 120] as $setting => $max) {
            if (\core_text::strlen((string)workflow::config($setting, '')) > $max) {
                throw new \moodle_exception('error_declarationconfig', workflow::COMPONENT);
            }
        }
        preg_match_all('/\{([^{}]+)\}/', $text, $tokens);
        if (array_diff($tokens[1], ['aluno', 'nomesocial', 'cpf', 'matricula', 'turma', 'curso', 'secretaria', 'instituicao', 'data', 'protocolo', 'modalidade', 'vinculo', 'datamatricula'])) {
            throw new \moodle_exception('error_declarationconfig', workflow::COMPONENT);
        }
    }
    public static function substitutions(\stdClass $r, string $number, int $date, ?int $enrolmentdate = null): array {
        global $DB;
        $tzname = (string)workflow::config('declarationtimezone', 'America/Sao_Paulo');
        $tz = new \DateTimeZone($tzname);
        if ($enrolmentdate === null) {
            $enroltime = 0;
            if (!empty($r->userid)) {
                $courseids = array_filter([(int)($r->courseid ?? 0), (int)($r->receptioncourseid ?? 0)]);
                if (!empty($r->categoryid)) {
                    $catcourses = $DB->get_fieldset_select('course', 'id', 'category = :cat', ['cat' => (int)$r->categoryid]);
                    if ($catcourses) {
                        $courseids = array_unique(array_merge($courseids, $catcourses));
                    }
                }
                if ($courseids) {
                    [$in, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'crs');
                    $sql = "SELECT MIN(CASE WHEN ue.timestart > 0 THEN ue.timestart ELSE ue.timecreated END)
                            FROM {user_enrolments} ue
                            JOIN {enrol} e ON e.id = ue.enrolid
                            WHERE ue.userid = :uid AND e.courseid $in";
                    $enroltime = (int)$DB->get_field_sql($sql, $params + ['uid' => (int)$r->userid]);
                }
            }
            if (!$enroltime && !empty($r->timecompleted)) {
                $enroltime = (int)$r->timecompleted;
            }
            if (!$enroltime && !empty($r->timecreated)) {
                $enroltime = (int)$r->timecreated;
            }
            $enrolmentdate = $enroltime ?: $date;
        }
        $dtdate = (new \DateTime('@' . $date))->setTimezone($tz);
        $dtenrol = (new \DateTime('@' . $enrolmentdate))->setTimezone($tz);
        return ['{aluno}' => trim($r->firstname . ($r->lastname === '-' ? '' : ' ' . $r->lastname)),
            '{nomesocial}' => $r->socialname ?? '', '{cpf}' => !empty($r->cpf) ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $r->cpf) : '',
            '{matricula}' => $number, '{turma}' => workflow::category((int)$r->categoryid)->name,
            '{curso}' => workflow::category((int)$r->categoryid)->name, '{modalidade}' => (string)workflow::config('declarationmodality', get_string('declarationmodality_default', 'mod_lumen10admissao')),
            '{vinculo}' => get_string('declarationrelationship', workflow::COMPONENT, workflow::category((int)$r->categoryid)->name),
            '{secretaria}' => trim(workflow::course((int)$r->receptioncourseid)->fullname), '{instituicao}' => self::institution(),
            '{data}' => $dtdate->format('d/m/Y'),
            '{datamatricula}' => $dtenrol->format('d/m/Y'),
            '{protocolo}' => $r->protocol ?? ''];
    }
    public static function file(\stdClass $r): ?\stored_file {
        $cm = get_coursemodule_from_instance('lumen10admissao', $r->admissaoid);
        if (!$cm) { return null; }
        $files = get_file_storage()->get_area_files(\context_module::instance($cm->id)->id, workflow::COMPONENT, 'declaracao', $r->id, 'id', false);
        return $files ? reset($files) : null;
    }
    /** Caller holds the same request lock as mail/confirmation/erasure. */
    public static function issue_locked(\stdClass $r): ?\stored_file {
        global $DB;
        if ($r->status !== 'approved') { throw new \moodle_exception('error_state', workflow::COMPONENT); }
        if ($file = self::file($r)) { return $file; }
        if (!self::enabled($r)) { return null; }
        self::check_configuration();
        return workflow::atomic(function() use ($r, $DB) {
            $registration = $DB->get_record('lumen10admissao_reg', ['userid' => $r->userid], '*', MUST_EXIST);
            $date = time();
            $values = self::substitutions($r, $registration->number, $date);
            $row = (object)['requestid' => $r->id, 'code' => strtoupper(bin2hex(random_bytes(16))),
                'studentname' => $values['{aluno}'], 'registration' => $registration->number,
                'institution' => $values['{instituicao}'], 'classname' => $values['{turma}'],
                'signaturetype' => 'none', 'timecreated' => $date];
            $branding = self::branding(); $signing = self::signing();
            if ($signing) { $row->signaturetype = 'digital'; }
            else if ($branding['signature']) { $row->signaturetype = 'visual'; }
            $row->id = $DB->insert_record('lumen10admissao_decl', $row);
            $pdf = self::build($row, $values, $branding, $signing);
            $cm = get_coursemodule_from_instance('lumen10admissao', $r->admissaoid, 0, false, MUST_EXIST);
            $file = get_file_storage()->create_file_from_string(['contextid' => \context_module::instance($cm->id)->id,
                'component' => workflow::COMPONENT, 'filearea' => 'declaracao', 'itemid' => $r->id, 'filepath' => '/',
                'filename' => 'declaracao-' . $row->code . '.pdf', 'mimetype' => 'application/pdf', 'userid' => $r->userid], $pdf);
            $DB->set_field('lumen10admissao_decl', 'sha256', hash('sha256', $pdf), ['id' => $row->id]);
            workflow::audit($r, 'declaration_issued', $row->signaturetype);
            return $file;
        });
    }
    /** Reuse the institutional template for a new immutable issue, with its own verification URL. */
    public static function render_issue(\stdClass $row, array $values, \moodle_url $verificationurl): string {
        self::check_configuration();
        return self::build($row, $values, self::branding(), self::signing(), $verificationurl);
    }
    private static function build(\stdClass $row, array $values, array $branding, ?array $signing,
            ?\moodle_url $verificationurl = null): string {
        $pdf = new declaration_pdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->branding = $branding; $pdf->institution = $row->institution; $pdf->verificationcode = $row->code;
        $pdf->SetCreator('Lumen10 Inscrições e Admissão'); $pdf->SetAuthor($row->institution);
        $pdf->SetTitle(get_string('declarationtitle', 'mod_lumen10admissao')); $pdf->SetMargins(18, 56, 18); $pdf->SetAutoPageBreak(false);
        $chain = '';
        if ($signing) {
            if (!empty($signing['extracerts'])) {
                $chain = make_request_directory() . '/institution-chain.pem';
                file_put_contents($chain, implode("\n", $signing['extracerts']));
            }
            $pdf->setSignature($signing['cert'], $signing['pkey'], '', $chain, 1,
                ['Name' => (string)workflow::config('declarationsignatory', $row->institution),
                 'Reason' => get_string('pdfsignaturepurpose', 'mod_lumen10admissao'), 'Location' => $row->institution]);
        }
        $pdf->AddPage(); $pdf->SetFont('freesans', 'B', 23); $pdf->SetTextColor(0, 81, 77);
        $pdf->MultiCell(174, 13, get_string('pdfheading', 'mod_lumen10admissao'), 0, 'C');
        $subtitle = (string)workflow::config('declarationsubtitle', get_string('declarationsubtitle_default', 'mod_lumen10admissao'));
        $pdf->SetFont('freesans', '', 8.5); $pdf->SetTextColor(112, 122, 125);
        $pdf->MultiCell(174, 6, $subtitle, 0, 'C'); $pdf->Ln(5);
        $datamatricula = $values['{datamatricula}'] ?? $values['{data}'];
        $summary = [get_string('pdfstudent', 'mod_lumen10admissao') => $values['{aluno}'], get_string('pdfcpf', 'mod_lumen10admissao') => $values['{cpf}'], get_string('pdfregistration', 'mod_lumen10admissao') => $values['{matricula}'], get_string('pdfcourse', 'mod_lumen10admissao') => $values['{curso}'],
            get_string('pdfmodality', 'mod_lumen10admissao') => $values['{modalidade}'], get_string('pdfenrolmentdate', 'mod_lumen10admissao') => $datamatricula, get_string('pdfstatus', 'mod_lumen10admissao') => get_string('pdfregular', 'mod_lumen10admissao')];
        if ($values['{cpf}'] === '') { unset($summary[get_string('pdfcpf', 'mod_lumen10admissao')]); }
        $top = $pdf->GetY(); $heights = [];
        foreach ($summary as $label => $value) { $pdf->SetFont('freesans', '', 10.5); $heights[$label] = max(8.5, $pdf->getStringHeight(114, $value) + 2.5); }
        $height = array_sum($heights) + 8; $pdf->SetFillColor(246, 252, 250); $pdf->SetDrawColor(165, 231, 211);
        $pdf->RoundedRect(18, $top, 174, $height, 2, '1111', 'DF');
        $y = $top + 4;
        $lastlabel = array_key_last($summary);
        foreach ($summary as $label => $value) {
            $pdf->SetXY(27, $y); $pdf->SetTextColor(0, 104, 91); $pdf->SetFont('freesans', 'B', 10.5);
            $pdf->MultiCell(42, $heights[$label], $label, 0, 'L');
            $pdf->SetXY(70, $y); $pdf->SetTextColor(28, 44, 58); $pdf->SetFont('freesans', '', 10.5);
            $pdf->MultiCell(114, $heights[$label], $value, 0, 'L');
            $y += $heights[$label];
            if ($label !== $lastlabel) { $pdf->SetDrawColor(221, 235, 234); $pdf->Line(27, $y - 1.5, 184, $y - 1.5); }
        }
        $pdf->SetXY(18, $top + $height + 7); $pdf->SetTextColor(28, 44, 58); $pdf->SetFont('freesans', '', 10.5);
        $text = strtr((string)workflow::config('declarationtext', get_string('declarationdefault', workflow::COMPONENT)), $values);
        if ($values['{cpf}'] === '') { $text = str_replace(', CPF nº ,', ',', $text); }
        $pdf->MultiCell(174, 6.2, $text, 0, 'L'); $pdf->Ln(5);
        $pdf->SetFont('freesans', '', 10);
        $pdf->MultiCell(174, 5.5, get_string('pdfissueddate', workflow::COMPONENT, $values['{data}']), 0, 'L');
        $y = max(232, $pdf->GetY() + 6);
        if (workflow::config('declarationvisual', 1)) {
            if ($branding['signature']) { $pdf->Image('@' . $branding['signature'], 80, $y, 48, 0, '', '', '', false, 150, '', false, false, 20, true); }
            $pdf->SetDrawColor(72, 83, 100); $pdf->SetLineWidth(0.3); $pdf->Line(61, $y + 21, 149, $y + 21);
            $pdf->SetXY(55, $y + 24); $pdf->SetFont('freesans', '', 9.5);
            $pdf->MultiCell(100, 5, (string)workflow::config('declarationsignatory', '') ?: get_string('pdfsignatory', 'mod_lumen10admissao'), 0, 'C');
            $pdf->SetXY(55, $pdf->GetY()); $pdf->SetFont('freesans', '', 9); $pdf->MultiCell(100, 5, (string)workflow::config('declarationsignatoryrole', '') ?: $row->institution, 0, 'C');
        }
        if ($signing) { $pdf->setSignatureAppearance(61, $y, 88, 34); }
        $verify = $verificationurl ?? new \moodle_url('/mod/lumen10admissao/verificar.php', ['code' => $row->code]);
        if (workflow::config('declarationqr', 1)) {
            $pdf->write2DBarcode($verify->out(false), 'QRCODE,M', 164, 231, 25, 25, ['border' => 0, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => [255, 255, 255]]);
            $pdf->SetXY(157, 258); $pdf->SetFont('freesans', 'B', 7.5); $pdf->MultiCell(40, 4, get_string('pdfvalidation', 'mod_lumen10admissao'), 0, 'C');
            $pdf->SetXY(157, $pdf->GetY()); $pdf->SetFont('freesans', '', 7); $pdf->MultiCell(40, 4, get_string('pdfcurrentstatus', 'mod_lumen10admissao'), 0, 'C');
        }
        try { return $pdf->Output('', 'S'); }
        finally { if ($chain !== '' && is_file($chain)) { unlink($chain); } }
    }
    public static function authorized(\stdClass $r): void {
        global $USER;
        if (isloggedin() && !isguestuser()) {
            if ($r->userid == $USER->id) { return; }
            workflow::assert_scope($r); return;
        }
        workflow::session_request((int)$r->id);
    }
    /** No cached status: global suspension and native course enrolment are read on every QR lookup. */
    public static function verification(string $code): ?array {
        global $DB;
        if (!preg_match('/^[A-F0-9]{32}$/', $code) ||
                !$row = $DB->get_record('lumen10admissao_decl', ['code' => $code])) { return null; }
        $r = $DB->get_record(workflow::TABLE, ['id' => $row->requestid]);
        if (!$r || $r->status !== 'approved' || !$row->sha256) { return null; }
        $user = $DB->get_record('user', ['id' => $r->userid], 'id,deleted,suspended,confirmed');
        $courses = [(int)$r->receptioncourseid];
        if ($r->courseid != $r->receptioncourseid && in_array($r->enrolstatus, ['existing', 'enrolled'])) { $courses[] = (int)$r->courseid; }
        $names = []; $states = [];
        foreach ($courses as $id) {
            $course = $DB->get_record('course', ['id' => $id], 'id,fullname');
            $names[] = $course ? trim($course->fullname) : get_string('courseunavailable', 'mod_lumen10admissao');
            $states[] = $course && $user && !$user->deleted ? workflow::enrolment((int)$user->id, $id) : 'none';
        }
        $account = !$user || $user->deleted ? get_string('accountclosed', 'mod_lumen10admissao') : ($user->suspended ? get_string('accountsuspended', 'mod_lumen10admissao') : ($user->confirmed ? get_string('accountactive', 'mod_lumen10admissao') : get_string('accountunconfirmed', 'mod_lumen10admissao')));
        $enrolment = in_array('none', $states) ? get_string('enrolremoved', 'mod_lumen10admissao') : (in_array('inactive', $states) ? get_string('enrolsuspended', 'mod_lumen10admissao') : get_string('accountactive', 'mod_lumen10admissao'));
        if ($user && !$user->deleted && $user->suspended) { $status = 'suspended'; $label = get_string('suspended', 'mod_lumen10admissao'); }
        else if (!$user || $user->deleted || in_array('none', $states)) { $status = 'closed'; $label = get_string('enrolclosed', 'mod_lumen10admissao'); }
        else if (in_array('inactive', $states)) { $status = 'suspended'; $label = get_string('suspended', 'mod_lumen10admissao'); }
        else if (!$user->confirmed) { $status = 'inactive'; $label = get_string('inactiveunconfirmed', 'mod_lumen10admissao'); }
        else { $status = 'active'; $label = get_string('active', 'mod_lumen10admissao'); }
        return ['record' => $row, 'status' => $status, 'label' => $label, 'scope' => implode(' / ', $names),
            'account' => $account, 'enrolment' => $enrolment];
    }
    public static function render_link(\stdClass $r): void {
        if (self::file($r)) {
            echo \html_writer::link(new \moodle_url('/mod/lumen10admissao/declaracao.php', ['id' => $r->id]),
                get_string('declarationview', 'mod_lumen10admissao'), ['class' => 'btn btn-outline-primary l10adm-preview ms-2', 'data-mime' => 'application/pdf', 'data-label' => get_string('declarationtitle', 'mod_lumen10admissao')]);
        }
    }
}
