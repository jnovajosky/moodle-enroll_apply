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

        // Required routing fields.
        $this->component    = 'enrol_apply';   // Must match db/messages.php
        $this->notification = 1;               // Treat as a notification

        // Sender / recipient.
        $this->userfrom = $from;
        $this->userto   = $to;

        // Message bodies.
        $this->subject           = $subject;
        $this->fullmessage       = html_to_text($content, 0, false);
        $this->fullmessageformat = FORMAT_PLAIN;
        $this->fullmessagehtml   = $content;

        // Click target for popup (must be a string URL).
        $this->contexturl     = is_object($url) ? $url->out(false) : (string)$url;
        $this->contexturlname = get_string('manage_enrol_requests', 'enrol_apply');

        // Course context (0 is fine if you intentionally unset to avoid deferral).
        $this->courseid = (int)$courseid;

        // Prepare course name for popup text (after courseid is set).
        $coursename = '';
        if (!empty($this->courseid)) {
            $course = get_course($this->courseid);
            $coursename = format_string($course->fullname);
        }

        // Provider name + smallmessage per type.
        switch ($type) {
            case 'application':
                $this->name = 'application'; // Provider key defined in db/messages.php
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
