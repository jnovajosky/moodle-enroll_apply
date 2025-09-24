<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    enrol_apply
 * @copyright  emeneo.com (http://emeneo.com/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     emeneo.com (http://emeneo.com/)
 * @author     Johannes Burk <johannes.burk@sudile.com>
 * @editor     Josh Novajosky <jnovajosky@chas.org>
 */

// The name of your plug-in. Displayed on admin menus.
$string['enrolname'] = 'Enrollment by Approval';
$string['pluginname'] = 'Enrollment by Approval';
$string['pluginname_desc'] = 'With this plug-in users can apply to be enrolled in a course. A teacher or site manager will then have to approve the enrollment before the user gets enroled.';

// Custom menus and badges.
$string['approvals'] = 'Approvals';
$string['pendingapproval'] = 'Pending approval';
$string['waitlist'] = 'Waitlist';

$string['confirmmail_heading'] = 'Confirmation email';
$string['confirmmail_desc'] = '';
$string['confirmmailsubject'] = 'Confirmation email subject';
$string['confirmmailsubject_desc'] = '';
$string['confirmmailcontent'] = 'Confirmation email content';
$string['confirmmailcontent_desc'] = 'Please use the following special marks to replace email content with data from Moodle.<br/>{firstname}:The first name of the user; {content}:The course name;{lastname}:The last name of the user;{username}:The users registration username;{timeend}: The enrolment expiration date';

$string['waitmail_heading'] = 'Waiting list email';
$string['waitmail_desc'] = '';
$string['waitmailsubject'] = 'Waiting list mail subject';
$string['waitmailsubject_desc'] = '';
$string['waitmailcontent'] = 'Waiting list mail content';
$string['waitmailcontent_desc'] = 'Please use the following special marks to replace email content with data from Moodle.<br/>{firstname}:The first name of the user; {content}:The course name;{lastname}:The last name of the user;{username}:The users registration username';

$string['cancelmail_heading'] = 'Cancelation email';
$string['cancelmail_desc'] = '';
$string['cancelmailsubject'] = 'Cancelation email subject';
$string['cancelmailsubject_desc'] = '';
$string['cancelmailcontent'] = 'Cancelation email content';
$string['cancelmailcontent_desc'] = 'Please use the following special marks to replace email content with data from Moodle.<br/>{firstname}:The first name of the user; {content}:The course name;{lastname}:The last name of the user;{username}:The users registration username';

//Student Withdrawal
$string['withdrawrequest'] = 'Withdraw request';
$string['requestwithdrawn'] = 'Your enrolment request has been withdrawn.';
$string['alreadyapplied'] = 'Your enrolment request is currently: {$a}.';
$string['cancelfailed'] = 'Unable to withdraw this request.';

$string['notify_heading'] = 'Notification settings';
$string['notify_desc'] = 'Define who gets notified about new enrollment applications.';
$string['notifycoursebased'] = "New enrollment application notification (instance based, eg. course teachers)";
$string['notifycoursebased_desc'] = "Default for new instances: Notify everyone who have the 'Manage apply enrollment' capability for the corresponding course (eg. teachers and managers)";
$string['notifyglobal'] = "New enrollment application notification (global, eg. global managers and admins)";
$string['notifyglobal_desc'] = "Define who gets notified about new enrollment applications for any course.";
$string['sendbeforestart'] = 'Send notifications before course start';
$string['sendbeforestart_help'] = 'If enabled, enrollment request notifications will be sent immediately, even if the course start date is in the future.';

$string['notify_pending_popup'] = 'There is an enrollment approval pending for {$a->coursename}. Click here to manage request.';
$string['manage_enrol_requests'] = 'Manage requests';

$string['messageprovider:application'] = 'Course enrollment application notifications';
$string['messageprovider:confirmation'] = 'Course enrollment application confirmation notifications';
$string['messageprovider:cancelation'] = 'Course enrollment application cancelation notifications';
$string['messageprovider:waitinglist'] = 'Course enrollment application defer notifications';

$string['newapplicationnotification'] = 'There is a new course enrollment application awaiting review.';
$string['applicationconfirmednotification'] = 'Your course enrollment application was confirmed.';
$string['applicationcancelednotification'] = 'Your course enrollment application was canceled.';
$string['applicationdeferrednotification'] = 'Your course enrollment application was deferred (you are currently on the waiting list).';

$string['confirmusers'] = 'Confirm Enrollment';
$string['confirmusers_desc'] = 'Users in gray colored rows are on the waiting list.';

$string['coursename'] = 'Course';
$string['applyuser'] = 'First name / Last Name';
$string['applyusermail'] = 'Email';
$string['applydate'] = 'Enrollment date';
$string['btnconfirm'] = 'Confirm requests';
$string['btnwait'] = 'Defer requests';
$string['btncancel'] = 'Cancel requests';
$string['enrolusers'] = 'Enroll users';

$string['status'] = 'Allow Course enrollment confirmation';
$string['newenrols'] = 'Allow new course enrollment request';
$string['confirmenrol'] = 'Manage application';

$string['apply:config'] = 'Configure apply enrollment instances';
$string['apply:manage'] = 'Manage user enrollments';
$string['apply:manageapplications'] = 'Manage enrollment application';
$string['apply:unenrol'] = 'Unenroll users from the course';
$string['apply:unenrolself'] = 'Unenroll self from the course';

$string['notification'] = '<b>Enrollment application successfully sent</b>. <br/><br/>You will be informed by email when your enrollment has been confirmed.';

$string['mailtoteacher_suject'] = 'New Enrollment request!';
$string['editdescription'] = 'Enrollment Information (Displayed on form)';
$string['comment'] = 'Comment';
$string['applycomment'] = 'Comment';
$string['applymanage'] = 'Manage enrollment applications';

$string['status_desc'] = 'Allow course access of internally enrolled users.';
$string['user_profile'] = 'User Profile';

$string['show_standard_user_profile'] = 'Show standard user profile fields on enrollment screen';
$string['show_extra_user_profile'] = 'Show extra user profile fields on enrollment screen';

//$string['custom_label'] = 'Custom label "{replace_title}"';
$string['custom_label'] = 'Custom label';

$string['maxenrolled'] = 'Max enrolled users';
$string['maxenrolled_help'] = 'Specifies the maximum number of users that can self enroll. 0 means no limit.';
$string['maxenrolledreached_left'] = 'Maximum number of users allowed';
$string['maxenrolledreached_right'] = 'has already been reached.';

$string['cantenrol'] = 'Enrollment is disabled or inactive';

$string['maxenrolled_tip_1'] = 'out of';
$string['maxenrolled_tip_2'] = 'seats already booked.';

$string['defaultperiod'] = 'Default enrollment duration';
$string['defaultperiod_desc'] = 'Default length of time that the enrollment is valid. If set to zero, the enrollment duration will be unlimited by default.';
$string['defaultperiod_help'] = 'Default length of time that the enrollment is valid, starting with the moment the user is enrolled. If disabled, the enrollment duration will be unlimited by default.';
$string['expiry_heading'] = 'Expiry settings';
$string['expiry_desc'] = '';
$string['expiredaction'] = 'Enrollment expiry action';
$string['expiredaction_help'] = 'Select action to carry out when user enrollment expires. Please note that some user data and settings are purged from course during course unenrolment.';

$string['submitted_info'] = 'Enrollment info';
$string['privacy:metadata'] = 'The Course enrollment confirmation plugin does not store any personal data.';

$string['enrolperiod'] = 'Enrollment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrollment is valid. If set to zero, the enrollment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrollment is valid, starting with the moment the user enrolls themselves. If disabled, the enrollment duration will be unlimited.';

$string['expirynotifyall'] = 'Teacher and enrolled user';
$string['expirynotifyenroller'] = 'Teacher only';

$string['group'] = 'Group assignment';
$string['group_help'] = 'You can assign none or multiples groups';

$string['opt_commentaryzone'] = 'Comments field';
$string['opt_commentaryzone_help'] = 'Yes -> Enable the comments field in the enrollment form';

$string['expirymessageenrollersubject'] = 'Apply enrollment expiry notification';
$string['expirymessageenrollerbody'] = 'Apply enrollment in the course \'{$a->course}\' will expire within the next {$a->threshold} for the following users:

    {$a->users}

To extend their enrolment, go to {$a->extendurl}';
$string['expirymessageenrolledsubject'] = 'Apply enrollment expiry notification';
$string['expirymessageenrolledbody'] = 'Dear {$a->user},

This is a notification that your enrolment in the course \'{$a->course}\' is due to expire on {$a->timeend}.

If you need help, please contact {$a->enroller}.';

$string['sendexpirynotificationstask'] = "Apply enrollment send expiry notifications task";

$string['messageprovider:expiry_notification'] = 'Apply enrollment expiry notifications';

$string["profileoption"] = "Profile Field to Show in Table";

