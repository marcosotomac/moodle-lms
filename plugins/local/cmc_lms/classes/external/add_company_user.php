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
use core_external\external_single_structure;
use core_external\external_value;
use local_cmc_lms\local\company_repository;

/**
 * External function associating a Moodle user with a company.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_company_user extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'CMC client company id.'),
            'userid' => new external_value(PARAM_INT, 'Moodle user id.'),
            'companyrole' => new external_value(PARAM_ALPHANUMEXT, 'Role inside the company context.', VALUE_DEFAULT, 'student'),
            'active' => new external_value(PARAM_BOOL, 'Whether the association is active.', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $companyid Company id.
     * @param int $userid Moodle user id.
     * @param string $companyrole Company context role.
     * @param bool $active Active flag.
     * @return array
     */
    public static function execute(int $companyid, int $userid, string $companyrole = 'student', bool $active = true): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'companyid' => $companyid,
            'userid' => $userid,
            'companyrole' => $companyrole,
            'active' => $active,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/cmc_lms:managecompanies', $context);

        $repository = new company_repository();
        $repository->get($params['companyid']);
        $DB->get_record('user', ['id' => $params['userid'], 'deleted' => 0], '*', MUST_EXIST);

        $id = $repository->add_user(
            $params['companyid'],
            $params['userid'],
            $params['companyrole'],
            $params['active']
        );

        return [
            'id' => $id,
            'companyid' => $params['companyid'],
            'userid' => $params['userid'],
            'companyrole' => $params['companyrole'],
            'active' => $params['active'],
        ];
    }

    /**
     * Describe return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Company-user association id.'),
            'companyid' => new external_value(PARAM_INT, 'Company id.'),
            'userid' => new external_value(PARAM_INT, 'Moodle user id.'),
            'companyrole' => new external_value(PARAM_ALPHANUMEXT, 'Role inside the company context.'),
            'active' => new external_value(PARAM_BOOL, 'Whether the association is active.'),
        ]);
    }
}
