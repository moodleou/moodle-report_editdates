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

require_once($CFG->dirroot.'/mod/assignment/lib.php');

/**
 * Simple class capturing the information needed to check
 * date settings for the assignments module
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_editdates_mod_assignment_date_extractor
extends report_editdates_mod_date_extractor {

    /**
     * Constructor for the assignment date extractor.
     *
     * Initializes the date extractor for assignment modules by invoking
     * the parent constructor with the course and 'assignment' as the module type.
     * Additionally, it loads necessary data related to the assignments.
     *
     * @param stdClass $course The course object.
     */
    public function __construct($course) {
        parent::__construct($course, 'assignment');
        parent::load_data();
    }

    /**
     * Get a list of date settings for the assignment module instance.
     *
     * @param cm_info $cm The course module information.
     * @return array An array where the keys are setting names ('timeavailable', 'timedue')
     *               and the values are report_editdates_date_setting objects containing
     *               the date settings for the assignment.
     */
    public function get_settings(cm_info $cm) {
        $ass = $this->mods[$cm->instance];
        // Availability and due date settings for an assignment.
        return [
            'timeavailable' => new report_editdates_date_setting(
                    get_string('availabledate', 'assignment'),
                    $ass->timeavailable, self::DATETIME, true),
            'timedue' => new report_editdates_date_setting(
                    get_string('duedate', 'assignment'),
                    $ass->timedue, self::DATETIME, true),
        ];
    }

    /**
     * Validate the submitted dates for this course_module instance.
     *
     * @param cm_info $cm the activity to validate the dates for.
     * @param array $dates an array with array keys matching those
     * returned by get_settings(), and the new
     * dates as values.
     * @return array Any validation errors. The array keys need to
     * match the keys returned by get_settings().
     * Return an empty array if there are no erros.
     */
    public function validate_dates(cm_info $cm, array $dates) {
        $errors = [];
        if ($dates['timeavailable'] != 0 && $dates['timedue'] != 0 && $dates['timedue'] < $dates['timeavailable']) {
            $errors['timedue'] = get_string('timedue', 'report_editdates');
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

        // Fetch module instance from $mods array.
        $assignment = $this->mods[$cm->instance];

        $assignment->instance = $cm->instance;
        $assignment->coursemodule = $cm->id;
        $assignment->cmidnumber = $cm->id;

        // Updating date values.
        foreach ($dates as $datetype => $datevalue) {
            $assignment->$datetype = $datevalue;
        }

        // Method name to udpate the instance and associated events.
        $methodname = $cm->modname.'_update_instance';
        // Calling the method.
        $methodname($assignment);
    }
}
