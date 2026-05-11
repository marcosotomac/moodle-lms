<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms;

use advanced_testcase;
use local_cmc_lms\local\company_repository;
use local_cmc_lms\local\program_repository;
use local_cmc_lms\local\report_repository;

/**
 * Repository tests for local_cmc_lms.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_cmc_lms\local\company_repository
 * @covers     \local_cmc_lms\local\program_repository
 * @covers     \local_cmc_lms\local\report_repository
 */
final class repository_test extends advanced_testcase {
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
