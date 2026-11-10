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
require_once($GLOBALS['CFG']->libdir . '/pdflib.php');

/** Repeated institutional header, watermark and discreet footer, including multi-page declarations. */
final class declaration_pdf extends \pdf {
    public array $branding = [];
    public string $institution = '';
    public string $verificationcode = '';
    public function Header() {
        if (workflow::config('declarationdecoration', 1)) {
            $this->SetFillColor(224, 248, 240); $this->Polygon([145, 0, 210, 0, 210, 43], 'F');
            $this->SetFillColor(157, 216, 199); $this->Polygon([169, 0, 210, 0, 210, 36, 195, 29], 'F');
            $this->SetFillColor(95, 191, 173); $this->Polygon([189, 0, 210, 0, 210, 28, 203, 24], 'F');
            $this->SetFillColor(216, 245, 235); $this->Polygon([0, 230, 0, 297, 88, 297, 48, 280], 'F');
            $this->SetFillColor(129, 210, 187); $this->Polygon([0, 257, 0, 297, 52, 297, 29, 285], 'F');
            $this->SetFillColor(31, 163, 132); $this->Polygon([0, 269, 0, 297, 43, 297, 18, 281], 'F');
            $this->SetFillColor(9, 136, 113); $this->Polygon([0, 283, 0, 297, 28, 297, 10, 288], 'F');
            $this->SetFillColor(148, 224, 201);
            foreach ([[12, 10], [189, 276]] as [$x, $y]) { for ($i = 0; $i < 4; $i++) { for ($j = 0; $j < 4; $j++) { $this->Circle($x + $i * 4.4, $y + $j * 4.4, 0.85, 0, 360, 'F'); } } }
        }
        $this->SetAlpha(0.08);
        if (!empty($this->branding['watermark'])) {
            $this->Image('@' . $this->branding['watermark'], 50, 106, 110, 0, '', '', '', false, 150);
        } else if (!empty($this->branding['watermarktext'])) {
            $this->StartTransform(); $this->Rotate(35, 105, 148);
            $this->SetFont('freesans', 'B', 27); $this->SetTextColor(0, 120, 99);
            $this->SetXY(22, 136); $this->MultiCell(166, 16, $this->branding['watermarktext'], 0, 'C');
            $this->StopTransform();
        }
        $this->SetAlpha(1); $this->SetTextColor(22, 42, 54);
        if (!empty($this->branding['logo'])) { $this->Image('@' . $this->branding['logo'], 53, 18, 104, 0, '', '', '', false, 150, '', false, false, 33, true); }
        else { $this->SetFont('freesans', 'B', 18); $this->SetXY(30, 27); $this->MultiCell(150, 18, $this->institution, 0, 'C'); }
        $this->SetDrawColor(0, 134, 107); $this->SetLineWidth(0.45); $this->Line(18.5, 55.5, 191.5, 55.5);
    }
    public function Footer() {
        $this->SetY(-15); $this->SetFont('freesans', '', 6.5); $this->SetTextColor(109, 122, 139);
        $this->Cell(174, 4, get_string('pdffooterverification', workflow::COMPONENT, $this->verificationcode), 0, 1, 'C');
        $this->Cell(174, 4, get_string('pdffooterpage', workflow::COMPONENT, (object)['page' => $this->getAliasNumPage(), 'total' => $this->getAliasNbPages()]), 0, 0, 'C');
    }
}
