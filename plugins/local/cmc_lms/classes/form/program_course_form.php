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

        $mform->addElement('text', 'contentlabel', get_string('contentlabel', 'local_cmc_lms'), ['size' => 60]);
        $mform->setType('contentlabel', PARAM_TEXT);

        $mform->addElement('select', 'contentformat', get_string('contentformat', 'local_cmc_lms'), [
            'video' => get_string('contentformatvideo', 'local_cmc_lms'),
            'document' => get_string('contentformatdocument', 'local_cmc_lms'),
            'external' => get_string('contentformatexternal', 'local_cmc_lms'),
            'lesson' => get_string('contentformatlesson', 'local_cmc_lms'),
            'quiz' => get_string('contentformatquiz', 'local_cmc_lms'),
            'other' => get_string('contentformatother', 'local_cmc_lms'),
        ]);
        $mform->setDefault('contentformat', 'other');

        $mform->addElement('textarea', 'reusenotes', get_string('reusenotes', 'local_cmc_lms'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('reusenotes', PARAM_TEXT);

        $mform->addElement('text', 'plannedhours', get_string('plannedhours', 'local_cmc_lms'), ['size' => 10]);
        $mform->setType('plannedhours', PARAM_FLOAT);
        $mform->setDefault('plannedhours', 0);

        $mform->addElement('date_time_selector', 'schedulestart', get_string('schedulestart', 'local_cmc_lms'), [
            'optional' => true,
        ]);
        $mform->setDefault('schedulestart', 0);

        $mform->addElement('date_time_selector', 'scheduleend', get_string('scheduleend', 'local_cmc_lms'), [
            'optional' => true,
        ]);
        $mform->setDefault('scheduleend', 0);

        $mform->addElement('select', 'liveprovider', get_string('liveprovider', 'local_cmc_lms'), [
            '' => get_string('none'),
            'zoom' => 'Zoom',
            'meet' => 'Google Meet',
            'teams' => 'Microsoft Teams',
            'bbb' => 'BigBlueButton',
            'other' => get_string('other'),
        ]);

        $mform->addElement('text', 'liveurl', get_string('liveurl', 'local_cmc_lms'), ['size' => 60]);
        $mform->setType('liveurl', PARAM_URL);

        $mform->addElement('advcheckbox', 'createlivesession', get_string('createlivesession', 'local_cmc_lms'));
        $mform->addHelpButton('createlivesession', 'createlivesession', 'local_cmc_lms');

        $mform->addElement('advcheckbox', 'attendancetracking', get_string('attendancetracking', 'local_cmc_lms'));

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
        if (!empty($data['schedulestart']) && !empty($data['scheduleend']) && $data['scheduleend'] < $data['schedulestart']) {
            $errors['scheduleend'] = get_string('scheduleendbeforestart', 'local_cmc_lms');
        }
        if (!empty($data['createlivesession'])) {
            if (empty($data['liveprovider']) || !in_array($data['liveprovider'], ['zoom', 'meet'], true)) {
                $errors['liveprovider'] = get_string('invalidliveintegrationprovider', 'local_cmc_lms');
            }
            if (empty($data['schedulestart']) || empty($data['scheduleend'])) {
                $errors['schedulestart'] = get_string('livesessionrequiresdates', 'local_cmc_lms');
            }
        }

        return $errors;
    }
}
