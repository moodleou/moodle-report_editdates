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
 * Display date setting report for a course
 *
 * @package   report_editdates
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/form.php');

$id = required_param('id', PARAM_INT);       // course id
$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

// needed to setup proper $COURSE
require_login($course);

//setting page url
$PAGE->set_url('/report/editdates/index.php', array('id'=>$id));
//setting page layout to report
$PAGE->set_pagelayout('report');

//coursecontext instance
$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

//checking if user is capable of viewing this report in $coursecontext
require_capability('report/editdates:view', $coursecontext);

//initializing array to contain modules in a course.
$modinfo = array();

//fetching all modules in the course
$modinfo = get_fast_modinfo($course);

//creating form instance, passed course id as parameter to action url
$mform = new report_editdates_form(new moodle_url('/report/editdates/index.php',
array('id' => $id)), array('modinfo' => $modinfo, 'course' => $course));
//create the return url after form processing
$returnurl = new moodle_url('/course/view.php', array('id' => $id));

if ($mform->is_cancelled()) {            //check if form is cancelled
    //redirect to course view page if form is cancelled
    redirect($returnurl);
} else if ($data = $mform->get_data()) {        //check if form is submitted
    //process if data submitted
    //arrays to store date settings of each type
    $moddatesettings = array();        //store module date settings
    $blockdatesettings = array();    //store block date settings
    $forceddatesettings = array();    //store forced date settings for each module(if enabled)
    foreach ($data as $key => $value) {
        if ($key == "coursestartdate") {        //check if element is course start date
            $course->startdate = $value;
        } else {        //its module, need to extract date settings for each module
            $cmsettings = explode('_', $key);
            //array should have 4 keys
            if (count($cmsettings) == 4) {
                //ignore 0th position, it will be 'date'
                //1st position should be the mod type
                //2nd will be the id of module
                //3rd will be property of module
                //ensure that the name is proper
                if (isset($cmsettings['1']) && isset($cmsettings['2']) && isset($cmsettings['3'])) {
                    //check if its mod date settings
                    if ($cmsettings['1'] == 'mod') {
                        //module context
                        $modcontext = get_context_instance(CONTEXT_MODULE, $cmsettings['2']);
                        //user should be capable of updating individual module
                        if (has_capability('moodle/course:manageactivities', $modcontext)) {
                            /*
                             * check if config date settings are forced
                             * and this is one of the forced date setting
                             */
                            if ( ($CFG->enablecompletion || $CFG->enableavailability)
                            && ($cmsettings['3'] == "completionexpected"
                            || $cmsettings['3'] == "availablefrom"
                            || $cmsettings['3'] == "availableuntil") ) {
                                $forceddatesettings[$cmsettings['2']][$cmsettings['3']]=$value;
                            } else {
                                //its module date setting
                                $moddatesettings[$cmsettings['2']][$cmsettings['3']] = $value;
                            }
                        }
                    } else if ($cmsettings['1'] == 'block') {    //check if its block date setting
                        //if user is capable of updating blocks in course context
                        if (has_capability('moodle/site:manageblocks', $coursecontext)) {
                            $blockdatesettings[$cmsettings['2']][$cmsettings['3']] = $value;
                        }
                    }
                }
            }
        }
    }        //end of for loop
    //start transaction
    $transaction = $DB->start_delegated_transaction();
    //allow to update only if user is capable
    if (has_capability('moodle/course:update', $coursecontext)) {
        //update course start date
        $DB->set_field('course', 'startdate', $course->startdate, array('id' => $course->id));
    }

    //updating forced date settings
    foreach ($forceddatesettings as $modid => $datesettings) {
        $cm = new stdClass();
        $cm->id = $modid;
        foreach ($datesettings as $datetype => $value) {
            $cm->$datetype = $value;
        }
        //update object in course_modules class
        $DB->update_record('course_modules', $cm, true);
    }
    //updating mod date settings
    foreach ($moddatesettings as $modid => $datesettings) {
        //course module object
        $cm = $modinfo->cms[$modid];
        //get instance of module date extractor class, if exists
        $mod = report_editdates_mod_date_extractor::make($cm->modname, $course);
        if ($mod) {
            $mod->save_dates($cm, $datesettings);
        }
    }

    /*
     * fetching all the blocks added directly under the course
     * i.e parentcontextid = coursecontextid
     */
    $courseblocks = $DB->get_records("block_instances",
    array('parentcontextid' => $coursecontext->id));

    //updating block date settings
    foreach ($blockdatesettings as $blockid => $datesettings) {
        $block = $courseblocks[$blockid];

        $blockobj = block_instance($block->blockname, $block, $PAGE);

        if ($blockobj->user_can_edit()) {

            $blockdatextrator =
            report_editdates_block_date_extractor::make($block->blockname, $course);
            if ($blockdatextrator) {
                $blockdatextrator->save_dates($blockobj, $datesettings);
            }
        }
    }
    //commit transaction
    $transaction->allow_commit();
    //rebuild course cache after updating data in database
    rebuild_course_cache($course->id);
    //redirect to course view page after updating DB
    redirect($returnurl);
}
//making log entry
add_to_log($course->id, 'course', 'report edit dates', "report/editdates/index.php?id=$course->id",
$course->id);

//setting page title and page heading
$PAGE->set_title($course->shortname .': '. get_string('editdates' , 'report_editdates'));
$PAGE->set_heading($course->fullname);

//Displaying header and heading
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->fullname));

//display form
$mform->display();
//display page footer
echo $OUTPUT->footer();
