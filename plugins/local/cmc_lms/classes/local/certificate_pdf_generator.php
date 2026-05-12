<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\local;

use stdClass;

/**
 * Generates downloadable CMC certificate PDFs using Moodle core TCPDF integration.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_pdf_generator {
    /**
     * Generate a certificate PDF as a binary string.
     *
     * @param stdClass $certificate Certificate record with joined display data.
     * @param string $verificationurl Public verification URL.
     * @return string PDF bytes.
     */
    public function generate(stdClass $certificate, string $verificationurl): string {
        global $CFG;

        require_once($CFG->libdir . '/pdflib.php');

        $pdf = new \pdf('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Moodle local_cmc_lms');
        $pdf->SetAuthor('CMC & Soluciones en Gestión Humana');
        $pdf->SetTitle($certificate->certificatetitle ?: get_string('defaultcertificateheading', 'local_cmc_lms'));
        $pdf->SetMargins(20, 18, 20);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage('L');

        $this->render_brand($pdf);

        $pdf->SetTextColor(30, 45, 68);
        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->Ln(10);
        $pdf->Cell(0, 14, $certificate->certificatetitle ?: get_string('defaultcertificateheading', 'local_cmc_lms'), 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 13);
        $pdf->Ln(5);
        $pdf->Cell(0, 8, get_string('certificatestatement', 'local_cmc_lms'), 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 22);
        $pdf->Ln(4);
        $pdf->Cell(0, 12, $certificate->userfullname, 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 13);
        $pdf->Cell(0, 8, get_string('certificatecoursestatement', 'local_cmc_lms'), 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->MultiCell(0, 10, format_string($certificate->coursefullname), 0, 'C');

        $pdf->Ln(6);
        $pdf->SetFont('helvetica', '', 11);
        $details = $this->build_details($certificate, $verificationurl);
        foreach ($details as $label => $value) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(45, 7, $label . ':', 0, 0, 'R');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(170, 7, $value, 0, 'L');
        }

        $pdf->write2DBarcode($verificationurl, 'QRCODE,M', 238, 120, 36, 36);
        $pdf->SetXY(225, 158);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell(62, 5, get_string('scanverify', 'local_cmc_lms'), 0, 'C');

        return $pdf->Output($this->get_filename($certificate), 'S');
    }

    /**
     * Build a safe file name for a certificate.
     *
     * @param stdClass $certificate Certificate record.
     * @return string
     */
    public function get_filename(stdClass $certificate): string {
        return clean_filename('CMC-certificate-' . $certificate->code . '.pdf');
    }

    /**
     * Render CMC wordmark/logo, falling back to text if the asset cannot be used by TCPDF.
     *
     * @param \pdf $pdf PDF document.
     * @return void
     */
    private function render_brand(\pdf $pdf): void {
        global $CFG;

        $logo = $CFG->dirroot . '/local/cmc_lms/pix/cmc-logo.svg';
        if (is_readable($logo) && method_exists($pdf, 'ImageSVG')) {
            $pdf->ImageSVG($logo, 20, 16, 42, 16);
            return;
        }

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(15, 55, 105);
        $pdf->Cell(0, 8, 'CMC', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 5, 'Soluciones en Gestión Humana', 0, 1, 'L');
    }

    /**
     * Build printable certificate details.
     *
     * @param stdClass $certificate Certificate record.
     * @param string $verificationurl Public verification URL.
     * @return array<string,string>
     */
    private function build_details(stdClass $certificate, string $verificationurl): array {
        $details = [
            get_string('certificatecode', 'local_cmc_lms') => $certificate->code,
            get_string('issueddate', 'local_cmc_lms') => userdate((int)$certificate->timeissued),
        ];

        if (!empty($certificate->completiontime)) {
            $details[get_string('completiondate', 'local_cmc_lms')] = userdate((int)$certificate->completiontime);
        }
        if ((float)$certificate->coursehours > 0) {
            $details[get_string('coursehours', 'local_cmc_lms')] = format_float((float)$certificate->coursehours, 2);
        }
        if (!empty($certificate->programname)) {
            $details[get_string('program', 'local_cmc_lms')] = format_string($certificate->programname);
        }
        if (!empty($certificate->companyname)) {
            $details[get_string('company', 'local_cmc_lms')] = format_string($certificate->companyname);
        }
        $details[get_string('verificationurl', 'local_cmc_lms')] = $verificationurl;

        return $details;
    }
}
