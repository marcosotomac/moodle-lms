<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\local;

use context_course;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/enrollib.php');

/**
 * Automatic CMC user provisioning service.
 *
 * This service creates or reuses Moodle users, associates them to a CMC client
 * company, and enrols them in every Moodle course linked to a CMC program.
 * It deliberately uses Moodle core user/enrol APIs so authentication and course
 * roles remain owned by Moodle.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_provisioning_service {
    /** @var string User was created by this call. */
    public const USER_STATUS_CREATED = 'created';

    /** @var string Existing user was reused. */
    public const USER_STATUS_EXISTING = 'existing';

    /**
     * Create/reuse a user and enrol them in a CMC program.
     *
     * @param stdClass $data Provisioning data.
     * @return stdClass Provisioning result.
     */
    public function provision(stdClass $data): stdClass {
        global $DB;

        $companyid = (int)($data->companyid ?? 0);
        $programid = (int)($data->programid ?? 0);
        $email = strtolower(trim((string)($data->email ?? '')));
        $username = strtolower(trim((string)($data->username ?? '')));
        $firstname = trim((string)($data->firstname ?? ''));
        $lastname = trim((string)($data->lastname ?? ''));
        $companyrole = role_repository::normalise_company_role((string)($data->companyrole ?? role_repository::ROLE_STUDENT));
        $roleshortname = trim((string)($data->roleshortname ?? 'student'));
        $password = (string)($data->password ?? '');

        if ($email === '' || !validate_email($email)) {
            throw new moodle_exception('invalidemail');
        }
        if ($firstname === '' || $lastname === '') {
            throw new moodle_exception('required');
        }
        if ($username === '') {
            $username = $email;
        }

        $companyrepository = new company_repository();
        $programrepository = new program_repository();
        $companyrepository->get($companyid);
        $programrepository->get($programid);
        $role = $DB->get_record('role', ['shortname' => $roleshortname], '*', MUST_EXIST);

        $transaction = $DB->start_delegated_transaction();
        [$userid, $userstatus] = $this->create_or_reuse_user($username, $email, $firstname, $lastname, $password);
        $associationid = $companyrepository->add_user($companyid, $userid, $companyrole, true);
        $enrolments = $this->enrol_in_program_courses($programid, $userid, (int)$role->id);
        $transaction->allow_commit();

        return (object)[
            'userid' => $userid,
            'userstatus' => $userstatus,
            'companyassociationid' => $associationid,
            'companyid' => $companyid,
            'programid' => $programid,
            'enrolments' => $enrolments,
        ];
    }

    /**
     * Create a Moodle user or reuse an existing one by username/email.
     *
     * @param string $username Username.
     * @param string $email Email.
     * @param string $firstname First name.
     * @param string $lastname Last name.
     * @param string $password Optional initial password.
     * @return array{0:int,1:string}
     */
    private function create_or_reuse_user(
        string $username,
        string $email,
        string $firstname,
        string $lastname,
        string $password
    ): array {
        global $CFG, $DB;

        $existing = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
        if (!$existing) {
            $existing = $DB->get_record('user', ['email' => $email, 'deleted' => 0]);
        }
        if ($existing) {
            if (!empty($existing->suspended)) {
                throw new moodle_exception('suspended', 'auth');
            }
            return [(int)$existing->id, self::USER_STATUS_EXISTING];
        }

        $generatedpassword = false;
        if ($password === '') {
            $password = generate_password(16);
            $generatedpassword = true;
        }

        $user = (object)[
            'auth' => 'manual',
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id,
            'username' => $username,
            'password' => $password,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'lang' => current_language(),
        ];

        $userid = (int)user_create_user($user, true, true);
        if ($generatedpassword) {
            set_user_preference('auth_forcepasswordchange', 1, $userid);
        }

        return [$userid, self::USER_STATUS_CREATED];
    }

    /**
     * Enrol a user in all Moodle courses linked to a CMC program.
     *
     * @param int $programid Program id.
     * @param int $userid User id.
     * @param int $roleid Moodle course role id.
     * @return array<int,array<string,mixed>>
     */
    private function enrol_in_program_courses(int $programid, int $userid, int $roleid): array {
        $manualplugin = enrol_get_plugin('manual');
        if (!$manualplugin) {
            throw new moodle_exception('manualpluginnotinstalled', 'enrol_manual');
        }

        $programrepository = new program_repository();
        $courses = $programrepository->get_courses($programid);
        $enrolments = [];

        foreach ($courses as $course) {
            $coursecontext = context_course::instance((int)$course->courseid);
            $status = 'already_enrolled';
            if (!is_enrolled($coursecontext, $userid, '', true)) {
                $instance = $this->get_manual_enrol_instance((int)$course->courseid);
                $manualplugin->enrol_user($instance, $userid, $roleid);
                $status = 'enrolled';
            }

            $enrolments[] = [
                'courseid' => (int)$course->courseid,
                'shortname' => $course->shortname,
                'status' => $status,
            ];
        }

        return $enrolments;
    }

    /**
     * Return the active manual enrolment instance for a course.
     *
     * @param int $courseid Course id.
     * @return stdClass
     */
    private function get_manual_enrol_instance(int $courseid): stdClass {
        $instances = enrol_get_instances($courseid, true);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual') {
                return $instance;
            }
        }

        throw new moodle_exception('wsnoinstance', 'enrol_manual', '', (object)['courseid' => $courseid]);
    }
}
