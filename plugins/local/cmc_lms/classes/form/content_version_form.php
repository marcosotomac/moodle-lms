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
 * Form for adding immutable versions to reusable CMC content items.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_version_form extends moodleform {
    /** @var int Content item id. */
    private int $contentitemid;

    /**
     * Form constructor.
     *
     * @param string|null $action Form action.
     * @param array|null $customdata Custom data.
     */
    public function __construct(?string $action = null, ?array $customdata = null) {
        $this->contentitemid = (int)($customdata['contentitemid'] ?? 0);
        parent::__construct($action, $customdata);
    }

    /**
     * Define form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'contentitemid');
        $mform->setType('contentitemid', PARAM_INT);

        $mform->addElement('static', 'immutablewarning', get_string('contentversionimmutable', 'local_cmc_lms'));

        $mform->addElement('text', 'versioncode', get_string('versioncode', 'local_cmc_lms'), ['size' => 30]);
        $mform->setType('versioncode', PARAM_TEXT);
        $mform->addRule('versioncode', null, 'required');

        $mform->addElement('textarea', 'changenotes', get_string('contentversionnotes', 'local_cmc_lms'), ['rows' => 5, 'cols' => 60]);
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
        if (!empty($data['versioncode']) && $DB->record_exists('local_cmc_lms_content_version', [
            'contentitemid' => $this->contentitemid,
            'versioncode' => $data['versioncode'],
        ])) {
            $errors['versioncode'] = get_string('contentversionexists', 'local_cmc_lms');
        }

        return $errors;
    }
}
