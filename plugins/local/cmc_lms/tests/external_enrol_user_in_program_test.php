<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms;

use advanced_testcase;
use local_cmc_lms\external\enrol_user_in_program;
use local_cmc_lms\local\company_repository;
use local_cmc_lms\local\program_repository;

/**
 * External enrolment tests for local_cmc_lms.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_cmc_lms\external\enrol_user_in_program
 */
final class external_enrol_user_in_program_test extends advanced_testcase {
    /**
     * Enrolling a user in a program is idempotent across company association and course enrolments.
     */
    public function test_enrol_user_in_program_is_idempotent(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Auditor ISO 45001',
            'shortname' => 'ISO45001-AUD',
        ]);

        $companyrepository = new company_repository();
        $companyid = $companyrepository->create((object) [
            'name' => 'CMC Cliente Industrial',
            'shortname' => 'CMC-IND',
            'country' => 'PE',
            'active' => 1,
        ]);

        $programrepository = new program_repository();
        $programid = $programrepository->create((object) [
            'name' => 'Programa ISO 45001',
            'shortname' => 'ISO45001',
            'description' => 'Malla formativa ISO 45001.',
        ]);
        $programrepository->add_course($programid, (int) $course->id, 10, true);

        $firstresult = enrol_user_in_program::execute($companyid, (int) $user->id, $programid);
        $secondresult = enrol_user_in_program::execute($companyid, (int) $user->id, $programid);

        $this->assertEquals($firstresult['companyassociationid'], $secondresult['companyassociationid']);
        $this->assertEquals('enrolled', $firstresult['enrolments'][0]['status']);
        $this->assertEquals('already_enrolled', $secondresult['enrolments'][0]['status']);
        $this->assertEquals((int) $course->id, $secondresult['enrolments'][0]['courseid']);
        $this->assertEquals('ISO45001-AUD', $secondresult['enrolments'][0]['shortname']);

        $manualinstance = $DB->get_record('enrol', [
            'courseid' => (int) $course->id,
            'enrol' => 'manual',
        ], '*', MUST_EXIST);

        $this->assertEquals(1, $DB->count_records('user_enrolments', [
            'enrolid' => (int) $manualinstance->id,
            'userid' => (int) $user->id,
        ]));
    }
}
