<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * CMC attendance administration page.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use local_cmc_lms\form\attendance_form;
use local_cmc_lms\local\program_repository;

$programcourseid = optional_param('programcourseid', 0, PARAM_INT);

admin_externalpage_setup('local_cmc_lms_attendance');

$context = context_system::instance();
require_capability('local/cmc_lms:manageattendance', $context);

$repository = new program_repository();
$links = $repository->list_attendance_course_links();
$linkoptions = [];
foreach ($links as $link) {
    $label = format_string($link->programname) . ' / ' . format_string($link->coursefullname);
    if (!empty($link->schedulestart)) {
        $label .= ' — ' . userdate($link->schedulestart, get_string('strftimedatetimeshort', 'langconfig'));
    }
    $linkoptions[(int)$link->id] = $label;
}

if ($programcourseid > 0 && !isset($linkoptions[$programcourseid])) {
    throw new moodle_exception('invalidattendancecourse', 'local_cmc_lms');
}

$selectedlink = $programcourseid > 0 ? $repository->get_program_course_link($programcourseid) : null;
$roster = $programcourseid > 0 ? $repository->get_attendance_roster($programcourseid) : [];
$history = $programcourseid > 0 ? $repository->get_attendance_with_users($programcourseid) : [];

$PAGE->set_url(new moodle_url('/local/cmc_lms/attendance.php', ['programcourseid' => $programcourseid]));
$PAGE->set_title(get_string('attendance', 'local_cmc_lms'));

$form = null;
if ($selectedlink !== null && !empty($roster)) {
    $form = new attendance_form($PAGE->url->out(false), ['roster' => $roster]);
    $form->set_data([
        'programcourseid' => $programcourseid,
        'timetaken' => time(),
    ]);

    if ($data = $form->get_data()) {
        $saved = 0;
        foreach ($roster as $user) {
            $fieldname = 'status_' . (int)$user->id;
            $status = $data->{$fieldname} ?? '';
            if ($status === '') {
                continue;
            }
            $repository->record_attendance($programcourseid, (int)$user->id, $status, (int)$data->timetaken);
            $saved++;
        }

        redirect(
            new moodle_url('/local/cmc_lms/attendance.php', ['programcourseid' => $programcourseid]),
            get_string('attendancerecordssaved', 'local_cmc_lms', $saved),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('attendance', 'local_cmc_lms'));

if (empty($linkoptions)) {
    echo $OUTPUT->notification(get_string('noattendancecourses', 'local_cmc_lms'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$selector = html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out(false), 'class' => 'mb-3']);
$selector .= html_writer::label(get_string('attendancecourse', 'local_cmc_lms'), 'programcourseid', false, ['class' => 'me-2']);
$selector .= html_writer::select($linkoptions, 'programcourseid', $programcourseid, false, ['id' => 'programcourseid']);
$selector .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('view'), 'class' => 'btn btn-secondary ms-2']);
$selector .= html_writer::end_tag('form');
echo $selector;

if ($selectedlink === null) {
    echo $OUTPUT->notification(get_string('selectattendancecourse', 'local_cmc_lms'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$details = [
    get_string('program', 'local_cmc_lms') => format_string($selectedlink->programname),
    get_string('course', 'local_cmc_lms') => html_writer::link(
        new moodle_url('/course/view.php', ['id' => $selectedlink->courseid]),
        format_string($selectedlink->coursefullname)
    ),
];
if (!empty($selectedlink->schedulestart)) {
    $details[get_string('schedulestart', 'local_cmc_lms')] = userdate(
        $selectedlink->schedulestart,
        get_string('strftimedatetimeshort', 'langconfig')
    );
}
if (!empty($selectedlink->scheduleend)) {
    $details[get_string('scheduleend', 'local_cmc_lms')] = userdate(
        $selectedlink->scheduleend,
        get_string('strftimedatetimeshort', 'langconfig')
    );
}
if (!empty($selectedlink->liveprovider)) {
    $details[get_string('liveprovider', 'local_cmc_lms')] = s($selectedlink->liveprovider);
}
if (!empty($selectedlink->liveurl)) {
    $details[get_string('liveurl', 'local_cmc_lms')] = html_writer::link($selectedlink->liveurl, s($selectedlink->liveurl));
}

$detailtable = new html_table();
$detailtable->attributes['class'] = 'generaltable mb-3';
foreach ($details as $name => $value) {
    $detailtable->data[] = [$name, $value];
}
echo html_writer::table($detailtable);

if (empty($roster)) {
    echo $OUTPUT->notification(get_string('noattendanceroster', 'local_cmc_lms'), 'info');
} else if ($form !== null) {
    $form->display();
}

echo $OUTPUT->heading(get_string('attendancehistory', 'local_cmc_lms'), 3);
if (empty($history)) {
    echo $OUTPUT->notification(get_string('noattendancehistory', 'local_cmc_lms'), 'info');
} else {
    $historytable = new html_table();
    $historytable->head = [
        get_string('user', 'local_cmc_lms'),
        get_string('status', 'local_cmc_lms'),
        get_string('attendancedate', 'local_cmc_lms'),
        get_string('timecreated', 'local_cmc_lms'),
    ];

    foreach ($history as $row) {
        $historytable->data[] = [
            fullname($row) . ' (' . s($row->email) . ')',
            get_string('attendance' . $row->status, 'local_cmc_lms'),
            userdate($row->timetaken, get_string('strftimedatetimeshort', 'langconfig')),
            userdate($row->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        ];
    }
    echo html_writer::table($historytable);
}

echo $OUTPUT->footer();
