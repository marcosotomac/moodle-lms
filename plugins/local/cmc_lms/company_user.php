<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Add a Moodle user to a CMC client company.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_cmc_lms\form\company_user_form;
use local_cmc_lms\local\company_repository;

$companyid = required_param('companyid', PARAM_INT);
$returnurl = new moodle_url('/local/cmc_lms/company_users.php', ['companyid' => $companyid]);

admin_externalpage_setup('local_cmc_lms_companies');

$context = context_system::instance();
require_capability('local/cmc_lms:managecompanies', $context);

$repository = new company_repository();
$company = $repository->get($companyid);

$PAGE->set_url(new moodle_url('/local/cmc_lms/company_user.php', ['companyid' => $companyid]));
$PAGE->set_title(get_string('addcompanyuser', 'local_cmc_lms'));

$useroptions = [];
$records = $DB->get_records_select('user', 'deleted = 0 AND id <> :guestid', ['guestid' => $CFG->siteguest], 'lastname ASC, firstname ASC', 'id, firstname, lastname, email, username');
foreach ($records as $user) {
    $useroptions[$user->id] = fullname($user) . ' (' . s($user->email) . ')';
}

$mform = new company_user_form($PAGE->url->out(false), ['users' => $useroptions]);
$mform->set_data(['companyid' => $companyid]);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $mform->get_data()) {
    $repository->add_user((int)$data->companyid, (int)$data->userid, $data->companyrole, !empty($data->active));
    redirect($returnurl, get_string('saved', 'local_cmc_lms'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addcompanyuser', 'local_cmc_lms') . ': ' . format_string($company->name));
$mform->display();
echo $OUTPUT->footer();
