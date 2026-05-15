<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Company users administration page.
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
use local_cmc_lms\local\role_repository;

$companyid = required_param('companyid', PARAM_INT);
$returnurl = new moodle_url('/local/cmc_lms/companies.php');

$context = context_system::instance();
$access = new access_helper();
if (has_capability('local/cmc_lms:viewcompanies', $context)) {
    admin_externalpage_setup('local_cmc_lms_companies');
} else {
    require_login();
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/cmc_lms/company_users.php', ['companyid' => $companyid]));
    $PAGE->set_title(get_string('users', 'local_cmc_lms'));
    $PAGE->set_heading(get_string('users', 'local_cmc_lms'));
}
if (!$access->can_view_company($companyid)) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('companies', 'local_cmc_lms'));
}

$repository = new company_repository();
$company = $repository->get($companyid);
$users = $repository->list_users($companyid);

$PAGE->set_url(new moodle_url('/local/cmc_lms/company_users.php', ['companyid' => $companyid]));
$PAGE->set_title(get_string('users', 'local_cmc_lms') . ': ' . format_string($company->name));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('users', 'local_cmc_lms') . ': ' . format_string($company->name));
echo html_writer::div(html_writer::link($returnurl, get_string('companies', 'local_cmc_lms')), 'mb-3');

if (has_capability('local/cmc_lms:managecompanies', $context)) {
    echo html_writer::div(
        $OUTPUT->single_button(
            new moodle_url('/local/cmc_lms/company_user.php', ['companyid' => $companyid]),
            get_string('addcompanyuser', 'local_cmc_lms')
        ),
        'mb-3'
    );
}

if (empty($users)) {
    echo $OUTPUT->notification(get_string('nousers'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('user', 'local_cmc_lms'),
    get_string('email'),
    get_string('companyrole', 'local_cmc_lms'),
    get_string('status', 'local_cmc_lms'),
];

foreach ($users as $user) {
    $table->data[] = [
        fullname($user),
        s($user->email),
        get_string(role_repository::display_key($user->companyrole), 'local_cmc_lms'),
        $user->active ? get_string('active', 'local_cmc_lms') : get_string('inactive', 'local_cmc_lms'),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
