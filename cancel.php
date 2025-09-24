<?php
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

// Redirect.
redirect(new moodle_url('/course/view.php', ['id' => $course->id]),
    get_string('requestwithdrawn', 'enrol_apply'), null, \core\output\notification::NOTIFY_SUCCESS);
