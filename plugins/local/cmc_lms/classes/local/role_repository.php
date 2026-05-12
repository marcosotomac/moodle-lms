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
 * CMC LMS business role helper and program assignment repository.
 *
 * These roles express the CMC/B2B domain model. Moodle core roles still own
 * course-level permissions, enrolments and authentication.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_repository {
    /** @var string Coordinates academic programs and content. */
    public const ROLE_COORDINATOR = 'coordinator';

    /** @var string Internal CMC teacher/facilitator. */
    public const ROLE_TEACHER_INTERNAL = 'teacher_internal';

    /** @var string External teacher/facilitator. */
    public const ROLE_TEACHER_EXTERNAL = 'teacher_external';

    /** @var string Learner in the client company context. */
    public const ROLE_STUDENT = 'student';

    /** @var string Client/company supervisor. */
    public const ROLE_CLIENT_SUPERVISOR = 'client_supervisor';

    /** @var string Legacy value kept for backwards compatibility. */
    public const ROLE_LEGACY_SUPERVISOR = 'supervisor';

    /** @var string Program role assignment table. */
    private const PROGRAM_ROLE_TABLE = 'local_cmc_lms_program_role';

    /** @var string[] Roles accepted for company-user association. */
    public const COMPANY_ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_CLIENT_SUPERVISOR,
        self::ROLE_TEACHER_INTERNAL,
        self::ROLE_TEACHER_EXTERNAL,
        self::ROLE_COORDINATOR,
    ];

    /** @var string[] Roles accepted for program-level assignments. */
    public const PROGRAM_ROLES = [
        self::ROLE_COORDINATOR,
        self::ROLE_TEACHER_INTERNAL,
        self::ROLE_TEACHER_EXTERNAL,
    ];

    /**
     * Normalise a CMC role value for persistence.
     *
     * @param string $role Candidate role.
     * @param string $default Fallback role.
     * @return string
     */
    public static function normalise_role(string $role, string $default = self::ROLE_STUDENT): string {
        $role = strtolower(trim($role));
        if ($role === self::ROLE_LEGACY_SUPERVISOR) {
            return self::ROLE_CLIENT_SUPERVISOR;
        }

        $allowed = array_unique(array_merge(self::COMPANY_ROLES, self::PROGRAM_ROLES));
        return in_array($role, $allowed, true) ? $role : $default;
    }

    /**
     * Normalise a company role value.
     *
     * @param string $role Candidate role.
     * @return string
     */
    public static function normalise_company_role(string $role): string {
        $role = self::normalise_role($role, self::ROLE_STUDENT);
        return in_array($role, self::COMPANY_ROLES, true) ? $role : self::ROLE_STUDENT;
    }

    /**
     * Normalise a program assignment role value.
     *
     * @param string $role Candidate role.
     * @return string
     */
    public static function normalise_program_role(string $role): string {
        $role = self::normalise_role($role, self::ROLE_TEACHER_INTERNAL);
        return in_array($role, self::PROGRAM_ROLES, true) ? $role : self::ROLE_TEACHER_INTERNAL;
    }

    /**
     * Return display key for a stored CMC role, including legacy values.
     *
     * @param string $role Stored role.
     * @return string
     */
    public static function display_key(string $role): string {
        return self::normalise_role($role, self::ROLE_STUDENT);
    }

    /**
     * Return role options for Moodle forms.
     *
     * @param string[] $roles Role constants.
     * @return array
     */
    public static function role_options(array $roles): array {
        $options = [];
        foreach ($roles as $role) {
            $options[$role] = get_string($role, 'local_cmc_lms');
        }
        return $options;
    }

    /**
     * Assign a coordinator or teacher to a CMC program.
     *
     * @param int $programid Program id.
     * @param int $userid Moodle user id.
     * @param string $cmcrole CMC program role.
     * @param bool $active Active flag.
     * @return int Assignment id.
     */
    public function assign_program_role(int $programid, int $userid, string $cmcrole, bool $active = true): int {
        global $DB;

        $cmcrole = self::normalise_program_role($cmcrole);
        $now = time();
        $existing = $DB->get_record(self::PROGRAM_ROLE_TABLE, [
            'programid' => $programid,
            'userid' => $userid,
            'cmcrole' => $cmcrole,
        ]);

        if ($existing) {
            $existing->active = $active ? 1 : 0;
            $existing->timemodified = $now;
            $DB->update_record(self::PROGRAM_ROLE_TABLE, $existing);
            return (int)$existing->id;
        }

        return (int)$DB->insert_record(self::PROGRAM_ROLE_TABLE, (object)[
            'programid' => $programid,
            'userid' => $userid,
            'cmcrole' => $cmcrole,
            'active' => $active ? 1 : 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Return program role assignments.
     *
     * @param int $programid Program id.
     * @param bool $activeonly Whether inactive assignments should be excluded.
     * @return stdClass[]
     */
    public function list_program_roles(int $programid, bool $activeonly = false): array {
        global $DB;

        $where = 'pr.programid = :programid AND u.deleted = 0';
        $params = ['programid' => $programid];
        if ($activeonly) {
            $where .= ' AND pr.active = :active';
            $params['active'] = 1;
        }

        $sql = "SELECT pr.id,
                       pr.programid,
                       pr.userid,
                       pr.cmcrole,
                       pr.active,
                       pr.timecreated,
                       pr.timemodified,
                       u.firstname,
                       u.lastname,
                       u.firstnamephonetic,
                       u.lastnamephonetic,
                       u.middlename,
                       u.alternatename,
                       u.email,
                       u.username
                  FROM {local_cmc_lms_program_role} pr
                  JOIN {user} u ON u.id = pr.userid
                 WHERE $where
              ORDER BY pr.cmcrole ASC, u.lastname ASC, u.firstname ASC, pr.id ASC";

        return array_values($DB->get_records_sql($sql, $params));
    }
}
