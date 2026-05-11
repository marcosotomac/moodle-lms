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
 * Form for creating and editing client companies.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_form extends moodleform {
    /** @var int Company id. */
    private int $companyid;

    /**
     * Form constructor.
     *
     * @param string|null $action Form action.
     * @param array|null $customdata Custom data.
     */
    public function __construct(?string $action = null, ?array $customdata = null) {
        $this->companyid = (int)($customdata['id'] ?? 0);
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

        $mform->addElement('text', 'name', get_string('companyname', 'local_cmc_lms'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $mform->addElement('text', 'shortname', get_string('shortname'), ['size' => 30]);
        $mform->setType('shortname', PARAM_ALPHANUMEXT);
        $mform->addRule('shortname', null, 'required');

        $mform->addElement('text', 'sector', get_string('sector', 'local_cmc_lms'), ['size' => 40]);
        $mform->setType('sector', PARAM_TEXT);

        $mform->addElement('text', 'size', get_string('size', 'local_cmc_lms'), ['size' => 20]);
        $mform->setType('size', PARAM_TEXT);

        $mform->addElement('text', 'location', get_string('location', 'local_cmc_lms'), ['size' => 40]);
        $mform->setType('location', PARAM_TEXT);

        $mform->addElement('text', 'country', get_string('country', 'local_cmc_lms'), ['size' => 2]);
        $mform->setType('country', PARAM_ALPHA);

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
            $existing = $DB->get_record('local_cmc_lms_company', ['shortname' => $data['shortname']]);
            if ($existing && (int)$existing->id !== $this->companyid) {
                $errors['shortname'] = get_string('shortnameexists', 'local_cmc_lms');
            }
        }

        return $errors;
    }
}
