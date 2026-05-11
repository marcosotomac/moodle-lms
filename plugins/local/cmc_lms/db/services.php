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
    'local_cmc_lms_create_company' => [
        'classname' => 'local_cmc_lms\external\create_company',
        'methodname' => 'execute',
        'description' => 'Creates a B2B client company for CMC LMS segmentation.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/cmc_lms:managecompanies',
    ],
    'local_cmc_lms_create_program' => [
        'classname' => 'local_cmc_lms\external\create_program',
        'methodname' => 'execute',
        'description' => 'Creates a CMC training program or curriculum mesh.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/cmc_lms:manageprograms',
    ],
    'local_cmc_lms_add_program_course' => [
        'classname' => 'local_cmc_lms\external\add_program_course',
        'methodname' => 'execute',
        'description' => 'Links an existing Moodle course to a CMC training program.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/cmc_lms:manageprograms',
    ],
    'local_cmc_lms_get_company_users' => [
        'classname' => 'local_cmc_lms\external\get_company_users',
        'methodname' => 'execute',
        'description' => 'Returns Moodle users associated with a CMC client company.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/cmc_lms:viewcompanies',
    ],
    'local_cmc_lms_add_company_user' => [
        'classname' => 'local_cmc_lms\external\add_company_user',
        'methodname' => 'execute',
        'description' => 'Associates a Moodle user with a CMC client company.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/cmc_lms:managecompanies',
    ],
];

$services = [
    'CMC LMS MCP service' => [
        'functions' => [
            'local_cmc_lms_get_companies',
            'local_cmc_lms_get_programs',
            'local_cmc_lms_create_company',
            'local_cmc_lms_create_program',
            'local_cmc_lms_add_program_course',
            'local_cmc_lms_get_company_users',
            'local_cmc_lms_add_company_user',
        ],
        'enabled' => 0,
        'restrictedusers' => 1,
        'shortname' => 'cmc_lms_mcp',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
