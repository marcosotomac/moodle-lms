<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\form;

use local_cmc_lms\local\evaluation_repository;
use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for mapping existing Moodle Quiz activities to CMC evaluation rules.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluation_rule_form extends moodleform {
    /**
     * Define form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $quizzes = $this->_customdata['quizzes'] ?? [];
        $programs = $this->_customdata['programs'] ?? [];

        $mform->addElement('select', 'cmid', get_string('quizactivity', 'local_cmc_lms'), $quizzes);
        $mform->addRule('cmid', null, 'required');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('select', 'programid', get_string('program', 'local_cmc_lms'), $programs);
        $mform->setType('programid', PARAM_INT);
        $mform->addHelpButton('programid', 'evaluationprogram', 'local_cmc_lms');

        $mform->addElement('select', 'scope', get_string('evaluationscope', 'local_cmc_lms'), [
            evaluation_repository::SCOPE_MODULE => get_string('evaluationscopemodule', 'local_cmc_lms'),
            evaluation_repository::SCOPE_COURSE => get_string('evaluationscopecourse', 'local_cmc_lms'),
        ]);
        $mform->setType('scope', PARAM_ALPHA);
        $mform->setDefault('scope', evaluation_repository::SCOPE_MODULE);

        $mform->addElement('text', 'passgrade', get_string('passgrade', 'local_cmc_lms'), ['size' => 10]);
        $mform->setType('passgrade', PARAM_FLOAT);
        $mform->setDefault('passgrade', 0);

        $mform->addElement('text', 'passpercentage', get_string('passpercentage', 'local_cmc_lms'), ['size' => 10]);
        $mform->setType('passpercentage', PARAM_FLOAT);
        $mform->setDefault('passpercentage', 0);

        $mform->addElement('text', 'maxattempts', get_string('maxattempts', 'local_cmc_lms'), ['size' => 10]);
        $mform->setType('maxattempts', PARAM_INT);
        $mform->setDefault('maxattempts', 0);

        $mform->addElement('text', 'timelimit', get_string('timelimitseconds', 'local_cmc_lms'), ['size' => 10]);
        $mform->setType('timelimit', PARAM_INT);
        $mform->setDefault('timelimit', 0);

        $mform->addElement('advcheckbox', 'active', get_string('active', 'local_cmc_lms'));
        $mform->setDefault('active', 1);

        $this->add_action_buttons(false, get_string('saveevaluationrule', 'local_cmc_lms'));
    }

    /**
     * Validate rule thresholds and activity selection.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $quizzes = $this->_customdata['quizzes'] ?? [];

        if (empty($data['cmid']) || !array_key_exists((int)$data['cmid'], $quizzes)) {
            $errors['cmid'] = get_string('invalidevaluationquiz', 'local_cmc_lms');
        }
        if ((float)($data['passgrade'] ?? 0) < 0) {
            $errors['passgrade'] = get_string('invalidnegativevalue', 'local_cmc_lms');
        }
        $passpercentage = (float)($data['passpercentage'] ?? 0);
        if ($passpercentage < 0 || $passpercentage > 100) {
            $errors['passpercentage'] = get_string('invalidpercentage', 'local_cmc_lms');
        }
        if ((int)($data['maxattempts'] ?? 0) < 0) {
            $errors['maxattempts'] = get_string('invalidnegativevalue', 'local_cmc_lms');
        }
        if ((int)($data['timelimit'] ?? 0) < 0) {
            $errors['timelimit'] = get_string('invalidnegativevalue', 'local_cmc_lms');
        }

        return $errors;
    }
}
