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
 * External function creating a CMC training program.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_program extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'name' => new external_value(PARAM_TEXT, 'Program display name.'),
            'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Stable program shortname.'),
            'description' => new external_value(PARAM_RAW, 'Program description.', VALUE_DEFAULT, ''),
            'active' => new external_value(PARAM_BOOL, 'Whether the program is active.', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param string $name Program name.
     * @param string $shortname Stable shortname.
     * @param string $description Description.
     * @param bool $active Active flag.
     * @return array
     */
    public static function execute(string $name, string $shortname, string $description = '', bool $active = true): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'name' => $name,
            'shortname' => $shortname,
            'description' => $description,
            'active' => $active,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/cmc_lms:manageprograms', $context);

        $repository = new program_repository();
        $id = $repository->create((object) [
            'name' => $params['name'],
            'shortname' => $params['shortname'],
            'description' => $params['description'],
            'active' => $params['active'] ? 1 : 0,
        ]);

        return [
            'id' => $id,
            'name' => $params['name'],
            'shortname' => $params['shortname'],
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
            'id' => new external_value(PARAM_INT, 'New program id.'),
            'name' => new external_value(PARAM_TEXT, 'Program name.'),
            'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Program shortname.'),
            'active' => new external_value(PARAM_BOOL, 'Whether the program is active.'),
        ]);
    }
}
