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
use local_cmc_lms\local\program_repository;

/**
 * External function returning CMC training programs.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_programs extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'activeonly' => new external_value(PARAM_BOOL, 'Return only active programs.', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param bool $activeonly Return only active programs.
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
        require_capability('local/cmc_lms:viewprograms', $context);

        $repository = new program_repository();
        return array_map(static function($program): array {
            return [
                'id' => (int) $program->id,
                'name' => $program->name,
                'shortname' => $program->shortname,
                'description' => $program->description ?? '',
                'versioncode' => $program->versioncode ?? 'v1',
                'modality' => $program->modality ?? 'async',
                'versionnotes' => $program->versionnotes ?? '',
                'effectivefrom' => (int)($program->effectivefrom ?? 0),
                'plannedhours' => (float)($program->plannedhours ?? 0),
                'active' => (bool) $program->active,
                'courses' => array_map(static function($course): array {
                    return [
                        'courseid' => (int) $course->courseid,
                        'fullname' => $course->fullname,
                        'shortname' => $course->shortname,
                        'sortorder' => (int) $course->sortorder,
                        'required' => (bool) $course->required,
                        'contentlabel' => $course->contentlabel ?? '',
                        'contentformat' => $course->contentformat ?? 'other',
                        'contentitemid' => empty($course->contentitemid) ? 0 : (int)$course->contentitemid,
                        'contentversionid' => empty($course->contentversionid) ? 0 : (int)$course->contentversionid,
                        'contentitemname' => $course->contentitemname ?? '',
                        'contentitemcode' => $course->contentitemcode ?? '',
                        'isoreference' => $course->isoreference ?? '',
                        'contentversioncode' => $course->contentversioncode ?? '',
                        'contentversionstatus' => $course->contentversionstatus ?? '',
                        'reusenotes' => $course->reusenotes ?? '',
                        'plannedhours' => (float)($course->plannedhours ?? 0),
                        'schedulestart' => (int)($course->schedulestart ?? 0),
                        'scheduleend' => (int)($course->scheduleend ?? 0),
                        'liveprovider' => $course->liveprovider ?? '',
                        'liveurl' => $course->liveurl ?? '',
                        'liveexternalid' => $course->liveexternalid ?? '',
                        'liveintegrationstatus' => $course->liveintegrationstatus ?? 'manual',
                        'liveintegrationerror' => $course->liveintegrationerror ?? '',
                        'attendancetracking' => (bool)($course->attendancetracking ?? false),
                        'visible' => (bool) $course->visible,
                    ];
                }, $program->courses),
            ];
        }, $repository->list_with_courses($activeonly));
    }

    /**
     * Describe return structure.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Program id.'),
                'name' => new external_value(PARAM_TEXT, 'Program display name.'),
                'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Stable program shortname.'),
                'description' => new external_value(PARAM_RAW, 'Program description.', VALUE_OPTIONAL),
                'versioncode' => new external_value(PARAM_TEXT, 'Program version label/code.'),
                'modality' => new external_value(PARAM_ALPHA, 'Program modality.'),
                'versionnotes' => new external_value(PARAM_TEXT, 'Program version notes/change summary.'),
                'effectivefrom' => new external_value(PARAM_INT, 'Unix timestamp when this version becomes effective.'),
                'plannedhours' => new external_value(PARAM_FLOAT, 'Planned program hours.'),
                'active' => new external_value(PARAM_BOOL, 'Whether the program is active.'),
                'courses' => new external_multiple_structure(
                    new external_single_structure([
                        'courseid' => new external_value(PARAM_INT, 'Linked Moodle course id.'),
                        'fullname' => new external_value(PARAM_TEXT, 'Moodle course full name.'),
                        'shortname' => new external_value(PARAM_TEXT, 'Moodle course short name.'),
                        'sortorder' => new external_value(PARAM_INT, 'Program ordering.'),
                        'required' => new external_value(PARAM_BOOL, 'Whether this course is required.'),
                        'contentlabel' => new external_value(PARAM_TEXT, 'Section/module label or content role.'),
                        'contentformat' => new external_value(PARAM_ALPHA, 'Content format/category.'),
                        'contentitemid' => new external_value(PARAM_INT, 'Reusable CMC content item id.'),
                        'contentversionid' => new external_value(PARAM_INT, 'Reusable CMC content version id.'),
                        'contentitemname' => new external_value(PARAM_TEXT, 'Reusable CMC content item name.'),
                        'contentitemcode' => new external_value(PARAM_TEXT, 'Reusable CMC content item code.'),
                        'isoreference' => new external_value(PARAM_TEXT, 'ISO reference for the reusable content.'),
                        'contentversioncode' => new external_value(PARAM_TEXT, 'Reusable CMC content version code.'),
                        'contentversionstatus' => new external_value(PARAM_TEXT, 'Reusable CMC content version status.'),
                        'reusenotes' => new external_value(PARAM_TEXT, 'Reuse source or notes.'),
                        'plannedhours' => new external_value(PARAM_FLOAT, 'Planned hours for this content link.'),
                        'schedulestart' => new external_value(PARAM_INT, 'Scheduled start timestamp.'),
                        'scheduleend' => new external_value(PARAM_INT, 'Scheduled end timestamp.'),
                        'liveprovider' => new external_value(PARAM_TEXT, 'Live session provider.'),
                        'liveurl' => new external_value(PARAM_TEXT, 'Live session URL.'),
                        'liveexternalid' => new external_value(PARAM_TEXT, 'External provider session id.'),
                        'liveintegrationstatus' => new external_value(PARAM_TEXT, 'Live session integration status.'),
                        'liveintegrationerror' => new external_value(PARAM_TEXT, 'Live session integration error.'),
                        'attendancetracking' => new external_value(PARAM_BOOL, 'Whether attendance tracking is enabled.'),
                        'visible' => new external_value(PARAM_BOOL, 'Whether this Moodle course is visible.'),
                    ])
                ),
            ])
        );
    }
}
