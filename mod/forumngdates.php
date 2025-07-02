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
 * date settings for the forumng module
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_editdates_mod_forumng_date_extractor
        extends report_editdates_mod_date_extractor {

    /**
     * Constructor for the forumng date extractor.
     *
     * Initializes the date extractor for forumng modules by invoking
     * the parent constructor with the course and 'forumng' as the module type.
     * Additionally, it loads necessary data related to the forumngs.
     *
     * @param stdClass $course The course object.
     */
    public function __construct($course) {
        parent::__construct($course, 'forumng');
        parent::load_data();
    }

    /**
     * Get a list of date settings for the forumng module instance.
     *
     * @param cm_info $cm The course module information.
     * @return array An array where the keys are setting names ('timeavailable', 'timedue')
     *               and the values are report_editdates_date_setting objects containing
     *               the date settings for the forumng.
     */
    public function get_settings(cm_info $cm) {
        $forumng = $this->mods[$cm->instance];
        $forumngdatesettings = [];

        if ($forumng->ratingscale != 0) {
            $forumngdatesettings['ratingfrom'] = new report_editdates_date_setting(
                                                    get_string('ratingfrom', 'forumng'),
                                                    $forumng->ratingfrom,
                                                    self::DATETIME, true);
            $forumngdatesettings['ratinguntil'] = new report_editdates_date_setting(
                                                    get_string('ratinguntil', 'forumng'),
                                                    $forumng->ratinguntil,
                                                    self::DATETIME, true);
        }

        $forumngdatesettings['postingfrom'] = new report_editdates_date_setting(
                                                    get_string('postingfrom', 'forumng'),
                                                    $forumng->postingfrom,
                                                    self::DATETIME, true);
        $forumngdatesettings['postinguntil'] = new report_editdates_date_setting(
                                                    get_string('postinguntil', 'forumng'),
                                                    $forumng->postinguntil,
                                                    self::DATETIME, true);
        return $forumngdatesettings;
    }
    /**
     * Validates the submitted dates for an forumng activity.
     *
     * This function takes in the course module information and an associative array of date
     * settings and returns an associative array of validation errors. The keys of the returned
     * array are the same as the keys of the input array, and the values are error strings.
     *
     * @param cm_info $cm the course module information.
     * @param array $dates an associative array of date settings for the forumng module.
     * @return array an associative array of validation errors.
     */
    public function validate_dates(cm_info $cm, array $dates) {
        $errors = [];
        if (isset($dates['ratingfrom']) && isset($dates['ratinguntil'])
                && $dates['ratingfrom'] != 0 && $dates['ratinguntil'] != 0
                && $dates['ratinguntil'] < $dates['ratingfrom']) {

            $errors['ratinguntil'] = get_string('timeuntil', 'report_editdates');
        }
        if ($dates['postingfrom'] != 0 && $dates['postinguntil'] != 0
                && $dates['postinguntil'] < $dates['postingfrom']) {
            $errors['postinguntil'] = get_string('timeuntil', 'report_editdates');
        }
        return $errors;
    }
}
