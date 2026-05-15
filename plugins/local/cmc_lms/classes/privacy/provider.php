<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;

/**
 * Privacy provider for local_cmc_lms.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadata_provider {
    /**
     * Return metadata stored by this plugin.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_cmc_lms_company_user', [
            'companyid' => 'privacy:metadata:company_user:companyid',
            'userid' => 'privacy:metadata:company_user:userid',
            'companyrole' => 'privacy:metadata:company_user:companyrole',
        ], 'privacy:metadata:company_user');

        $collection->add_database_table('local_cmc_lms_cert', [
            'userid' => 'privacy:metadata:cert:userid',
            'courseid' => 'privacy:metadata:cert:courseid',
            'companyid' => 'privacy:metadata:cert:companyid',
            'programid' => 'privacy:metadata:cert:programid',
            'code' => 'privacy:metadata:cert:code',
            'verifytoken' => 'privacy:metadata:cert:verifytoken',
            'timeissued' => 'privacy:metadata:cert:timeissued',
            'issuerid' => 'privacy:metadata:cert:issuerid',
            'certificatetitle' => 'privacy:metadata:cert:certificatetitle',
            'coursehours' => 'privacy:metadata:cert:coursehours',
            'completiontime' => 'privacy:metadata:cert:completiontime',
            'pdfgenerated' => 'privacy:metadata:cert:pdfgenerated',
            'timegenerated' => 'privacy:metadata:cert:timegenerated',
            'status' => 'privacy:metadata:cert:status',
        ], 'privacy:metadata:cert');

        $collection->add_database_table('local_cmc_lms_attendance', [
            'programcourseid' => 'privacy:metadata:attendance:programcourseid',
            'userid' => 'privacy:metadata:attendance:userid',
            'status' => 'privacy:metadata:attendance:status',
            'timetaken' => 'privacy:metadata:attendance:timetaken',
        ], 'privacy:metadata:attendance');

        $collection->add_database_table('local_cmc_lms_program_role', [
            'programid' => 'privacy:metadata:program_role:programid',
            'userid' => 'privacy:metadata:program_role:userid',
            'cmcrole' => 'privacy:metadata:program_role:cmcrole',
        ], 'privacy:metadata:program_role');

        $collection->add_database_table('local_cmc_lms_eval_rule', [
            'programid' => 'privacy:metadata:eval_rule:programid',
            'courseid' => 'privacy:metadata:eval_rule:courseid',
            'quizid' => 'privacy:metadata:eval_rule:quizid',
            'cmid' => 'privacy:metadata:eval_rule:cmid',
            'scope' => 'privacy:metadata:eval_rule:scope',
        ], 'privacy:metadata:eval_rule');

        $collection->add_database_table('local_cmc_lms_content_version', [
            'contentitemid' => 'privacy:metadata:content_version:contentitemid',
            'versioncode' => 'privacy:metadata:content_version:versioncode',
            'changenotes' => 'privacy:metadata:content_version:changenotes',
            'effectivefrom' => 'privacy:metadata:content_version:effectivefrom',
            'status' => 'privacy:metadata:content_version:status',
            'createdby' => 'privacy:metadata:content_version:createdby',
        ], 'privacy:metadata:content_version');

        $collection->add_database_table('local_cmc_lms_notification', [
            'userid' => 'privacy:metadata:notification:userid',
            'type' => 'privacy:metadata:notification:type',
            'courseid' => 'privacy:metadata:notification:courseid',
            'programid' => 'privacy:metadata:notification:programid',
            'certificateid' => 'privacy:metadata:notification:certificateid',
            'subject' => 'privacy:metadata:notification:subject',
            'message' => 'privacy:metadata:notification:message',
            'timecreated' => 'privacy:metadata:notification:timecreated',
            'timesent' => 'privacy:metadata:notification:timesent',
            'status' => 'privacy:metadata:notification:status',
        ], 'privacy:metadata:notification');

        return $collection;
    }
}
