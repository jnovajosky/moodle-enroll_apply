# Description
A fork of the Emeneo Plugin - Enrolment upon Approval

The enrolment plugin "enrol on approval" add an approval step into the course enrolment process.
Users will be informed by mail as soon as their course application has been approved/rejected.

* Moodle Forum: https://moodle.org/mod/forum/discuss.php?d=189334
* Moodle Plugins Directory: https://moodle.org/plugins/view.php?plugin=enrol_apply

* New to this 4.5 Beta fork - added a toggle in the course settings that allows for notifications to be sent out even if the course has not started yet.
* Update to 4.5.1 Beta - added course navigation menu for easy access to pending approvals that can be accessed if user has the right permissions

# Installation
## Install with git
* use a command line interface of your choice on the destination system (server with moodle installation)
* switch to the moodle enrol folder: cd /path/to/moodle/enrol/
* git clone https://github.com/emeneo/apply.git
* navigate on your moodle page to admin --> notifivations and follow the instructions

## Install from zip
* download zip file from github: [https://github.com/jnovajosky/moodle-enrol_apply](https://github.com/jnovajosky/moodle-enrol_apply)
* unpack zip file to /path/to/moodle/enrol/
* navigate on your moodle page to admin --> notifivations and follow the instructions
