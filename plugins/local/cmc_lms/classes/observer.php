<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms;

use core\event\course_completed;
use core\event\user_enrolment_created;
use local_cmc_lms\local\certificate_repository;
use local_cmc_lms\local\notification_service;
use Throwable;

/**
 * Moodle event observers for local_cmc_lms.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Issue CMC certificates when Moodle core marks a course completed.
     *
     * The observer is deliberately defensive: CMC program links are optional domain metadata, so Moodle completion must
     * never fail because certificate automation could not run.
     *
     * @param course_completed $event Moodle course completion event.
     * @return void
     */
    public static function course_completed(course_completed $event): void {
        try {
            $data = $event->get_data();
            $userid = (int)($data['relateduserid'] ?? $data['userid'] ?? 0);
            $courseid = (int)($data['courseid'] ?? 0);
            if ($userid <= 0 || $courseid <= 0) {
                return;
            }

            $repository = new certificate_repository();
            $certificates = $repository->issue_for_completion($userid, $courseid, (int)$event->timecreated, 0);
            $notificationservice = new notification_service();
            foreach ($certificates as $certificate) {
                $notificationservice->notify_certificate_available($certificate);
            }
        } catch (Throwable $exception) {
            debugging('CMC certificate automatic issuance skipped: ' . $exception->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Send/log CMC course start notifications for new enrolments into linked courses.
     *
     * @param user_enrolment_created $event Moodle enrolment event.
     * @return void
     */
    public static function user_enrolment_created(user_enrolment_created $event): void {
        try {
            $data = $event->get_data();
            $userid = (int)($data['relateduserid'] ?? $data['userid'] ?? 0);
            $courseid = (int)($data['courseid'] ?? 0);
            if ($userid <= 0 || $courseid <= 0) {
                return;
            }

            (new notification_service())->notify_course_start($userid, $courseid);
        } catch (Throwable $exception) {
            debugging('CMC course start notification skipped: ' . $exception->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
