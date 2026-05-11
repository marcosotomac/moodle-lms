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
 * Form for linking Moodle courses to CMC programs.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program_course_form extends moodleform {
    /** @var int Program id. */
    private int $programid;

    /**
     * Form constructor.
     *
     * @param string|null $action Form action.
     * @param array|null $customdata Custom data.
     */
    public function __construct(?string $action = null, ?array $customdata = null) {
        $this->programid = (int)($customdata['programid'] ?? 0);
        parent::__construct($action, $customdata);
    }

    /**
     * Define form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $courses = $this->_customdata['courses'] ?? [];

        $mform->addElement('hidden', 'programid');
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('select', 'courseid', get_string('course', 'local_cmc_lms'), $courses);
        $mform->addRule('courseid', null, 'required');

        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_cmc_lms'), ['size' => 10]);
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        $mform->addElement('advcheckbox', 'required', get_string('required', 'local_cmc_lms'));
        $mform->setDefault('required', 1);

        $this->add_action_buttons();
    }

    /**
     * Validate form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        global $DB;

        $errors = parent::validation($data, $files);
        if (!empty($data['courseid']) && $DB->record_exists('local_cmc_lms_program_course', [
            'programid' => $this->programid,
            'courseid' => $data['courseid'],
        ])) {
            $errors['courseid'] = get_string('coursealreadylinked', 'local_cmc_lms');
        }

        return $errors;
    }
}
