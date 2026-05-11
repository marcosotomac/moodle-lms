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

/**
 * External function linking a Moodle course to a CMC program.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_program_course extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'programid' => new external_value(PARAM_INT, 'CMC program id.'),
            'courseid' => new external_value(PARAM_INT, 'Existing Moodle course id.'),
            'sortorder' => new external_value(PARAM_INT, 'Course position inside the program.', VALUE_DEFAULT, 0),
            'required' => new external_value(PARAM_BOOL, 'Whether the course is required.', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $programid Program id.
     * @param int $courseid Moodle course id.
     * @param int $sortorder Sort order.
     * @param bool $required Required flag.
     * @return array
     */
    public static function execute(int $programid, int $courseid, int $sortorder = 0, bool $required = true): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'programid' => $programid,
            'courseid' => $courseid,
            'sortorder' => $sortorder,
            'required' => $required,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/cmc_lms:manageprograms', $context);

        $DB->get_record('local_cmc_lms_program', ['id' => $params['programid']], '*', MUST_EXIST);
        $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);

        $repository = new program_repository();
        $id = $repository->add_course(
            $params['programid'],
            $params['courseid'],
            $params['sortorder'],
            $params['required']
        );

        return [
            'id' => $id,
            'programid' => $params['programid'],
            'courseid' => $params['courseid'],
            'sortorder' => $params['sortorder'],
            'required' => $params['required'],
        ];
    }

    /**
     * Describe return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'New program-course mapping id.'),
            'programid' => new external_value(PARAM_INT, 'Program id.'),
            'courseid' => new external_value(PARAM_INT, 'Moodle course id.'),
            'sortorder' => new external_value(PARAM_INT, 'Course position inside the program.'),
            'required' => new external_value(PARAM_BOOL, 'Whether the course is required.'),
        ]);
    }
}
