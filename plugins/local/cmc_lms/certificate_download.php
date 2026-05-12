<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Download endpoint for issued CMC certificate PDFs.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_cmc_lms\local\certificate_pdf_generator;
use local_cmc_lms\local\certificate_repository;

$certificateid = required_param('certid', PARAM_INT);

require_login();

$context = context_system::instance();
$repository = new certificate_repository();
$certificate = $repository->get_by_id($certificateid);

$canviewall = has_capability('local/cmc_lms:viewcertificates', $context);
$isowner = ((int)$certificate->userid === (int)$USER->id);
if (!$canviewall && !$isowner) {
    throw new required_capability_exception($context, 'local/cmc_lms:viewcertificates', 'nopermissions', 'error');
}

if ($certificate->status === certificate_repository::STATUS_REVOKED) {
    throw new moodle_exception('certificateisrevoked', 'local_cmc_lms');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/cmc_lms/certificate_download.php', ['certid' => $certificateid]));

$generator = new certificate_pdf_generator();
$verificationurl = $repository->get_verification_url($certificate)->out(false);
$pdf = $generator->generate($certificate, $verificationurl);
$repository->mark_pdf_generated($certificateid);

\core\session\manager::write_close();

$filename = $generator->get_filename($certificate);
@header('Content-Type: application/pdf');
@header('Content-Disposition: attachment; filename="' . $filename . '"');
@header('Content-Length: ' . strlen($pdf));
@header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
@header('Pragma: public');
echo $pdf;
die;
