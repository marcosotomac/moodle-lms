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

    /** @var string Attendance table name without Moodle prefix. */
    private const ATTENDANCE_TABLE = 'local_cmc_lms_attendance';

    /** @var string[] Supported program modalities. */
    public const MODALITIES = ['async', 'sync', 'blended'];

    /** @var string[] Supported program-course content formats. */
    public const CONTENT_FORMATS = ['video', 'document', 'external', 'lesson', 'quiz', 'other'];

    /** @var string[] Supported live session providers. */
    public const LIVE_PROVIDERS = ['', 'zoom', 'meet', 'teams', 'bbb', 'other'];

    /** @var string[] Supported attendance statuses. */
    public const ATTENDANCE_STATUSES = ['present', 'absent', 'late', 'excused'];

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
            $program->courses = $this->get_courses((int) $program->id);
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
        $program->versioncode = $program->versioncode ?? 'v1';
        $program->modality = $this->normalise_choice($program->modality ?? 'async', self::MODALITIES, 'async');
        $program->versionnotes = $program->versionnotes ?? '';
        $program->effectivefrom = (int)($program->effectivefrom ?? 0);
        $program->plannedhours = (float)($program->plannedhours ?? 0);
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

        if (isset($program->modality)) {
            $program->modality = $this->normalise_choice($program->modality, self::MODALITIES, 'async');
        }
        if (isset($program->plannedhours)) {
            $program->plannedhours = (float)$program->plannedhours;
        }
        if (isset($program->effectivefrom)) {
            $program->effectivefrom = (int)$program->effectivefrom;
        }
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
     * @param stdClass|null $metadata Optional content metadata for the link.
     * @return int Mapping id.
     */
    public function add_course(
        int $programid,
        int $courseid,
        int $sortorder = 0,
        bool $required = true,
        ?stdClass $metadata = null
    ): int {
        global $DB;

        $metadata = $metadata ?? new stdClass();
        $mapping = (object) [
            'programid' => $programid,
            'courseid' => $courseid,
            'sortorder' => $sortorder,
            'required' => $required ? 1 : 0,
            'contentlabel' => $metadata->contentlabel ?? '',
            'contentformat' => $this->normalise_choice($metadata->contentformat ?? 'other', self::CONTENT_FORMATS, 'other'),
            'reusenotes' => $metadata->reusenotes ?? '',
            'plannedhours' => (float)($metadata->plannedhours ?? 0),
            'schedulestart' => (int)($metadata->schedulestart ?? 0),
            'scheduleend' => (int)($metadata->scheduleend ?? 0),
            'liveprovider' => $this->normalise_choice($metadata->liveprovider ?? '', self::LIVE_PROVIDERS, ''),
            'liveurl' => $metadata->liveurl ?? '',
            'attendancetracking' => empty($metadata->attendancetracking) ? 0 : 1,
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
    public function get_courses(int $programid): array {
        global $DB;

        $sql = "SELECT pc.id,
                       pc.programid,
                       pc.courseid,
                       pc.sortorder,
                       pc.required,
                       pc.contentlabel,
                       pc.contentformat,
                       pc.reusenotes,
                       pc.plannedhours,
                       pc.schedulestart,
                       pc.scheduleend,
                       pc.liveprovider,
                       pc.liveurl,
                       pc.attendancetracking,
                       c.fullname,
                       c.shortname,
                       c.visible
                  FROM {local_cmc_lms_program_course} pc
                  JOIN {course} c ON c.id = pc.courseid
                 WHERE pc.programid = :programid
              ORDER BY pc.sortorder ASC, c.fullname ASC";

        return array_values($DB->get_records_sql($sql, ['programid' => $programid]));
    }

    /**
     * Record attendance for a user in a program-course link.
     *
     * @param int $programcourseid Program-course link id.
     * @param int $userid Moodle user id.
     * @param string $status Attendance status.
     * @param int|null $timetaken Attendance timestamp; defaults to now.
     * @return int Attendance record id.
     */
    public function record_attendance(int $programcourseid, int $userid, string $status, ?int $timetaken = null): int {
        global $DB;

        $now = time();
        $record = (object) [
            'programcourseid' => $programcourseid,
            'userid' => $userid,
            'status' => $this->normalise_choice($status, self::ATTENDANCE_STATUSES, 'present'),
            'timetaken' => $timetaken ?? $now,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        return (int)$DB->insert_record(self::ATTENDANCE_TABLE, $record);
    }

    /**
     * Return attendance rows for a program-course link.
     *
     * @param int $programcourseid Program-course link id.
     * @return array
     */
    public function get_attendance(int $programcourseid): array {
        global $DB;

        return array_values($DB->get_records(
            self::ATTENDANCE_TABLE,
            ['programcourseid' => $programcourseid],
            'timetaken ASC, id ASC'
        ));
    }

    /**
     * Keep persisted enum-like metadata portable and backward compatible.
     *
     * @param string $value Candidate value.
     * @param array $allowed Allowed values.
     * @param string $default Fallback value.
     * @return string
     */
    private function normalise_choice(string $value, array $allowed, string $default): string {
        $value = strtolower(trim($value));
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
