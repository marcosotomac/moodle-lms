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
}
