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

require_once($CFG->dirroot.'/mod/assignment/lib.php');

class report_editdates_mod_assignment_date_extractor
extends report_editdates_mod_date_extractor {

    //constructor
    public function __construct($course) {
        parent::__construct($course, 'assignment');
        parent::load_data();
    }

    //overriden abstract method
    public function get_settings(cm_info $cm) {
        $ass = $this->mods[$cm->instance];
        //availability and due date settings for an assignment
        return array('timeavailable' => new report_editdates_date_setting(
        get_string('availabledate', 'assignment'),
        $ass->timeavailable,
        self::DATETIME, true, 5),

                      'timedue' => new report_editdates_date_setting(
        get_string('duedate', 'assignment'),
        $ass->timedue,
        self::DATETIME, true, 5)
        );
    }

    //overriden abstract method
    public function validate_dates(cm_info $cm, array $dates) {
        $errors = array();
        if ($dates['timeavailable'] != 0 && $dates['timedue'] != 0
        && $dates['timedue'] < $dates['timeavailable']) {
            $errors['timedue'] = get_string('timedue', 'report_editdates');
        }
        return $errors;
    }

    //overriden abstract method
    public function save_dates(cm_info $cm, array $dates) {
        global $DB, $COURSE;

        //fetch module instance from $mods array
        $assignment = $this->mods[$cm->instance];

        $assignment->instance = $cm->instance;
        $assignment->coursemodule = $cm->id;
        $assignment->cmidnumber = $cm->id;

        //updating date values
        foreach ($dates as $datetype => $datevalue) {
            $assignment->$datetype = $datevalue;
        }

        //method name to udpate the instance and associated events
        $methodname = $cm->modname.'_update_instance';
        //calling the method
        $methodname($assignment);
    }
}
