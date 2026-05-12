<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Training programs administration page.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use local_cmc_lms\local\program_repository;

admin_externalpage_setup('local_cmc_lms_programs');

$context = context_system::instance();
require_capability('local/cmc_lms:viewprograms', $context);

$repository = new program_repository();
$programs = $repository->list_with_courses(false);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('programs', 'local_cmc_lms'));

if (has_capability('local/cmc_lms:manageprograms', $context)) {
    echo html_writer::div(
        $OUTPUT->single_button(new moodle_url('/local/cmc_lms/program_edit.php'), get_string('addprogram', 'local_cmc_lms')),
        'mb-3'
    );
}

if (empty($programs)) {
    echo $OUTPUT->notification(get_string('noprograms', 'local_cmc_lms'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('programname', 'local_cmc_lms'),
    get_string('shortname'),
    get_string('versioncode', 'local_cmc_lms'),
    get_string('modality', 'local_cmc_lms'),
    get_string('plannedhours', 'local_cmc_lms'),
    get_string('courses', 'local_cmc_lms'),
    get_string('status', 'local_cmc_lms'),
    get_string('actions', 'local_cmc_lms'),
];

foreach ($programs as $program) {
    $courses = [];
    foreach ($program->courses as $course) {
        $metadata = [];
        if (!empty($course->contentlabel)) {
            $metadata[] = s($course->contentlabel);
        }
        if (!empty($course->contentformat) && $course->contentformat !== 'other') {
            $metadata[] = s($course->contentformat);
        }
        if (!empty($course->schedulestart)) {
            $metadata[] = userdate($course->schedulestart, get_string('strftimedatetimeshort', 'langconfig'));
        }
        if (!empty($course->liveprovider)) {
            $status = $course->liveintegrationstatus ?? 'manual';
            $statuskey = 'liveintegrationstatus' . $status;
            $statuslabel = get_string_manager()->string_exists($statuskey, 'local_cmc_lms')
                ? get_string($statuskey, 'local_cmc_lms')
                : s($status);
            $metadata[] = s($course->liveprovider) . ': ' . $statuslabel;
        }
        if (!empty($course->liveurl)) {
            $metadata[] = html_writer::link($course->liveurl, get_string('liveurl', 'local_cmc_lms'));
        }
        $courses[] = s($course->fullname) . ' (' . s($course->shortname) . ')' . (empty($metadata) ? '' : ' — ' . implode(' · ', $metadata));
    }

    if (empty($courses)) {
        $courselist = get_string('nocourses', 'local_cmc_lms');
    } else {
        $courselist = html_writer::alist($courses);
    }

    $actions = [];
    if (has_capability('local/cmc_lms:manageprograms', $context)) {
        $actions[] = html_writer::link(
            new moodle_url('/local/cmc_lms/program_edit.php', ['id' => $program->id]),
            get_string('edit', 'local_cmc_lms')
        );
        $actions[] = html_writer::link(
            new moodle_url('/local/cmc_lms/program_course.php', ['programid' => $program->id]),
            get_string('addcourse', 'local_cmc_lms')
        );
    }

    $table->data[] = [
        format_string($program->name),
        s($program->shortname),
        s($program->versioncode ?? 'v1'),
        get_string('modality' . ($program->modality ?? 'async'), 'local_cmc_lms'),
        format_float((float)($program->plannedhours ?? 0), 2),
        $courselist,
        $program->active ? get_string('active', 'local_cmc_lms') : get_string('inactive', 'local_cmc_lms'),
        implode(' | ', $actions),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
