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

    return true;
}
