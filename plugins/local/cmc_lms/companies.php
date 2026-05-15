<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Client companies administration page.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use local_cmc_lms\local\access_helper;
use local_cmc_lms\local\company_repository;

$context = context_system::instance();
$access = new access_helper();
if (has_capability('local/cmc_lms:viewcompanies', $context)) {
    admin_externalpage_setup('local_cmc_lms_companies');
} else {
    require_login();
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/cmc_lms/companies.php'));
    $PAGE->set_title(get_string('companies', 'local_cmc_lms'));
    $PAGE->set_heading(get_string('companies', 'local_cmc_lms'));
}
if (!$access->can_view_any_company()) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('companies', 'local_cmc_lms'));
}

$repository = new company_repository();
$companies = $access->filter_companies($repository->list(false));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('companies', 'local_cmc_lms'));

if (has_capability('local/cmc_lms:managecompanies', $context)) {
    echo html_writer::div(
        $OUTPUT->single_button(new moodle_url('/local/cmc_lms/company_edit.php'), get_string('addcompany', 'local_cmc_lms')),
        'mb-3'
    );
}

if (empty($companies)) {
    echo $OUTPUT->notification(get_string('nocompanies', 'local_cmc_lms'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('companyname', 'local_cmc_lms'),
    get_string('shortname'),
    get_string('sector', 'local_cmc_lms'),
    get_string('country', 'local_cmc_lms'),
    get_string('status', 'local_cmc_lms'),
    get_string('actions', 'local_cmc_lms'),
];

foreach ($companies as $company) {
    $actions = '';
    if (has_capability('local/cmc_lms:managecompanies', $context)) {
        $actions = html_writer::link(
            new moodle_url('/local/cmc_lms/company_edit.php', ['id' => $company->id]),
            get_string('edit', 'local_cmc_lms')
        );
        $actions .= ' | ' . html_writer::link(
            new moodle_url('/local/cmc_lms/company_users.php', ['companyid' => $company->id]),
            get_string('users', 'local_cmc_lms')
        );
    } else if ($access->can_view_company((int)$company->id)) {
        $actions = html_writer::link(
            new moodle_url('/local/cmc_lms/company_users.php', ['companyid' => $company->id]),
            get_string('users', 'local_cmc_lms')
        );
    }

    $table->data[] = [
        format_string($company->name),
        s($company->shortname),
        s($company->sector ?? ''),
        s($company->country ?? ''),
        $company->active ? get_string('active', 'local_cmc_lms') : get_string('inactive', 'local_cmc_lms'),
        $actions,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
