<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\form;

use local_cmc_lms\local\content_repository;
use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating reusable CMC content items with an initial ISO version.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_item_form extends moodleform {
    /**
     * Define form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'contentitemheader', get_string('reusablecontent', 'local_cmc_lms'));
        $mform->addElement('text', 'name', get_string('contentitemname', 'local_cmc_lms'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $mform->addElement('text', 'code', get_string('contentitemcode', 'local_cmc_lms'), ['size' => 30]);
        $mform->setType('code', PARAM_ALPHANUMEXT);
        $mform->addRule('code', null, 'required');

        $mform->addElement('select', 'contenttype', get_string('contentformat', 'local_cmc_lms'), [
            'video' => get_string('contentformatvideo', 'local_cmc_lms'),
            'document' => get_string('contentformatdocument', 'local_cmc_lms'),
            'external' => get_string('contentformatexternal', 'local_cmc_lms'),
            'lesson' => get_string('contentformatlesson', 'local_cmc_lms'),
            'quiz' => get_string('contentformatquiz', 'local_cmc_lms'),
            'other' => get_string('contentformatother', 'local_cmc_lms'),
        ]);
        $mform->setDefault('contenttype', 'document');

        $mform->addElement('text', 'sourceurl', get_string('contentsourceurl', 'local_cmc_lms'), ['size' => 60]);
        $mform->setType('sourceurl', PARAM_URL);

        $mform->addElement('text', 'isoreference', get_string('isoreference', 'local_cmc_lms'), ['size' => 40]);
        $mform->setType('isoreference', PARAM_TEXT);

        $mform->addElement('textarea', 'description', get_string('description', 'local_cmc_lms'), ['rows' => 5, 'cols' => 60]);
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'active', get_string('active', 'local_cmc_lms'));
        $mform->setDefault('active', 1);

        $mform->addElement('header', 'contentversionheader', get_string('initialcontentversion', 'local_cmc_lms'));
        $mform->addElement('text', 'versioncode', get_string('versioncode', 'local_cmc_lms'), ['size' => 30]);
        $mform->setType('versioncode', PARAM_TEXT);
        $mform->setDefault('versioncode', 'v1');
        $mform->addRule('versioncode', null, 'required');

        $mform->addElement('textarea', 'changenotes', get_string('contentversionnotes', 'local_cmc_lms'), ['rows' => 4, 'cols' => 60]);
        $mform->setType('changenotes', PARAM_TEXT);

        $mform->addElement('date_time_selector', 'effectivefrom', get_string('effectivefrom', 'local_cmc_lms'), [
            'optional' => true,
        ]);
        $mform->setDefault('effectivefrom', 0);

        $mform->addElement('select', 'status', get_string('contentversionstatus', 'local_cmc_lms'), [
            'draft' => get_string('contentversionstatusdraft', 'local_cmc_lms'),
            'published' => get_string('contentversionstatuspublished', 'local_cmc_lms'),
            'obsolete' => get_string('contentversionstatusobsolete', 'local_cmc_lms'),
        ]);
        $mform->setDefault('status', 'published');

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
        if (!empty($data['code']) && $DB->record_exists('local_cmc_lms_content_item', ['code' => $data['code']])) {
            $errors['code'] = get_string('contentitemcodeexists', 'local_cmc_lms');
        }
        if (!empty($data['contenttype']) && !in_array($data['contenttype'], content_repository::CONTENT_TYPES, true)) {
            $errors['contenttype'] = get_string('invalidcontenttype', 'local_cmc_lms');
        }

        return $errors;
    }
}
