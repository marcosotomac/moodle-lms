<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Certificate administration page.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use local_cmc_lms\form\certificate_issue_form;
use local_cmc_lms\local\certificate_repository;

$action = optional_param('action', '', PARAM_ALPHA);
$certificateid = optional_param('certid', 0, PARAM_INT);

admin_externalpage_setup('local_cmc_lms_certificates');

$context = context_system::instance();
require_capability('local/cmc_lms:viewcertificates', $context);

$repository = new certificate_repository();
$form = null;
$issuedcertificate = null;

if ($action === 'revoke' && $certificateid > 0) {
    require_capability('local/cmc_lms:issuecertificates', $context);
    require_sesskey();
    $repository->revoke($certificateid, (int) $USER->id, get_string('manualrevocation', 'local_cmc_lms'));
    redirect(new moodle_url('/local/cmc_lms/certificates.php'), get_string('certificaterevoked', 'local_cmc_lms'));
}

if (has_capability('local/cmc_lms:issuecertificates', $context)) {
    $form = new certificate_issue_form(new moodle_url('/local/cmc_lms/certificates.php'));
    if ($data = $form->get_data()) {
        $issuedcertificate = $repository->issue(
            (int) $data->userid,
            (int) $data->courseid,
            empty($data->companyid) ? null : (int) $data->companyid,
            empty($data->programid) ? null : (int) $data->programid,
            (int) $USER->id
        );
    }
}

$certificates = $repository->list_recent(50);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('certificates', 'local_cmc_lms'));

if ($issuedcertificate !== null) {
    $verificationurl = $repository->get_verification_url($issuedcertificate)->out(false);
    echo $OUTPUT->notification(get_string('certificateissued', 'local_cmc_lms'), 'success');
    echo html_writer::tag('p', get_string('verificationurl', 'local_cmc_lms') . ': ' .
        html_writer::link($verificationurl, s($verificationurl)));
    echo html_writer::tag('p', get_string('qrpayload', 'local_cmc_lms') . ': ' . s($verificationurl));
}

if ($form !== null) {
    echo $OUTPUT->heading(get_string('issuecertificate', 'local_cmc_lms'), 3);
    $form->display();
}

echo $OUTPUT->heading(get_string('recentcertificates', 'local_cmc_lms'), 3);

if (empty($certificates)) {
    echo $OUTPUT->notification(get_string('nocertificates', 'local_cmc_lms'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('certificatecode', 'local_cmc_lms'),
    get_string('user'),
    get_string('course'),
    get_string('company', 'local_cmc_lms'),
    get_string('program', 'local_cmc_lms'),
    get_string('issueddate', 'local_cmc_lms'),
    get_string('status', 'local_cmc_lms'),
    get_string('verificationurl', 'local_cmc_lms'),
    get_string('actions', 'local_cmc_lms'),
];

foreach ($certificates as $certificate) {
    $verificationurl = $repository->get_verification_url($certificate)->out(false);
    $actions = [];
    if ($certificate->status === certificate_repository::STATUS_ISSUED &&
            has_capability('local/cmc_lms:issuecertificates', $context)) {
        $actions[] = html_writer::link(
            new moodle_url('/local/cmc_lms/certificates.php', [
                'action' => 'revoke',
                'certid' => $certificate->id,
                'sesskey' => sesskey(),
            ]),
            get_string('revoke', 'local_cmc_lms')
        );
    }

    $status = $certificate->status === certificate_repository::STATUS_REVOKED
        ? get_string('revoked', 'local_cmc_lms')
        : get_string('issued', 'local_cmc_lms');

    $table->data[] = [
        s($certificate->code),
        s($certificate->userfullname) . ' (' . s($certificate->useremail) . ')',
        format_string($certificate->coursefullname) . ' (' . s($certificate->courseshortname) . ')',
        $certificate->companyname ? format_string($certificate->companyname) : get_string('none'),
        $certificate->programname ? format_string($certificate->programname) : get_string('none'),
        userdate($certificate->timeissued),
        $status,
        html_writer::link($verificationurl, s($verificationurl)),
        implode(' | ', $actions),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
