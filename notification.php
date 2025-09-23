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
 * @copyright  2016 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Johannes Burk <johannes.burk@sudile.com>
 * @editor     Josh Novajosky <jnovajosky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

class enrol_apply_notification extends \core\message\message {
    public function __construct($to, $from, $type, $subject, $content, $url, $courseid) {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $this->component = 'enrol_apply';
        $this->userfrom  = $from;
        $this->userto    = $to;

        $this->subject           = $subject;
        $this->fullmessage       = html_to_text($content, 0, false);
        $this->fullmessageformat = FORMAT_PLAIN;
        $this->fullmessagehtml   = $content;

        // Use a proper URL string for contexturl (popup click target).
        $this->contexturl     = is_object($url) ? $url->out(false) : (string)$url;
        $this->contexturlname = get_string('manage_enrol_requests', 'enrol_apply');

        $this->courseid     = (int)$courseid;
        $this->notification = 1; // Mark as notification.

        // Prepare a course name for smallmessage placeholders.
        $coursename = '';
        if (!empty($this->courseid)) {
            $course = get_course($this->courseid);
            $coursename = format_string($course->fullname);
        }

        switch ($type) {
            case 'application':
                // Provider key used in db/messages.php.
                $this->name = 'application';
                // Popup text with course name and clear CTA.
                $this->smallmessage = get_string('notify_pending_popup', 'enrol_apply',
                    (object)['coursename' => $coursename]);
                break;

            case 'confirmation':
                $this->name = 'confirmation';
                $this->smallmessage = get_string('applicationconfirmednotification', 'enrol_apply');
                break;

            case 'cancelation':
                $this->name = 'cancelation';
                $this->smallmessage = get_string('applicationcancelednotification', 'enrol_apply');
                break;

            case 'waitinglist':
                $this->name = 'waitinglist';
                $this->smallmessage = get_string('applicationdeferrednotification', 'enrol_apply');
                break;

            default:
                throw new invalid_parameter_exception('Invalid enrol_apply notification type.');
        }
    }
}
