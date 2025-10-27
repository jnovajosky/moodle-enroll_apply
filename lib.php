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
 * @package    moodle-enroll_apply
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Josh Novajosky <jnovajosky@gmail.com>
 */

/** The user is put onto a waiting list and therefore the enrolment not active (used in user_enrolments->status) */
define('ENROL_APPLY_USER_WAIT', 2);
require_once($CFG->dirroot.'/group/lib.php');

class enrol_apply_plugin extends enrol_plugin {

    /**
     * Add new instance of enrol plugin with default settings.
     * @param object $course
     * @return int id of new instance
     */
    public function add_default_instance($course) {
        $fields = $this->get_instance_defaults();
        return $this->add_instance($course, $fields);
    }

    public function allow_unenrol(stdClass $instance) {
        // Users with unenrol cap may unenrol other users manually.
        return true;
    }
	
    public function roles_protected() {
        // Users may tweak the roles later.
        return false;
    }

    public function allow_apply(stdClass $instance) {
        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            return get_string('cantenrol', 'enrol_apply');
        }
        if (!$instance->customint6) {
            // New enrols not allowed.
            return get_string('cantenrol', 'enrol_apply');
        }
        return true;
    }
    /**
     * Prevent the unenrollment of a user with a pending application
     * @param stdClass $instance course enrol instance
     * @param stdClass $ue record from user_enrolments table, specifies user
     * @return bool
     */
    public function allow_unenrol_user(stdClass $instance, stdClass $ue) {
        global $DB;
        return parent::allow_unenrol_user($instance, $ue);
    }
	
    public function allow_manage(stdClass $instance) {
        // Users with manage cap may tweak period and status.
        return true;
    }
	
    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * Multiple instances supported.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);
        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/apply:config', $context)) {
            return null;
        }
        return new moodle_url('/enrol/apply/edit.php', array('courseid' => $courseid));
    }

    public function enrol_page_hook(stdClass $instance) {
        global $CFG, $OUTPUT, $SESSION, $USER, $DB;
        if (isguestuser()) {
            // Can not enrol guest!
            return null;
        }
        $allowapply = $this->allow_apply($instance);
			if ($allowapply !== true) {
				return '<div class="alert alert-error">' . $allowapply . '</div>';
			}

			if ($DB->record_exists('user_enrolments', array('userid' => $USER->id, 'enrolid' => $instance->id))) {
				return $OUTPUT->notification(get_string('notification', 'enrol_apply'), 'notifysuccess');
			}

			if ($instance->customint3 > 0) {
				// Max enrol limit specified.
				$count = $DB->count_records('user_enrolments', array('enrolid' => $instance->id));
				if ($count >= $instance->customint3) {
					// Bad luck, no more self enrolments here.
					return '<div class="alert alert-error">'.get_string('maxenrolledreached_left', 'enrol_apply')." (".$count.") ".get_string('maxenrolledreached_right', 'enrol_apply').'</div>';
				}
			}
			
			if ($DB->record_exists('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
				$ue = $DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id], '*', MUST_EXIST);
				$statuspending  = ($ue->status == ENROL_USER_SUSPENDED);
				$statuswaitlist = (defined('ENROL_APPLY_USER_WAIT') && $ue->status == ENROL_APPLY_USER_WAIT);
			
				if ($statuspending || $statuswaitlist) {
					$statuslabel = $statuspending ? get_string('pendingapproval', 'enrol_apply')
												  : get_string('waitlist', 'enrol_apply');
			
					$out  = $OUTPUT->notification(get_string('alreadyapplied', 'enrol_apply', $statuslabel), 'info');
					$url  = new moodle_url('/enrol/apply/cancel.php', ['id' => $instance->id, 'sesskey' => sesskey()]);
					$btn  = $OUTPUT->single_button($url, get_string('withdrawrequest', 'enrol_apply'), 'post');
					return $out . $btn;
				}
			
				// If not pending/waitlist, keep your original message:
				return $OUTPUT->notification(get_string('notification', 'enrol_apply'), 'notifysuccess');
			}

        require_once("$CFG->dirroot/enrol/apply/apply_form.php");

        $form = new enrol_apply_apply_form(null, $instance);
			if ($data = $form->get_data()) {
				// Only process when form submission is for this instance (multi instance support).
				if ($data->instance == $instance->id) {
					$timestart = time();
					$timeend = $timestart + $instance->enrolperiod;
					$roleid = $instance->roleid;
					$this->enrol_user($instance, $USER->id, $roleid, $timestart, $timeend, ENROL_USER_SUSPENDED);
					$userenrolment = $DB->get_record(
						'user_enrolments',
						array(
							'userid' => $USER->id,
							'enrolid' => $instance->id),
						'id', MUST_EXIST);
					$applicationinfo = new stdClass();
					$applicationinfo->userenrolmentid = $userenrolment->id;
					
					// Removed student comment on enrollment page, can be turned back on.
					//if (isset($data->applydescription)) {
					//    $applicationinfo->comment = $data->applydescription;
					// } else {
					//     $applicationinfo->comment = '';
					// }

					$DB->insert_record('enrol_apply_applicationinfo', $applicationinfo, false);

					// Allow for adding user to groups
					$groups = $DB->get_records(
						'enrol_apply_groups',
						array('enrolid' => $instance->id),
						null,
						'*',
						null,
						null
					);
					foreach ($groups as $value) {
						groups_add_member($value->groupid, $USER->id);
					}

					$this->send_application_notification($instance, $USER->id, $data);
					$notification = $OUTPUT->notification(get_string('notification', 'enrol_apply'), 'notifysuccess');
					$button = $OUTPUT->single_button(new moodle_url('/course/view.php', array('id'=> $instance->courseid)),
						get_string('continue'));
					return $notification . $button;
				}
        }

        $output = $form->render();

        return $OUTPUT->box($output);
    }

    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;
        if ($instance->enrol !== 'apply') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/apply:config', $context)) {
            $editlink = new moodle_url("/enrol/apply/edit.php", array('courseid' => $instance->courseid, 'id' => $instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon(
                't/edit',
                get_string('edit'),
                'core',
                array('class' => 'iconsmall')));
        }

        if (has_capability('enrol/apply:manageapplications', $context)) {
            $managelink = new moodle_url("/enrol/apply/manage.php", array('id' => $instance->id));
            $icons[] = $OUTPUT->action_icon($managelink, new pix_icon(
                'i/users',
                get_string('confirmenrol', 'enrol_apply'),
                'core',
                array('class' => 'iconsmall')));

            $infolink = new moodle_url("/enrol/apply/info.php", array('id' => $instance->id));
            $icons[] = $OUTPUT->action_icon($infolink, new pix_icon(
                'i/files',
                get_string('submitted_info', 'enrol_apply'),
                'core',
                array('class' => 'iconsmall')));
        }

        return $icons;
    }

    /**
     * Check if possible to hide/show enrollment instance
     * @param  stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
            $context = context_course::instance($instance->courseid);
            return has_capability('enrol/apply:config', $context);
    }

    /**
     * Check if possible to delete enrollment instance
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
            $context = context_course::instance($instance->courseid);
            return has_capability('enrol/apply:config', $context);
    }

    /**
     * Sets up navigation entries.
     * @param stdClass $instancesnode
     * @param stdClass $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        if ($instance->enrol !== 'apply') {
             throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/apply:config', $context)) {
            $managelink = new moodle_url('/enrol/apply/edit.php', array('courseid' => $instance->courseid, 'id' => $instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = array();
        $context  = $manager->get_context();
        $instance = $ue->enrolmentinstance;
    
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;

        // Unenrol (Custom).
        if ($this->allow_unenrol_user($instance, $ue) && has_capability("enrol/apply:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(
                new pix_icon('t/delete', ''),
                get_string('unenrol', 'enrol'),
                $url,
                array('class' => 'unenrollink', 'rel' => $ue->id)
            );
        }
    
        // Edit (Custom).
        if ($this->allow_manage($instance) && has_capability("enrol/apply:manage", $context)) {
            $url = new moodle_url('/enrol/editenrolment.php', $params);
            $actions[] = new user_enrolment_action(
                new pix_icon('t/edit', ''),
                get_string('edit'),
                $url,
                array('class'=>'editenrollink', 'rel'=>$ue->id)
            );
        }
    
        // Status badges + manage icon for enrol_apply.
        $isapply     = (isset($instance->enrol) && $instance->enrol === 'apply');
        $ispending   = ($ue->status == ENROL_USER_SUSPENDED); // your pending state
        $iswaitlist  = (defined('ENROL_APPLY_USER_WAIT') && $ue->status == ENROL_APPLY_USER_WAIT);
    
        if ($isapply && ($ispending || $iswaitlist)) {
            // Badge text depends on status.
            $label = $ispending
                ? get_string('pendingapproval', 'enrol_apply')
                : get_string('waitlist', 'enrol_apply');
    
            // Badge (non-clickable).
            $badge = new user_enrolment_action(
                new pix_icon('i/warning', '', 'core'),
                $label,
                new moodle_url('#'),
                array('class' => 'apply-status-badge', 'aria-disabled' => 'true', 'onclick' => 'return false;')
            );
            array_unshift($actions, $badge);
    
            // Manage icon.
            if (has_capability('enrol/apply:manageapplications', $context)) {
                $manageurl = new moodle_url('/enrol/apply/manage.php', ['id' => $instance->id]);
                $actions[] = new user_enrolment_action(
                    new pix_icon('i/users', '', 'core'),
                    get_string('confirmenrol', 'enrol_apply'),
                    $manageurl,
                    array('class' => 'apply-managelink', 'rel' => $ue->id)
                );
            }
        }
    
        return $actions;
    }

    /**
     * Returns defaults for new instances.
     * @return array
     */
    public function get_instance_defaults() {
        $fields = array();
        $fields['status']          = $this->get_config('status');
        $fields['roleid']          = $this->get_config('roleid', 0);
        $fields['customint1']      = $this->get_config('show_standard_user_profile');
        $fields['customint2']      = $this->get_config('show_extra_user_profile');
        $fields['customtext2']     = '';
        $fields['customtext3']     = $this->get_config('notifycoursebased') ? '$@ALL@$' : '';
        $fields['enrolperiod']     = $this->get_config('enrolperiod', 0);
        $fields['customint3']      = $this->get_config('maxenrolled');
        $fields['customint4']      = $this->get_config('sendcoursewelcomemessage');
        $fields['customint5']      = 0;
        $fields['customint6']      = $this->get_config('newenrols');

        return $fields;
    }

    function check_privileges($courseid,$userid){
        global $DB;
        //check for sistem privilege
        $context = context_system::instance();
        if(has_capability('enrol/apply:manageapplications', $context)){
            return true;
        }
        $context = context_course::instance($courseid, MUST_EXIST);
        if(has_capability('enrol/apply:manageapplications', $context)){
            return true;
        }
        $contextuser = $DB->get_record("context",array("instanceid"=>$userid,"contextlevel"=>CONTEXT_USER));
        $context = context::instance_by_id($contextuser->id);
        if(has_capability('enrol/apply:manageapplications', $context)){
            return true;
        }
    }
   
    public function confirm_enrolment($enrols) {
        global $DB;
        foreach ($enrols as $enrol) {
            $userenrolment = $DB->get_record_select(
                'user_enrolments',
                'id = :id AND (status = :enrolusersuspended OR status = :enrolapplyuserwait)',
                array(
                    'id' => $enrol,
                    'enrolusersuspended' => ENROL_USER_SUSPENDED,
                    'enrolapplyuserwait' => ENROL_APPLY_USER_WAIT),
                '*',
                MUST_EXIST);

            $instance = $DB->get_record('enrol', array('id' => $userenrolment->enrolid, 'enrol' => 'apply'), '*', MUST_EXIST);

            // Check privileges.
            if(!$this->check_privileges($instance->courseid,$userenrolment->userid)){
                continue;
            }

            // Set timestart and timeend if an enrolment duration is set.
            $userenrolment->timestart = time();
            $userenrolment->timeend   = 0;
            if ($instance->enrolperiod) {
                $userenrolment->timeend = $userenrolment->timestart + $instance->enrolperiod;
            }

            $this->update_user_enrol($instance, $userenrolment->userid, ENROL_USER_ACTIVE, $userenrolment->timestart, $userenrolment->timeend);
            $DB->delete_records('enrol_apply_applicationinfo', array('userenrolmentid' => $enrol));

            $this->notify_applicant(
                    $instance,
                    $userenrolment,
                    'confirmation',
                    get_config('enrol_apply', 'confirmmailsubject'),
                    get_config('enrol_apply', 'confirmmailcontent'));
        }
    }

    public function wait_enrolment($enrols) {
        global $DB;
        foreach ($enrols as $enrol) {
            $userenrolment = $DB->get_record(
                'user_enrolments',
                array('id' => $enrol, 'status' => ENROL_USER_SUSPENDED),
                '*', IGNORE_MISSING);

            if ($userenrolment != null) {
                $instance = $DB->get_record('enrol', array('id' => $userenrolment->enrolid, 'enrol' => 'apply'), '*', MUST_EXIST);

                // Check privileges.
                if(!$this->check_privileges($instance->courseid,$userenrolment->userid)){
                    continue;
                }
    

                $this->update_user_enrol($instance, $userenrolment->userid, ENROL_APPLY_USER_WAIT);

                $this->notify_applicant(
                    $instance,
                    $userenrolment,
                    'waitinglist',
                    get_config('enrol_apply', 'waitmailsubject'),
                    get_config('enrol_apply', 'waitmailcontent'));
            }
        }
    }

    public function cancel_enrolment($enrols) {
        global $DB;
        foreach ($enrols as $enrol) {
            $userenrolment = $DB->get_record_select(
                'user_enrolments',
                'id = :id AND (status = :enrolusersuspended OR status = :enrolapplyuserwait)',
                array(
                    'id' => $enrol,
                    'enrolusersuspended' => ENROL_USER_SUSPENDED,
                    'enrolapplyuserwait' => ENROL_APPLY_USER_WAIT),
                '*',
                MUST_EXIST);

            $instance = $DB->get_record('enrol', array('id' => $userenrolment->enrolid, 'enrol' => 'apply'), '*', MUST_EXIST);

            // Check privileges.
            if(!$this->check_privileges($instance->courseid,$userenrolment->userid)){
                continue;
            }


            $this->unenrol_user($instance, $userenrolment->userid);
            $DB->delete_records('enrol_apply_applicationinfo', array('userenrolmentid' => $enrol));

            $this->notify_applicant(
                $instance,
                $userenrolment,
                'cancelation',
                get_config('enrol_apply', 'cancelmailsubject'),
                get_config('enrol_apply', 'cancelmailcontent'));
        }
    }

	/**
	* Sends notifications to Instructors and System Admins
	* Note COURSE, USER, or SYSTEM contexts
	*/
    private function send_application_notification($instance, $userid, $data) {
        global $CFG, $PAGE,$DB;
        require_once($CFG->dirroot.'/enrol/apply/notification.php');
        // Required for course_get_url() function.
        require_once($CFG->dirroot.'/course/lib.php');

        $renderer = $PAGE->get_renderer('enrol_apply');

        $course = get_course($instance->courseid);
        $applicant = core_user::get_user($userid);

        // Include standard user profile fields?
        $standarduserfields = null;
        if ($instance->customint1) {
            $standarduserfields = clone $data;
            unset($standarduserfields->applydescription);
        }

        // Include extra user profile fields?
        $extrauserfields = null;
        if ($instance->customint2) {
            require_once($CFG->dirroot.'/user/profile/lib.php');
            profile_load_custom_fields($applicant);
            $extrauserfields = $applicant->profile;
        }

        // Send email to Instructors / Managers in COURSE context
        $courseuserstonotify = $this->get_notifycoursebased_users($instance);
        if (!empty($courseuserstonotify)) {
            $manageurl = new moodle_url("/enrol/apply/manage.php", array('id' => $instance->id));
            if (!isset($data->applydescription)) {
                $data->applydescription = '';
            }
            $content = $renderer->application_notification_mail_body(
                $course,
                $applicant,
                $manageurl,
                $data->applydescription,
                $standarduserfields,
                $extrauserfields);
				
			// Honor the "Send notifications before course start" toggle (customint9).
            foreach ($courseuserstonotify as $user) {
                $courseid = $instance->courseid;
                if (!empty($instance->customint9)) {
                    $courseid = 0;
                }
            
                $message = new enrol_apply_notification(
                    $user,
                    $applicant,
                    'application',
                    get_string('mailtoteacher_suject', 'enrol_apply'),
                    $content,
                    $manageurl,
                    $courseid
                );
                message_send($message);
            }
        }

        // Send email to Instructors / Managers in USER context
        $cohortuserstonotify = $this->get_users_from_usercapabilits($userid);

        if (!empty($cohortuserstonotify)) {
            $userenrol = $DB->get_record("user_enrolments",array("userid"=>$userid,"enrolid"=>$instance->id));
            $manageurl = new moodle_url("/enrol/apply/manage.php", array('userenrol' => $userenrol->id));
            if (!isset($data->applydescription)) {
                $data->applydescription = '';
            }
            $content = $renderer->application_notification_mail_body(
                $course,
                $applicant,
                $manageurl,
                $data->applydescription,
                $standarduserfields,
                $extrauserfields);
            foreach ($cohortuserstonotify as $user) {
                               $courseid = $instance->courseid;
                if (!empty($instance->customint9)) {
                    $courseid = 0;
                }
                $message = new enrol_apply_notification(
                    $user,
                    $applicant,
                    'application',
                    get_string('mailtoteacher_suject', 'enrol_apply'),
                    $content,
                    $manageurl,
                    $courseid);
                message_send($message);
            }
 
        }
		
		// Notify Instructor / Manager via System Message
        $courseuserstonotify = $this->get_notifycoursebased_users($instance);
        if (!empty($courseuserstonotify)) {
            $manageurl = new moodle_url("/enrol/apply/manage.php", ['id' => $instance->id]);
            $content = $renderer->application_notification_mail_body($course, $applicant, $manageurl);
            foreach ($courseuserstonotify as $user) {
                $message = new \core\message\message();
                $message->component = 'enrol_apply';
                $message->name = 'application';
                $message->userfrom = core_user::get_support_user();
                $message->userto = $user;
                $message->subject = get_string('newapplicationnotification', 'enrol_apply');
                $message->fullmessage = html_to_text($content);
                $message->fullmessagehtml = $content;
                $message->notification = 1;
                message_send($message);
            }
        }
		
        // Send email to users in SYSTEM context
        $globaluserstonotify = $this->get_notifyglobal_users();
        
        $globaluserstonotify = array_udiff($globaluserstonotify, $courseuserstonotify, function($usera, $userb) {
            return $usera->id == $userb->id ? 0 : -1;
        });
        if (!empty($globaluserstonotify)) {
            $manageurl = new moodle_url('/enrol/apply/manage.php');
            if (!isset($data->applydescription)) {
                $data->applydescription = '';
            }
            $content = $renderer->application_notification_mail_body(
                $course,
                $applicant,
                $manageurl,
                $data->applydescription,
                $standarduserfields,
                $extrauserfields);
            foreach ($globaluserstonotify as $user) {
                                $courseid = $instance->courseid;
                if (!empty($instance->customint9)) {
                    $courseid = 0;
                }

                $message = new enrol_apply_notification(
                    $user,
                    $applicant,
                    'application',
                    get_string('mailtoteacher_suject', 'enrol_apply'),
                    $content,
                    $manageurl,
                    $courseid);
                message_send($message);
            }
        }
        
        // Notify System Users via System Message
        $globaluserstonotify = $this->get_notifyglobal_users();
        $globaluserstonotify = array_udiff($globaluserstonotify, $courseuserstonotify, function($usera, $userb) {
            return $usera->id == $userb->id ? 0 : -1;
            });
            if (!empty($globaluserstonotify)) {
                $manageurl = new moodle_url('/enrol/apply/manage.php');
            if (!isset($data->applydescription)) {
                $data->applydescription = '';
            }
            $content = $renderer->application_notification_mail_body(
                $course,
                $applicant,
                $manageurl,
                $data->applydescription,
                $standarduserfields,
                $extrauserfields);
            foreach ($globaluserstonotify as $user) {
                $courseid = $instance->courseid;
                $message = new enrol_apply_notification(
                    $user,
                    $applicant,
                    'application',
                    get_string('mailtoteacher_suject', 'enrol_apply'),
                    $content,
                    $manageurl,
                    $courseid);
                message_send($message);
            }
        }
		
        // Notify Applicant about their application status via System Message
        $student = core_user::get_user($userid);
        $status_message = get_string('applicationreceived', 'enrol_apply', $course);
			// Set message based on the user's application status
			switch ($applicant->status) {
				case ENROL_USER_SUSPENDED:
					$status_message = get_string('applicationapprovednotification', 'enrol_apply', $course);
					break;
				case ENROL_APPLY_USER_WAIT:
					$status_message = get_string('applicationdeferrednotification', 'enrol_apply', $course);
					break;
				default:
					$status_message = get_string('applicationcancelednotification', 'enrol_apply', $course);
					break;
			}
			
		$message = new \core\message\message();
		$message->component = 'enrol_apply';
		$message->name = 'application_status_update';
		$message->userfrom = core_user::get_support_user();
		$message->userto = $student;
		$message->subject = get_string('applicationchangenotification', 'enrol_apply');
		$message->fullmessage = html_to_text($status_message);
		$message->fullmessagehtml = $status_message;
		$message->notification = 1;
		message_send($message);
    }

    /**
     * Returns enrolled users who should be notified about new applications in Course context
     *
     * Note: mostly copied from get_users_from_config() function in moodlelib.php.
     * @param  array $instance Enrol apply instance record.
     * @return array           Array of user IDs.
     */
    public function get_notifycoursebased_users($instance) {
        $value = $instance->customtext3;
        if (empty($value) or $value === '$@NONE@$') {
            return array();
        }

        $context = context_course::instance($instance->courseid);
        $users = get_enrolled_users($context, 'enrol/apply:manageapplications');

        if ($value === '$@ALL@$') {
            return $users;
        }

        $result = array();
        $allowed = explode(',', $value);
        foreach ($allowed as $uid) {
            if (isset($users[$uid])) {
                $user = $users[$uid];
                $result[$user->id] = $user;
            }
        }

        return $result;
    }

    function get_users_from_usercapabilits($userid) {
        global $DB;
        $context = $DB->get_record("context",array("instanceid"=>$userid,"contextlevel"=>CONTEXT_USER));
        return get_users_by_capability(context::instance_by_id($context->id), 'enrol/apply:manageapplications');  
    }

    /**
     * Returns users who should be notified about new applications in SYSTEM context.
     * @return array Array of user IDs.
     */
    public function get_notifyglobal_users() {
        return get_users_from_config($this->get_config('notifyglobal'), 'enrol/apply:manageapplications', false);
    }

    private function update_mail_content($content, $course, $user, $userenrolment) {
        $replace = array(
            'firstname' => $user->firstname,
            'content'   => format_string($course->fullname),
            'lastname'  => $user->lastname,
            'username'  => $user->username,
            'timeend'   => !empty($userenrolment->timeend) ? userdate($userenrolment->timeend) : ''
        );
        foreach ($replace as $key => $val) {
            $content = str_replace('{' . $key . '}', $val, $content);
        }
        return $content;
    }

    /**
     * Backup execution step hook.
     *
     * @param backup_enrolments_execution_step $step
     * @param stdClass $enrol
     */
    public function backup_annotate_custom_fields(backup_enrolments_execution_step $step, stdClass $enrol) {
        // annotate customint1 as a role
        $step->annotate_id('role', $enrol->customint1);
    }

    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB, $CFG;

        $data->customint1 = $step->get_mappingid('role', $data->customint1, null);

        $instanceid = $this->add_instance($course, (array)$data);
        $step->set_mapping('enrol', $oldid, $instanceid);

        //$this->sync_enrols($DB->get_record('enrol', array('id'=>$instanceid)));
    }

    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $oldinstancestatus);
    }

    /**
     * Returns the user who is responsible for self enrolments in given instance.
     *
     * Usually it is the first editing teacher - the person with "highest authority"
     * as defined by sort_by_roleassignment_authority() having 'enrol/self:manage'
     * capability.
     *
     * @param int $instanceid enrolment instance id
     * @return stdClass user record
     */
    protected function get_enroller($instanceid) {
        global $DB;

        if ($this->lasternollerinstanceid == $instanceid and $this->lasternoller) {
            return $this->lasternoller;
        }

        $instance = $DB->get_record('enrol', array('id' => $instanceid, 'enrol' => $this->get_name()), '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);

        if ($users = get_enrolled_users($context, 'enrol/apply:manage')) {
            $users = sort_by_roleassignment_authority($users, $context);
            $this->lasternoller = reset($users);
            unset($users);
        } else {
            $this->lasternoller = parent::get_enroller($instanceid);
        }

        $this->lasternollerinstanceid = $instanceid;

        return $this->lasternoller;
    }

    /**
     * Enrol cron support.
     * @return void
     */
    public function cron() {
        $trace = new text_progress_trace();
        $this->process_expirations($trace);
    }

    /**
     * Add an "Approvals" link under course More menu if enrol_apply is enabled in the course.
     *
     * @param navigation_node $navigation Course navigation node (Moodle will place under More in 4.x).
     * @param stdClass        $course
     * @param context_course  $context
     */
	function enrol_apply_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context) {
		 // Only users with permission should see the link.
		 if (!has_capability('enrol/apply:manageapplications', $context)) {
			 return;
		 }
		 
		// Only show if this course actually uses enrol_apply.
		$instances = enrol_get_instances($course->id, true);
		$applyinstanceid = null;
			foreach ($instances as $inst) {
				if ($inst->enrol === 'apply') { $applyinstanceid = $inst->id; break; }
			}
			if (!$applyinstanceid) { return; }
		$url = new moodle_url('/enrol/apply/manage.php', ['id' => $applyinstanceid]);
		
		// Add node under course settings (appears in the "More" menu in Boost-based themes).
		$navigation->add(
		get_string('approvals', 'enrol_apply'),
		$url,
		navigation_node::TYPE_SETTING,
		null,
		'enrol_apply_manage'
			);
	}
}
