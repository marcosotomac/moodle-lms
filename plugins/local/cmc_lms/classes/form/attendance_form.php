<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\form;

use local_cmc_lms\local\program_repository;
use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for recording CMC attendance for a program-course roster.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendance_form extends moodleform {
    /**
     * Define form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $roster = $this->_customdata['roster'] ?? [];

        $mform->addElement('hidden', 'programcourseid');
        $mform->setType('programcourseid', PARAM_INT);

        $mform->addElement('date_time_selector', 'timetaken', get_string('attendancedate', 'local_cmc_lms'));
        $mform->setDefault('timetaken', time());

        $mform->addElement('header', 'attendanceroster', get_string('attendanceroster', 'local_cmc_lms'));

        $options = ['' => get_string('notrecorded', 'local_cmc_lms')];
        foreach (program_repository::ATTENDANCE_STATUSES as $status) {
            $options[$status] = get_string('attendance' . $status, 'local_cmc_lms');
        }

        foreach ($roster as $user) {
            $fieldname = 'status_' . (int)$user->id;
            $label = fullname($user) . ' (' . s($user->email) . ')';
            $mform->addElement('select', $fieldname, $label, $options);
            $mform->setType($fieldname, PARAM_ALPHANUMEXT);
        }

        $this->add_action_buttons(false, get_string('saveattendance', 'local_cmc_lms'));
    }

    /**
     * Validate attendance status fields.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $allowed = array_merge([''], program_repository::ATTENDANCE_STATUSES);
        $roster = $this->_customdata['roster'] ?? [];

        foreach ($roster as $user) {
            $fieldname = 'status_' . (int)$user->id;
            $status = $data[$fieldname] ?? '';
            if (!in_array($status, $allowed, true)) {
                $errors[$fieldname] = get_string('invalidattendance', 'local_cmc_lms');
            }
        }

        return $errors;
    }
}
