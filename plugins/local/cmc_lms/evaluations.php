<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * CMC evaluation mapping and reporting page.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use local_cmc_lms\form\evaluation_rule_form;
use local_cmc_lms\local\evaluation_repository;
use local_cmc_lms\local\program_repository;

$ruleid = optional_param('ruleid', 0, PARAM_INT);

admin_externalpage_setup('local_cmc_lms_evaluations');

$context = context_system::instance();
require_capability('local/cmc_lms:viewevaluationreports', $context);

$repository = new evaluation_repository();
$canmanage = has_capability('local/cmc_lms:manageevaluations', $context);
$quizzes = $repository->list_quiz_activities();
$quizoptions = [];
foreach ($quizzes as $quiz) {
    $quizoptions[(int)$quiz->cmid] = format_string($quiz->coursefullname) . ' / ' . format_string($quiz->quizname) .
        ' (cmid ' . (int)$quiz->cmid . ')';
}

$programoptions = [0 => get_string('none')];
foreach ((new program_repository())->list_with_courses(false) as $program) {
    $programoptions[(int)$program->id] = format_string($program->name) . ' (' . s($program->shortname) . ')';
}

$form = null;
if ($canmanage) {
    $form = new evaluation_rule_form(null, [
        'quizzes' => $quizoptions,
        'programs' => $programoptions,
    ]);
    if ($data = $form->get_data()) {
        $savedruleid = $repository->save_rule($data);
        redirect(new moodle_url('/local/cmc_lms/evaluations.php', ['ruleid' => $savedruleid]), get_string('saved', 'local_cmc_lms'));
    }
}

$rules = $repository->list_rules(false);
if ($ruleid <= 0 && !empty($rules)) {
    $ruleid = (int)$rules[0]->id;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('evaluations', 'local_cmc_lms'));
echo $OUTPUT->notification(get_string('evaluationquiznotice', 'local_cmc_lms'), 'info');

if ($canmanage) {
    if (empty($quizoptions)) {
        echo $OUTPUT->notification(get_string('noquizactivities', 'local_cmc_lms'), 'warning');
    } else {
        echo $OUTPUT->heading(get_string('addevaluationrule', 'local_cmc_lms'), 3);
        $form->display();
    }
}

echo $OUTPUT->heading(get_string('evaluationrules', 'local_cmc_lms'), 3);
if (empty($rules)) {
    echo $OUTPUT->notification(get_string('noevaluationrules', 'local_cmc_lms'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$ruletable = new html_table();
$ruletable->head = [
    get_string('program', 'local_cmc_lms'),
    get_string('course', 'local_cmc_lms'),
    get_string('quizactivity', 'local_cmc_lms'),
    get_string('evaluationscope', 'local_cmc_lms'),
    get_string('passgrade', 'local_cmc_lms'),
    get_string('passpercentage', 'local_cmc_lms'),
    get_string('maxattempts', 'local_cmc_lms'),
    get_string('timelimitseconds', 'local_cmc_lms'),
    get_string('status', 'local_cmc_lms'),
];

foreach ($rules as $rule) {
    $url = new moodle_url('/local/cmc_lms/evaluations.php', ['ruleid' => $rule->id]);
    $programlabel = empty($rule->programid) ? get_string('none') : format_string($rule->programname);
    $ruletable->data[] = [
        $programlabel,
        format_string($rule->coursefullname),
        html_writer::link($url, format_string($rule->quizname)),
        s($rule->scope),
        format_float((float)$rule->passgrade, 2),
        format_float((float)$rule->passpercentage, 2) . '%',
        (int)$rule->maxattempts,
        (int)$rule->timelimit,
        $rule->active ? get_string('active', 'local_cmc_lms') : get_string('inactive', 'local_cmc_lms'),
    ];
}
echo html_writer::table($ruletable);

if ($ruleid > 0) {
    echo $OUTPUT->heading(get_string('evaluationresults', 'local_cmc_lms'), 3);
    $results = $repository->get_results_for_rule($ruleid);
    if (empty($results)) {
        echo $OUTPUT->notification(get_string('noevaluationresults', 'local_cmc_lms'), 'info');
    } else {
        $resulttable = new html_table();
        $resulttable->head = [
            get_string('user'),
            get_string('email'),
            get_string('attemptcount', 'local_cmc_lms'),
            get_string('bestgrade', 'local_cmc_lms'),
            get_string('finalgrade', 'local_cmc_lms'),
            get_string('passed', 'local_cmc_lms'),
            get_string('lastattempttime', 'local_cmc_lms'),
        ];
        foreach ($results as $result) {
            $resulttable->data[] = [
                html_writer::link(new moodle_url('/user/profile.php', ['id' => $result->userid]), s($result->fullname)),
                s($result->email),
                (int)$result->attempts,
                format_float((float)$result->bestgrade, 2),
                format_float((float)$result->finalgrade, 2),
                $result->passed ? get_string('yes') : get_string('no'),
                empty($result->lastattempttime) ? '-' : userdate($result->lastattempttime, get_string('strftimedatetimeshort', 'langconfig')),
            ];
        }
        echo html_writer::table($resulttable);
    }
}

echo $OUTPUT->footer();
