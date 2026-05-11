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
            'contentlabel' => new external_value(PARAM_TEXT, 'Section/module label or content role.', VALUE_DEFAULT, ''),
            'contentformat' => new external_value(PARAM_ALPHA, 'Content format/category.', VALUE_DEFAULT, 'other'),
            'reusenotes' => new external_value(PARAM_TEXT, 'Reuse source or notes.', VALUE_DEFAULT, ''),
            'plannedhours' => new external_value(PARAM_FLOAT, 'Planned hours for this content link.', VALUE_DEFAULT, 0),
            'schedulestart' => new external_value(PARAM_INT, 'Scheduled start timestamp.', VALUE_DEFAULT, 0),
            'scheduleend' => new external_value(PARAM_INT, 'Scheduled end timestamp.', VALUE_DEFAULT, 0),
            'liveprovider' => new external_value(PARAM_TEXT, 'Live session provider.', VALUE_DEFAULT, ''),
            'liveurl' => new external_value(PARAM_TEXT, 'Live session URL.', VALUE_DEFAULT, ''),
            'attendancetracking' => new external_value(PARAM_BOOL, 'Whether attendance tracking is enabled.', VALUE_DEFAULT, false),
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
    public static function execute(
        int $programid,
        int $courseid,
        int $sortorder = 0,
        bool $required = true,
        string $contentlabel = '',
        string $contentformat = 'other',
        string $reusenotes = '',
        float $plannedhours = 0,
        int $schedulestart = 0,
        int $scheduleend = 0,
        string $liveprovider = '',
        string $liveurl = '',
        bool $attendancetracking = false
    ): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'programid' => $programid,
            'courseid' => $courseid,
            'sortorder' => $sortorder,
            'required' => $required,
            'contentlabel' => $contentlabel,
            'contentformat' => $contentformat,
            'reusenotes' => $reusenotes,
            'plannedhours' => $plannedhours,
            'schedulestart' => $schedulestart,
            'scheduleend' => $scheduleend,
            'liveprovider' => $liveprovider,
            'liveurl' => $liveurl,
            'attendancetracking' => $attendancetracking,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/cmc_lms:manageprograms', $context);

        if (!empty($params['schedulestart']) && !empty($params['scheduleend'])
            && $params['scheduleend'] < $params['schedulestart']) {
            throw new \invalid_parameter_exception(get_string('scheduleendbeforestart', 'local_cmc_lms'));
        }

        $DB->get_record('local_cmc_lms_program', ['id' => $params['programid']], '*', MUST_EXIST);
        $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);

        $repository = new program_repository();
        $id = $repository->add_course(
            $params['programid'],
            $params['courseid'],
            $params['sortorder'],
            $params['required'],
            (object) $params
        );
        $courses = $repository->get_courses($params['programid']);
        $course = null;
        foreach ($courses as $candidate) {
            if ((int)$candidate->id === $id) {
                $course = $candidate;
                break;
            }
        }

        return [
            'id' => $id,
            'programid' => $params['programid'],
            'courseid' => $params['courseid'],
            'sortorder' => $params['sortorder'],
            'required' => $params['required'],
            'contentlabel' => $course->contentlabel,
            'contentformat' => $course->contentformat,
            'reusenotes' => $course->reusenotes,
            'plannedhours' => (float)$course->plannedhours,
            'schedulestart' => (int)$course->schedulestart,
            'scheduleend' => (int)$course->scheduleend,
            'liveprovider' => $course->liveprovider,
            'liveurl' => $course->liveurl,
            'attendancetracking' => (bool)$course->attendancetracking,
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
            'contentlabel' => new external_value(PARAM_TEXT, 'Section/module label or content role.'),
            'contentformat' => new external_value(PARAM_ALPHA, 'Content format/category.'),
            'reusenotes' => new external_value(PARAM_TEXT, 'Reuse source or notes.'),
            'plannedhours' => new external_value(PARAM_FLOAT, 'Planned hours for this content link.'),
            'schedulestart' => new external_value(PARAM_INT, 'Scheduled start timestamp.'),
            'scheduleend' => new external_value(PARAM_INT, 'Scheduled end timestamp.'),
            'liveprovider' => new external_value(PARAM_TEXT, 'Live session provider.'),
            'liveurl' => new external_value(PARAM_TEXT, 'Live session URL.'),
            'attendancetracking' => new external_value(PARAM_BOOL, 'Whether attendance tracking is enabled.'),
        ]);
    }
}
