<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Training program edit page.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_cmc_lms\form\program_form;
use local_cmc_lms\local\program_repository;

$id = optional_param('id', 0, PARAM_INT);
$returnurl = new moodle_url('/local/cmc_lms/programs.php');

admin_externalpage_setup('local_cmc_lms_programs');

$context = context_system::instance();
require_capability('local/cmc_lms:manageprograms', $context);

$repository = new program_repository();
$program = $id ? $repository->get($id) : null;

$title = $id ? get_string('editprogram', 'local_cmc_lms') : get_string('addprogram', 'local_cmc_lms');
$PAGE->set_url(new moodle_url('/local/cmc_lms/program_edit.php', ['id' => $id]));
$PAGE->set_title($title);

$mform = new program_form($PAGE->url->out(false), ['id' => $id]);
if ($program) {
    $mform->set_data($program);
}

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $mform->get_data()) {
    $data->active = empty($data->active) ? 0 : 1;
    if (!empty($data->id)) {
        $repository->update($data);
    } else {
        $repository->create($data);
    }
    redirect($returnurl, get_string('saved', 'local_cmc_lms'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$mform->display();
echo $OUTPUT->footer();
