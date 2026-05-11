<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cmc_lms\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;

/**
 * Privacy provider for local_cmc_lms.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadata_provider {
    /**
     * Return metadata stored by this plugin.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_cmc_lms_company_user', [
            'companyid' => 'privacy:metadata:company_user:companyid',
            'userid' => 'privacy:metadata:company_user:userid',
            'companyrole' => 'privacy:metadata:company_user:companyrole',
        ], 'privacy:metadata:company_user');

        return $collection;
    }
}
