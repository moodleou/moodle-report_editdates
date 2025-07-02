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
 * date settings for the chat module
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_editdates_mod_chat_date_extractor
                extends report_editdates_mod_date_extractor {

    /**
     * Constructor for the chat date extractor.
     *
     * Initializes the date extractor for chat modules by invoking
     * the parent constructor with the course and 'chat' as the module type.
     * Additionally, it loads necessary data related to the chats.
     *
     * @param stdClass $course The course object.
     */
    public function __construct($course) {
        parent::__construct($course, 'chat');
        parent::load_data();
    }

    /**
     * Get a list of date settings for the chat module instance.
     *
     * @param cm_info $cm The course module information.
     * @return array An array where the keys are setting names ('timeavailable', 'timedue')
     *               and the values are report_editdates_date_setting objects containing
     *               the date settings for the chat.
     */
    public function get_settings(cm_info $cm) {
        $chat = $this->mods[$cm->instance];
        return [
            'chattime' => new report_editdates_date_setting(
                                    get_string('chattime', 'chat'),
                                    $chat->chattime,
                                    self::DATETIME, false),
        ];
    }

    /**
     * Validates the submitted dates for an chat activity.
     *
     * This function takes in the course module information and an associative array of date
     * settings and returns an associative array of validation errors. The keys of the returned
     * array are the same as the keys of the input array, and the values are error strings.
     *
     * @param cm_info $cm the course module information.
     * @param array $dates an associative array of date settings for the chat module.
     * @return array an associative array of validation errors.
     */
    public function validate_dates(cm_info $cm, array $dates) {
        $errors = [];
        return $errors;
    }

    /**
     * Save the new dates for an chat activity.
     *
     * This method updates the chat instance with the new date values provided,
     * and triggers the necessary calendar event updates and gradebook updates.
     *
     * @param cm_info $cm The course module information.
     * @param array $dates An associative array where keys are date type strings
     *                     and values are the new date values to be saved.
     */
    public function save_dates(cm_info $cm, array $dates) {

        // Fetch module instance from $mods array.
        $chat = $this->mods[$cm->instance];

        $chat->instance = $cm->instance;
        $chat->coursemodule = $cm->id;

        // Updating date values.
        foreach ($dates as $datetype => $datevalue) {
            $chat->$datetype = $datevalue;
        }

        // Method name to update the instance and associated events.
        chat_update_instance($chat);
    }
}
