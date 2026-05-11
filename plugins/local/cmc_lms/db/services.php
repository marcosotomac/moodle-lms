<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Web service definitions for local_cmc_lms.
 *
 * These functions are intentionally read-only for the first development slice.
 * They are designed to be exposed through webservice_mcp as MCP tools.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_cmc_lms_get_companies' => [
        'classname' => 'local_cmc_lms\external\get_companies',
        'methodname' => 'execute',
        'description' => 'Returns B2B client companies managed by the CMC LMS domain plugin.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/cmc_lms:viewcompanies',
    ],
    'local_cmc_lms_get_programs' => [
        'classname' => 'local_cmc_lms\external\get_programs',
        'methodname' => 'execute',
        'description' => 'Returns training programs and their linked Moodle courses.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/cmc_lms:viewprograms',
    ],
];

$services = [
    'CMC LMS MCP service' => [
        'functions' => [
            'local_cmc_lms_get_companies',
            'local_cmc_lms_get_programs',
        ],
        'enabled' => 0,
        'restrictedusers' => 1,
        'shortname' => 'cmc_lms_mcp',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
