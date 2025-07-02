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

require_once($CFG->dirroot.'/mod/assign/locallib.php');

/**
 * Simple class capturing the information needed to check
 * date settings for the assign module
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_editdates_mod_assign_date_extractor
    extends report_editdates_mod_date_extractor {

    /**
     * Constructor for the assignment date extractor.
     *
     * Initializes the date extractor for assignment modules by calling the parent constructor
     * with the course and 'assign' as the module type. Additionally, it loads necessary data.
     *
     * @param stdClass $course The course object.
     */
    public function __construct($course) {
        parent::__construct($course, 'assign');
        parent::load_data();
    }

    /**
     * Retrieves the date settings for an assignment activity.
     *
     * This function returns an array of date settings specific to an assignment module.
     * The settings include allowsubmissionsfromdate, duedate, cutoffdate, and gradingduedate.
     * Each setting is represented by a report_editdates_date_setting object, which includes
     * localization strings and date values.
     *
     * @param cm_info $cm The course module information.
     * @return array An associative array of date settings for the assignment module.
     */
    public function get_settings(cm_info $cm) {
        $assign = $this->mods[$cm->instance];

        return [
            'allowsubmissionsfromdate' => new report_editdates_date_setting(
                    get_string('allowsubmissionsfromdate', 'assign'),
                    $assign->allowsubmissionsfromdate,
                    self::DATETIME, true),
            'duedate' => new report_editdates_date_setting(
                    get_string('duedate', 'assign'),
                    $assign->duedate,
                    self::DATETIME, true),
            'cutoffdate' => new report_editdates_date_setting(
                    get_string('cutoffdate', 'assign'),
                    $assign->cutoffdate,
                    self::DATETIME, true),
            'gradingduedate' => new report_editdates_date_setting(
                    get_string('gradingduedate', 'assign'),
                    $assign->gradingduedate,
                    self::DATETIME, true),
        ];
    }

    /**
     * Validates the submitted dates for an assignment activity.
     *
     * This function takes in the course module information and an associative array of date
     * settings and returns an associative array of validation errors. The keys of the returned
     * array are the same as the keys of the input array, and the values are error strings.
     *
     * @param cm_info $cm the course module information.
     * @param array $dates an associative array of date settings for the assignment module.
     * @return array an associative array of validation errors.
     */
    public function validate_dates(cm_info $cm, array $dates) {
        $errors = [];
        if ($dates['allowsubmissionsfromdate'] && $dates['duedate']
                && $dates['duedate'] < $dates['allowsubmissionsfromdate']) {
            $errors['duedate'] = get_string('duedatevalidation', 'assign');
        }

        if ($dates['duedate'] && $dates['cutoffdate'] && $dates['duedate'] > $dates['cutoffdate']) {
            $errors['cutoffdate'] = get_string('cutoffdatevalidation', 'assign');
        }

        if ($dates['duedate'] && $dates['gradingduedate'] && $dates['duedate'] > $dates['gradingduedate']) {
            $errors['gradingduedate'] = get_string('gradingdueduedatevalidation', 'assign');
        }

        if ($dates['allowsubmissionsfromdate'] && $dates['gradingduedate'] &&
            $dates['allowsubmissionsfromdate'] > $dates['gradingduedate']) {
            $errors['gradingduedate'] = get_string('gradingduefromdatevalidation', 'assign');
        }
        return $errors;
    }

    /**
     * Save the new dates for an assignment activity.
     *
     * This method updates the assignment instance with the new date values provided,
     * and triggers the necessary calendar event updates and gradebook updates.
     *
     * @param cm_info $cm The course module information.
     * @param array $dates An associative array where keys are date type strings
     *                     and values are the new date values to be saved.
     */
    public function save_dates(cm_info $cm, array $dates) {
        global $DB, $COURSE;

        $update = new stdClass();
        $update->id = $cm->instance;
        $update->duedate = $dates['duedate'];
        $update->allowsubmissionsfromdate = $dates['allowsubmissionsfromdate'];
        $update->cutoffdate = $dates['cutoffdate'];
        $update->gradingduedate = $dates['gradingduedate'];

        $result = $DB->update_record('assign', $update);

        $module = new assign(context_module::instance($cm->id), null, null);

        // Update the calendar and grades.
        $module->update_calendar($cm->id);

        $module->update_gradebook(false, $cm->id);
    }
}
