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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Josh Novajosky <jnovajosky@gmail.com>
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);          // enrol instance id
require_sesskey();

$instance = $DB->get_record('enrol', ['id' => $id, 'enrol' => 'apply'], '*', MUST_EXIST);
$course   = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($course->id);

$ue = $DB->get_record('user_enrolments', [
    'enrolid' => $instance->id,
    'userid'  => $USER->id
], '*', MUST_EXIST);

// Only allow while still pending/waitlist.
$allowed = in_array($ue->status, [ENROL_USER_SUSPENDED, ENROL_APPLY_USER_WAIT]);
if (!$allowed) {
    print_error('cancelfailed', 'enrol_apply');
}

// Remove enrol + application record.
$plugin = enrol_get_plugin('apply');
$plugin->unenrol_user($instance, $USER->id);
$DB->delete_records('enrol_apply_applicationinfo', ['userenrolmentid' => $ue->id]);

// Notify course managers.
$plugin->notify_applicant(
    $instance,
    (object)['id' => $ue->id, 'userid' => $USER->id, 'timestart' => $ue->timestart, 'timeend' => $ue->timeend],
    'cancelation',
    get_config('enrol_apply', 'cancelmailsubject') ?: get_string('applicationcancelednotification', 'enrol_apply'),
    get_config('enrol_apply', 'cancelmailcontent') ?: get_string('applicationcancelednotification', 'enrol_apply')
);

// Also notify course managers (popup/email) that a request was withdrawn.
require_once($CFG->dirroot.'/enrol/apply/notification.php');

$plugin   = enrol_get_plugin('apply'); // you already have this earlier
$managers = $plugin->get_notifycoursebased_users($instance);

if (!empty($managers)) {
    $manageurl = new moodle_url('/enrol/apply/manage.php', ['id' => $instance->id]);
    $a         = (object)['coursename' => format_string($course->fullname)];

    // Subject/body for email (popup uses smallmessage from notification.php).
    $subject = get_string('applicationwithdrawnsubject', 'enrol_apply', $a);
    $content = get_string('applicationwithdrawnbody',    'enrol_apply', $a);

    // Honor your "send before start" toggle.
    $courseidfornotify = !empty($instance->customint9) ? 0 : $instance->courseid;

    foreach ($managers as $muser) {
        $msg = new enrol_apply_notification(
            $muser,
            $USER,             // from: applicant (or use core_user::get_support_user() if you prefer)
            'withdrawn',       // <= new provider/type
            $subject,
            $content,
            $manageurl,
            $courseidfornotify
        );
        message_send($msg);
    }
}

// Redirect.
redirect(new moodle_url('/course/view.php', ['id' => $course->id]),
    get_string('requestwithdrawn', 'enrol_apply'), null, \core\output\notification::NOTIFY_SUCCESS);
