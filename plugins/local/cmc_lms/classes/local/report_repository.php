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
 * Repository for B2B reporting metrics scoped to CMC program courses.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_repository {
    /** @var string Student role in a B2B company context. */
    private const ROLE_STUDENT = 'student';

    /**
     * Return one dashboard row per active company.
     *
     * Enrolments and completions are intentionally scoped to Moodle courses that
     * are linked through local_cmc_lms_program_course, so Moodle-wide activity
     * from unrelated courses does not leak into the B2B dashboard.
     *
     * @return stdClass[]
     */
    public function get_company_dashboard_rows(): array {
        global $DB;

        $sql = "SELECT c.id,
                       c.name,
                       c.shortname,
                       COALESCE(usersummary.activeusers, 0) AS activeusers,
                       COALESCE(usersummary.students, 0) AS students,
                       COALESCE(usersummary.supervisors, 0) AS supervisors,
                       COALESCE(enrolsummary.enrolmentcount, 0) AS enrolmentcount,
                       COALESCE(completionsummary.completioncount, 0) AS completioncount
                  FROM {local_cmc_lms_company} c
             LEFT JOIN (
                       SELECT cu.companyid,
                              COUNT(DISTINCT cu.userid) AS activeusers,
                               COUNT(DISTINCT CASE WHEN cu.companyrole = :studentrole THEN cu.userid END) AS students,
                               COUNT(DISTINCT CASE WHEN cu.companyrole IN (:supervisorrole, :legacysupervisorrole)
                                                   THEN cu.userid END) AS supervisors
                         FROM {local_cmc_lms_company_user} cu
                         JOIN {user} u ON u.id = cu.userid
                        WHERE cu.active = :activeuser
                          AND u.deleted = 0
                     GROUP BY cu.companyid
                       ) usersummary ON usersummary.companyid = c.id
             LEFT JOIN (
                       SELECT activecu.companyid,
                              COUNT(1) AS enrolmentcount
                         FROM (
                              SELECT DISTINCT cu.companyid, cu.userid, e.courseid
                                FROM {local_cmc_lms_company_user} cu
                                JOIN {user} u ON u.id = cu.userid
                                JOIN {user_enrolments} ue ON ue.userid = cu.userid
                                JOIN {enrol} e ON e.id = ue.enrolid
                                JOIN {local_cmc_lms_program_course} pc ON pc.courseid = e.courseid
                               WHERE cu.active = :activeenrolassociation
                                 AND u.deleted = 0
                                 AND ue.status = :activeenrolment
                                 AND e.status = :activeenrolinstance
                              ) activecu
                     GROUP BY activecu.companyid
                       ) enrolsummary ON enrolsummary.companyid = c.id
             LEFT JOIN (
                       SELECT completedcu.companyid,
                              COUNT(1) AS completioncount
                         FROM (
                               SELECT DISTINCT cu.companyid, cu.userid, cc.course
                                 FROM {local_cmc_lms_company_user} cu
                                 JOIN {user} u ON u.id = cu.userid
                                 JOIN {course_completions} cc ON cc.userid = cu.userid
                                 JOIN {user_enrolments} ue ON ue.userid = cu.userid
                                 JOIN {enrol} e ON e.id = ue.enrolid
                                                   AND e.courseid = cc.course
                                 JOIN {local_cmc_lms_program_course} pc ON pc.courseid = cc.course
                                WHERE cu.active = :activecompletionassociation
                                  AND u.deleted = 0
                                  AND cc.timecompleted IS NOT NULL
                                  AND ue.status = :activecompletionenrolment
                                  AND e.status = :activecompletionenrolinstance
                               ) completedcu
                      GROUP BY completedcu.companyid
                        ) completionsummary ON completionsummary.companyid = c.id
                 WHERE c.active = :activecompany
              ORDER BY c.name ASC, c.id ASC";

        $params = [
            'studentrole' => self::ROLE_STUDENT,
            'supervisorrole' => role_repository::ROLE_CLIENT_SUPERVISOR,
            'legacysupervisorrole' => role_repository::ROLE_LEGACY_SUPERVISOR,
            'activeuser' => 1,
            'activeenrolassociation' => 1,
            'activeenrolment' => 0,
            'activeenrolinstance' => 0,
            'activecompletionassociation' => 1,
            'activecompletionenrolment' => 0,
            'activecompletionenrolinstance' => 0,
            'activecompany' => 1,
        ];

        $rows = array_values($DB->get_records_sql($sql, $params));
        foreach ($rows as $row) {
            $this->normalise_company_dashboard_row($row);
        }

        return $rows;
    }

    /**
     * Return user progress rows for one active company.
     *
     * @param int $companyid Company id.
     * @return stdClass[]
     */
    public function get_company_user_progress_rows(int $companyid): array {
        global $DB;

        $sql = "SELECT cu.id,
                       cu.companyid,
                       cu.userid,
                       cu.companyrole,
                       u.firstname,
                       u.lastname,
                       u.email,
                       COALESCE(enrolsummary.enrolledcourses, 0) AS enrolledcourses,
                       COALESCE(completionsummary.completedcourses, 0) AS completedcourses
                  FROM {local_cmc_lms_company_user} cu
                  JOIN {local_cmc_lms_company} c ON c.id = cu.companyid
                  JOIN {user} u ON u.id = cu.userid
             LEFT JOIN (
                       SELECT enrolled.userid,
                              COUNT(1) AS enrolledcourses
                         FROM (
                              SELECT DISTINCT ue.userid, e.courseid
                                FROM {user_enrolments} ue
                                JOIN {enrol} e ON e.id = ue.enrolid
                                JOIN {local_cmc_lms_program_course} pc ON pc.courseid = e.courseid
                               WHERE ue.status = :activeenrolment
                                 AND e.status = :activeenrolinstance
                              ) enrolled
                     GROUP BY enrolled.userid
                       ) enrolsummary ON enrolsummary.userid = cu.userid
             LEFT JOIN (
                       SELECT completed.userid,
                              COUNT(1) AS completedcourses
                         FROM (
                               SELECT DISTINCT cc.userid, cc.course
                                 FROM {course_completions} cc
                                 JOIN {user_enrolments} ue ON ue.userid = cc.userid
                                 JOIN {enrol} e ON e.id = ue.enrolid
                                                   AND e.courseid = cc.course
                                 JOIN {local_cmc_lms_program_course} pc ON pc.courseid = cc.course
                                WHERE cc.timecompleted IS NOT NULL
                                  AND ue.status = :activecompletionenrolment
                                  AND e.status = :activecompletionenrolinstance
                               ) completed
                      GROUP BY completed.userid
                        ) completionsummary ON completionsummary.userid = cu.userid
                 WHERE cu.companyid = :companyid
                   AND cu.active = :activeassociation
                   AND c.active = :activecompany
                   AND u.deleted = 0
              ORDER BY u.lastname ASC, u.firstname ASC, cu.companyrole ASC, cu.id ASC";

        $params = [
            'activeenrolment' => 0,
            'activeenrolinstance' => 0,
            'activecompletionenrolment' => 0,
            'activecompletionenrolinstance' => 0,
            'companyid' => $companyid,
            'activeassociation' => 1,
            'activecompany' => 1,
        ];

        $rows = array_values($DB->get_records_sql($sql, $params));
        foreach ($rows as $row) {
            $this->normalise_user_progress_row($row);
        }

        return $rows;
    }

    /**
     * Cast numeric values and calculate company completion percentage.
     *
     * @param stdClass $row Dashboard row.
     * @return void
     */
    private function normalise_company_dashboard_row(stdClass $row): void {
        $row->id = (int) $row->id;
        $row->activeusers = (int) $row->activeusers;
        $row->students = (int) $row->students;
        $row->supervisors = (int) $row->supervisors;
        $row->enrolmentcount = (int) $row->enrolmentcount;
        $row->completioncount = (int) $row->completioncount;
        $row->completionpercentage = $this->calculate_percentage($row->completioncount, $row->enrolmentcount);
    }

    /**
     * Cast numeric values and calculate per-user completion percentage.
     *
     * @param stdClass $row User progress row.
     * @return void
     */
    private function normalise_user_progress_row(stdClass $row): void {
        $row->id = (int) $row->id;
        $row->companyid = (int) $row->companyid;
        $row->userid = (int) $row->userid;
        $row->companyrole = role_repository::display_key($row->companyrole);
        $row->fullname = fullname($row);
        $row->enrolledcourses = (int) $row->enrolledcourses;
        $row->completedcourses = (int) $row->completedcourses;
        $row->completionpercentage = $this->calculate_percentage($row->completedcourses, $row->enrolledcourses);
    }

    /**
     * Calculate a rounded percentage with defensive zero handling.
     *
     * @param int $completed Completed count.
     * @param int $total Total count.
     * @return float
     */
    private function calculate_percentage(int $completed, int $total): float {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($completed / $total) * 100, 2);
    }
}
