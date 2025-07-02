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

// This is an example of how to make a block date extractor.
// If we ever need to edit dates in block, use this as a template.

/**
 * Example of how you would extract date settings from a block.
 *
 * @package   report_editdates
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Example {report_editdates_block_date_extractor} subclass.
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_editdates_block_html_date_extractor  extends report_editdates_block_date_extractor {

    /**
     * Constructor.
     * @param object $course course settings from the DB.
     */
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
        parent::__construct($course, 'html');
        parent::load_data();
    }

    /**
     * Return an array of settings for the dates that we handle.
     *
     * This function takes in the course module information and returns an associative array
     * of date settings for the module. The keys of the returned array are the string names
     * of the settings, and the values are objects of the report_editdates_date_setting class.
     *
     * @param block_base $block the block to get the settings for.
     * @return array an associative array of date settings for the block.
     */
    public function get_settings(block_base $block) {
        // Check if title text is a valid date then return the array.
        $title = $block->title;
        if ((string) (int) $title === $title) {
                return [
                    'title' => new report_editdates_date_setting(get_string('availabledate', 'assignment'),
                                                                 $block->title,
                                                                 self::DATETIME, false, 5),
                ];
        }
    }

    /**
     * Validate the submitted dates for this course_module instance.
     *
     * This function takes in the course module information and an associative array of date
     * settings and returns an associative array of validation errors. The keys of the returned
     * array are the same as the keys of the input array, and the values are error strings.
     *
     * @param block_base $block the block to validate the dates for.
     * @param array $dates an associative array of date settings for the block.
     * @return array an associative array of validation errors.
     */
    public function validate_dates(block_base $block, array $dates) {
        $errors = [];
        if ($dates['title'] == 0 ) {
            $errors['title'] = get_string('datemustnotzero', 'report_editdates');
        }
        return $errors;
    }
}
