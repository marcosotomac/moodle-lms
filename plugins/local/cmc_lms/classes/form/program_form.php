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
 * Form for creating and editing training programs.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program_form extends moodleform {
    /** @var int Program id. */
    private int $programid;

    /**
     * Form constructor.
     *
     * @param string|null $action Form action.
     * @param array|null $customdata Custom data.
     */
    public function __construct(?string $action = null, ?array $customdata = null) {
        $this->programid = (int)($customdata['id'] ?? 0);
        parent::__construct($action, $customdata);
    }

    /**
     * Define form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('programname', 'local_cmc_lms'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $mform->addElement('text', 'shortname', get_string('shortname'), ['size' => 30]);
        $mform->setType('shortname', PARAM_ALPHANUMEXT);
        $mform->addRule('shortname', null, 'required');

        $mform->addElement('textarea', 'description', get_string('description', 'local_cmc_lms'), ['rows' => 6, 'cols' => 60]);
        $mform->setType('description', PARAM_RAW);

        $mform->addElement('text', 'versioncode', get_string('versioncode', 'local_cmc_lms'), ['size' => 30]);
        $mform->setType('versioncode', PARAM_TEXT);
        $mform->setDefault('versioncode', 'v1');

        $mform->addElement('select', 'modality', get_string('modality', 'local_cmc_lms'), [
            'async' => get_string('modalityasync', 'local_cmc_lms'),
            'sync' => get_string('modalitysync', 'local_cmc_lms'),
            'blended' => get_string('modalityblended', 'local_cmc_lms'),
        ]);
        $mform->setDefault('modality', 'async');

        $mform->addElement('textarea', 'versionnotes', get_string('versionnotes', 'local_cmc_lms'), ['rows' => 4, 'cols' => 60]);
        $mform->setType('versionnotes', PARAM_TEXT);

        $mform->addElement('date_time_selector', 'effectivefrom', get_string('effectivefrom', 'local_cmc_lms'), [
            'optional' => true,
        ]);
        $mform->setDefault('effectivefrom', 0);

        $mform->addElement('text', 'plannedhours', get_string('plannedhours', 'local_cmc_lms'), ['size' => 10]);
        $mform->setType('plannedhours', PARAM_FLOAT);
        $mform->setDefault('plannedhours', 0);

        $mform->addElement('advcheckbox', 'active', get_string('active', 'local_cmc_lms'));
        $mform->setDefault('active', 1);

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
        if (!empty($data['shortname'])) {
            $existing = $DB->get_record('local_cmc_lms_program', ['shortname' => $data['shortname']]);
            if ($existing && (int)$existing->id !== $this->programid) {
                $errors['shortname'] = get_string('shortnameexists', 'local_cmc_lms');
            }
        }

        return $errors;
    }
}
