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
use local_cmc_lms\local\company_repository;

/**
 * External function returning users associated with a company.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_company_users extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'CMC client company id.'),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $companyid Company id.
     * @return array
     */
    public static function execute(int $companyid): array {
        $params = self::validate_parameters(self::execute_parameters(), ['companyid' => $companyid]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/cmc_lms:viewcompanies', $context);

        $repository = new company_repository();
        $repository->get($params['companyid']);

        return array_map(static function($user): array {
            return [
                'id' => (int) $user->id,
                'companyid' => (int) $user->companyid,
                'userid' => (int) $user->userid,
                'fullname' => fullname($user),
                'email' => $user->email,
                'username' => $user->username,
                'companyrole' => $user->companyrole,
                'active' => (bool) $user->active,
            ];
        }, $repository->list_users($params['companyid']));
    }

    /**
     * Describe return structure.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Company-user association id.'),
                'companyid' => new external_value(PARAM_INT, 'Company id.'),
                'userid' => new external_value(PARAM_INT, 'Moodle user id.'),
                'fullname' => new external_value(PARAM_TEXT, 'User full name.'),
                'email' => new external_value(PARAM_EMAIL, 'User email.'),
                'username' => new external_value(PARAM_USERNAME, 'Username.'),
                'companyrole' => new external_value(PARAM_ALPHANUMEXT, 'Role inside the company context.'),
                'active' => new external_value(PARAM_BOOL, 'Whether the association is active.'),
            ])
        );
    }
}
