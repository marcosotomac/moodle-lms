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
 * Repository for B2B client companies.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_repository {
    /** @var string Database table name without Moodle prefix. */
    private const TABLE = 'local_cmc_lms_company';

    /**
     * Return companies ordered by display name.
     *
     * @param bool $activeonly Whether inactive companies should be excluded.
     * @return stdClass[]
     */
    public function list(bool $activeonly = true): array {
        global $DB;

        if ($activeonly) {
            return array_values($DB->get_records_select(self::TABLE, 'active = :active', ['active' => 1], 'name ASC, id ASC'));
        }

        return array_values($DB->get_records(self::TABLE, null, 'name ASC, id ASC'));
    }

    /**
     * Create a company record.
     *
     * @param stdClass $company Company data.
     * @return int New company id.
     */
    public function create(stdClass $company): int {
        global $DB;

        $now = time();
        $company->active = $company->active ?? 1;
        $company->timecreated = $company->timecreated ?? $now;
        $company->timemodified = $company->timemodified ?? $now;

        return (int) $DB->insert_record(self::TABLE, $company);
    }

    /**
     * Return a company by id.
     *
     * @param int $id Company id.
     * @return stdClass
     */
    public function get(int $id): stdClass {
        global $DB;

        return $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Update a company record.
     *
     * @param stdClass $company Company data including id.
     * @return void
     */
    public function update(stdClass $company): void {
        global $DB;

        $company->timemodified = time();
        $DB->update_record(self::TABLE, $company);
    }

    /**
     * Return users associated with a company.
     *
     * @param int $companyid Company id.
     * @return stdClass[]
     */
    public function list_users(int $companyid): array {
        global $DB;

        $sql = "SELECT cu.id,
                       cu.companyid,
                       cu.userid,
                       cu.companyrole,
                       cu.active,
                       u.firstname,
                       u.lastname,
                       u.email,
                       u.username
                  FROM {local_cmc_lms_company_user} cu
                  JOIN {user} u ON u.id = cu.userid
                 WHERE cu.companyid = :companyid
                   AND u.deleted = 0
              ORDER BY u.lastname ASC, u.firstname ASC, cu.companyrole ASC";

        return array_values($DB->get_records_sql($sql, ['companyid' => $companyid]));
    }

    /**
     * Associate a Moodle user with a company.
     *
     * The association is idempotent per company/user/role tuple: if it already
     * exists, the active flag is updated instead of creating a duplicate.
     *
     * @param int $companyid Company id.
     * @param int $userid Moodle user id.
     * @param string $companyrole Role inside the B2B company context.
     * @param bool $active Whether the association is active.
     * @return int Association id.
     */
    public function add_user(int $companyid, int $userid, string $companyrole = 'student', bool $active = true): int {
        global $DB;

        $companyrole = role_repository::normalise_company_role($companyrole);
        $now = time();
        $existing = $DB->get_record('local_cmc_lms_company_user', [
            'companyid' => $companyid,
            'userid' => $userid,
            'companyrole' => $companyrole,
        ]);

        if ($existing) {
            $existing->active = $active ? 1 : 0;
            $existing->timemodified = $now;
            $DB->update_record('local_cmc_lms_company_user', $existing);
            return (int) $existing->id;
        }

        return (int) $DB->insert_record('local_cmc_lms_company_user', (object) [
            'companyid' => $companyid,
            'userid' => $userid,
            'companyrole' => $companyrole,
            'active' => $active ? 1 : 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
