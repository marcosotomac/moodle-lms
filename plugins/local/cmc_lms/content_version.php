<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Add immutable ISO versions to reusable CMC content items.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_cmc_lms\form\content_version_form;
use local_cmc_lms\local\content_repository;

$contentitemid = required_param('contentitemid', PARAM_INT);
$returnurl = new moodle_url('/local/cmc_lms/content_library.php');

admin_externalpage_setup('local_cmc_lms_contentlibrary');

$context = context_system::instance();
require_capability('local/cmc_lms:manageprogramcontent', $context);

$repository = new content_repository();
$item = $repository->get_item($contentitemid);

$PAGE->set_url(new moodle_url('/local/cmc_lms/content_version.php', ['contentitemid' => $contentitemid]));
$PAGE->set_title(get_string('addcontentversion', 'local_cmc_lms'));

$mform = new content_version_form($PAGE->url->out(false), ['contentitemid' => $contentitemid]);
$mform->set_data(['contentitemid' => $contentitemid]);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $mform->get_data()) {
    $repository->create_version($contentitemid, $data, (int)$USER->id);
    redirect($returnurl, get_string('saved', 'local_cmc_lms'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addcontentversion', 'local_cmc_lms') . ': ' . format_string($item->name));
echo html_writer::div(html_writer::link($returnurl, get_string('backtocontentlibrary', 'local_cmc_lms')), 'mb-3');
$mform->display();
echo $OUTPUT->footer();
