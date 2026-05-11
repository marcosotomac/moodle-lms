<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\local;

use coding_exception;
use dml_exception;
use moodle_url;
use stdClass;

/**
 * Repository for issued CMC certificates.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_repository {
    /** @var string Certificate database table name without Moodle prefix. */
    private const TABLE = 'local_cmc_lms_cert';

    /** @var string Issued certificate status. */
    public const STATUS_ISSUED = 'issued';

    /** @var string Revoked certificate status. */
    public const STATUS_REVOKED = 'revoked';

    /**
     * Issue a certificate idempotently for an active user/course/context tuple.
     *
     * An existing issued certificate for the same user, course, company and program
     * is returned as-is. Revoked certificates are intentionally not reused.
     *
     * @param int $userid Moodle user id.
     * @param int $courseid Moodle course id.
     * @param int|null $companyid Optional CMC company id.
     * @param int|null $programid Optional CMC program id.
     * @param int $issuerid Moodle user id issuing the certificate.
     * @return stdClass Certificate record enriched with joined display data.
     * @throws dml_exception
     */
    public function issue(int $userid, int $courseid, ?int $companyid, ?int $programid, int $issuerid): stdClass {
        global $DB;

        $companyid = $companyid ?: null;
        $programid = $programid ?: null;

        $existing = $this->get_existing_issued($userid, $courseid, $companyid, $programid);
        if ($existing !== null) {
            return $this->get_by_id((int) $existing->id);
        }

        $now = time();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $record = (object) [
                'userid' => $userid,
                'courseid' => $courseid,
                'companyid' => $companyid,
                'programid' => $programid,
                'code' => $this->generate_code($userid, $courseid),
                'verifytoken' => $this->generate_token(),
                'timeissued' => $now,
                'issuerid' => $issuerid,
                'status' => self::STATUS_ISSUED,
                'timerevoked' => null,
                'revokerid' => null,
                'revocationreason' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ];

            if ($DB->record_exists(self::TABLE, ['code' => $record->code]) ||
                    $DB->record_exists(self::TABLE, ['verifytoken' => $record->verifytoken])) {
                continue;
            }

            $id = (int) $DB->insert_record(self::TABLE, $record);
            return $this->get_by_id($id);
        }

        throw new coding_exception('Unable to generate a unique CMC certificate code after multiple attempts.');
    }

    /**
     * List recent certificates with joined Moodle and CMC display data.
     *
     * @param int $limit Maximum rows to return.
     * @return stdClass[]
     */
    public function list_recent(int $limit = 50): array {
        global $DB;

        $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
        $sql = "SELECT cert.id,
                       cert.userid,
                       cert.courseid,
                       cert.companyid,
                       cert.programid,
                       cert.code,
                       cert.verifytoken,
                       cert.timeissued,
                       cert.issuerid,
                       cert.status,
                       cert.timerevoked,
                       cert.revokerid,
                       cert.revocationreason,
                       {$fullname} AS userfullname,
                       u.email AS useremail,
                       c.fullname AS coursefullname,
                       c.shortname AS courseshortname,
                       company.name AS companyname,
                       program.name AS programname
                  FROM {local_cmc_lms_cert} cert
                  JOIN {user} u ON u.id = cert.userid
                  JOIN {course} c ON c.id = cert.courseid
             LEFT JOIN {local_cmc_lms_company} company ON company.id = cert.companyid
             LEFT JOIN {local_cmc_lms_program} program ON program.id = cert.programid
              ORDER BY cert.timeissued DESC, cert.id DESC";

        return array_values($DB->get_records_sql($sql, [], 0, $limit));
    }

    /**
     * Fetch a certificate by public verification token or readable code.
     *
     * @param string $identifier Public token/hash or certificate code.
     * @return stdClass|null
     */
    public function get_for_verification(string $identifier): ?stdClass {
        global $DB;

        $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
        $sql = "SELECT cert.id,
                       cert.userid,
                       cert.courseid,
                       cert.companyid,
                       cert.programid,
                       cert.code,
                       cert.verifytoken,
                       cert.timeissued,
                       cert.issuerid,
                       cert.status,
                       cert.timerevoked,
                       cert.revokerid,
                       cert.revocationreason,
                       {$fullname} AS userfullname,
                       c.fullname AS coursefullname,
                       c.shortname AS courseshortname,
                       company.name AS companyname,
                       program.name AS programname
                  FROM {local_cmc_lms_cert} cert
                  JOIN {user} u ON u.id = cert.userid
                  JOIN {course} c ON c.id = cert.courseid
             LEFT JOIN {local_cmc_lms_company} company ON company.id = cert.companyid
             LEFT JOIN {local_cmc_lms_program} program ON program.id = cert.programid
                 WHERE cert.verifytoken = :token OR cert.code = :code";

        $record = $DB->get_record_sql($sql, ['token' => $identifier, 'code' => $identifier], IGNORE_MULTIPLE);
        return $record ?: null;
    }

    /**
     * Fetch a certificate by id with joined display data.
     *
     * @param int $id Certificate id.
     * @return stdClass
     */
    public function get_by_id(int $id): stdClass {
        global $DB;

        $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
        $sql = "SELECT cert.id,
                       cert.userid,
                       cert.courseid,
                       cert.companyid,
                       cert.programid,
                       cert.code,
                       cert.verifytoken,
                       cert.timeissued,
                       cert.issuerid,
                       cert.status,
                       cert.timerevoked,
                       cert.revokerid,
                       cert.revocationreason,
                       {$fullname} AS userfullname,
                       u.email AS useremail,
                       c.fullname AS coursefullname,
                       c.shortname AS courseshortname,
                       company.name AS companyname,
                       program.name AS programname
                  FROM {local_cmc_lms_cert} cert
                  JOIN {user} u ON u.id = cert.userid
                  JOIN {course} c ON c.id = cert.courseid
             LEFT JOIN {local_cmc_lms_company} company ON company.id = cert.companyid
             LEFT JOIN {local_cmc_lms_program} program ON program.id = cert.programid
                 WHERE cert.id = :id";

        return $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);
    }

    /**
     * Revoke an issued certificate.
     *
     * @param int $certificateid Certificate id.
     * @param int $revokerid Moodle user id revoking the certificate.
     * @param string $reason Optional revocation reason.
     * @return void
     */
    public function revoke(int $certificateid, int $revokerid, string $reason = ''): void {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['id' => $certificateid], '*', MUST_EXIST);
        if ($record->status === self::STATUS_REVOKED) {
            return;
        }

        $record->status = self::STATUS_REVOKED;
        $record->timerevoked = time();
        $record->revokerid = $revokerid;
        $record->revocationreason = $reason;
        $record->timemodified = time();
        $DB->update_record(self::TABLE, $record);
    }

    /**
     * Build the public verification URL for a certificate.
     *
     * @param stdClass $certificate Certificate record with verifytoken.
     * @return moodle_url
     */
    public function get_verification_url(stdClass $certificate): moodle_url {
        return new moodle_url('/local/cmc_lms/verify_certificate.php', ['t' => $certificate->verifytoken]);
    }

    /**
     * Return an existing issued certificate for the exact context tuple.
     *
     * @param int $userid Moodle user id.
     * @param int $courseid Moodle course id.
     * @param int|null $companyid Optional company id.
     * @param int|null $programid Optional program id.
     * @return stdClass|null
     */
    private function get_existing_issued(int $userid, int $courseid, ?int $companyid, ?int $programid): ?stdClass {
        global $DB;

        $conditions = [
            'userid = :userid',
            'courseid = :courseid',
            'status = :status',
        ];
        $params = [
            'userid' => $userid,
            'courseid' => $courseid,
            'status' => self::STATUS_ISSUED,
        ];

        if ($companyid === null) {
            $conditions[] = 'companyid IS NULL';
        } else {
            $conditions[] = 'companyid = :companyid';
            $params['companyid'] = $companyid;
        }

        if ($programid === null) {
            $conditions[] = 'programid IS NULL';
        } else {
            $conditions[] = 'programid = :programid';
            $params['programid'] = $programid;
        }

        $records = $DB->get_records_select(self::TABLE, implode(' AND ', $conditions), $params, 'id ASC', 'id', 0, 1);
        $record = reset($records);

        return $record ?: null;
    }

    /**
     * Generate a readable, auditable certificate code.
     *
     * @param int $userid Moodle user id.
     * @param int $courseid Moodle course id.
     * @return string
     */
    private function generate_code(int $userid, int $courseid): string {
        $suffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        return sprintf('CMC-%s-U%d-C%d-%s', date('Y'), $userid, $courseid, $suffix);
    }

    /**
     * Generate a public verification token.
     *
     * @return string
     */
    private function generate_token(): string {
        return hash('sha256', random_bytes(32));
    }
}
