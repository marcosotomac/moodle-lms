<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upgrade steps for local_cmc_lms.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade local_cmc_lms database schema.
 *
 * @param int $oldversion Installed plugin version.
 * @return bool
 */
function xmldb_local_cmc_lms_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026051106) {
        $table = new xmldb_table('local_cmc_lms_cert');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('programid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('code', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('verifytoken', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeissued', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('issuerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'issued');
        $table->add_field('timerevoked', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('revokerid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('revocationreason', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('company_fk', XMLDB_KEY_FOREIGN, ['companyid'], 'local_cmc_lms_company', ['id']);
        $table->add_key('program_fk', XMLDB_KEY_FOREIGN, ['programid'], 'local_cmc_lms_program', ['id']);

        $table->add_index('code', XMLDB_INDEX_UNIQUE, ['code']);
        $table->add_index('verifytoken', XMLDB_INDEX_UNIQUE, ['verifytoken']);
        $table->add_index('user_course', XMLDB_INDEX_NOTUNIQUE, ['userid', 'courseid']);
        $table->add_index('companyid', XMLDB_INDEX_NOTUNIQUE, ['companyid']);
        $table->add_index('programid', XMLDB_INDEX_NOTUNIQUE, ['programid']);
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026051106, 'local', 'cmc_lms');
    }

    if ($oldversion < 2026051107) {
        $table = new xmldb_table('local_cmc_lms_program');
        $fields = [
            new xmldb_field('versioncode', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'v1', 'descriptionformat'),
            new xmldb_field('modality', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'async', 'versioncode'),
            new xmldb_field('versionnotes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'modality'),
            new xmldb_field('effectivefrom', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'versionnotes'),
            new xmldb_field('plannedhours', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0', 'effectivefrom'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $table = new xmldb_table('local_cmc_lms_program_course');
        $fields = [
            new xmldb_field('contentlabel', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'required'),
            new xmldb_field('contentformat', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'other', 'contentlabel'),
            new xmldb_field('reusenotes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'contentformat'),
            new xmldb_field('plannedhours', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0', 'reusenotes'),
            new xmldb_field('schedulestart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'plannedhours'),
            new xmldb_field('scheduleend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'schedulestart'),
            new xmldb_field('liveprovider', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'scheduleend'),
            new xmldb_field('liveurl', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'liveprovider'),
            new xmldb_field('attendancetracking', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'liveurl'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $table = new xmldb_table('local_cmc_lms_attendance');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('programcourseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'present');
        $table->add_field('timetaken', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('programcourse_fk', XMLDB_KEY_FOREIGN, ['programcourseid'], 'local_cmc_lms_program_course', ['id']);
        $table->add_index('programcourse_user', XMLDB_INDEX_NOTUNIQUE, ['programcourseid', 'userid']);
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026051107, 'local', 'cmc_lms');
    }

    if ($oldversion < 2026051108) {
        $table = new xmldb_table('local_cmc_lms_program_role');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmcrole', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, 'teacher_internal');
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('program_fk', XMLDB_KEY_FOREIGN, ['programid'], 'local_cmc_lms_program', ['id']);

        $table->add_index('program_user_role', XMLDB_INDEX_UNIQUE, ['programid', 'userid', 'cmcrole']);
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $table->add_index('cmcrole', XMLDB_INDEX_NOTUNIQUE, ['cmcrole']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026051108, 'local', 'cmc_lms');
    }

    if ($oldversion < 2026051109) {
        $table = new xmldb_table('local_cmc_lms_eval_rule');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('programid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('scope', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'module');
        $table->add_field('passgrade', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('passpercentage', XMLDB_TYPE_NUMBER, '5, 2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('maxattempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timelimit', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('program_fk', XMLDB_KEY_FOREIGN, ['programid'], 'local_cmc_lms_program', ['id']);

        $table->add_index('program_course_quiz', XMLDB_INDEX_NOTUNIQUE, ['programid', 'courseid', 'quizid', 'cmid', 'scope']);
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $table->add_index('quizid', XMLDB_INDEX_NOTUNIQUE, ['quizid']);
        $table->add_index('cmid', XMLDB_INDEX_NOTUNIQUE, ['cmid']);
        $table->add_index('active', XMLDB_INDEX_NOTUNIQUE, ['active']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026051109, 'local', 'cmc_lms');
    }

    if ($oldversion < 2026051110) {
        $table = new xmldb_table('local_cmc_lms_cert');
        $fields = [
            new xmldb_field('certificatetitle', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'Certificado CMC', 'issuerid'),
            new xmldb_field('coursehours', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0', 'certificatetitle'),
            new xmldb_field('completiontime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'coursehours'),
            new xmldb_field('pdfgenerated', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'completiontime'),
            new xmldb_field('timegenerated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'pdfgenerated'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $DB->execute("UPDATE {local_cmc_lms_cert}
                          SET certificatetitle = :title
                        WHERE certificatetitle IS NULL OR certificatetitle = ''", [
            'title' => 'Certificado CMC',
        ]);
        $DB->execute("UPDATE {local_cmc_lms_cert}
                         SET completiontime = timeissued
                       WHERE completiontime = 0 OR completiontime IS NULL");

        upgrade_plugin_savepoint(true, 2026051110, 'local', 'cmc_lms');
    }

    return true;
}
