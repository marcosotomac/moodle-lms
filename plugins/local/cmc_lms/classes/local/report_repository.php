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
                       u.firstnamephonetic,
                       u.lastnamephonetic,
                       u.middlename,
                       u.alternatename,
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
     * Return enrolment/completion metrics for each CMC program-course link.
     *
     * Moodle enrolments and course completions are counted only for active
     * Moodle users and active enrolment instances. The row grain remains the
     * CMC program-course link, so the same Moodle course can appear once per
     * CMC program when it is reused across curricula.
     *
     * @return stdClass[]
     */
    public function get_course_enrolment_rows(): array {
        global $DB;

        $sql = "SELECT pc.id,
                       pc.programid,
                       pc.courseid,
                       p.name AS programname,
                       p.shortname AS programshortname,
                       c.fullname AS coursefullname,
                       c.shortname AS courseshortname,
                       COALESCE(enrolsummary.enrolledstudents, 0) AS enrolledstudents,
                       COALESCE(completionsummary.completedstudents, 0) AS completedstudents
                  FROM {local_cmc_lms_program_course} pc
                  JOIN {local_cmc_lms_program} p ON p.id = pc.programid
                  JOIN {course} c ON c.id = pc.courseid
             LEFT JOIN (
                       SELECT e.courseid,
                              COUNT(DISTINCT ue.userid) AS enrolledstudents
                         FROM {user_enrolments} ue
                         JOIN {enrol} e ON e.id = ue.enrolid
                         JOIN {user} u ON u.id = ue.userid
                        WHERE ue.status = :activeenrolment
                          AND e.status = :activeenrolinstance
                          AND u.deleted = :notdeleted
                     GROUP BY e.courseid
                       ) enrolsummary ON enrolsummary.courseid = pc.courseid
             LEFT JOIN (
                       SELECT completed.courseid,
                              COUNT(1) AS completedstudents
                         FROM (
                              SELECT DISTINCT cc.userid, cc.course AS courseid
                                FROM {course_completions} cc
                                JOIN {user_enrolments} ue ON ue.userid = cc.userid
                                JOIN {enrol} e ON e.id = ue.enrolid
                                                  AND e.courseid = cc.course
                                JOIN {user} u ON u.id = cc.userid
                               WHERE cc.timecompleted IS NOT NULL
                                 AND ue.status = :activecompletionenrolment
                                 AND e.status = :activecompletionenrolinstance
                                 AND u.deleted = :notdeletedcompletion
                              ) completed
                     GROUP BY completed.courseid
                       ) completionsummary ON completionsummary.courseid = pc.courseid
              ORDER BY p.name ASC, pc.sortorder ASC, c.fullname ASC, pc.id ASC";

        $params = [
            'activeenrolment' => 0,
            'activeenrolinstance' => 0,
            'notdeleted' => 0,
            'activecompletionenrolment' => 0,
            'activecompletionenrolinstance' => 0,
            'notdeletedcompletion' => 0,
        ];

        $rows = array_values($DB->get_records_sql($sql, $params));
        foreach ($rows as $row) {
            $this->normalise_course_enrolment_row($row);
        }

        return $rows;
    }

    /**
     * Return aggregate Quiz evaluation results for active CMC evaluation rules.
     *
     * Moodle Quiz remains the source of truth for attempts and final grades.
     * Rows are scoped to rules whose course is linked to the selected CMC
     * program when a program is present, or to any CMC program-course link for
     * course-level rules without a program.
     *
     * @return stdClass[]
     */
    public function get_evaluation_summary_rows(): array {
        global $DB;

        if (!$this->table_exists('quiz') || !$this->table_exists('quiz_attempts') || !$this->table_exists('quiz_grades')) {
            return [];
        }

        $sql = "SELECT r.id,
                       r.programid,
                       r.courseid,
                       r.quizid,
                       r.cmid,
                       r.scope,
                       r.passgrade,
                       r.passpercentage,
                       p.name AS programname,
                       p.shortname AS programshortname,
                       c.fullname AS coursefullname,
                       c.shortname AS courseshortname,
                       q.name AS quizname,
                       q.grade AS quizgrade,
                       COALESCE(attemptsummary.attempts, 0) AS attempts,
                       COUNT(DISTINCT qg.userid) AS participants,
                       COUNT(DISTINCT CASE WHEN r.passgrade > 0 AND qg.grade >= r.passgrade THEN qg.userid
                                           WHEN r.passgrade <= 0 AND r.passpercentage > 0 AND q.grade > 0
                                                AND ((qg.grade / q.grade) * 100) >= r.passpercentage THEN qg.userid
                                           ELSE NULL END) AS passed,
                       AVG(qg.grade) AS averagefinalgrade
                  FROM {local_cmc_lms_eval_rule} r
             LEFT JOIN {local_cmc_lms_program} p ON p.id = r.programid
                  JOIN {course} c ON c.id = r.courseid
                  JOIN {quiz} q ON q.id = r.quizid
                  JOIN {local_cmc_lms_program_course} pc ON pc.courseid = r.courseid
                       AND (r.programid IS NULL OR pc.programid = r.programid)
             LEFT JOIN (
                       SELECT qa.quiz,
                              COUNT(qa.id) AS attempts
                         FROM {quiz_attempts} qa
                         JOIN {user} u ON u.id = qa.userid
                        WHERE qa.preview = :previewattempt
                          AND u.deleted = :notdeletedattempt
                     GROUP BY qa.quiz
                       ) attemptsummary ON attemptsummary.quiz = r.quizid
             LEFT JOIN {quiz_grades} qg ON qg.quiz = r.quizid
             LEFT JOIN {user} gu ON gu.id = qg.userid AND gu.deleted = :notdeletedgrade
                 WHERE r.active = :activerule
                   AND (qg.userid IS NULL OR gu.id IS NOT NULL)
              GROUP BY r.id, r.programid, r.courseid, r.quizid, r.cmid, r.scope, r.passgrade, r.passpercentage,
                       p.name, p.shortname, c.fullname, c.shortname, q.name, q.grade,
                       attemptsummary.attempts
              ORDER BY c.fullname ASC, q.name ASC, r.scope ASC, r.id ASC";

        $params = [
            'previewattempt' => 0,
            'notdeletedattempt' => 0,
            'notdeletedgrade' => 0,
            'activerule' => 1,
        ];

        $rows = array_values($DB->get_records_sql($sql, $params));
        foreach ($rows as $row) {
            $this->normalise_evaluation_summary_row($row);
        }

        return $rows;
    }

    /**
     * Return recent issued/revoked certificate rows for the academic report.
     *
     * @param int $limit Maximum rows to return.
     * @return stdClass[]
     */
    public function get_certificate_report_rows(int $limit = 100): array {
        global $DB;

        $limit = max(1, min(500, $limit));
        $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
        $sql = "SELECT cert.id,
                       cert.userid,
                       cert.courseid,
                       cert.companyid,
                       cert.programid,
                       cert.code,
                       cert.timeissued,
                       cert.status,
                       cert.timerevoked,
                       {$fullname} AS userfullname,
                       u.email AS useremail,
                       c.fullname AS coursefullname,
                       c.shortname AS courseshortname,
                       company.name AS companyname,
                       company.shortname AS companyshortname,
                       program.name AS programname,
                       program.shortname AS programshortname
                  FROM {local_cmc_lms_cert} cert
                  JOIN {user} u ON u.id = cert.userid
                  JOIN {course} c ON c.id = cert.courseid
             LEFT JOIN {local_cmc_lms_company} company ON company.id = cert.companyid
             LEFT JOIN {local_cmc_lms_program} program ON program.id = cert.programid
                 WHERE u.deleted = :notdeleted
                   AND EXISTS (
                       SELECT 1
                         FROM {local_cmc_lms_program_course} pc
                        WHERE pc.courseid = cert.courseid
                          AND (cert.programid IS NULL OR pc.programid = cert.programid)
                       )
              ORDER BY cert.timeissued DESC, cert.id DESC";

        $rows = array_values($DB->get_records_sql($sql, ['notdeleted' => 0], 0, $limit));
        foreach ($rows as $row) {
            $this->normalise_certificate_report_row($row);
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
     * Cast numeric values and calculate course completion percentages.
     *
     * @param stdClass $row Course enrolment row.
     * @return void
     */
    private function normalise_course_enrolment_row(stdClass $row): void {
        $row->id = (int)$row->id;
        $row->programid = (int)$row->programid;
        $row->courseid = (int)$row->courseid;
        $row->enrolledstudents = (int)$row->enrolledstudents;
        $row->completedstudents = (int)$row->completedstudents;
        $row->incompletestudents = max(0, $row->enrolledstudents - $row->completedstudents);
        $row->completionpercentage = $this->calculate_percentage($row->completedstudents, $row->enrolledstudents);
    }

    /**
     * Cast numeric values and calculate evaluation pass percentages.
     *
     * @param stdClass $row Evaluation summary row.
     * @return void
     */
    private function normalise_evaluation_summary_row(stdClass $row): void {
        $row->id = (int)$row->id;
        $row->programid = empty($row->programid) ? null : (int)$row->programid;
        $row->courseid = (int)$row->courseid;
        $row->quizid = (int)$row->quizid;
        $row->cmid = (int)$row->cmid;
        $row->passgrade = (float)$row->passgrade;
        $row->passpercentage = (float)$row->passpercentage;
        $row->quizgrade = (float)$row->quizgrade;
        $row->attempts = (int)$row->attempts;
        $row->participants = (int)$row->participants;
        $row->passed = (int)$row->passed;
        $row->passrate = $this->calculate_percentage($row->passed, $row->participants);
        $row->averagefinalgrade = $row->averagefinalgrade === null ? null : round((float)$row->averagefinalgrade, 2);
    }

    /**
     * Cast numeric values for certificate report rows.
     *
     * @param stdClass $row Certificate report row.
     * @return void
     */
    private function normalise_certificate_report_row(stdClass $row): void {
        $row->id = (int)$row->id;
        $row->userid = (int)$row->userid;
        $row->courseid = (int)$row->courseid;
        $row->companyid = empty($row->companyid) ? null : (int)$row->companyid;
        $row->programid = empty($row->programid) ? null : (int)$row->programid;
        $row->timeissued = (int)$row->timeissued;
        $row->timerevoked = empty($row->timerevoked) ? null : (int)$row->timerevoked;
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

    /**
     * Check whether a database table exists on the current Moodle install.
     *
     * @param string $tablename Table name without Moodle prefix.
     * @return bool
     */
    private function table_exists(string $tablename): bool {
        global $DB;

        $dbman = $DB->get_manager();
        return $dbman->table_exists(new \xmldb_table($tablename));
    }
}
