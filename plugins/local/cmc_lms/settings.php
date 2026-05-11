<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin navigation entries for local_cmc_lms.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/cmc_lms:viewcompanies',
    'local/cmc_lms:viewprograms',
    'local/cmc_lms:viewreports',
];

if ($hassiteconfig || has_any_capability($capabilities, context_system::instance())) {
    $ADMIN->add('localplugins', new admin_category('local_cmc_lms', get_string('pluginname', 'local_cmc_lms')));
    $ADMIN->add(
        'local_cmc_lms',
        new admin_externalpage(
            'local_cmc_lms_companies',
            get_string('companies', 'local_cmc_lms'),
            new moodle_url('/local/cmc_lms/companies.php'),
            'local/cmc_lms:viewcompanies'
        )
    );
    $ADMIN->add(
        'local_cmc_lms',
        new admin_externalpage(
            'local_cmc_lms_programs',
            get_string('programs', 'local_cmc_lms'),
            new moodle_url('/local/cmc_lms/programs.php'),
            'local/cmc_lms:viewprograms'
        )
    );
    $ADMIN->add(
        'local_cmc_lms',
        new admin_externalpage(
            'local_cmc_lms_reports',
            get_string('b2breports', 'local_cmc_lms'),
            new moodle_url('/local/cmc_lms/reports.php'),
            'local/cmc_lms:viewreports'
        )
    );
}
