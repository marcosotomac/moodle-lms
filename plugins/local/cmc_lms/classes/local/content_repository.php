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
 * Repository for reusable CMC content items and immutable ISO content versions.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_repository {
    /** @var string Content item database table name without Moodle prefix. */
    private const CONTENT_ITEM_TABLE = 'local_cmc_lms_content_item';

    /** @var string Content version database table name without Moodle prefix. */
    private const CONTENT_VERSION_TABLE = 'local_cmc_lms_content_version';

    /** @var string[] Supported reusable content types. */
    public const CONTENT_TYPES = ['video', 'document', 'external', 'lesson', 'quiz', 'other'];

    /** @var string[] Supported immutable version statuses. */
    public const VERSION_STATUSES = ['draft', 'published', 'obsolete'];

    /**
     * Create a reusable content item.
     *
     * @param stdClass $item Item data.
     * @return int New content item id.
     */
    public function create_item(stdClass $item): int {
        global $DB;

        $now = time();
        $record = (object)[
            'name' => $item->name,
            'code' => $item->code,
            'contenttype' => $this->normalise_choice($item->contenttype ?? 'other', self::CONTENT_TYPES, 'other'),
            'sourceurl' => $item->sourceurl ?? '',
            'isoreference' => $item->isoreference ?? '',
            'description' => $item->description ?? '',
            'active' => empty($item->active) ? 0 : 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        return (int)$DB->insert_record(self::CONTENT_ITEM_TABLE, $record);
    }

    /**
     * Create an immutable content version record.
     *
     * Versions are append-only by design. Updating an ISO content version would
     * destroy auditability, so changes must be represented by a new version row.
     *
     * @param int $contentitemid Reusable content item id.
     * @param stdClass $version Version data.
     * @param int $userid User creating the version.
     * @return int New content version id.
     */
    public function create_version(int $contentitemid, stdClass $version, int $userid = 0): int {
        global $DB;

        $DB->get_record(self::CONTENT_ITEM_TABLE, ['id' => $contentitemid], 'id', MUST_EXIST);
        $record = (object)[
            'contentitemid' => $contentitemid,
            'versioncode' => $version->versioncode,
            'changenotes' => $version->changenotes ?? '',
            'effectivefrom' => (int)($version->effectivefrom ?? 0),
            'status' => $this->normalise_choice($version->status ?? 'draft', self::VERSION_STATUSES, 'draft'),
            'immutable' => 1,
            'createdby' => $userid,
            'timecreated' => time(),
        ];

        return (int)$DB->insert_record(self::CONTENT_VERSION_TABLE, $record);
    }

    /**
     * Create a reusable content item and its first immutable version atomically.
     *
     * @param stdClass $data Submitted item and version data.
     * @param int $userid User creating the records.
     * @return stdClass Object with contentitemid and contentversionid.
     */
    public function create_item_with_initial_version(stdClass $data, int $userid = 0): stdClass {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $contentitemid = $this->create_item($data);
        $contentversionid = $this->create_version($contentitemid, $data, $userid);
        $transaction->allow_commit();

        return (object)[
            'contentitemid' => $contentitemid,
            'contentversionid' => $contentversionid,
        ];
    }

    /**
     * Return a reusable content item.
     *
     * @param int $id Content item id.
     * @return stdClass
     */
    public function get_item(int $id): stdClass {
        global $DB;

        return $DB->get_record(self::CONTENT_ITEM_TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Return an immutable content version with item context.
     *
     * @param int $id Content version id.
     * @return stdClass
     */
    public function get_version(int $id): stdClass {
        global $DB;

        $sql = "SELECT v.id,
                       v.contentitemid,
                       v.versioncode,
                       v.changenotes,
                       v.effectivefrom,
                       v.status,
                       v.immutable,
                       v.createdby,
                       v.timecreated,
                       i.name AS contentname,
                       i.code AS contentcode,
                       i.contenttype,
                       i.sourceurl,
                       i.isoreference,
                       i.active
                  FROM {local_cmc_lms_content_version} v
                  JOIN {local_cmc_lms_content_item} i ON i.id = v.contentitemid
                 WHERE v.id = :id";

        return $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);
    }

    /**
     * List reusable content items with their latest version summary.
     *
     * @param bool $activeonly Whether inactive items should be excluded.
     * @return stdClass[]
     */
    public function list_items_with_latest_version(bool $activeonly = false): array {
        global $DB;

        $where = $activeonly ? 'WHERE i.active = :active' : '';
        $params = $activeonly ? ['active' => 1] : [];
        $sql = "SELECT i.id,
                       i.name,
                       i.code,
                       i.contenttype,
                       i.sourceurl,
                       i.isoreference,
                       i.description,
                       i.active,
                       i.timecreated,
                       i.timemodified,
                       latest.versionid,
                       latest.versioncode,
                       latest.versionstatus,
                       latest.effectivefrom
                  FROM {local_cmc_lms_content_item} i
             LEFT JOIN (
                       SELECT v.contentitemid,
                              v.id AS versionid,
                              v.versioncode,
                              v.status AS versionstatus,
                              v.effectivefrom
                         FROM {local_cmc_lms_content_version} v
                         JOIN (
                              SELECT contentitemid, MAX(id) AS latestid
                                FROM {local_cmc_lms_content_version}
                            GROUP BY contentitemid
                              ) mx ON mx.latestid = v.id
                       ) latest ON latest.contentitemid = i.id
                       $where
              ORDER BY i.name ASC, i.id ASC";

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * List all immutable versions for one content item.
     *
     * @param int $contentitemid Content item id.
     * @return stdClass[]
     */
    public function list_versions(int $contentitemid): array {
        global $DB;

        return array_values($DB->get_records(
            self::CONTENT_VERSION_TABLE,
            ['contentitemid' => $contentitemid],
            'effectivefrom DESC, id DESC'
        ));
    }

    /**
     * Return select options for active reusable content versions.
     *
     * @return array<int,string>
     */
    public function get_version_options(): array {
        global $DB;

        $sql = "SELECT v.id,
                       i.name,
                       i.code,
                       i.contenttype,
                       i.isoreference,
                       v.versioncode,
                       v.status
                  FROM {local_cmc_lms_content_version} v
                  JOIN {local_cmc_lms_content_item} i ON i.id = v.contentitemid
                 WHERE i.active = :active
              ORDER BY i.name ASC, v.effectivefrom DESC, v.id DESC";
        $records = $DB->get_records_sql($sql, ['active' => 1]);
        $options = [0 => get_string('none')];
        foreach ($records as $record) {
            $label = $record->name . ' [' . $record->code . '] v' . $record->versioncode;
            if (!empty($record->isoreference)) {
                $label .= ' — ' . $record->isoreference;
            }
            $label .= ' (' . get_string('contentformat' . $record->contenttype, 'local_cmc_lms') . ')';
            if ($record->status !== 'published') {
                $label .= ' · ' . get_string('contentversionstatus' . $record->status, 'local_cmc_lms');
            }
            $options[(int)$record->id] = $label;
        }

        return $options;
    }

    /**
     * Keep persisted enum-like metadata portable and backward compatible.
     *
     * @param string $value Candidate value.
     * @param array $allowed Allowed values.
     * @param string $default Fallback value.
     * @return string
     */
    private function normalise_choice(string $value, array $allowed, string $default): string {
        $value = strtolower(trim($value));
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
