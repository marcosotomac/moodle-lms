<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Public CMC certificate verification page.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_cmc_lms\local\certificate_repository;

$token = optional_param('t', '', PARAM_ALPHANUMEXT);

$urlparams = $token === '' ? [] : ['t' => $token];
$url = new moodle_url('/local/cmc_lms/verify_certificate.php', $urlparams);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('verifycertificate', 'local_cmc_lms'));
$PAGE->set_heading(get_string('verifycertificate', 'local_cmc_lms'));

$repository = new certificate_repository();
$certificate = $repository->get_for_verification($token);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('verifycertificate', 'local_cmc_lms'));

if ($certificate === null) {
    echo $OUTPUT->notification(get_string('certificatenotfound', 'local_cmc_lms'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

if ($certificate->status === certificate_repository::STATUS_REVOKED) {
    echo $OUTPUT->notification(get_string('certificateisrevoked', 'local_cmc_lms'), 'error');
    if (!empty($certificate->timerevoked)) {
        echo html_writer::tag('p', get_string('revokeddate', 'local_cmc_lms') . ': ' . userdate($certificate->timerevoked));
    }
    echo $OUTPUT->footer();
    exit;
}

$verificationurl = $repository->get_verification_url($certificate)->out(false);
echo $OUTPUT->notification(get_string('certificateisvalid', 'local_cmc_lms'), 'success');

$table = new html_table();
$table->data = [
    [get_string('certificatecode', 'local_cmc_lms'), s($certificate->code)],
    [get_string('user'), s($certificate->userfullname)],
    [get_string('course'), format_string($certificate->coursefullname) . ' (' . s($certificate->courseshortname) . ')'],
    [get_string('program', 'local_cmc_lms'), $certificate->programname ? format_string($certificate->programname) : get_string('none')],
    [get_string('company', 'local_cmc_lms'), $certificate->companyname ? format_string($certificate->companyname) : get_string('none')],
    [get_string('certificatetitle', 'local_cmc_lms'), s($certificate->certificatetitle)],
    [get_string('coursehours', 'local_cmc_lms'), (float)$certificate->coursehours > 0 ? format_float((float)$certificate->coursehours, 2) : get_string('none')],
    [get_string('completiondate', 'local_cmc_lms'), !empty($certificate->completiontime) ? userdate($certificate->completiontime) : get_string('none')],
    [get_string('issueddate', 'local_cmc_lms'), userdate($certificate->timeissued)],
];

echo html_writer::table($table);
echo html_writer::tag('p', get_string('verificationurl', 'local_cmc_lms') . ': ' . html_writer::link($verificationurl, s($verificationurl)));
echo html_writer::tag('p', get_string('qrpayloadhelp', 'local_cmc_lms'));
echo html_writer::tag('pre', s($verificationurl));

echo $OUTPUT->footer();
