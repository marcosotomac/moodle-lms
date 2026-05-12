<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms;

use advanced_testcase;
use local_cmc_lms\local\certificate_repository;
use local_cmc_lms\local\company_repository;
use local_cmc_lms\local\evaluation_repository;
use local_cmc_lms\local\program_repository;
use local_cmc_lms\local\report_repository;
use local_cmc_lms\local\role_repository;

/**
 * Repository tests for local_cmc_lms.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_cmc_lms\local\company_repository
 * @covers     \local_cmc_lms\local\certificate_repository
 * @covers     \local_cmc_lms\local\program_repository
 * @covers     \local_cmc_lms\local\report_repository
 * @covers     \local_cmc_lms\local\role_repository
 * @covers     \local_cmc_lms\local\evaluation_repository
 */
final class repository_test extends advanced_testcase {
    /**
     * Certificates are idempotent per active user/course/company/program tuple and publicly verifiable.
     */
    public function test_certificate_repository_issues_idempotently_and_verifies(): void {
        $this->resetAfterTest(true);

        $issuer = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user([
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
        ]);
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Gestión de Calidad',
            'shortname' => 'CALIDAD',
        ]);

        $companyrepository = new company_repository();
        $companyid = $companyrepository->create((object) [
            'name' => 'CMC Certificados',
            'shortname' => 'CMC-CERT',
            'country' => 'PE',
        ]);

        $programrepository = new program_repository();
        $programid = $programrepository->create((object) [
            'name' => 'Programa Certificable',
            'shortname' => 'CERT',
            'description' => 'Programa usado para certificados.',
        ]);

        $repository = new certificate_repository();
        $completiontime = 1778457600;
        $first = $repository->issue((int) $student->id, (int) $course->id, $companyid, $programid, (int) $issuer->id, (object)[
            'certificatetitle' => 'Certificado personalizado CMC',
            'coursehours' => 12.5,
            'completiontime' => $completiontime,
        ]);
        $second = $repository->issue((int) $student->id, (int) $course->id, $companyid, $programid, (int) $issuer->id);

        $this->assertEquals((int) $first->id, (int) $second->id);
        $this->assertStringStartsWith('CMC-' . date('Y') . '-U' . $student->id . '-C' . $course->id . '-', $first->code);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first->verifytoken);

        $verified = $repository->get_for_verification($first->verifytoken);
        $this->assertNotNull($verified);
        $this->assertEquals('Grace Hopper', $verified->userfullname);
        $this->assertEquals('Gestión de Calidad', $verified->coursefullname);
        $this->assertEquals('Certificado personalizado CMC', $verified->certificatetitle);
        $this->assertEquals(12.5, (float)$verified->coursehours);
        $this->assertEquals($completiontime, (int)$verified->completiontime);
        $this->assertEquals(certificate_repository::STATUS_ISSUED, $verified->status);

        $bycode = $repository->get_for_verification($first->code);
        $this->assertNotNull($bycode);
        $this->assertEquals((int) $first->id, (int) $bycode->id);

        $repository->revoke((int) $first->id, (int) $issuer->id, 'Test revocation');
        $revoked = $repository->get_for_verification($first->verifytoken);
        $this->assertEquals(certificate_repository::STATUS_REVOKED, $revoked->status);
        $this->assertEquals('Test revocation', $revoked->revocationreason);
    }

    /**
     * Automatic certificate issuance uses CMC program-course links and active company associations idempotently.
     */
    public function test_certificate_repository_issues_for_course_completion_context(): void {
        $this->resetAfterTest(true);

        $student = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Curso con Certificado Automático',
            'shortname' => 'CERT-AUTO',
        ]);
        $companyrepository = new company_repository();
        $companyid = $companyrepository->create((object)[
            'name' => 'Empresa Certificable',
            'shortname' => 'EMP-CERT',
            'country' => 'PE',
        ]);
        $companyrepository->add_user($companyid, (int)$student->id, 'student', true);

        $programrepository = new program_repository();
        $programid = $programrepository->create((object)[
            'name' => 'Programa con Certificado',
            'shortname' => 'PROG-CERT',
            'description' => 'Programa con emisión automática.',
        ]);
        $programrepository->add_course($programid, (int)$course->id, 1, true, (object)[
            'plannedhours' => 8.5,
        ]);

        $completiontime = 1778544000;
        $repository = new certificate_repository();
        $first = $repository->issue_for_completion((int)$student->id, (int)$course->id, $completiontime);
        $second = $repository->issue_for_completion((int)$student->id, (int)$course->id, $completiontime);

        $this->assertCount(1, $first);
        $this->assertCount(1, $second);
        $this->assertEquals((int)$first[0]->id, (int)$second[0]->id);
        $this->assertEquals($companyid, (int)$first[0]->companyid);
        $this->assertEquals($programid, (int)$first[0]->programid);
        $this->assertEquals(8.5, (float)$first[0]->coursehours);
        $this->assertEquals($completiontime, (int)$first[0]->completiontime);
        $this->assertStringContainsString('Curso con Certificado Automático', $first[0]->certificatetitle);
    }

    /**
     * Companies are filtered by active state by default.
     */
    public function test_company_repository_lists_active_companies_by_default(): void {
        $this->resetAfterTest(true);

        $repository = new company_repository();
        $repository->create((object) [
            'name' => 'CMC Cliente Activo',
            'shortname' => 'CMC-ACTIVO',
            'country' => 'PE',
            'active' => 1,
        ]);
        $repository->create((object) [
            'name' => 'CMC Cliente Inactivo',
            'shortname' => 'CMC-INACTIVO',
            'country' => 'PE',
            'active' => 0,
        ]);

        $companies = $repository->list();

        $this->assertCount(1, $companies);
        $this->assertEquals('CMC-ACTIVO', $companies[0]->shortname);
    }

    /**
     * Company roles accept the strict CMC role model and normalise legacy supervisor values.
     */
    public function test_company_repository_normalises_supported_company_roles(): void {
        $this->resetAfterTest(true);

        $student = $this->getDataGenerator()->create_user();
        $legacy = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();

        $repository = new company_repository();
        $companyid = $repository->create((object)[
            'name' => 'CMC Roles Empresa',
            'shortname' => 'CMC-ROLES-EMP',
            'country' => 'PE',
        ]);

        $repository->add_user($companyid, (int)$student->id, 'student', true);
        $repository->add_user($companyid, (int)$legacy->id, 'supervisor', true);
        $repository->add_user($companyid, (int)$teacher->id, 'teacher_external', true);

        $rolesbyuserid = [];
        foreach ($repository->list_users($companyid) as $user) {
            $rolesbyuserid[(int)$user->userid] = $user->companyrole;
        }

        $this->assertEquals(role_repository::ROLE_STUDENT, $rolesbyuserid[(int)$student->id]);
        $this->assertEquals(role_repository::ROLE_CLIENT_SUPERVISOR, $rolesbyuserid[(int)$legacy->id]);
        $this->assertEquals(role_repository::ROLE_TEACHER_EXTERNAL, $rolesbyuserid[(int)$teacher->id]);
        $this->assertEquals(role_repository::ROLE_CLIENT_SUPERVISOR, role_repository::display_key('supervisor'));
    }

    /**
     * Programs include ordered Moodle courses.
     */
    public function test_program_repository_returns_linked_courses(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Auditor ISO 9001',
            'shortname' => 'ISO9001-AUD',
        ]);

        $repository = new program_repository();
        $programid = $repository->create((object) [
            'name' => 'Programa ISO 9001',
            'shortname' => 'ISO9001',
            'description' => 'Malla formativa ISO 9001.',
        ]);
        $repository->add_course($programid, (int) $course->id, 10, true);

        $programs = $repository->list_with_courses();

        $this->assertCount(1, $programs);
        $this->assertEquals('ISO9001', $programs[0]->shortname);
        $this->assertCount(1, $programs[0]->courses);
        $this->assertEquals((int) $course->id, (int) $programs[0]->courses[0]->courseid);
    }

    /**
     * Programs persist strict CMC versioning and modality metadata with safe defaults.
     */
    public function test_program_repository_persists_versioned_program_metadata(): void {
        $this->resetAfterTest(true);

        $repository = new program_repository();
        $effectivefrom = 1778457600;
        $programid = $repository->create((object) [
            'name' => 'Programa Blended 5.1',
            'shortname' => 'BLENDED51',
            'description' => 'Programa con metadatos 5.1 estrictos.',
            'versioncode' => '2026-Q2',
            'modality' => 'blended',
            'versionnotes' => 'Se agregan sesiones sincrónicas y versión formal.',
            'effectivefrom' => $effectivefrom,
            'plannedhours' => 24.5,
            'active' => 1,
        ]);

        $program = $repository->get($programid);

        $this->assertEquals('2026-Q2', $program->versioncode);
        $this->assertEquals('blended', $program->modality);
        $this->assertEquals('Se agregan sesiones sincrónicas y versión formal.', $program->versionnotes);
        $this->assertEquals($effectivefrom, (int) $program->effectivefrom);
        $this->assertEquals(24.5, (float) $program->plannedhours);
    }

    /**
     * Program-course links persist content metadata, schedules, live links and attendance configuration.
     */
    public function test_program_repository_persists_enriched_program_course_metadata(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Sesión sincrónica de liderazgo',
            'shortname' => 'LIDER-SYNC',
        ]);

        $repository = new program_repository();
        $programid = $repository->create((object) [
            'name' => 'Programa Liderazgo',
            'shortname' => 'LIDER',
            'description' => 'Malla con contenido enriquecido.',
        ]);
        $start = 1778544000;
        $end = 1778551200;
        $mappingid = $repository->add_course($programid, (int) $course->id, 20, false, (object) [
            'contentlabel' => 'Taller sincrónico 1',
            'contentformat' => 'external',
            'reusenotes' => 'Reutiliza material base 2026.',
            'plannedhours' => 2.5,
            'schedulestart' => $start,
            'scheduleend' => $end,
            'liveprovider' => 'zoom',
            'liveurl' => 'https://example.test/live/liderazgo',
            'attendancetracking' => 1,
        ]);

        $courses = $repository->get_courses($programid);

        $this->assertEquals($mappingid, (int) $courses[0]->id);
        $this->assertEquals('Taller sincrónico 1', $courses[0]->contentlabel);
        $this->assertEquals('external', $courses[0]->contentformat);
        $this->assertEquals('Reutiliza material base 2026.', $courses[0]->reusenotes);
        $this->assertEquals(2.5, (float) $courses[0]->plannedhours);
        $this->assertEquals($start, (int) $courses[0]->schedulestart);
        $this->assertEquals($end, (int) $courses[0]->scheduleend);
        $this->assertEquals('zoom', $courses[0]->liveprovider);
        $this->assertEquals('https://example.test/live/liderazgo', $courses[0]->liveurl);
        $this->assertEquals(1, (int) $courses[0]->attendancetracking);
    }

    /**
     * Attendance records attach learners to a specific program-course link.
     */
    public function test_program_repository_records_attendance_for_program_course_link(): void {
        $this->resetAfterTest(true);

        $student = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $repository = new program_repository();
        $programid = $repository->create((object) [
            'name' => 'Programa Asistencia',
            'shortname' => 'ASIST',
            'description' => 'Malla con asistencia.',
        ]);
        $mappingid = $repository->add_course($programid, (int) $course->id, 1, true, (object) [
            'attendancetracking' => 1,
        ]);
        $timetaken = 1778547600;

        $attendanceid = $repository->record_attendance($mappingid, (int) $student->id, 'late', $timetaken);
        $rows = $repository->get_attendance($mappingid);

        $this->assertCount(1, $rows);
        $this->assertEquals($attendanceid, (int) $rows[0]->id);
        $this->assertEquals((int) $student->id, (int) $rows[0]->userid);
        $this->assertEquals('late', $rows[0]->status);
        $this->assertEquals($timetaken, (int) $rows[0]->timetaken);
    }

    /**
     * Program role assignments are idempotent and normalise unsupported roles safely.
     */
    public function test_role_repository_assigns_program_roles_idempotently(): void {
        $this->resetAfterTest(true);

        $teacher = $this->getDataGenerator()->create_user([
            'firstname' => 'Marie',
            'lastname' => 'Curie',
        ]);
        $programrepository = new program_repository();
        $programid = $programrepository->create((object)[
            'name' => 'Programa Docentes',
            'shortname' => 'DOCENTES',
            'description' => 'Malla con asignaciones docentes.',
        ]);

        $repository = new role_repository();
        $first = $repository->assign_program_role($programid, (int)$teacher->id, 'teacher_internal', true);
        $second = $repository->assign_program_role($programid, (int)$teacher->id, 'teacher_internal', false);
        $fallback = $repository->assign_program_role($programid, (int)$teacher->id, 'student', true);

        $rows = $repository->list_program_roles($programid);

        $this->assertEquals($first, $second);
        $this->assertEquals($first, $fallback);
        $this->assertCount(1, $rows);
        $this->assertEquals(role_repository::ROLE_TEACHER_INTERNAL, $rows[0]->cmcrole);
        $this->assertEquals(1, (int)$rows[0]->active);
        $this->assertEquals('Marie Curie', fullname($rows[0]));
    }

    /**
     * Evaluation rules map existing Moodle Quiz activities and update idempotently.
     */
    public function test_evaluation_repository_saves_and_lists_quiz_rules_idempotently(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Evaluación 5.4',
            'shortname' => 'EVAL54',
        ]);
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => (int)$course->id,
            'name' => 'Control MCQ',
            'grade' => 20,
            'sumgrades' => 20,
            'attempts' => 3,
            'timelimit' => 1800,
        ]);
        $programrepository = new program_repository();
        $programid = $programrepository->create((object)[
            'name' => 'Programa Evaluable',
            'shortname' => 'EVAL-PROG',
            'description' => 'Programa con reglas de evaluación.',
        ]);

        $repository = new evaluation_repository();
        $activities = $repository->list_quiz_activities((int)$course->id);
        $this->assertCount(1, $activities);
        $this->assertEquals((int)$quiz->cmid, (int)$activities[0]->cmid);

        $first = $repository->save_rule((object)[
            'programid' => $programid,
            'courseid' => (int)$course->id,
            'quizid' => (int)$quiz->id,
            'cmid' => (int)$quiz->cmid,
            'scope' => evaluation_repository::SCOPE_MODULE,
            'passgrade' => 14,
            'passpercentage' => 70,
            'maxattempts' => 3,
            'timelimit' => 1800,
            'active' => 1,
        ]);
        $second = $repository->save_rule((object)[
            'programid' => $programid,
            'courseid' => (int)$course->id,
            'quizid' => (int)$quiz->id,
            'cmid' => (int)$quiz->cmid,
            'scope' => evaluation_repository::SCOPE_MODULE,
            'passgrade' => 15,
            'passpercentage' => 75,
            'maxattempts' => 2,
            'timelimit' => 1200,
            'active' => 0,
        ]);

        $rules = $repository->list_rules(false);

        $this->assertEquals($first, $second);
        $this->assertCount(1, $rules);
        $this->assertEquals($programid, (int)$rules[0]->programid);
        $this->assertEquals((int)$course->id, (int)$rules[0]->courseid);
        $this->assertEquals((int)$quiz->id, (int)$rules[0]->quizid);
        $this->assertEquals((int)$quiz->cmid, (int)$rules[0]->cmid);
        $this->assertEquals('Control MCQ', $rules[0]->quizname);
        $this->assertEquals(15.0, (float)$rules[0]->passgrade);
        $this->assertEquals(75.0, (float)$rules[0]->passpercentage);
        $this->assertEquals(2, (int)$rules[0]->maxattempts);
        $this->assertEquals(1200, (int)$rules[0]->timelimit);
        $this->assertEquals(0, (int)$rules[0]->active);
    }

    /**
     * Company dashboard metrics only count active associations and CMC-linked courses.
     */
    public function test_report_repository_returns_company_dashboard_metrics(): void {
        global $DB;

        $this->resetAfterTest(true);

        $student = $this->getDataGenerator()->create_user();
        $supervisor = $this->getDataGenerator()->create_user();
        $inactiveuser = $this->getDataGenerator()->create_user();
        $linkedcourse = $this->getDataGenerator()->create_course();
        $unlinkedcourse = $this->getDataGenerator()->create_course();

        $companyrepository = new company_repository();
        $companyid = $companyrepository->create((object) [
            'name' => 'CMC Reportable',
            'shortname' => 'CMC-REP',
            'country' => 'PE',
            'active' => 1,
        ]);
        $companyrepository->add_user($companyid, (int) $student->id, 'student', true);
        $companyrepository->add_user($companyid, (int) $supervisor->id, 'supervisor', true);
        $companyrepository->add_user($companyid, (int) $inactiveuser->id, 'student', false);

        $programrepository = new program_repository();
        $programid = $programrepository->create((object) [
            'name' => 'Programa Reportable',
            'shortname' => 'REP',
            'description' => 'Malla con curso reportable.',
        ]);
        $programrepository->add_course($programid, (int) $linkedcourse->id, 10, true);

        $this->getDataGenerator()->enrol_user((int) $student->id, (int) $linkedcourse->id);
        $this->getDataGenerator()->enrol_user((int) $student->id, (int) $unlinkedcourse->id);
        $this->getDataGenerator()->enrol_user((int) $inactiveuser->id, (int) $linkedcourse->id);
        $DB->insert_record('course_completions', (object) [
            'userid' => (int) $student->id,
            'course' => (int) $linkedcourse->id,
            'timecompleted' => time(),
        ]);
        $DB->insert_record('course_completions', (object) [
            'userid' => (int) $student->id,
            'course' => (int) $unlinkedcourse->id,
            'timecompleted' => time(),
        ]);

        $rows = (new report_repository())->get_company_dashboard_rows();

        $this->assertCount(1, $rows);
        $this->assertEquals($companyid, $rows[0]->id);
        $this->assertEquals(2, $rows[0]->activeusers);
        $this->assertEquals(1, $rows[0]->students);
        $this->assertEquals(1, $rows[0]->supervisors);
        $this->assertEquals(1, $rows[0]->enrolmentcount);
        $this->assertEquals(1, $rows[0]->completioncount);
        $this->assertEquals(100.0, $rows[0]->completionpercentage);
    }

    /**
     * Company user progress metrics are scoped to the selected company and CMC-linked courses.
     */
    public function test_report_repository_returns_company_user_progress(): void {
        global $DB;

        $this->resetAfterTest(true);

        $student = $this->getDataGenerator()->create_user([
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'email' => 'ada@example.test',
        ]);
        $linkedcourseone = $this->getDataGenerator()->create_course();
        $linkedcoursetwo = $this->getDataGenerator()->create_course();
        $unlinkedcourse = $this->getDataGenerator()->create_course();

        $companyrepository = new company_repository();
        $companyid = $companyrepository->create((object) [
            'name' => 'CMC Progress',
            'shortname' => 'CMC-PROG',
            'country' => 'PE',
            'active' => 1,
        ]);
        $companyrepository->add_user($companyid, (int) $student->id, 'student', true);

        $programrepository = new program_repository();
        $programid = $programrepository->create((object) [
            'name' => 'Programa Avance',
            'shortname' => 'AVANCE',
            'description' => 'Malla con dos cursos.',
        ]);
        $programrepository->add_course($programid, (int) $linkedcourseone->id, 10, true);
        $programrepository->add_course($programid, (int) $linkedcoursetwo->id, 20, true);

        $this->getDataGenerator()->enrol_user((int) $student->id, (int) $linkedcourseone->id);
        $this->getDataGenerator()->enrol_user((int) $student->id, (int) $linkedcoursetwo->id);
        $this->getDataGenerator()->enrol_user((int) $student->id, (int) $unlinkedcourse->id);
        $DB->insert_record('course_completions', (object) [
            'userid' => (int) $student->id,
            'course' => (int) $linkedcourseone->id,
            'timecompleted' => time(),
        ]);

        $rows = (new report_repository())->get_company_user_progress_rows($companyid);

        $this->assertCount(1, $rows);
        $this->assertEquals((int) $student->id, $rows[0]->userid);
        $this->assertEquals('Ada Lovelace', $rows[0]->fullname);
        $this->assertEquals('ada@example.test', $rows[0]->email);
        $this->assertEquals('student', $rows[0]->companyrole);
        $this->assertEquals(2, $rows[0]->enrolledcourses);
        $this->assertEquals(1, $rows[0]->completedcourses);
        $this->assertEquals(50.0, $rows[0]->completionpercentage);
    }
}
