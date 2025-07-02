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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/questionnaire/lib.php');

/**
 * Simple class capturing the information needed to check
 * date settings for the questionnaire module
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_editdates_mod_questionnaire_date_extractor
        extends report_editdates_mod_date_extractor {

    /**
     * Constructor for the questionnaire date extractor.
     *
     * Initializes the date extractor for questionnaire modules by invoking
     * the parent constructor with the course and 'questionnaire' as the module type.
     * Additionally, it loads necessary data related to the questionnaires.
     *
     * @param stdClass $course The course object.
     */
    public function __construct($course) {
        parent::__construct($course, 'questionnaire');
        parent::load_data();
    }

    /**
     * Get a list of date settings for the questionnaire module instance.
     *
     * @param cm_info $cm The course module information.
     * @return array An array where the keys are setting names ('timeavailable', 'timedue')
     *               and the values are report_editdates_date_setting objects containing
     *               the date settings for the questionnaire.
     */
    public function get_settings(cm_info $cm) {
        $mod = $this->mods[$cm->instance];
        return [
            'opendate' => new report_editdates_date_setting(
                                        get_string('opendate', 'questionnaire'),
                                        $mod->opendate, self::DATETIME, true),
            'closedate' => new report_editdates_date_setting(
                                        get_string('closedate', 'questionnaire'),
                                        $mod->closedate, self::DATETIME, true),
        ];
    }
    /**
     * Validates the submitted dates for an questionnaire activity.
     *
     * This function takes in the course module information and an associative array of date
     * settings and returns an associative array of validation errors. The keys of the returned
     * array are the same as the keys of the input array, and the values are error strings.
     *
     * @param cm_info $cm the course module information.
     * @param array $dates an associative array of date settings for the questionnaire module.
     * @return array an associative array of validation errors.
     */
    public function validate_dates(cm_info $cm, array $dates) {
        $errors = [];
        if ($dates['opendate'] != 0 && $dates['closedate'] != 0
                && $dates['closedate'] < $dates['opendate']) {
            $errors['closedate'] = get_string('closedate', 'report_editdates');
        }
        return $errors;
    }

    /**
     * Save the new dates for an questionnaire activity.
     *
     * This method updates the questionnaire instance with the new date values provided,
     * and triggers the necessary calendar event updates and gradebook updates.
     *
     * @param cm_info $cm The course module information.
     * @param array $dates An associative array where keys are date type strings
     *                     and values are the new date values to be saved.
     */
    public function save_dates(cm_info $cm, array $dates) {
        global $DB, $COURSE;

        // Fetch module instance from $mods array.
        $questionnaire = $this->mods[$cm->instance];
        $questionnaire->instance = $cm->instance;
        $questionnaire->cmidnumber = $cm->id;

        // Updating date values.
        foreach ($dates as $datetype => $datevalue) {
            $questionnaire->$datetype = $datevalue;
            if ($datevalue != 0) {
                $property = 'use'.$datetype;
                $questionnaire->$property = 1;
            }
        }

        // Method name to udpate the instance and associated events.
        $methodname = $cm->modname.'_update_instance';
        // Calling the method.
        $methodname($questionnaire);
    }
}
