<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\local;

use context_system;
use stdClass;

/**
 * Scoped access rules for CMC business roles.
 *
 * Moodle roles/capabilities remain the security boundary for global admin
 * actions. This helper adds the CMC business-role scope required by the LMS
 * domain: program coordinators/teachers see their assigned programs, client
 * supervisors see their own companies, and students see their own panel.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_helper {
    /**
     * Does the user have global access to all CMC programs?
     *
     * @param int|null $userid User id, current user by default.
     * @return bool
     */
    public function can_view_all_programs(?int $userid = null): bool {
        $userid = $this->resolve_userid($userid);
        $context = context_system::instance();

        return is_siteadmin($userid)
            || has_capability('local/cmc_lms:viewprograms', $context, $userid)
            || has_capability('local/cmc_lms:manageprograms', $context, $userid);
    }

    /**
     * Does the user have at least one CMC program they may view?
     *
     * @param int|null $userid User id, current user by default.
     * @return bool
     */
    public function can_view_any_program(?int $userid = null): bool {
        $userid = $this->resolve_userid($userid);

        return $this->can_view_all_programs($userid) || !empty($this->get_scoped_program_ids($userid));
    }

    /**
     * Filter program records to the user's CMC scope.
     *
     * @param stdClass[] $programs Program records.
     * @param int|null $userid User id, current user by default.
     * @return stdClass[]
     */
    public function filter_programs(array $programs, ?int $userid = null): array {
        $userid = $this->resolve_userid($userid);
        if ($this->can_view_all_programs($userid)) {
            return $programs;
        }

        $allowed = array_flip($this->get_scoped_program_ids($userid));
        return array_values(array_filter($programs, static function (stdClass $program) use ($allowed): bool {
            return isset($allowed[(int)$program->id]);
        }));
    }

    /**
     * Return program ids visible through CMC role assignments or enrolment.
     *
     * @param int $userid User id.
     * @return int[]
     */
    public function get_scoped_program_ids(int $userid): array {
        global $DB;

        $assigned = $DB->get_fieldset_select(
            'local_cmc_lms_program_role',
            'programid',
            'userid = :userid AND active = :active',
            ['userid' => $userid, 'active' => 1]
        );

        $enrolledsql = "SELECT DISTINCT pc.programid
                          FROM {local_cmc_lms_program_course} pc
                          JOIN {enrol} e ON e.courseid = pc.courseid AND e.status = :enrolactive
                          JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = :useractive
                         WHERE ue.userid = :userid";
        $enrolled = $DB->get_fieldset_sql($enrolledsql, [
            'userid' => $userid,
            'enrolactive' => 0,
            'useractive' => 0,
        ]);

        return array_values(array_unique(array_map('intval', array_merge($assigned, $enrolled))));
    }

    /**
     * Does the user have global access to all CMC companies?
     *
     * @param int|null $userid User id, current user by default.
     * @return bool
     */
    public function can_view_all_companies(?int $userid = null): bool {
        $userid = $this->resolve_userid($userid);
        $context = context_system::instance();

        return is_siteadmin($userid)
            || has_capability('local/cmc_lms:viewcompanies', $context, $userid)
            || has_capability('local/cmc_lms:managecompanies', $context, $userid);
    }

    /**
     * Does the user have at least one CMC company they may view?
     *
     * @param int|null $userid User id, current user by default.
     * @return bool
     */
    public function can_view_any_company(?int $userid = null): bool {
        $userid = $this->resolve_userid($userid);

        return $this->can_view_all_companies($userid) || !empty($this->get_scoped_company_ids($userid));
    }

    /**
     * Does the user have access to a specific company?
     *
     * @param int $companyid Company id.
     * @param int|null $userid User id, current user by default.
     * @return bool
     */
    public function can_view_company(int $companyid, ?int $userid = null): bool {
        $userid = $this->resolve_userid($userid);
        if ($this->can_view_all_companies($userid)) {
            return true;
        }

        return in_array($companyid, $this->get_scoped_company_ids($userid), true);
    }

    /**
     * Filter company records to the user's CMC scope.
     *
     * @param stdClass[] $companies Company records.
     * @param int|null $userid User id, current user by default.
     * @return stdClass[]
     */
    public function filter_companies(array $companies, ?int $userid = null): array {
        $userid = $this->resolve_userid($userid);
        if ($this->can_view_all_companies($userid)) {
            return $companies;
        }

        $allowed = array_flip($this->get_scoped_company_ids($userid));
        return array_values(array_filter($companies, static function (stdClass $company) use ($allowed): bool {
            return isset($allowed[(int)$company->id]);
        }));
    }

    /**
     * Return active company ids visible through CMC company roles.
     *
     * @param int $userid User id.
     * @return int[]
     */
    public function get_scoped_company_ids(int $userid): array {
        global $DB;

        $sql = "SELECT DISTINCT cu.companyid
                  FROM {local_cmc_lms_company_user} cu
                  JOIN {local_cmc_lms_company} company ON company.id = cu.companyid
                 WHERE cu.userid = :userid
                   AND cu.active = :active
                   AND company.active = :companyactive";

        return array_values(array_map('intval', $DB->get_fieldset_sql($sql, [
            'userid' => $userid,
            'active' => 1,
            'companyactive' => 1,
        ])));
    }

    /**
     * Does the user have report access for a specific company?
     *
     * @param int $companyid Company id.
     * @param int|null $userid User id, current user by default.
     * @return bool
     */
    public function can_view_company_report(int $companyid, ?int $userid = null): bool {
        $userid = $this->resolve_userid($userid);
        $context = context_system::instance();

        return is_siteadmin($userid)
            || has_capability('local/cmc_lms:viewreports', $context, $userid)
            || has_capability('local/cmc_lms:viewcompanyreports', $context, $userid)
            || $this->has_company_role($companyid, $userid, [role_repository::ROLE_CLIENT_SUPERVISOR]);
    }

    /**
     * Does the user have an active client supervisor company scope?
     *
     * @param int|null $userid User id, current user by default.
     * @return bool
     */
    public function has_client_supervisor_scope(?int $userid = null): bool {
        $userid = $this->resolve_userid($userid);
        $context = context_system::instance();
        if (is_siteadmin($userid)
            || has_capability('local/cmc_lms:viewreports', $context, $userid)
            || has_capability('local/cmc_lms:viewcompanyreports', $context, $userid)) {
            return true;
        }

        $companyids = $this->get_scoped_company_ids($userid);
        if (empty($companyids)) {
            return false;
        }

        foreach ($companyids as $companyid) {
            if ($this->has_company_role($companyid, $userid, [role_repository::ROLE_CLIENT_SUPERVISOR])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Can the user view another student's CMC panel?
     *
     * @param int $targetuserid Student/user id to view.
     * @param int|null $userid Viewer id, current user by default.
     * @return bool
     */
    public function can_view_student(int $targetuserid, ?int $userid = null): bool {
        $userid = $this->resolve_userid($userid);
        if ($userid === $targetuserid) {
            return true;
        }

        $context = context_system::instance();
        if (is_siteadmin($userid) || has_capability('local/cmc_lms:viewstudentpanel', $context, $userid)) {
            return true;
        }

        return $this->supervises_student_company($userid, $targetuserid)
            || $this->shares_assigned_program_with_student($userid, $targetuserid);
    }

    /**
     * Filter attendance links to the user's assigned program scope.
     *
     * @param stdClass[] $links Program-course links.
     * @param int|null $userid User id, current user by default.
     * @return stdClass[]
     */
    public function filter_attendance_links(array $links, ?int $userid = null): array {
        $userid = $this->resolve_userid($userid);
        $context = context_system::instance();
        if (is_siteadmin($userid) || has_capability('local/cmc_lms:manageattendance', $context, $userid)) {
            return $links;
        }

        $programids = array_flip($this->get_assigned_staff_program_ids($userid));
        return array_values(array_filter($links, static function (stdClass $link) use ($programids): bool {
            return isset($programids[(int)$link->programid]);
        }));
    }

    /**
     * Does the user have any attendance scope?
     *
     * @param int|null $userid User id, current user by default.
     * @return bool
     */
    public function can_manage_any_attendance(?int $userid = null): bool {
        $userid = $this->resolve_userid($userid);
        $context = context_system::instance();

        return is_siteadmin($userid)
            || has_capability('local/cmc_lms:manageattendance', $context, $userid)
            || !empty($this->get_assigned_staff_program_ids($userid));
    }

    /**
     * Return active coordinator/teacher program ids for the user.
     *
     * @param int $userid User id.
     * @return int[]
     */
    private function get_assigned_staff_program_ids(int $userid): array {
        global $DB;

        [$rolesql, $params] = $DB->get_in_or_equal(role_repository::PROGRAM_ROLES, SQL_PARAMS_NAMED, 'role');
        $params['userid'] = $userid;
        $params['active'] = 1;

        return array_values(array_map('intval', $DB->get_fieldset_select(
            'local_cmc_lms_program_role',
            'programid',
            "userid = :userid AND active = :active AND cmcrole $rolesql",
            $params
        )));
    }

    /**
     * Check a company role assignment.
     *
     * @param int $companyid Company id.
     * @param int $userid User id.
     * @param string[] $roles Accepted CMC roles.
     * @return bool
     */
    private function has_company_role(int $companyid, int $userid, array $roles): bool {
        global $DB;

        [$rolesql, $params] = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'role');
        $params['companyid'] = $companyid;
        $params['userid'] = $userid;
        $params['active'] = 1;

        return $DB->record_exists_select(
            'local_cmc_lms_company_user',
            "companyid = :companyid AND userid = :userid AND active = :active AND companyrole $rolesql",
            $params
        );
    }

    /**
     * Return whether a client supervisor shares an active company with a student.
     *
     * @param int $supervisorid Supervisor user id.
     * @param int $studentid Student user id.
     * @return bool
     */
    private function supervises_student_company(int $supervisorid, int $studentid): bool {
        global $DB;

        $sql = "SELECT 1
                  FROM {local_cmc_lms_company_user} supervisor
                  JOIN {local_cmc_lms_company_user} student ON student.companyid = supervisor.companyid
                 WHERE supervisor.userid = :supervisorid
                   AND supervisor.companyrole = :supervisorrole
                   AND supervisor.active = :active
                   AND student.userid = :studentid
                   AND student.companyrole = :studentrole
                   AND student.active = :studentactive";

        return $DB->record_exists_sql($sql, [
            'supervisorid' => $supervisorid,
            'studentid' => $studentid,
            'supervisorrole' => role_repository::ROLE_CLIENT_SUPERVISOR,
            'studentrole' => role_repository::ROLE_STUDENT,
            'active' => 1,
            'studentactive' => 1,
        ]);
    }

    /**
     * Return whether a coordinator/teacher shares an assigned program with a student enrolment.
     *
     * @param int $staffid Coordinator/teacher user id.
     * @param int $studentid Student user id.
     * @return bool
     */
    private function shares_assigned_program_with_student(int $staffid, int $studentid): bool {
        global $DB;

        $assigned = $this->get_assigned_staff_program_ids($staffid);
        if (empty($assigned)) {
            return false;
        }

        [$programsql, $params] = $DB->get_in_or_equal($assigned, SQL_PARAMS_NAMED, 'program');
        $params['studentid'] = $studentid;
        $params['enrolactive'] = 0;
        $params['useractive'] = 0;

        $sql = "SELECT 1
                  FROM {local_cmc_lms_program_course} pc
                  JOIN {enrol} e ON e.courseid = pc.courseid AND e.status = :enrolactive
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = :useractive
                 WHERE pc.programid $programsql
                   AND ue.userid = :studentid";

        return $DB->record_exists_sql($sql, $params);
    }

    /**
     * Resolve current user id when none is passed.
     *
     * @param int|null $userid Optional user id.
     * @return int
     */
    private function resolve_userid(?int $userid): int {
        global $USER;

        return $userid ?? (int)$USER->id;
    }
}
