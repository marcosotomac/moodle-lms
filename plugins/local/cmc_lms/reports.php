<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * B2B reports page for CMC LMS.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use local_cmc_lms\local\company_repository;
use local_cmc_lms\local\report_repository;

$companyid = optional_param('companyid', 0, PARAM_INT);

admin_externalpage_setup('local_cmc_lms_reports');

$context = context_system::instance();
require_capability('local/cmc_lms:viewreports', $context);

$reportrepository = new report_repository();
$companyrepository = new company_repository();
$companies = $reportrepository->get_company_dashboard_rows();
$courseenrolments = $reportrepository->get_course_enrolment_rows();
$evaluationsummaries = $reportrepository->get_evaluation_summary_rows();
$certificatereports = $reportrepository->get_certificate_report_rows(100);
$selectedcompany = null;
$userrows = [];

if ($companyid > 0) {
    $selectedcompany = $companyrepository->get($companyid);
    if (!$selectedcompany->active) {
        throw new moodle_exception('invalidcompany', 'local_cmc_lms');
    }
    $userrows = $reportrepository->get_company_user_progress_rows($companyid);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('b2breports', 'local_cmc_lms'));

if (empty($companies)) {
    echo $OUTPUT->notification(get_string('nocompanies', 'local_cmc_lms'), 'info');
} else {
    echo $OUTPUT->heading(get_string('companyactivitysummary', 'local_cmc_lms'), 3);

    $summarytable = new html_table();
    $summarytable->head = [
        get_string('companyname', 'local_cmc_lms'),
        get_string('shortname'),
        get_string('activeusers', 'local_cmc_lms'),
        get_string('students', 'local_cmc_lms'),
        get_string('supervisors', 'local_cmc_lms'),
        get_string('enrolments', 'local_cmc_lms'),
        get_string('completions', 'local_cmc_lms'),
        get_string('completionpercentage', 'local_cmc_lms'),
    ];

    foreach ($companies as $company) {
        $companyurl = new moodle_url('/local/cmc_lms/reports.php', ['companyid' => $company->id]);
        $summarytable->data[] = [
            html_writer::link($companyurl, format_string($company->name)),
            s($company->shortname),
            $company->activeusers,
            $company->students,
            $company->supervisors,
            $company->enrolmentcount,
            $company->completioncount,
            format_float($company->completionpercentage, 2) . '%',
        ];
    }

    echo html_writer::table($summarytable);
}

if ($selectedcompany !== null) {
    echo $OUTPUT->heading(
        get_string('companyprogress', 'local_cmc_lms', format_string($selectedcompany->name)),
        3
    );

    if (empty($userrows)) {
        echo $OUTPUT->notification(get_string('nousersforcompany', 'local_cmc_lms'), 'info');
    } else {
        $detailtable = new html_table();
        $detailtable->head = [
            get_string('user'),
            get_string('email'),
            get_string('companyrole', 'local_cmc_lms'),
            get_string('enrolledcourses', 'local_cmc_lms'),
            get_string('completedcourses', 'local_cmc_lms'),
            get_string('completionpercentage', 'local_cmc_lms'),
        ];

        foreach ($userrows as $userrow) {
            $rolekey = $userrow->companyrole;
            $rolename = get_string_manager()->string_exists($rolekey, 'local_cmc_lms')
                ? get_string($rolekey, 'local_cmc_lms')
                : s($rolekey);

            $detailtable->data[] = [
                html_writer::link(new moodle_url('/user/profile.php', ['id' => $userrow->userid]), s($userrow->fullname)),
                s($userrow->email),
                $rolename,
                $userrow->enrolledcourses,
                $userrow->completedcourses,
                format_float($userrow->completionpercentage, 2) . '%',
            ];
        }

        echo html_writer::table($detailtable);
    }
}

echo $OUTPUT->heading(get_string('courseenrolmentreport', 'local_cmc_lms'), 3);
if (empty($courseenrolments)) {
    echo $OUTPUT->notification(get_string('nocourseenrolmentreport', 'local_cmc_lms'), 'info');
} else {
    $coursetable = new html_table();
    $coursetable->head = [
        get_string('program', 'local_cmc_lms'),
        get_string('course', 'local_cmc_lms'),
        get_string('enrolledstudents', 'local_cmc_lms'),
        get_string('completedstudents', 'local_cmc_lms'),
        get_string('incompletestudents', 'local_cmc_lms'),
        get_string('completionpercentage', 'local_cmc_lms'),
    ];

    foreach ($courseenrolments as $row) {
        $coursetable->data[] = [
            format_string($row->programname),
            html_writer::link(new moodle_url('/course/view.php', ['id' => $row->courseid]), format_string($row->coursefullname)),
            $row->enrolledstudents,
            $row->completedstudents,
            $row->incompletestudents,
            format_float($row->completionpercentage, 2) . '%',
        ];
    }

    echo html_writer::table($coursetable);
}

echo $OUTPUT->heading(get_string('evaluationresultssummary', 'local_cmc_lms'), 3);
if (empty($evaluationsummaries)) {
    echo $OUTPUT->notification(get_string('noevaluationresults', 'local_cmc_lms'), 'info');
} else {
    $evaluationtable = new html_table();
    $evaluationtable->head = [
        get_string('program', 'local_cmc_lms'),
        get_string('course', 'local_cmc_lms'),
        get_string('quizactivity', 'local_cmc_lms'),
        get_string('attemptcount', 'local_cmc_lms'),
        get_string('participants', 'local_cmc_lms'),
        get_string('passed', 'local_cmc_lms'),
        get_string('passpercentage', 'local_cmc_lms'),
        get_string('averagefinalgrade', 'local_cmc_lms'),
    ];

    foreach ($evaluationsummaries as $row) {
        $evaluationtable->data[] = [
            empty($row->programname) ? get_string('notavailable', 'local_cmc_lms') : format_string($row->programname),
            html_writer::link(new moodle_url('/course/view.php', ['id' => $row->courseid]), format_string($row->coursefullname)),
            format_string($row->quizname),
            $row->attempts,
            $row->participants,
            $row->passed,
            format_float($row->passrate, 2) . '%',
            $row->averagefinalgrade === null ? get_string('notavailable', 'local_cmc_lms') : format_float($row->averagefinalgrade, 2),
        ];
    }

    echo html_writer::table($evaluationtable);
}

echo $OUTPUT->heading(get_string('certificatereport', 'local_cmc_lms'), 3);
if (empty($certificatereports)) {
    echo $OUTPUT->notification(get_string('nocertificates', 'local_cmc_lms'), 'info');
} else {
    $certificatetable = new html_table();
    $certificatetable->head = [
        get_string('certificatecode', 'local_cmc_lms'),
        get_string('user'),
        get_string('course', 'local_cmc_lms'),
        get_string('program', 'local_cmc_lms'),
        get_string('company', 'local_cmc_lms'),
        get_string('status'),
        get_string('issueddate', 'local_cmc_lms'),
        get_string('revokeddate', 'local_cmc_lms'),
    ];

    foreach ($certificatereports as $row) {
        $statuskey = $row->status;
        $status = get_string_manager()->string_exists($statuskey, 'local_cmc_lms')
            ? get_string($statuskey, 'local_cmc_lms')
            : s($statuskey);
        $certificatetable->data[] = [
            html_writer::link(new moodle_url('/local/cmc_lms/verify_certificate.php', ['t' => $row->code]), s($row->code)),
            html_writer::link(new moodle_url('/user/profile.php', ['id' => $row->userid]), s($row->userfullname)),
            html_writer::link(new moodle_url('/course/view.php', ['id' => $row->courseid]), format_string($row->coursefullname)),
            empty($row->programname) ? get_string('notavailable', 'local_cmc_lms') : format_string($row->programname),
            empty($row->companyname) ? get_string('notavailable', 'local_cmc_lms') : format_string($row->companyname),
            $status,
            userdate($row->timeissued),
            empty($row->timerevoked) ? get_string('notavailable', 'local_cmc_lms') : userdate($row->timerevoked),
        ];
    }

    echo html_writer::table($certificatetable);
}

echo $OUTPUT->footer();
