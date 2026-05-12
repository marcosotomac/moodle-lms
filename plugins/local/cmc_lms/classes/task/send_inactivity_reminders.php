<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\task;

use core\task\scheduled_task;
use local_cmc_lms\local\notification_service;
use moodle_url;

/**
 * Sends CMC inactivity reminders for linked active enrolments.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_inactivity_reminders extends scheduled_task {
    /** @var int Simple inactivity threshold: seven days without course access. */
    private const THRESHOLD_DAYS = 7;

    /**
     * Task display name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('tasksendinactivityreminders', 'local_cmc_lms');
    }

    /**
     * Execute inactivity reminder scan.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $cutoff = time() - (self::THRESHOLD_DAYS * DAYSECS);
        $sql = "SELECT pc.id AS mappingid,
                       ue.userid,
                       e.courseid,
                       pc.programid,
                       p.name AS programname,
                       c.fullname AS coursefullname
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                  JOIN {local_cmc_lms_program_course} pc ON pc.courseid = e.courseid
                  JOIN {local_cmc_lms_program} p ON p.id = pc.programid AND p.active = 1
             LEFT JOIN {user_lastaccess} ula ON ula.userid = ue.userid AND ula.courseid = e.courseid
             LEFT JOIN {course_completions} cc ON cc.userid = ue.userid AND cc.course = e.courseid
                 WHERE ue.status = 0
                   AND e.status = 0
                   AND cc.timecompleted IS NULL
                   AND (ula.timeaccess IS NULL OR ula.timeaccess < :cutoff)
              ORDER BY ue.userid ASC, e.courseid ASC, pc.programid ASC";

        $service = new notification_service();
        $recordset = $DB->get_recordset_sql($sql, ['cutoff' => $cutoff]);
        foreach ($recordset as $row) {
            $subject = get_string('notificationinactivitysubject', 'local_cmc_lms', $row->coursefullname);
            $body = get_string('notificationinactivitybody', 'local_cmc_lms', (object)[
                'course' => $row->coursefullname,
                'days' => self::THRESHOLD_DAYS,
            ]);
            $service->create((int)$row->userid, notification_service::TYPE_INACTIVITY_REMINDER, $subject, $body, [
                'courseid' => (int)$row->courseid,
                'programid' => (int)$row->programid,
                'contexturl' => new moodle_url('/course/view.php', ['id' => (int)$row->courseid]),
                'contexturlname' => $row->coursefullname,
            ]);
        }
        $recordset->close();
    }
}
