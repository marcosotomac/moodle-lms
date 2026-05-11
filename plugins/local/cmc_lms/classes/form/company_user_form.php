<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for associating Moodle users with a CMC client company.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_user_form extends moodleform {
    /**
     * Define form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $users = $this->_customdata['users'] ?? [];

        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        $mform->addElement('select', 'userid', get_string('user', 'local_cmc_lms'), $users);
        $mform->addRule('userid', null, 'required');

        $roles = [
            'student' => get_string('student', 'local_cmc_lms'),
            'supervisor' => get_string('supervisor', 'local_cmc_lms'),
        ];
        $mform->addElement('select', 'companyrole', get_string('companyrole', 'local_cmc_lms'), $roles);
        $mform->setType('companyrole', PARAM_ALPHANUMEXT);

        $mform->addElement('advcheckbox', 'active', get_string('active', 'local_cmc_lms'));
        $mform->setDefault('active', 1);

        $this->add_action_buttons();
    }
}
