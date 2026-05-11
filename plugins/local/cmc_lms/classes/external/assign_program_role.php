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
use local_cmc_lms\local\program_repository;
use local_cmc_lms\local\role_repository;

/**
 * External function assigning a CMC program business role to a Moodle user.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_program_role extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'programid' => new external_value(PARAM_INT, 'CMC training program id.'),
            'userid' => new external_value(PARAM_INT, 'Moodle user id.'),
            'cmcrole' => new external_value(PARAM_ALPHANUMEXT, 'CMC program role.', VALUE_DEFAULT, role_repository::ROLE_TEACHER_INTERNAL),
            'active' => new external_value(PARAM_BOOL, 'Whether the assignment is active.', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $programid Program id.
     * @param int $userid Moodle user id.
     * @param string $cmcrole CMC program role.
     * @param bool $active Active flag.
     * @return array
     */
    public static function execute(
        int $programid,
        int $userid,
        string $cmcrole = role_repository::ROLE_TEACHER_INTERNAL,
        bool $active = true
    ): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'programid' => $programid,
            'userid' => $userid,
            'cmcrole' => $cmcrole,
            'active' => $active,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/cmc_lms:manageprogramroles', $context);

        (new program_repository())->get($params['programid']);
        $DB->get_record('user', ['id' => $params['userid'], 'deleted' => 0], '*', MUST_EXIST);

        $cmcrole = role_repository::normalise_program_role($params['cmcrole']);
        $id = (new role_repository())->assign_program_role(
            $params['programid'],
            $params['userid'],
            $cmcrole,
            $params['active']
        );

        return [
            'id' => $id,
            'programid' => $params['programid'],
            'userid' => $params['userid'],
            'cmcrole' => $cmcrole,
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
            'id' => new external_value(PARAM_INT, 'Program role assignment id.'),
            'programid' => new external_value(PARAM_INT, 'Program id.'),
            'userid' => new external_value(PARAM_INT, 'Moodle user id.'),
            'cmcrole' => new external_value(PARAM_ALPHANUMEXT, 'CMC program role.'),
            'active' => new external_value(PARAM_BOOL, 'Whether the assignment is active.'),
        ]);
    }
}
