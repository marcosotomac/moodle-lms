<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\local;

use invalid_parameter_exception;
use stdClass;

/**
 * Repository for CMC evaluation mappings backed by Moodle Quiz.
 *
 * CMC does not duplicate quiz questions, attempts or grading. Moodle Quiz remains
 * the source of truth for MCQ/true-false mechanics, attempts, timing and grade
 * history; this repository stores only the CMC business mapping and reporting
 * thresholds used by the strict LMS 5.4 layer.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluation_repository {
    /** @var string Evaluation rule table name without Moodle prefix. */
    private const RULE_TABLE = 'local_cmc_lms_eval_rule';

    /** @var string Module-level evaluation scope. */
    public const SCOPE_MODULE = 'module';

    /** @var string Course-level evaluation scope. */
    public const SCOPE_COURSE = 'course';

    /** @var string[] Supported evaluation scopes. */
    public const SCOPES = [self::SCOPE_MODULE, self::SCOPE_COURSE];

    /**
     * List existing Moodle Quiz activities available for CMC evaluation mapping.
     *
     * @param int $courseid Optional Moodle course id filter.
     * @return stdClass[] Rows with course, course module and quiz metadata.
     */
    public function list_quiz_activities(int $courseid = 0): array {
        global $DB;

        $where = 'm.name = :modulename AND cm.deletioninprogress = :notdeleting';
        $params = [
            'modulename' => 'quiz',
            'notdeleting' => 0,
        ];
        if ($courseid > 0) {
            $where .= ' AND c.id = :courseid';
            $params['courseid'] = $courseid;
        }

        $sql = "SELECT cm.id AS cmid,
                       cm.course AS courseid,
                       cm.visible,
                       q.id AS quizid,
                       q.name AS quizname,
                       q.grade,
                       q.sumgrades,
                       q.attempts,
                       q.timelimit,
                       c.fullname AS coursefullname,
                       c.shortname AS courseshortname
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {quiz} q ON q.id = cm.instance
                  JOIN {course} c ON c.id = cm.course
                 WHERE {$where}
              ORDER BY c.fullname ASC, q.name ASC, cm.id ASC";

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Create or update a CMC evaluation rule idempotently.
     *
     * The idempotency key is program + course + quiz + course module + scope.
     * Program is optional; null means the rule applies to the Moodle course
     * without narrowing it to a specific CMC program.
     *
     * @param stdClass $rule Rule data.
     * @return int Rule id.
     */
    public function save_rule(stdClass $rule): int {
        global $DB;

        $quizactivity = $this->resolve_quiz_activity((int)($rule->cmid ?? 0), (int)($rule->quizid ?? 0), (int)($rule->courseid ?? 0));
        $scope = $this->normalise_scope($rule->scope ?? self::SCOPE_MODULE);
        $programid = empty($rule->programid) ? null : (int)$rule->programid;
        if ($programid !== null && !$DB->record_exists('local_cmc_lms_program', ['id' => $programid])) {
            throw new invalid_parameter_exception('Invalid CMC program id.');
        }

        $now = time();
        $record = (object)[
            'programid' => $programid,
            'courseid' => (int)$quizactivity->courseid,
            'quizid' => (int)$quizactivity->quizid,
            'cmid' => (int)$quizactivity->cmid,
            'scope' => $scope,
            'passgrade' => max(0, (float)($rule->passgrade ?? 0)),
            'passpercentage' => max(0, min(100, (float)($rule->passpercentage ?? 0))),
            'maxattempts' => max(0, (int)($rule->maxattempts ?? $quizactivity->attempts ?? 0)),
            'timelimit' => max(0, (int)($rule->timelimit ?? $quizactivity->timelimit ?? 0)),
            'active' => isset($rule->active) ? (empty($rule->active) ? 0 : 1) : 1,
            'timemodified' => $now,
        ];

        $existing = $this->find_existing_rule($record);
        if ($existing !== null) {
            $record->id = (int)$existing->id;
            $DB->update_record(self::RULE_TABLE, $record);
            return (int)$existing->id;
        }

        $record->timecreated = $now;
        return (int)$DB->insert_record(self::RULE_TABLE, $record);
    }

    /**
     * Return CMC evaluation rules with Moodle course and quiz labels.
     *
     * @param bool $activeonly Whether inactive rules should be excluded.
     * @return stdClass[]
     */
    public function list_rules(bool $activeonly = false): array {
        global $DB;

        $where = $activeonly ? 'WHERE r.active = :active' : '';
        $params = $activeonly ? ['active' => 1] : [];
        $sql = "SELECT r.*,
                       p.name AS programname,
                       p.shortname AS programshortname,
                       c.fullname AS coursefullname,
                       c.shortname AS courseshortname,
                       q.name AS quizname,
                       q.grade AS quizgrade,
                       q.sumgrades AS quizsumgrades
                  FROM {local_cmc_lms_eval_rule} r
             LEFT JOIN {local_cmc_lms_program} p ON p.id = r.programid
                  JOIN {course} c ON c.id = r.courseid
                  JOIN {quiz} q ON q.id = r.quizid
                 {$where}
              ORDER BY c.fullname ASC, q.name ASC, r.scope ASC, r.id ASC";

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Return one CMC evaluation rule by id.
     *
     * @param int $id Rule id.
     * @return stdClass
     */
    public function get_rule(int $id): stdClass {
        global $DB;

        return $DB->get_record(self::RULE_TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Aggregate Moodle Quiz historical student results for a CMC rule.
     *
     * Assumptions: final grade is read from {quiz_grades}.grade because Moodle
     * Quiz already applies the activity's grading method there. If no final
     * grade exists yet, best finished attempt sumgrades is scaled to quiz.grade
     * as a defensive preview value. Attempt count and last attempt include all
     * non-preview attempts so in-progress history remains visible.
     *
     * @param int $ruleid Rule id.
     * @return stdClass[] Rows with user identity, attempts, grades and pass flag.
     */
    public function get_results_for_rule(int $ruleid): array {
        global $DB;

        $rule = $this->get_rule($ruleid);
        $sql = "SELECT u.id AS userid,
                       u.firstname,
                       u.lastname,
                       u.email,
                       COUNT(qa.id) AS attempts,
                       MAX(CASE WHEN qa.state = :finishedstate THEN qa.sumgrades ELSE NULL END) AS bestsumgrades,
                       MAX(CASE WHEN qa.timefinish > 0 THEN qa.timefinish ELSE qa.timemodified END) AS lastattempttime,
                       qg.grade AS finalgrade,
                       q.grade AS quizgrade,
                       q.sumgrades AS quizsumgrades
                  FROM {quiz_attempts} qa
                  JOIN {user} u ON u.id = qa.userid
                  JOIN {quiz} q ON q.id = qa.quiz
             LEFT JOIN {quiz_grades} qg ON qg.quiz = qa.quiz AND qg.userid = qa.userid
                 WHERE qa.quiz = :quizid
                   AND qa.preview = :preview
                   AND u.deleted = :notdeleted
              GROUP BY u.id, u.firstname, u.lastname, u.email, qg.grade, q.grade, q.sumgrades
              ORDER BY u.lastname ASC, u.firstname ASC, u.id ASC";

        $rows = array_values($DB->get_records_sql($sql, [
            'finishedstate' => 'finished',
            'quizid' => (int)$rule->quizid,
            'preview' => 0,
            'notdeleted' => 0,
        ]));

        foreach ($rows as $row) {
            $this->normalise_result_row($row, $rule);
        }

        return $rows;
    }

    /**
     * Find the existing idempotent rule, including nullable program id matching.
     *
     * @param stdClass $record Candidate rule.
     * @return stdClass|null
     */
    private function find_existing_rule(stdClass $record): ?stdClass {
        global $DB;

        $programsql = $record->programid === null ? 'programid IS NULL' : 'programid = :programid';
        $params = [
            'courseid' => $record->courseid,
            'quizid' => $record->quizid,
            'cmid' => $record->cmid,
            'scope' => $record->scope,
        ];
        if ($record->programid !== null) {
            $params['programid'] = $record->programid;
        }

        $select = "courseid = :courseid AND quizid = :quizid AND cmid = :cmid AND scope = :scope AND {$programsql}";
        $records = $DB->get_records_select(self::RULE_TABLE, $select, $params, 'id ASC', '*', 0, 1);
        return empty($records) ? null : reset($records);
    }

    /**
     * Resolve and validate a Moodle Quiz activity from cmid, quizid or courseid.
     *
     * @param int $cmid Course module id.
     * @param int $quizid Quiz instance id.
     * @param int $courseid Course id.
     * @return stdClass
     */
    private function resolve_quiz_activity(int $cmid, int $quizid, int $courseid): stdClass {
        $activities = $this->list_quiz_activities($courseid);
        foreach ($activities as $activity) {
            if ($cmid > 0 && (int)$activity->cmid !== $cmid) {
                continue;
            }
            if ($quizid > 0 && (int)$activity->quizid !== $quizid) {
                continue;
            }
            if ($courseid > 0 && (int)$activity->courseid !== $courseid) {
                continue;
            }
            return $activity;
        }

        throw new invalid_parameter_exception('Invalid Moodle Quiz activity.');
    }

    /**
     * Normalise evaluation scope.
     *
     * @param string $scope Candidate scope.
     * @return string
     */
    private function normalise_scope(string $scope): string {
        $scope = strtolower(trim($scope));
        return in_array($scope, self::SCOPES, true) ? $scope : self::SCOPE_MODULE;
    }

    /**
     * Cast and enrich a result row.
     *
     * @param stdClass $row Result row.
     * @param stdClass $rule Evaluation rule.
     * @return void
     */
    private function normalise_result_row(stdClass $row, stdClass $rule): void {
        $row->userid = (int)$row->userid;
        $row->fullname = fullname($row);
        $row->attempts = (int)$row->attempts;
        $row->bestgrade = $this->scale_grade((float)($row->bestsumgrades ?? 0), (float)$row->quizsumgrades, (float)$row->quizgrade);
        $row->finalgrade = $row->finalgrade === null ? $row->bestgrade : (float)$row->finalgrade;
        $row->lastattempttime = (int)($row->lastattempttime ?? 0);
        $row->passed = $this->is_passed((float)$row->finalgrade, (float)$row->quizgrade, $rule);
    }

    /**
     * Scale quiz attempt sumgrades to the quiz final grade scale.
     *
     * @param float $sumgrade Attempt sumgrades.
     * @param float $quizsumgrades Quiz raw sumgrades.
     * @param float $quizgrade Quiz grade scale.
     * @return float
     */
    private function scale_grade(float $sumgrade, float $quizsumgrades, float $quizgrade): float {
        if ($quizsumgrades <= 0 || $quizgrade <= 0) {
            return round($sumgrade, 5);
        }

        return round(($sumgrade / $quizsumgrades) * $quizgrade, 5);
    }

    /**
     * Determine if a final grade satisfies the CMC rule thresholds.
     *
     * @param float $finalgrade Final grade on the quiz grade scale.
     * @param float $quizgrade Quiz grade scale.
     * @param stdClass $rule Evaluation rule.
     * @return bool
     */
    private function is_passed(float $finalgrade, float $quizgrade, stdClass $rule): bool {
        if ((float)$rule->passgrade > 0) {
            return $finalgrade >= (float)$rule->passgrade;
        }
        if ((float)$rule->passpercentage > 0 && $quizgrade > 0) {
            return (($finalgrade / $quizgrade) * 100) >= (float)$rule->passpercentage;
        }

        return false;
    }
}
