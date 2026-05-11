<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\form;

use local_cmc_lms\local\role_repository;
use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for assigning CMC coordinator/teacher roles to a program.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program_role_form extends moodleform {
    /**
     * Define form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $programs = $this->_customdata['programs'] ?? [];
        $users = $this->_customdata['users'] ?? [];

        $mform->addElement('select', 'programid', get_string('program', 'local_cmc_lms'), $programs);
        $mform->addRule('programid', null, 'required');
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('select', 'userid', get_string('user', 'local_cmc_lms'), $users);
        $mform->addRule('userid', null, 'required');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement(
            'select',
            'cmcrole',
            get_string('cmcrole', 'local_cmc_lms'),
            role_repository::role_options(role_repository::PROGRAM_ROLES)
        );
        $mform->setType('cmcrole', PARAM_ALPHANUMEXT);

        $mform->addElement('advcheckbox', 'active', get_string('active', 'local_cmc_lms'));
        $mform->setDefault('active', 1);

        $this->add_action_buttons();
    }
}
