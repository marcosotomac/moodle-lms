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
 * External function returning B2B companies.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_companies extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'activeonly' => new external_value(PARAM_BOOL, 'Return only active companies.', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param bool $activeonly Return only active companies.
     * @return array
     */
    public static function execute(bool $activeonly = true): array {
        [
            'activeonly' => $activeonly,
        ] = self::validate_parameters(self::execute_parameters(), [
            'activeonly' => $activeonly,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/cmc_lms:viewcompanies', $context);

        $repository = new company_repository();
        return array_map(static function($company): array {
            return [
                'id' => (int) $company->id,
                'name' => $company->name,
                'shortname' => $company->shortname,
                'sector' => $company->sector ?? '',
                'size' => $company->size ?? '',
                'location' => $company->location ?? '',
                'country' => $company->country ?? '',
                'active' => (bool) $company->active,
            ];
        }, $repository->list($activeonly));
    }

    /**
     * Describe return structure.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Company id.'),
                'name' => new external_value(PARAM_TEXT, 'Company legal or commercial name.'),
                'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Stable company shortname.'),
                'sector' => new external_value(PARAM_TEXT, 'Company sector.', VALUE_OPTIONAL),
                'size' => new external_value(PARAM_TEXT, 'Company size segment.', VALUE_OPTIONAL),
                'location' => new external_value(PARAM_TEXT, 'Company location.', VALUE_OPTIONAL),
                'country' => new external_value(PARAM_ALPHA, 'ISO 3166-1 alpha-2 country code.', VALUE_OPTIONAL),
                'active' => new external_value(PARAM_BOOL, 'Whether the company is active.'),
            ])
        );
    }
}
