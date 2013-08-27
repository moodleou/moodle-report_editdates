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

class report_editdates_mod_oucollaboratedates_date_extractor
            extends report_editdates_mod_date_extractor {

    public function __construct($course) {
        parent::__construct($course, 'oucollaborate');
        parent::load_data();
    }

    public function get_settings(cm_info $cm) {
        $oucollaborate = $this->mods[$cm->instance];
        return array('timestart' => new report_editdates_date_setting(
                            get_string('sessionbegins', 'oucollaborate'),
                            $oucollaborate->timestart,
                            self::DATETIME, false, 15),

                      'timeend' => new report_editdates_date_setting(
                            get_string('sessionends', 'oucollaborate'),
                            $oucollaborate->timeend,
                            self::DATETIME, false, 15)
        );
    }

    public function validate_dates(cm_info $cm, array $dates) {
        $oucollaborate = $this->mods[$cm->instance];
        $coursecontext = context_course::instance($this->course->id);

        $errors = array();
        if ($dates['timeend'] < $dates['timestart']) {
            $errors['timeend'] = get_string('timeclose', 'report_editdates');
        }

        if ($oucollaborate->timestart != $dates['timestart'] && $dates['timestart'] < time() &&
                !has_capability('mod/oucollaborate:modifysessionstart', $coursecontext)) {
            $errors['timestart'] = get_string('starttimemodified', 'ouelluminate');
        }

        return $errors;
    }
}
