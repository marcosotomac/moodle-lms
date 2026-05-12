<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\local;

use moodle_url;
use stdClass;

/**
 * Read model for the CMC student panel.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_repository {
    /** @var int Moodle completion tracking disabled value. */
    private const COMPLETION_TRACKING_NONE = 0;

    /**
     * Return active CMC-linked course enrolments for a user.
     *
     * @param int $userid User id.
     * @return stdClass[]
     */
    public function get_active_courses(int $userid): array {
        global $DB;

        $sql = "SELECT pc.id AS mappingid,
                       pc.programid,
                       p.name AS programname,
                       c.id AS courseid,
                       c.fullname AS coursename,
                       MIN(company.id) AS companyid,
                       MIN(company.name) AS companyname,
                       ue.status AS userenrolmentstatus,
                       e.status AS enrolmentstatus,
                       cc.timecompleted,
                       COUNT(DISTINCT cm.id) AS trackablemodules,
                       COUNT(DISTINCT CASE WHEN cmc.completionstate > 0 THEN cm.id END) AS completedmodules
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                  JOIN {local_cmc_lms_program_course} pc ON pc.courseid = c.id
                  JOIN {local_cmc_lms_program} p ON p.id = pc.programid AND p.active = 1
             LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ue.userid
             LEFT JOIN {course_modules} cm ON cm.course = c.id
                       AND cm.deletioninprogress = 0
                       AND cm.completion <> :completionnone
             LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = ue.userid
             LEFT JOIN {local_cmc_lms_company_user} cu ON cu.userid = ue.userid AND cu.active = 1
             LEFT JOIN {local_cmc_lms_company} company ON company.id = cu.companyid AND company.active = 1
                 WHERE ue.userid = :userid
                   AND ue.status = 0
                   AND e.status = 0
              GROUP BY pc.id, pc.programid, p.name, c.id, c.fullname, ue.status, e.status, cc.timecompleted
              ORDER BY p.name ASC, pc.id ASC, c.fullname ASC";

        $rows = array_values($DB->get_records_sql($sql, [
            'userid' => $userid,
            'completionnone' => self::COMPLETION_TRACKING_NONE,
        ]));

        foreach ($rows as $row) {
            $row->enrolmentstatuslabel = get_string('active', 'local_cmc_lms');
            $row->completionstatus = !empty($row->timecompleted)
                ? get_string('completed', 'local_cmc_lms')
                : get_string('incomplete', 'local_cmc_lms');
            $row->progresspercentage = $this->calculate_progress($row);
        }

        return $rows;
    }

    /**
     * Return issued certificates for a user with panel URLs.
     *
     * @param int $userid User id.
     * @return stdClass[]
     */
    public function get_certificates(int $userid): array {
        global $DB;

        $sql = "SELECT cert.id,
                       cert.userid,
                       cert.courseid,
                       cert.companyid,
                       cert.programid,
                       cert.code,
                       cert.verifytoken,
                       cert.timeissued,
                       cert.certificatetitle,
                       cert.status,
                       c.fullname AS coursefullname,
                       company.name AS companyname,
                       program.name AS programname
                  FROM {local_cmc_lms_cert} cert
                  JOIN {course} c ON c.id = cert.courseid
             LEFT JOIN {local_cmc_lms_company} company ON company.id = cert.companyid
             LEFT JOIN {local_cmc_lms_program} program ON program.id = cert.programid
                 WHERE cert.userid = :userid
                   AND cert.status = :status
              ORDER BY cert.timeissued DESC, cert.id DESC";

        $rows = array_values($DB->get_records_sql($sql, [
            'userid' => $userid,
            'status' => certificate_repository::STATUS_ISSUED,
        ]));

        $repository = new certificate_repository();
        foreach ($rows as $row) {
            $row->downloadurl = new moodle_url('/local/cmc_lms/certificate_download.php', ['certid' => (int)$row->id]);
            $row->verificationurl = $repository->get_verification_url($row);
        }

        return $rows;
    }

    /**
     * Return recent notifications for a user.
     *
     * @param int $userid User id.
     * @param int $limit Limit.
     * @return stdClass[]
     */
    public function get_notifications(int $userid, int $limit = 10): array {
        return (new notification_service())->list_for_user($userid, $limit);
    }

    /**
     * Calculate a defensive progress percentage.
     *
     * @param stdClass $row Course row with completion counts.
     * @return float
     */
    private function calculate_progress(stdClass $row): float {
        if (!empty($row->timecompleted)) {
            return 100.0;
        }

        $trackable = (int)$row->trackablemodules;
        if ($trackable <= 0) {
            return 0.0;
        }

        return round(min(100, ((int)$row->completedmodules / $trackable) * 100), 2);
    }
}
