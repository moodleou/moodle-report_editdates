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

require_once($CFG->dirroot.'/mod/zoom/locallib.php');

/**
 * Simple class capturing the information needed to check
 * date settings for the zoom module
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_editdates_mod_zoom_date_extractor
        extends report_editdates_mod_date_extractor {

    /**
     * Constructor for the zoom date extractor.
     *
     * Initializes the date extractor for zoom modules by invoking
     * the parent constructor with the course and 'zoom' as the module type.
     * Additionally, it loads necessary data related to the zooms.
     *
     * @param stdClass $course The course object.
     */
    public function __construct($course) {
        parent::__construct($course, 'zoom');
        parent::load_data();
    }

    /**
     * Get a list of date settings for the zoom module instance.
     *
     * @param cm_info $cm The course module information.
     * @return array An array where the keys are setting names ('timeavailable', 'timedue')
     *               and the values are report_editdates_date_setting objects containing
     *               the date settings for the zoom.
     */
    public function get_settings(cm_info $cm) {
        $zoom = $this->mods[$cm->instance];
        if (!empty($zoom->recurring)) {
            return [];
        } else {
            // Underscores currently don't behave well with this report, so we'll omit them.
            return [
                'starttime' => new report_editdates_date_setting(
                        get_string('meeting_time', 'zoom'),
                        $zoom->start_time, self::DATETIME, false),
            ];
        }
    }
    /**
     * Validates the submitted dates for an zoom activity.
     *
     * This function takes in the course module information and an associative array of date
     * settings and returns an associative array of validation errors. The keys of the returned
     * array are the same as the keys of the input array, and the values are error strings.
     *
     * @param cm_info $cm the course module information.
     * @param array $dates an associative array of date settings for the zoom module.
     * @return array an associative array of validation errors.
     */
    public function validate_dates(cm_info $cm, array $dates) {
        $errors = [];
        $zoom = $this->mods[$cm->instance];

        if (empty($zoom->recurring)) {
            // Only report a validation error if the user actually changed the time.
            if ($dates['starttime'] != $zoom->start_time
                    && $dates['starttime'] < strtotime('today')) {
                $errors['starttime'] = get_string('err_start_time_past', 'zoom');
            }
        }

        return $errors;
    }

    /**
     * Save the new dates for an zoom activity.
     *
     * This method updates the zoom instance with the new date values provided,
     * and triggers the necessary calendar event updates and gradebook updates.
     *
     * @param cm_info $cm The course module information.
     * @param array $dates An associative array where keys are date type strings
     *                     and values are the new date values to be saved.
     */
    public function save_dates(cm_info $cm, array $dates) {
        // Fetch module instance from $mods array.
        $zoom = $this->mods[$cm->instance];
        $zoom->instance = $cm->instance;
        $zoom->cmidnumber = $cm->id;

        // Updating date values.
        $zoom->start_time = $dates['starttime'];

        // Calling the method.
        zoom_update_instance($zoom);
    }
}
