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
 * This is form to display the modules for editdates reports
 *
 * @package   report_editdates
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Simple class capturing the information needed to check
 * date settings for the external module
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_editdates_mod_externalquiz_date_extractor
        extends report_editdates_mod_date_extractor {

    /**
     * Constructor for the external date extractor.
     *
     * Initializes the date extractor for external modules by invoking
     * the parent constructor with the course and 'external' as the module type.
     * Additionally, it loads necessary data related to the externals.
     *
     * @param stdClass $course The course object.
     */
    public function __construct($course) {
        parent::__construct($course, 'externalquiz');
        parent::load_data();
    }

    /**
     * Get a list of date settings for the external module instance.
     *
     * @param cm_info $cm The course module information.
     * @return array An array where the keys are setting names ('timeavailable', 'timedue')
     *               and the values are report_editdates_date_setting objects containing
     *               the date settings for the external.
     */
    public function get_settings(cm_info $cm) {
        $extquiz = $this->mods[$cm->instance];
        return [
            'timeopen' => new report_editdates_date_setting(
                                    get_string('quizopen', 'externalquiz'),
                                    $extquiz->timeopen,
                                    self::DATETIME, true),

            'timeclose' => new report_editdates_date_setting(
                                    get_string('quizclose', 'externalquiz'),
                                    $extquiz->timeclose,
                                    self::DATETIME, true),
        ];
    }
    /**
     * Validates the submitted dates for an external activity.
     *
     * This function takes in the course module information and an associative array of date
     * settings and returns an associative array of validation errors. The keys of the returned
     * array are the same as the keys of the input array, and the values are error strings.
     *
     * @param cm_info $cm the course module information.
     * @param array $dates an associative array of date settings for the external module.
     * @return array an associative array of validation errors.
     */
    public function validate_dates(cm_info $cm, array $dates) {
        $errors = [];
        if ($dates['timeopen'] != 0 && $dates['timeclose'] != 0 &&
                $dates['timeclose'] < $dates['timeopen']) {
            $errors['timeclose'] = get_string('timeclose', 'report_editdates');
        }
        return $errors;
    }
}
