<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

use local_cmc_lms\local\access_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Add CMC navigation items for scoped client supervisors and report viewers.
 *
 * @param global_navigation $navigation Global navigation tree.
 * @return void
 */
function local_cmc_lms_extend_navigation(global_navigation $navigation): void {
    global $USER;

    if (during_initial_install() || !isloggedin() || isguestuser()) {
        return;
    }

    $context = context_system::instance();
    $access = new access_helper();
    $canviewglobal = has_capability('local/cmc_lms:viewreports', $context)
        || has_capability('local/cmc_lms:viewcompanyreports', $context);
    $canviewscoped = $access->has_client_supervisor_scope((int)$USER->id);

    if (!$canviewglobal && !$canviewscoped) {
        return;
    }

    $url = new moodle_url('/local/cmc_lms/reports.php');
    if (!$canviewglobal) {
        $companyids = $access->get_scoped_company_ids((int)$USER->id);
        if (count($companyids) === 1) {
            $url = new moodle_url('/local/cmc_lms/reports.php', ['companyid' => reset($companyids)]);
        }
    }

    $parent = $navigation->find('mycourses', navigation_node::TYPE_ROOTNODE);
    if (!$parent) {
        $parent = $navigation->find('home', navigation_node::TYPE_ROOTNODE);
    }
    if (!$parent) {
        $parent = $navigation;
    }

    if (!$parent->find('cmc_reports', navigation_node::TYPE_CUSTOM)) {
        $parent->add(
            get_string('b2breports', 'local_cmc_lms'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'cmc_reports',
            new pix_icon('i/report', '')
        );
    }
}
