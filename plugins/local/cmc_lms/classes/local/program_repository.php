<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\local;

use stdClass;

/**
 * Repository for CMC training programs and course mappings.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program_repository {
    /** @var string Program database table name without Moodle prefix. */
    private const PROGRAM_TABLE = 'local_cmc_lms_program';

    /** @var string Program-course mapping table name without Moodle prefix. */
    private const PROGRAM_COURSE_TABLE = 'local_cmc_lms_program_course';

    /**
     * Return programs with linked Moodle courses.
     *
     * @param bool $activeonly Whether inactive programs should be excluded.
     * @return array
     */
    public function list_with_courses(bool $activeonly = true): array {
        global $DB;

        if ($activeonly) {
            $programs = $DB->get_records_select(self::PROGRAM_TABLE, 'active = :active', ['active' => 1], 'name ASC, id ASC');
        } else {
            $programs = $DB->get_records(self::PROGRAM_TABLE, null, 'name ASC, id ASC');
        }

        $result = [];
        foreach ($programs as $program) {
            $program->courses = $this->get_program_courses((int) $program->id);
            $result[] = $program;
        }

        return $result;
    }

    /**
     * Create a program record.
     *
     * @param stdClass $program Program data.
     * @return int New program id.
     */
    public function create(stdClass $program): int {
        global $DB;

        $now = time();
        $program->descriptionformat = $program->descriptionformat ?? FORMAT_HTML;
        $program->active = $program->active ?? 1;
        $program->timecreated = $program->timecreated ?? $now;
        $program->timemodified = $program->timemodified ?? $now;

        return (int) $DB->insert_record(self::PROGRAM_TABLE, $program);
    }

    /**
     * Return a program by id.
     *
     * @param int $id Program id.
     * @return stdClass
     */
    public function get(int $id): stdClass {
        global $DB;

        return $DB->get_record(self::PROGRAM_TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Update a program record.
     *
     * @param stdClass $program Program data including id.
     * @return void
     */
    public function update(stdClass $program): void {
        global $DB;

        $program->timemodified = time();
        $DB->update_record(self::PROGRAM_TABLE, $program);
    }

    /**
     * Link a Moodle course to a CMC program.
     *
     * @param int $programid Program id.
     * @param int $courseid Moodle course id.
     * @param int $sortorder Ordering inside the program.
     * @param bool $required Whether the course is required for completion.
     * @return int Mapping id.
     */
    public function add_course(int $programid, int $courseid, int $sortorder = 0, bool $required = true): int {
        global $DB;

        $mapping = (object) [
            'programid' => $programid,
            'courseid' => $courseid,
            'sortorder' => $sortorder,
            'required' => $required ? 1 : 0,
            'timecreated' => time(),
        ];

        return (int) $DB->insert_record(self::PROGRAM_COURSE_TABLE, $mapping);
    }

    /**
     * Return Moodle courses linked to a program.
     *
     * @param int $programid Program id.
     * @return array
     */
    private function get_program_courses(int $programid): array {
        global $DB;

        $sql = "SELECT pc.id,
                       pc.programid,
                       pc.courseid,
                       pc.sortorder,
                       pc.required,
                       c.fullname,
                       c.shortname,
                       c.visible
                  FROM {local_cmc_lms_program_course} pc
                  JOIN {course} c ON c.id = pc.courseid
                 WHERE pc.programid = :programid
              ORDER BY pc.sortorder ASC, c.fullname ASC";

        return array_values($DB->get_records_sql($sql, ['programid' => $programid]));
    }
}
