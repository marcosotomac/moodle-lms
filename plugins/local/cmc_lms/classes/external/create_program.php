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
            'versioncode' => new external_value(PARAM_TEXT, 'Program version label/code.', VALUE_DEFAULT, 'v1'),
            'modality' => new external_value(PARAM_ALPHA, 'Program modality: async, sync, or blended.', VALUE_DEFAULT, 'async'),
            'versionnotes' => new external_value(PARAM_TEXT, 'Program version notes/change summary.', VALUE_DEFAULT, ''),
            'effectivefrom' => new external_value(PARAM_INT, 'Unix timestamp when this version becomes effective.', VALUE_DEFAULT, 0),
            'plannedhours' => new external_value(PARAM_FLOAT, 'Planned program hours.', VALUE_DEFAULT, 0),
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
    public static function execute(
        string $name,
        string $shortname,
        string $description = '',
        bool $active = true,
        string $versioncode = 'v1',
        string $modality = 'async',
        string $versionnotes = '',
        int $effectivefrom = 0,
        float $plannedhours = 0
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'name' => $name,
            'shortname' => $shortname,
            'description' => $description,
            'active' => $active,
            'versioncode' => $versioncode,
            'modality' => $modality,
            'versionnotes' => $versionnotes,
            'effectivefrom' => $effectivefrom,
            'plannedhours' => $plannedhours,
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
            'versioncode' => $params['versioncode'],
            'modality' => $params['modality'],
            'versionnotes' => $params['versionnotes'],
            'effectivefrom' => $params['effectivefrom'],
            'plannedhours' => $params['plannedhours'],
        ]);
        $program = $repository->get($id);

        return [
            'id' => $id,
            'name' => $params['name'],
            'shortname' => $params['shortname'],
            'active' => $params['active'],
            'versioncode' => $program->versioncode,
            'modality' => $program->modality,
            'versionnotes' => $program->versionnotes,
            'effectivefrom' => (int)$program->effectivefrom,
            'plannedhours' => (float)$program->plannedhours,
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
            'versioncode' => new external_value(PARAM_TEXT, 'Program version label/code.'),
            'modality' => new external_value(PARAM_ALPHA, 'Program modality.'),
            'versionnotes' => new external_value(PARAM_TEXT, 'Program version notes/change summary.'),
            'effectivefrom' => new external_value(PARAM_INT, 'Unix timestamp when this version becomes effective.'),
            'plannedhours' => new external_value(PARAM_FLOAT, 'Planned program hours.'),
        ]);
    }
}
