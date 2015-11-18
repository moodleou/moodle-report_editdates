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

class report_editdates_mod_ouelluminate_date_extractor
            extends report_editdates_mod_date_extractor {

    public function __construct($course) {
        parent::__construct($course, 'ouelluminate');
        parent::load_data();
    }

    public function get_settings(cm_info $cm) {
        $ouelluminate = $this->mods[$cm->instance];
        return array('timestart' => new report_editdates_date_setting(
                            get_string('meetingbegins', 'ouelluminate'),
                            $ouelluminate->timestart,
                            self::DATETIME, false, 15),

                      'timeend' => new report_editdates_date_setting(
                            get_string('meetingends', 'ouelluminate'),
                            $ouelluminate->timeend,
                            self::DATETIME, false, 15)
        );
    }

    public function validate_dates(cm_info $cm, array $dates) {
        $errors = array();
        if (!empty($dates['timestart']) && !empty($dates['timeend']) &&
                            $dates['timeend'] < $dates['timestart']) {
            $errors['timeend'] = get_string('timeclose', 'report_editdates');
        }
        return $errors;
    }
}
