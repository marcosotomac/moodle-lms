<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_cmc_lms\local\user_provisioning_service;

/**
 * External function for automatic CMC user provisioning.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provision_user_in_program extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'CMC client company id.'),
            'programid' => new external_value(PARAM_INT, 'CMC training program id.'),
            'email' => new external_value(PARAM_EMAIL, 'User email.'),
            'firstname' => new external_value(PARAM_NOTAGS, 'User first name.'),
            'lastname' => new external_value(PARAM_NOTAGS, 'User last name.'),
            'username' => new external_value(PARAM_USERNAME, 'Optional username. Defaults to email.', VALUE_DEFAULT, ''),
            'password' => new external_value(PARAM_RAW, 'Optional initial password. If omitted, a temporary password is generated.', VALUE_DEFAULT, ''),
            'companyrole' => new external_value(PARAM_ALPHANUMEXT, 'Role inside the company context.', VALUE_DEFAULT, 'student'),
            'roleshortname' => new external_value(PARAM_ALPHANUMEXT, 'Moodle course role shortname used for enrolment.', VALUE_DEFAULT, 'student'),
        ]);
    }

    /**
     * Execute automatic user provisioning.
     *
     * @param int $companyid Company id.
     * @param int $programid Program id.
     * @param string $email Email.
     * @param string $firstname First name.
     * @param string $lastname Last name.
     * @param string $username Optional username.
     * @param string $password Optional password.
     * @param string $companyrole Company role.
     * @param string $roleshortname Moodle course role shortname.
     * @return array
     */
    public static function execute(
        int $companyid,
        int $programid,
        string $email,
        string $firstname,
        string $lastname,
        string $username = '',
        string $password = '',
        string $companyrole = 'student',
        string $roleshortname = 'student'
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'companyid' => $companyid,
            'programid' => $programid,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'username' => $username,
            'password' => $password,
            'companyrole' => $companyrole,
            'roleshortname' => $roleshortname,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/cmc_lms:managecompanies', $context);
        require_capability('local/cmc_lms:manageprograms', $context);

        $result = (new user_provisioning_service())->provision((object)$params);

        return [
            'userid' => (int)$result->userid,
            'userstatus' => $result->userstatus,
            'companyassociationid' => (int)$result->companyassociationid,
            'companyid' => (int)$result->companyid,
            'programid' => (int)$result->programid,
            'enrolments' => $result->enrolments,
        ];
    }

    /**
     * Describe return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'userid' => new external_value(PARAM_INT, 'Provisioned Moodle user id.'),
            'userstatus' => new external_value(PARAM_ALPHANUMEXT, 'created or existing.'),
            'companyassociationid' => new external_value(PARAM_INT, 'Company-user association id.'),
            'companyid' => new external_value(PARAM_INT, 'Company id.'),
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
