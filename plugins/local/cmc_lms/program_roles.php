<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Program coordinator and teacher assignment administration page.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use local_cmc_lms\form\program_role_form;
use local_cmc_lms\local\program_repository;
use local_cmc_lms\local\role_repository;

$programid = optional_param('programid', 0, PARAM_INT);

admin_externalpage_setup('local_cmc_lms_programroles');

$context = context_system::instance();
require_capability('local/cmc_lms:manageprogramroles', $context);

$programrepository = new program_repository();
$rolerepository = new role_repository();

$programoptions = [];
foreach ($programrepository->list_with_courses(false) as $program) {
    $programoptions[$program->id] = format_string($program->name) . ' (' . s($program->shortname) . ')';
}

$useroptions = [];
$users = $DB->get_records_select(
    'user',
    'deleted = 0 AND id <> :guestid',
    ['guestid' => $CFG->siteguest],
    'lastname ASC, firstname ASC',
    'id, firstname, lastname, email, username'
);
foreach ($users as $user) {
    $useroptions[$user->id] = fullname($user) . ' (' . s($user->email) . ')';
}

$PAGE->set_url(new moodle_url('/local/cmc_lms/program_roles.php', ['programid' => $programid]));
$PAGE->set_title(get_string('programroles', 'local_cmc_lms'));

$mform = new program_role_form($PAGE->url->out(false), ['programs' => $programoptions, 'users' => $useroptions]);
if ($programid > 0) {
    $mform->set_data(['programid' => $programid]);
}

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/cmc_lms/programs.php'));
}

if ($data = $mform->get_data()) {
    $programrepository->get((int)$data->programid);
    $DB->get_record('user', ['id' => (int)$data->userid, 'deleted' => 0], '*', MUST_EXIST);
    $rolerepository->assign_program_role((int)$data->programid, (int)$data->userid, $data->cmcrole, !empty($data->active));
    redirect(
        new moodle_url('/local/cmc_lms/program_roles.php', ['programid' => (int)$data->programid]),
        get_string('saved', 'local_cmc_lms'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$assignments = $programid > 0 ? $rolerepository->list_program_roles($programid) : [];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('programroles', 'local_cmc_lms'));
echo html_writer::div(html_writer::link(new moodle_url('/local/cmc_lms/programs.php'), get_string('programs', 'local_cmc_lms')), 'mb-3');
$mform->display();

if ($programid > 0) {
    if (empty($assignments)) {
        echo $OUTPUT->notification(get_string('noprogramroles', 'local_cmc_lms'), 'info');
    } else {
        $table = new html_table();
        $table->head = [
            get_string('program', 'local_cmc_lms'),
            get_string('user', 'local_cmc_lms'),
            get_string('cmcrole', 'local_cmc_lms'),
            get_string('status', 'local_cmc_lms'),
        ];
        foreach ($assignments as $assignment) {
            $table->data[] = [
                $programoptions[$assignment->programid] ?? $assignment->programid,
                fullname($assignment) . ' (' . s($assignment->email) . ')',
                get_string(role_repository::display_key($assignment->cmcrole), 'local_cmc_lms'),
                $assignment->active ? get_string('active', 'local_cmc_lms') : get_string('inactive', 'local_cmc_lms'),
            ];
        }
        echo html_writer::table($table);
    }
}

echo $OUTPUT->footer();
