<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\external;

use context_course;
use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_cmc_lms\local\company_repository;
use local_cmc_lms\local\program_repository;
use moodle_exception;

/**
 * External function enrolling a company user in all courses of a CMC program.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_user_in_program extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'CMC client company id.'),
            'userid' => new external_value(PARAM_INT, 'Moodle user id.'),
            'programid' => new external_value(PARAM_INT, 'CMC program id.'),
            'companyrole' => new external_value(PARAM_ALPHANUMEXT, 'Role inside the company context.', VALUE_DEFAULT, 'student'),
            'roleshortname' => new external_value(PARAM_ALPHANUMEXT, 'Moodle role shortname used for course enrolment.', VALUE_DEFAULT, 'student'),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $companyid Company id.
     * @param int $userid Moodle user id.
     * @param int $programid Program id.
     * @param string $companyrole Company role.
     * @param string $roleshortname Moodle course role shortname.
     * @return array
     */
    public static function execute(
        int $companyid,
        int $userid,
        int $programid,
        string $companyrole = 'student',
        string $roleshortname = 'student'
    ): array {
        global $CFG, $DB;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'companyid' => $companyid,
            'userid' => $userid,
            'programid' => $programid,
            'companyrole' => $companyrole,
            'roleshortname' => $roleshortname,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/cmc_lms:managecompanies', $context);
        require_capability('local/cmc_lms:manageprograms', $context);

        $companyrepository = new company_repository();
        $programrepository = new program_repository();
        $companyrepository->get($params['companyid']);
        $programrepository->get($params['programid']);
        $DB->get_record('user', ['id' => $params['userid'], 'deleted' => 0, 'suspended' => 0], '*', MUST_EXIST);
        $role = $DB->get_record('role', ['shortname' => $params['roleshortname']], '*', MUST_EXIST);

        $manualplugin = enrol_get_plugin('manual');
        if (!$manualplugin) {
            throw new moodle_exception('manualpluginnotinstalled', 'enrol_manual');
        }

        $transaction = $DB->start_delegated_transaction();
        $companyassociationid = $companyrepository->add_user(
            $params['companyid'],
            $params['userid'],
            $params['companyrole'],
            true
        );

        $courses = $programrepository->get_courses($params['programid']);
        $enrolments = [];
        foreach ($courses as $course) {
            $coursecontext = context_course::instance((int) $course->courseid);
            $status = 'already_enrolled';

            if (!is_enrolled($coursecontext, $params['userid'], '', true)) {
                $instance = self::get_manual_enrol_instance((int) $course->courseid);
                $manualplugin->enrol_user($instance, $params['userid'], (int) $role->id);
                $status = 'enrolled';
            }

            $enrolments[] = [
                'courseid' => (int) $course->courseid,
                'shortname' => $course->shortname,
                'status' => $status,
            ];
        }

        $transaction->allow_commit();

        return [
            'companyassociationid' => $companyassociationid,
            'companyid' => $params['companyid'],
            'userid' => $params['userid'],
            'programid' => $params['programid'],
            'enrolments' => $enrolments,
        ];
    }

    /**
     * Return an active manual enrolment instance for a course.
     *
     * @param int $courseid Course id.
     * @return \stdClass
     */
    private static function get_manual_enrol_instance(int $courseid): \stdClass {
        $instances = enrol_get_instances($courseid, true);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual') {
                return $instance;
            }
        }

        throw new moodle_exception('wsnoinstance', 'enrol_manual', '', (object) ['courseid' => $courseid]);
    }

    /**
     * Describe return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'companyassociationid' => new external_value(PARAM_INT, 'Company-user association id.'),
            'companyid' => new external_value(PARAM_INT, 'Company id.'),
            'userid' => new external_value(PARAM_INT, 'Moodle user id.'),
            'programid' => new external_value(PARAM_INT, 'Program id.'),
            'enrolments' => new external_multiple_structure(
                new external_single_structure([
                    'courseid' => new external_value(PARAM_INT, 'Moodle course id.'),
                    'shortname' => new external_value(PARAM_TEXT, 'Moodle course shortname.'),
                    'status' => new external_value(PARAM_ALPHANUMEXT, 'Enrolment status.'),
                ])
            ),
        ]);
    }
}
