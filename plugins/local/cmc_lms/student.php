<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * CMC student panel.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

use local_cmc_lms\local\access_helper;
use local_cmc_lms\local\student_repository;

$userid = optional_param('userid', 0, PARAM_INT);

require_login();

$context = context_system::instance();
$targetuserid = $userid > 0 ? $userid : (int)$USER->id;
$access = new access_helper();
if (!$access->can_view_student($targetuserid)) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('studentpanel', 'local_cmc_lms'));
}

$targetuser = $DB->get_record('user', ['id' => $targetuserid, 'deleted' => 0], '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/cmc_lms/student.php', ['userid' => $targetuserid]));
$PAGE->set_title(get_string('studentpanel', 'local_cmc_lms'));
$PAGE->set_heading(get_string('studentpanel', 'local_cmc_lms'));

$repository = new student_repository();
$courses = $repository->get_active_courses($targetuserid);
$certificates = $repository->get_certificates($targetuserid);
$notifications = $repository->get_notifications($targetuserid);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('studentpanel', 'local_cmc_lms'));
echo $OUTPUT->heading(fullname($targetuser), 3);

echo $OUTPUT->heading(get_string('activecmccourses', 'local_cmc_lms'), 4);
if (empty($courses)) {
    echo $OUTPUT->notification(get_string('noactivecmccourses', 'local_cmc_lms'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('course', 'local_cmc_lms'),
        get_string('program', 'local_cmc_lms'),
        get_string('company', 'local_cmc_lms'),
        get_string('status', 'local_cmc_lms'),
        get_string('completionstatus', 'local_cmc_lms'),
        get_string('progress', 'local_cmc_lms'),
    ];
    foreach ($courses as $course) {
        $table->data[] = [
            html_writer::link(new moodle_url('/course/view.php', ['id' => (int)$course->courseid]), format_string($course->coursename)),
            format_string($course->programname),
            $course->companyname ? format_string($course->companyname) : get_string('notavailable', 'local_cmc_lms'),
            s($course->enrolmentstatuslabel),
            s($course->completionstatus),
            format_float($course->progresspercentage, 2) . '%',
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->heading(get_string('mycertificates', 'local_cmc_lms'), 4);
if (empty($certificates)) {
    echo $OUTPUT->notification(get_string('nocertificatesforstudent', 'local_cmc_lms'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('certificatetitle', 'local_cmc_lms'),
        get_string('course', 'local_cmc_lms'),
        get_string('program', 'local_cmc_lms'),
        get_string('issueddate', 'local_cmc_lms'),
        get_string('certificatecode', 'local_cmc_lms'),
        get_string('actions', 'local_cmc_lms'),
    ];
    foreach ($certificates as $certificate) {
        $actions = [
            html_writer::link($certificate->downloadurl, get_string('certificatedownload', 'local_cmc_lms')),
            html_writer::link($certificate->verificationurl, get_string('verifycertificate', 'local_cmc_lms')),
        ];
        $table->data[] = [
            format_string($certificate->certificatetitle),
            format_string($certificate->coursefullname),
            $certificate->programname ? format_string($certificate->programname) : get_string('notavailable', 'local_cmc_lms'),
            userdate((int)$certificate->timeissued),
            s($certificate->code),
            implode(' | ', $actions),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->heading(get_string('recentnotifications', 'local_cmc_lms'), 4);
if (empty($notifications)) {
    echo $OUTPUT->notification(get_string('nonotifications', 'local_cmc_lms'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('type', 'local_cmc_lms'),
        get_string('subject', 'local_cmc_lms'),
        get_string('message', 'local_cmc_lms'),
        get_string('timecreated', 'local_cmc_lms'),
        get_string('status', 'local_cmc_lms'),
    ];
    foreach ($notifications as $notification) {
        $typekey = 'notificationtype' . str_replace('_', '', $notification->type);
        $typename = get_string_manager()->string_exists($typekey, 'local_cmc_lms')
            ? get_string($typekey, 'local_cmc_lms')
            : s($notification->type);
        $table->data[] = [
            $typename,
            s($notification->subject),
            format_text($notification->message, FORMAT_PLAIN),
            userdate((int)$notification->timecreated),
            s($notification->status),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
