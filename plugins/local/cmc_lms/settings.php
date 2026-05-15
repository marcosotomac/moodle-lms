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
    'local/cmc_lms:manageprogramcontent',
    'local/cmc_lms:manageattendance',
    'local/cmc_lms:manageprogramroles',
    'local/cmc_lms:teachprograms',
    'local/cmc_lms:viewreports',
    'local/cmc_lms:viewcertificates',
    'local/cmc_lms:viewevaluationreports',
    'local/cmc_lms:viewstudentpanel',
];

if ($hassiteconfig || has_any_capability($capabilities, context_system::instance())) {
    $ADMIN->add('localplugins', new admin_category('local_cmc_lms', get_string('pluginname', 'local_cmc_lms')));

    if ($hassiteconfig) {
        $settings = new admin_settingpage('local_cmc_lms_integrations', get_string('integrations', 'local_cmc_lms'));
        $settings->add(new admin_setting_heading(
            'local_cmc_lms/zoomintegration',
            get_string('zoomintegration', 'local_cmc_lms'),
            get_string('zoomintegration_desc', 'local_cmc_lms')
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_cmc_lms/zoom_enabled',
            get_string('zoomenabled', 'local_cmc_lms'),
            get_string('zoomenabled_desc', 'local_cmc_lms'),
            0
        ));
        $settings->add(new admin_setting_configtext(
            'local_cmc_lms/zoom_accountid',
            get_string('zoomaccountid', 'local_cmc_lms'),
            get_string('zoomaccountid_desc', 'local_cmc_lms'),
            '',
            PARAM_TEXT
        ));
        $settings->add(new admin_setting_configtext(
            'local_cmc_lms/zoom_clientid',
            get_string('zoomclientid', 'local_cmc_lms'),
            get_string('zoomclientid_desc', 'local_cmc_lms'),
            '',
            PARAM_TEXT
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_cmc_lms/zoom_clientsecret',
            get_string('zoomclientsecret', 'local_cmc_lms'),
            get_string('zoomclientsecret_desc', 'local_cmc_lms'),
            ''
        ));
        $settings->add(new admin_setting_configtext(
            'local_cmc_lms/zoom_userid',
            get_string('zoomuserid', 'local_cmc_lms'),
            get_string('zoomuserid_desc', 'local_cmc_lms'),
            'me',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_heading(
            'local_cmc_lms/googlemeetintegration',
            get_string('googlemeetintegration', 'local_cmc_lms'),
            get_string('googlemeetintegration_desc', 'local_cmc_lms')
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_cmc_lms/google_meet_enabled',
            get_string('googlemeetenabled', 'local_cmc_lms'),
            get_string('googlemeetenabled_desc', 'local_cmc_lms'),
            0
        ));
        $settings->add(new admin_setting_configtext(
            'local_cmc_lms/google_meet_service_account',
            get_string('googlemeetserviceaccount', 'local_cmc_lms'),
            get_string('googlemeetserviceaccount_desc', 'local_cmc_lms'),
            '',
            PARAM_EMAIL
        ));
        $settings->add(new admin_setting_configtextarea(
            'local_cmc_lms/google_meet_private_key',
            get_string('googlemeetprivatekey', 'local_cmc_lms'),
            get_string('googlemeetprivatekey_desc', 'local_cmc_lms'),
            '',
            PARAM_RAW,
            80,
            8
        ));
        $settings->add(new admin_setting_configtext(
            'local_cmc_lms/google_meet_subject',
            get_string('googlemeetsubject', 'local_cmc_lms'),
            get_string('googlemeetsubject_desc', 'local_cmc_lms'),
            '',
            PARAM_EMAIL
        ));
        $ADMIN->add('local_cmc_lms', $settings);
    }

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
            'local_cmc_lms_contentlibrary',
            get_string('contentlibrary', 'local_cmc_lms'),
            new moodle_url('/local/cmc_lms/content_library.php'),
            'local/cmc_lms:manageprogramcontent'
        )
    );
    $ADMIN->add(
        'local_cmc_lms',
        new admin_externalpage(
            'local_cmc_lms_programroles',
            get_string('programroles', 'local_cmc_lms'),
            new moodle_url('/local/cmc_lms/program_roles.php'),
            'local/cmc_lms:manageprogramroles'
        )
    );
    $ADMIN->add(
        'local_cmc_lms',
        new admin_externalpage(
            'local_cmc_lms_attendance',
            get_string('attendance', 'local_cmc_lms'),
            new moodle_url('/local/cmc_lms/attendance.php'),
            'local/cmc_lms:manageattendance'
        )
    );
    $ADMIN->add(
        'local_cmc_lms',
        new admin_externalpage(
            'local_cmc_lms_certificates',
            get_string('certificates', 'local_cmc_lms'),
            new moodle_url('/local/cmc_lms/certificates.php'),
            'local/cmc_lms:viewcertificates'
        )
    );
    $ADMIN->add(
        'local_cmc_lms',
        new admin_externalpage(
            'local_cmc_lms_evaluations',
            get_string('evaluations', 'local_cmc_lms'),
            new moodle_url('/local/cmc_lms/evaluations.php'),
            'local/cmc_lms:viewevaluationreports'
        )
    );
    $ADMIN->add(
        'local_cmc_lms',
        new admin_externalpage(
            'local_cmc_lms_studentpanel',
            get_string('studentpanel', 'local_cmc_lms'),
            new moodle_url('/local/cmc_lms/student.php'),
            'local/cmc_lms:viewstudentpanel'
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
