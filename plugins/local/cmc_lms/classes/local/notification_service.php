<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\local;

use core\message\message;
use core_text;
use core_user;
use moodle_url;
use stdClass;
use Throwable;

/**
 * Notification log and Moodle message delivery service for CMC student experience.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_service {
    /** @var string Notification table name. */
    private const TABLE = 'local_cmc_lms_notification';

    /** @var string Course start notification. */
    public const TYPE_COURSE_START = 'course_start';

    /** @var string Inactivity reminder notification. */
    public const TYPE_INACTIVITY_REMINDER = 'inactivity_reminder';

    /** @var string Certificate available notification. */
    public const TYPE_CERTIFICATE_AVAILABLE = 'certificate_available';

    /** @var string Pending status. */
    public const STATUS_PENDING = 'pending';

    /** @var string Sent status. */
    public const STATUS_SENT = 'sent';

    /** @var string Failed delivery status. */
    public const STATUS_FAILED = 'failed';

    /**
     * Create a notification idempotently and optionally deliver it through Moodle messaging.
     *
     * Idempotency is scoped by user, type and optional course/program/certificate tuple. This keeps event observers and
     * daily scheduled tasks safe to retry without spamming the same learner.
     *
     * @param int $userid Recipient user id.
     * @param string $type One of the TYPE_* constants.
     * @param string $subject Message subject.
     * @param string $body Plain-text message body.
     * @param array $context Optional courseid, programid, certificateid and contexturl/contexturlname values.
     * @param bool $send Whether to call Moodle message_send after logging.
     * @return stdClass Notification row.
     */
    public function create(int $userid, string $type, string $subject, string $body, array $context = [], bool $send = true): stdClass {
        global $DB;

        $type = $this->normalise_type($type);
        $courseid = $this->nullable_int($context['courseid'] ?? null);
        $programid = $this->nullable_int($context['programid'] ?? null);
        $certificateid = $this->nullable_int($context['certificateid'] ?? null);

        $existing = $this->find_existing($userid, $type, $courseid, $programid, $certificateid);
        if ($existing !== null) {
            return $existing;
        }

        $now = time();
        $record = (object)[
            'userid' => $userid,
            'type' => $type,
            'courseid' => $courseid,
            'programid' => $programid,
            'certificateid' => $certificateid,
            'subject' => core_text::substr(trim($subject), 0, 255),
            'message' => trim($body),
            'timecreated' => $now,
            'timesent' => null,
            'status' => self::STATUS_PENDING,
        ];

        $record->id = (int)$DB->insert_record(self::TABLE, $record);
        $record = $DB->get_record(self::TABLE, ['id' => $record->id], '*', MUST_EXIST);

        if ($send) {
            $this->send($record, $context);
            $record = $DB->get_record(self::TABLE, ['id' => $record->id], '*', MUST_EXIST);
        }

        return $record;
    }

    /**
     * List recent notifications for a user.
     *
     * @param int $userid User id.
     * @param int $limit Maximum rows.
     * @return stdClass[]
     */
    public function list_for_user(int $userid, int $limit = 10): array {
        global $DB;

        return array_values($DB->get_records(self::TABLE, ['userid' => $userid], 'timecreated DESC, id DESC', '*', 0, $limit));
    }

    /**
     * Send/log a course start notification for an active CMC program-course mapping.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @return stdClass[] Created/existing notification rows.
     */
    public function notify_course_start(int $userid, int $courseid): array {
        global $DB;

        $sql = "SELECT pc.id, pc.programid, p.name AS programname, c.fullname AS coursefullname
                  FROM {local_cmc_lms_program_course} pc
                  JOIN {local_cmc_lms_program} p ON p.id = pc.programid
                  JOIN {course} c ON c.id = pc.courseid
                 WHERE pc.courseid = :courseid
                   AND p.active = 1
              ORDER BY p.name ASC, pc.id ASC";
        $mappings = $DB->get_records_sql($sql, ['courseid' => $courseid]);
        $notifications = [];
        foreach ($mappings as $mapping) {
            $subject = get_string('notificationcoursestartsubject', 'local_cmc_lms', $mapping->coursefullname);
            $body = get_string('notificationcoursestartbody', 'local_cmc_lms', (object)[
                'course' => $mapping->coursefullname,
                'program' => $mapping->programname,
            ]);
            $notifications[] = $this->create($userid, self::TYPE_COURSE_START, $subject, $body, [
                'courseid' => $courseid,
                'programid' => (int)$mapping->programid,
                'contexturl' => new moodle_url('/course/view.php', ['id' => $courseid]),
                'contexturlname' => $mapping->coursefullname,
            ]);
        }

        return $notifications;
    }

    /**
     * Send/log a certificate availability notification.
     *
     * @param stdClass $certificate Issued certificate record.
     * @return stdClass
     */
    public function notify_certificate_available(stdClass $certificate): stdClass {
        $subject = get_string('notificationcertificateavailablesubject', 'local_cmc_lms', $certificate->coursefullname);
        $body = get_string('notificationcertificateavailablebody', 'local_cmc_lms', (object)[
            'course' => $certificate->coursefullname,
            'code' => $certificate->code,
        ]);

        return $this->create((int)$certificate->userid, self::TYPE_CERTIFICATE_AVAILABLE, $subject, $body, [
            'courseid' => (int)$certificate->courseid,
            'programid' => $this->nullable_int($certificate->programid ?? null),
            'certificateid' => (int)$certificate->id,
            'contexturl' => new moodle_url('/local/cmc_lms/certificate_download.php', ['certid' => (int)$certificate->id]),
            'contexturlname' => get_string('certificatedownload', 'local_cmc_lms'),
        ]);
    }

    /**
     * Deliver a logged notification through Moodle messaging.
     *
     * @param stdClass $notification Notification row.
     * @param array $context Optional message context.
     * @return void
     */
    private function send(stdClass $notification, array $context): void {
        global $DB;

        try {
            $recipient = $DB->get_record('user', ['id' => (int)$notification->userid, 'deleted' => 0], '*', MUST_EXIST);
            $eventdata = new message();
            $eventdata->component = 'local_cmc_lms';
            $eventdata->name = $notification->type;
            $eventdata->notification = 1;
            $eventdata->userfrom = core_user::get_noreply_user();
            $eventdata->userto = $recipient;
            $eventdata->subject = $notification->subject;
            $eventdata->fullmessage = $notification->message;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml = '';
            $eventdata->smallmessage = $notification->subject;
            $eventdata->courseid = (int)($notification->courseid ?? SITEID);
            if (!empty($context['contexturl'])) {
                $eventdata->contexturl = $context['contexturl'] instanceof moodle_url
                    ? $context['contexturl']->out(false)
                    : (string)$context['contexturl'];
            }
            if (!empty($context['contexturlname'])) {
                $eventdata->contexturlname = (string)$context['contexturlname'];
            }
            $eventdata->customdata = [
                'notificationid' => (int)$notification->id,
                'programid' => $this->nullable_int($notification->programid ?? null),
                'certificateid' => $this->nullable_int($notification->certificateid ?? null),
            ];

            $sent = message_send($eventdata);
            $notification->timesent = $sent ? time() : null;
            $notification->status = $sent ? self::STATUS_SENT : self::STATUS_FAILED;
        } catch (Throwable $exception) {
            debugging('CMC notification delivery failed: ' . $exception->getMessage(), DEBUG_DEVELOPER);
            $notification->status = self::STATUS_FAILED;
        }

        $DB->update_record(self::TABLE, $notification);
    }

    /**
     * Find existing idempotent notification row.
     *
     * @param int $userid User id.
     * @param string $type Type.
     * @param int|null $courseid Course id.
     * @param int|null $programid Program id.
     * @param int|null $certificateid Certificate id.
     * @return stdClass|null
     */
    private function find_existing(int $userid, string $type, ?int $courseid, ?int $programid, ?int $certificateid): ?stdClass {
        global $DB;

        $conditions = ['userid = :userid', 'type = :type'];
        $params = ['userid' => $userid, 'type' => $type];
        foreach (['courseid' => $courseid, 'programid' => $programid, 'certificateid' => $certificateid] as $field => $value) {
            if ($value === null) {
                $conditions[] = "$field IS NULL";
            } else {
                $conditions[] = "$field = :$field";
                $params[$field] = $value;
            }
        }

        $records = $DB->get_records_select(self::TABLE, implode(' AND ', $conditions), $params, 'id ASC', '*', 0, 1);
        $record = reset($records);
        return $record ?: null;
    }

    /**
     * Validate and normalise type.
     *
     * @param string $type Type.
     * @return string
     */
    private function normalise_type(string $type): string {
        $allowed = [self::TYPE_COURSE_START, self::TYPE_INACTIVITY_REMINDER, self::TYPE_CERTIFICATE_AVAILABLE];
        if (!in_array($type, $allowed, true)) {
            throw new \coding_exception('Unsupported CMC notification type: ' . $type);
        }
        return $type;
    }

    /**
     * Convert empty values to null ints.
     *
     * @param mixed $value Value.
     * @return int|null
     */
    private function nullable_int($value): ?int {
        $value = (int)$value;
        return $value > 0 ? $value : null;
    }
}
