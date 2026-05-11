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

/**
 * Repository tests for local_cmc_lms.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_cmc_lms\local\company_repository
 * @covers     \local_cmc_lms\local\program_repository
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
}
