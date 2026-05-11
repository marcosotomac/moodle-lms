<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Link Moodle courses to CMC programs.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_cmc_lms\form\program_course_form;
use local_cmc_lms\local\program_repository;

$programid = required_param('programid', PARAM_INT);
$returnurl = new moodle_url('/local/cmc_lms/programs.php');

admin_externalpage_setup('local_cmc_lms_programs');

$context = context_system::instance();
require_capability('local/cmc_lms:manageprograms', $context);

$repository = new program_repository();
$program = $repository->get($programid);

$PAGE->set_url(new moodle_url('/local/cmc_lms/program_course.php', ['programid' => $programid]));
$PAGE->set_title(get_string('addcourse', 'local_cmc_lms'));

$courses = [];
$courserecords = $DB->get_records_select('course', 'id <> :siteid', ['siteid' => SITEID], 'fullname ASC', 'id, fullname, shortname');
foreach ($courserecords as $course) {
    $courses[$course->id] = format_string($course->fullname) . ' (' . s($course->shortname) . ')';
}

$mform = new program_course_form($PAGE->url->out(false), ['programid' => $programid, 'courses' => $courses]);
$mform->set_data(['programid' => $programid]);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $mform->get_data()) {
    $repository->add_course((int)$data->programid, (int)$data->courseid, (int)$data->sortorder, !empty($data->required));
    redirect($returnurl, get_string('saved', 'local_cmc_lms'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addcourse', 'local_cmc_lms') . ': ' . format_string($program->name));
echo html_writer::div(html_writer::link($returnurl, get_string('backtoprograms', 'local_cmc_lms')), 'mb-3');
$mform->display();
echo $OUTPUT->footer();
