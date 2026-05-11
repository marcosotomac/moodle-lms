<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * English strings for local_cmc_lms.
 *
 * @package    local_cmc_lms
 * @copyright  2026 CMC & Soluciones en Gestión Humana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'CMC LMS domain';
$string['privacy:metadata'] = 'The CMC LMS domain plugin stores B2B company and program metadata configured by site managers.';
$string['privacy:metadata:company_user'] = 'Stores associations between Moodle users and CMC client companies.';
$string['privacy:metadata:company_user:companyid'] = 'The associated CMC client company.';
$string['privacy:metadata:company_user:userid'] = 'The Moodle user associated with the company.';
$string['privacy:metadata:company_user:companyrole'] = 'The role of the user inside the client company context.';
$string['privacy:metadata:cert'] = 'Stores CMC certificates issued to Moodle users.';
$string['privacy:metadata:cert:userid'] = 'The Moodle user who received the certificate.';
$string['privacy:metadata:cert:courseid'] = 'The Moodle course certified.';
$string['privacy:metadata:cert:companyid'] = 'The optional CMC client company associated with the certificate.';
$string['privacy:metadata:cert:programid'] = 'The optional CMC training program associated with the certificate.';
$string['privacy:metadata:cert:code'] = 'The readable certificate code.';
$string['privacy:metadata:cert:verifytoken'] = 'The public verification token for the certificate.';
$string['privacy:metadata:cert:timeissued'] = 'The time the certificate was issued.';
$string['privacy:metadata:cert:issuerid'] = 'The Moodle user who issued the certificate.';
$string['privacy:metadata:cert:status'] = 'The current certificate status.';
$string['privacy:metadata:attendance'] = 'Stores attendance records for scheduled CMC program-course links.';
$string['privacy:metadata:attendance:programcourseid'] = 'The CMC program-course link attended.';
$string['privacy:metadata:attendance:userid'] = 'The Moodle user whose attendance was recorded.';
$string['privacy:metadata:attendance:status'] = 'The recorded attendance status.';
$string['privacy:metadata:attendance:timetaken'] = 'The time the attendance status applies to.';
$string['cmc_lms:managecompanies'] = 'Manage CMC client companies';
$string['cmc_lms:viewcompanies'] = 'View CMC client companies';
$string['cmc_lms:manageprograms'] = 'Manage CMC training programs';
$string['cmc_lms:viewprograms'] = 'View CMC training programs';
$string['cmc_lms:viewreports'] = 'View CMC LMS reports';
$string['cmc_lms:issuecertificates'] = 'Issue and revoke CMC certificates';
$string['cmc_lms:viewcertificates'] = 'View CMC certificates';
$string['actions'] = 'Actions';
$string['active'] = 'Active';
$string['addcompany'] = 'Add company';
$string['addcompanyuser'] = 'Add company user';
$string['addcourse'] = 'Add course';
$string['addprogram'] = 'Add program';
$string['backtoprograms'] = 'Back to programs';
$string['activeusers'] = 'Active users';
$string['b2breports'] = 'B2B reports';
$string['certificatecode'] = 'Certificate code';
$string['certificateissued'] = 'Certificate issued.';
$string['certificateisrevoked'] = 'This certificate was revoked and is not valid.';
$string['certificateisvalid'] = 'This certificate is valid.';
$string['certificatenotfound'] = 'No certificate was found for this verification code.';
$string['certificaterevoked'] = 'Certificate revoked.';
$string['certificates'] = 'Certificates';
$string['companies'] = 'Client companies';
$string['company'] = 'Company';
$string['companyname'] = 'Company name';
$string['companyprogress'] = '{$a} user progress';
$string['companyrole'] = 'Company role';
$string['completedcourses'] = 'Completed CMC courses';
$string['completionpercentage'] = 'Completion %';
$string['completions'] = 'Completions';
$string['attendancetracking'] = 'Track attendance';
$string['contentformat'] = 'Content format';
$string['contentformatdocument'] = 'Document';
$string['contentformatexternal'] = 'External resource';
$string['contentformatlesson'] = 'Lesson';
$string['contentformatother'] = 'Other';
$string['contentformatquiz'] = 'Quiz';
$string['contentformatvideo'] = 'Video';
$string['contentlabel'] = 'Section/module label or content role';
$string['country'] = 'Country';
$string['course'] = 'Course';
$string['coursealreadylinked'] = 'This course is already linked to the selected program.';
$string['courses'] = 'Courses';
$string['description'] = 'Description';
$string['edit'] = 'Edit';
$string['editcompany'] = 'Edit company';
$string['editprogram'] = 'Edit program';
$string['effectivefrom'] = 'Effective from';
$string['inactive'] = 'Inactive';
$string['invalidcourse'] = 'The selected course does not exist.';
$string['invalidcompany'] = 'The selected company is not available for reports.';
$string['invalidprogram'] = 'The selected program does not exist.';
$string['invaliduser'] = 'The selected user does not exist or is deleted.';
$string['issued'] = 'Issued';
$string['issueddate'] = 'Issued date';
$string['issuecertificate'] = 'Issue certificate';
$string['location'] = 'Location';
$string['liveprovider'] = 'Live session provider';
$string['liveurl'] = 'Live session URL';
$string['manualrevocation'] = 'Manual revocation from CMC certificate administration.';
$string['modality'] = 'Modality';
$string['modalityasync'] = 'Asynchronous';
$string['modalityblended'] = 'Blended';
$string['modalitysync'] = 'Synchronous';
$string['nocompanies'] = 'No client companies have been created yet.';
$string['nocertificates'] = 'No certificates have been issued yet.';
$string['nocourses'] = 'No courses have been linked to this program yet.';
$string['noprograms'] = 'No training programs have been created yet.';
$string['nousersforcompany'] = 'No active users are associated with this company.';
$string['programname'] = 'Program name';
$string['plannedhours'] = 'Planned hours';
$string['program'] = 'Program';
$string['programs'] = 'Training programs';
$string['qrpayload'] = 'QR payload';
$string['qrpayloadhelp'] = 'Use this verification URL as the QR payload. No external QR dependency is bundled in this first slice.';
$string['recentcertificates'] = 'Recent certificates';
$string['reusenotes'] = 'Reuse source/notes';
$string['required'] = 'Required';
$string['revoke'] = 'Revoke';
$string['revoked'] = 'Revoked';
$string['revokeddate'] = 'Revoked date';
$string['saved'] = 'Changes saved';
$string['scheduleend'] = 'Schedule end';
$string['scheduleendbeforestart'] = 'Schedule end must be after schedule start.';
$string['schedulestart'] = 'Schedule start';
$string['sector'] = 'Sector';
$string['shortnameexists'] = 'This shortname is already in use.';
$string['size'] = 'Size';
$string['sortorder'] = 'Sort order';
$string['status'] = 'Status';
$string['student'] = 'Student';
$string['students'] = 'Students';
$string['supervisor'] = 'Client supervisor';
$string['supervisors'] = 'Client supervisors';
$string['enrolledcourses'] = 'Enrolled CMC courses';
$string['enrolments'] = 'Enrolments';
$string['user'] = 'User';
$string['userid'] = 'Moodle user ID';
$string['users'] = 'Users';
$string['verificationurl'] = 'Verification URL';
$string['versioncode'] = 'Version label/code';
$string['versionnotes'] = 'Version notes/change summary';
$string['verifycertificate'] = 'Verify certificate';
