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
 * Manual certificate issue form.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_issue_form extends moodleform {
    /**
     * Define form fields.
     *
     * @return void
     */
    public function definition(): void {
        global $DB;

        $mform = $this->_form;

        $mform->addElement('text', 'userid', get_string('userid', 'local_cmc_lms'), ['size' => 12]);
        $mform->setType('userid', PARAM_INT);
        $mform->addRule('userid', null, 'required');

        $courses = $DB->get_records_select_menu('course', 'id <> :siteid', ['siteid' => SITEID], 'fullname ASC', 'id, fullname');
        $mform->addElement('select', 'courseid', get_string('course'), $courses);
        $mform->setType('courseid', PARAM_INT);
        $mform->addRule('courseid', null, 'required');

        $companies = [0 => get_string('none')]
            + $DB->get_records_menu('local_cmc_lms_company', null, 'name ASC', 'id, name');
        $mform->addElement('select', 'companyid', get_string('company', 'local_cmc_lms'), $companies);
        $mform->setType('companyid', PARAM_INT);

        $programs = [0 => get_string('none')]
            + $DB->get_records_menu('local_cmc_lms_program', null, 'name ASC', 'id, name');
        $mform->addElement('select', 'programid', get_string('program', 'local_cmc_lms'), $programs);
        $mform->setType('programid', PARAM_INT);

        $this->add_action_buttons(false, get_string('issuecertificate', 'local_cmc_lms'));
    }

    /**
     * Validate referenced Moodle/CMC ids.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        global $DB;

        $errors = parent::validation($data, $files);

        if (empty($data['userid']) || !$DB->record_exists('user', ['id' => $data['userid'], 'deleted' => 0])) {
            $errors['userid'] = get_string('invaliduser', 'local_cmc_lms');
        }

        if (empty($data['courseid']) || !$DB->record_exists('course', ['id' => $data['courseid']])) {
            $errors['courseid'] = get_string('invalidcourse', 'local_cmc_lms');
        }

        if (!empty($data['companyid']) && !$DB->record_exists('local_cmc_lms_company', ['id' => $data['companyid']])) {
            $errors['companyid'] = get_string('invalidcompany', 'local_cmc_lms');
        }

        if (!empty($data['programid']) && !$DB->record_exists('local_cmc_lms_program', ['id' => $data['programid']])) {
            $errors['programid'] = get_string('invalidprogram', 'local_cmc_lms');
        }

        return $errors;
    }
}
