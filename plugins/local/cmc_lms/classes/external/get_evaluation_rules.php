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
use local_cmc_lms\local\evaluation_repository;

/**
 * External read-only function returning CMC evaluation rules and optional results.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_evaluation_rules extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'activeonly' => new external_value(PARAM_BOOL, 'Return only active rules.', VALUE_DEFAULT, false),
            'ruleid' => new external_value(PARAM_INT, 'Optional rule id to include historical results.', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param bool $activeonly Return only active rules.
     * @param int $ruleid Optional rule id for result details.
     * @return array
     */
    public static function execute(bool $activeonly = false, int $ruleid = 0): array {
        [
            'activeonly' => $activeonly,
            'ruleid' => $ruleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'activeonly' => $activeonly,
            'ruleid' => $ruleid,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/cmc_lms:viewevaluationreports', $context);

        $repository = new evaluation_repository();
        $rules = array_map(static function($rule): array {
            return [
                'id' => (int)$rule->id,
                'programid' => (int)($rule->programid ?? 0),
                'programname' => $rule->programname ?? '',
                'courseid' => (int)$rule->courseid,
                'coursefullname' => $rule->coursefullname,
                'quizid' => (int)$rule->quizid,
                'cmid' => (int)$rule->cmid,
                'quizname' => $rule->quizname,
                'scope' => $rule->scope,
                'passgrade' => (float)$rule->passgrade,
                'passpercentage' => (float)$rule->passpercentage,
                'maxattempts' => (int)$rule->maxattempts,
                'timelimit' => (int)$rule->timelimit,
                'active' => (bool)$rule->active,
            ];
        }, $repository->list_rules($activeonly));

        $results = [];
        if ($ruleid > 0) {
            $results = array_map(static function($result): array {
                return [
                    'userid' => (int)$result->userid,
                    'fullname' => $result->fullname,
                    'email' => $result->email,
                    'attempts' => (int)$result->attempts,
                    'bestgrade' => (float)$result->bestgrade,
                    'finalgrade' => (float)$result->finalgrade,
                    'passed' => (bool)$result->passed,
                    'lastattempttime' => (int)$result->lastattempttime,
                ];
            }, $repository->get_results_for_rule($ruleid));
        }

        return ['rules' => $rules, 'results' => $results];
    }

    /**
     * Describe return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'rules' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Rule id.'),
                'programid' => new external_value(PARAM_INT, 'Optional CMC program id, or zero.'),
                'programname' => new external_value(PARAM_TEXT, 'Program name, when scoped.'),
                'courseid' => new external_value(PARAM_INT, 'Moodle course id.'),
                'coursefullname' => new external_value(PARAM_TEXT, 'Moodle course full name.'),
                'quizid' => new external_value(PARAM_INT, 'Moodle Quiz instance id.'),
                'cmid' => new external_value(PARAM_INT, 'Moodle course module id.'),
                'quizname' => new external_value(PARAM_TEXT, 'Moodle Quiz name.'),
                'scope' => new external_value(PARAM_ALPHA, 'CMC evaluation scope.'),
                'passgrade' => new external_value(PARAM_FLOAT, 'CMC pass grade threshold.'),
                'passpercentage' => new external_value(PARAM_FLOAT, 'CMC pass percentage threshold.'),
                'maxattempts' => new external_value(PARAM_INT, 'CMC max attempts value.'),
                'timelimit' => new external_value(PARAM_INT, 'CMC time limit in seconds.'),
                'active' => new external_value(PARAM_BOOL, 'Whether the rule is active.'),
            ])),
            'results' => new external_multiple_structure(new external_single_structure([
                'userid' => new external_value(PARAM_INT, 'Moodle user id.'),
                'fullname' => new external_value(PARAM_TEXT, 'User full name.'),
                'email' => new external_value(PARAM_EMAIL, 'User email.'),
                'attempts' => new external_value(PARAM_INT, 'Attempt count.'),
                'bestgrade' => new external_value(PARAM_FLOAT, 'Best grade scaled to quiz final grade.'),
                'finalgrade' => new external_value(PARAM_FLOAT, 'Moodle Quiz final grade.'),
                'passed' => new external_value(PARAM_BOOL, 'Whether CMC thresholds are met.'),
                'lastattempttime' => new external_value(PARAM_INT, 'Last attempt timestamp.'),
            ])),
        ]);
    }
}
