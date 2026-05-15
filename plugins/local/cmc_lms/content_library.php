<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Reusable CMC content library administration page.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use local_cmc_lms\local\content_repository;

admin_externalpage_setup('local_cmc_lms_contentlibrary');

$context = context_system::instance();
require_capability('local/cmc_lms:manageprogramcontent', $context);

$repository = new content_repository();
$items = $repository->list_items_with_latest_version(false);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('contentlibrary', 'local_cmc_lms'));
echo html_writer::div(
    $OUTPUT->single_button(new moodle_url('/local/cmc_lms/content_item.php'), get_string('addcontentitem', 'local_cmc_lms')),
    'mb-3'
);

if (empty($items)) {
    echo $OUTPUT->notification(get_string('nocontentitems', 'local_cmc_lms'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('contentitemname', 'local_cmc_lms'),
    get_string('contentitemcode', 'local_cmc_lms'),
    get_string('contentformat', 'local_cmc_lms'),
    get_string('isoreference', 'local_cmc_lms'),
    get_string('latestcontentversion', 'local_cmc_lms'),
    get_string('status', 'local_cmc_lms'),
    get_string('actions', 'local_cmc_lms'),
];

foreach ($items as $item) {
    $versions = [];
    foreach ($repository->list_versions((int)$item->id) as $version) {
        $versions[] = s($version->versioncode) . ' · ' . get_string('contentversionstatus' . $version->status, 'local_cmc_lms')
            . (empty($version->effectivefrom) ? '' : ' · ' . userdate($version->effectivefrom, get_string('strftimedate', 'langconfig')));
    }
    $versionhistory = empty($versions) ? get_string('noversions', 'local_cmc_lms') : html_writer::alist($versions);

    $actions = [
        html_writer::link(
            new moodle_url('/local/cmc_lms/content_version.php', ['contentitemid' => $item->id]),
            get_string('addcontentversion', 'local_cmc_lms')
        ),
    ];
    if (!empty($item->sourceurl)) {
        $actions[] = html_writer::link($item->sourceurl, get_string('contentsourceurl', 'local_cmc_lms'));
    }

    $table->data[] = [
        format_string($item->name),
        s($item->code),
        get_string('contentformat' . $item->contenttype, 'local_cmc_lms'),
        s($item->isoreference ?: get_string('none')),
        $versionhistory,
        $item->active ? get_string('active', 'local_cmc_lms') : get_string('inactive', 'local_cmc_lms'),
        implode(' | ', $actions),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
