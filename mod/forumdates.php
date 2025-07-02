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
 * date settings for the forum module
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_editdates_mod_forum_date_extractor
        extends report_editdates_mod_date_extractor {

    /**
     * Constructor for the forum date extractor.
     *
     * Initializes the date extractor for forum modules by invoking
     * the parent constructor with the course and 'forum' as the module type.
     * Additionally, it loads necessary data related to the forums.
     *
     * @param stdClass $course The course object.
     */
    public function __construct($course) {
        parent::__construct($course, 'forum');
        parent::load_data();
    }

    /**
     * Get a list of date settings for the forum module instance.
     *
     * @param cm_info $cm The course module information.
     * @return array An array where the keys are setting names ('timeavailable', 'timedue')
     *               and the values are report_editdates_date_setting objects containing
     *               the date settings for the forum.
     */
    public function get_settings(cm_info $cm) {
        $forum = $this->mods[$cm->instance];

        $fields = [];
        $fields['duedate'] = new report_editdates_date_setting(
                                           get_string('duedate', 'forum'),
                                           $forum->duedate,
                                           self::DATETIME, true);
        $fields['cutoffdate'] = new report_editdates_date_setting(
                                              get_string('cutoffdate', 'forum'),
                                              $forum->cutoffdate,
                                              self::DATETIME, true);
        if ($forum->assessed) {
            $fields['assesstimestart'] = new report_editdates_date_setting(
                                             get_string('assesstimefrom', 'report_editdates'),
                                             $forum->assesstimestart,
                                             self::DATETIME, true);
            $fields['assesstimefinish'] = new report_editdates_date_setting(
                                              get_string('assesstimeto', 'report_editdates'),
                                              $forum->assesstimefinish,
                                              self::DATETIME, true);
        }
        return $fields;
    }
    /**
     * Validates the submitted dates for an forum activity.
     *
     * This function takes in the course module information and an associative array of date
     * settings and returns an associative array of validation errors. The keys of the returned
     * array are the same as the keys of the input array, and the values are error strings.
     *
     * @param cm_info $cm the course module information.
     * @param array $dates an associative array of date settings for the forum module.
     * @return array an associative array of validation errors.
     */
    public function validate_dates(cm_info $cm, array $dates) {
        $errors = [];
        $forum = $this->mods[$cm->instance];
        if ($forum->assessed && $dates['assesstimestart'] != 0 && $dates['assesstimefinish'] != 0 &&
                $dates['assesstimefinish'] < $dates['assesstimestart']) {
            $errors['assesstimefinish'] = get_string('assesstimefinish', 'report_editdates');
        }

        if ($forum->assessed && $dates['assesstimestart'] == 0 && $dates['assesstimefinish'] != 0) {
            $errors['assesstimestart'] = get_string('dependentdate', 'report_editdates');
        }

        if ($forum->assessed && $dates['assesstimefinish'] == 0 && $dates['assesstimestart'] != 0) {
            $errors['assesstimefinish'] = get_string('dependentdate', 'report_editdates');
        }
        return $errors;
    }

    /**
     * Save the new dates for an forum activity.
     *
     * This method updates the forum instance with the new date values provided,
     * and triggers the necessary calendar event updates and gradebook updates.
     *
     * @param cm_info $cm The course module information.
     * @param array $dates An associative array where keys are date type strings
     *                     and values are the new date values to be saved.
     */
    public function save_dates(cm_info $cm, array $dates) {
        global $DB, $COURSE, $CFG;
        parent::save_dates($cm, $dates);

        require_once($CFG->dirroot.'/mod/forum/locallib.php');
        $forum = $DB->get_record('forum', ['id' => $cm->instance]);
        $forum->cmidnumber  = $cm->id;

        // Update the calendar and grades.
        forum_update_calendar($forum, $cm->id);
        forum_grade_item_update($forum);
    }
}
