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
 * External function creating a B2B company.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_company extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'name' => new external_value(PARAM_TEXT, 'Company legal or commercial name.'),
            'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Stable company shortname.'),
            'sector' => new external_value(PARAM_TEXT, 'Company sector.', VALUE_DEFAULT, ''),
            'size' => new external_value(PARAM_TEXT, 'Company size segment.', VALUE_DEFAULT, ''),
            'location' => new external_value(PARAM_TEXT, 'Company location.', VALUE_DEFAULT, ''),
            'country' => new external_value(PARAM_ALPHA, 'ISO 3166-1 alpha-2 country code.', VALUE_DEFAULT, ''),
            'active' => new external_value(PARAM_BOOL, 'Whether the company is active.', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param string $name Company name.
     * @param string $shortname Stable shortname.
     * @param string $sector Sector.
     * @param string $size Size segment.
     * @param string $location Location.
     * @param string $country Country code.
     * @param bool $active Active flag.
     * @return array
     */
    public static function execute(
        string $name,
        string $shortname,
        string $sector = '',
        string $size = '',
        string $location = '',
        string $country = '',
        bool $active = true
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'name' => $name,
            'shortname' => $shortname,
            'sector' => $sector,
            'size' => $size,
            'location' => $location,
            'country' => $country,
            'active' => $active,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/cmc_lms:managecompanies', $context);

        $repository = new company_repository();
        $id = $repository->create((object) [
            'name' => $params['name'],
            'shortname' => $params['shortname'],
            'sector' => $params['sector'],
            'size' => $params['size'],
            'location' => $params['location'],
            'country' => strtoupper($params['country']),
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
            'id' => new external_value(PARAM_INT, 'New company id.'),
            'name' => new external_value(PARAM_TEXT, 'Company name.'),
            'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Company shortname.'),
            'active' => new external_value(PARAM_BOOL, 'Whether the company is active.'),
        ]);
    }
}
