<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Create reusable CMC content items.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_cmc_lms\form\content_item_form;
use local_cmc_lms\local\content_repository;

$returnurl = new moodle_url('/local/cmc_lms/content_library.php');

admin_externalpage_setup('local_cmc_lms_contentlibrary');

$context = context_system::instance();
require_capability('local/cmc_lms:manageprogramcontent', $context);

$PAGE->set_url(new moodle_url('/local/cmc_lms/content_item.php'));
$PAGE->set_title(get_string('addcontentitem', 'local_cmc_lms'));

$mform = new content_item_form($PAGE->url->out(false));

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $mform->get_data()) {
    (new content_repository())->create_item_with_initial_version($data, (int)$USER->id);
    redirect($returnurl, get_string('saved', 'local_cmc_lms'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addcontentitem', 'local_cmc_lms'));
echo html_writer::div(html_writer::link($returnurl, get_string('backtocontentlibrary', 'local_cmc_lms')), 'mb-3');
$mform->display();
echo $OUTPUT->footer();
