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

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);

// Setup page.
$PAGE->set_url('/report/editdates/index.php', array('id'=>$id));
$PAGE->set_pagelayout('report');
$returnurl = new moodle_url('/course/view.php', array('id' => $id));

// Check permissions.
$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('report/editdates:view', $coursecontext);

// Fetching all modules in the course.
$modinfo = get_fast_modinfo($course);

// Creating the form.
$mform = new report_editdates_form(new moodle_url('/report/editdates/index.php',
        array('id' => $id)), array('modinfo' => $modinfo, 'course' => $course));

if ($mform->is_cancelled()) {
    redirect($returnurl);

} else if ($data = $mform->get_data()) {
    // Process submitted data.

    $moddatesettings = array();
    $blockdatesettings = array();
    $forceddatesettings = array();

    foreach ($data as $key => $value) {
        if ($key == "coursestartdate") {
            $course->startdate = $value;
        } else {
            // It is a module. Need to extract date settings for each module.
            $cmsettings = explode('_', $key);
            // The array should have 4 keys.
            if (count($cmsettings) == 4) {
                // Ignore 0th position, it will be 'date'
                // 1st position should be the mod type
                // 2nd will be the id of module
                // 3rd will be property of module
                // ensure that the name is proper.
                if (isset($cmsettings['1']) && isset($cmsettings['2']) && isset($cmsettings['3'])) {
                    // Check if its mod date settings.
                    if ($cmsettings['1'] == 'mod') {
                        // Module context.
                        $modcontext = get_context_instance(CONTEXT_MODULE, $cmsettings['2']);
                        // User should be capable of updating individual module.
                        if (has_capability('moodle/course:manageactivities', $modcontext)) {
                            // Check if config date settings are forced
                            // and this is one of the forced date setting.
                            if (($CFG->enablecompletion || $CFG->enableavailability)
                                    && ($cmsettings['3'] == "completionexpected"
                                    || $cmsettings['3'] == "availablefrom"
                                    || $cmsettings['3'] == "availableuntil") ) {
                                $forceddatesettings[$cmsettings['2']][$cmsettings['3']]=$value;
                            } else {
                                // Module date setting.
                                $moddatesettings[$cmsettings['2']][$cmsettings['3']] = $value;
                            }
                        }
                    } else if ($cmsettings['1'] == 'block') {
                        // If user is capable of updating blocks in course context.
                        if (has_capability('moodle/site:manageblocks', $coursecontext)) {
                            $blockdatesettings[$cmsettings['2']][$cmsettings['3']] = $value;
                        }
                    }
                }
            }
        }
    }

    // Start transaction.
    $transaction = $DB->start_delegated_transaction();
    // Allow to update only if user is capable.
    if (has_capability('moodle/course:update', $coursecontext)) {
        $DB->set_field('course', 'startdate', $course->startdate, array('id' => $course->id));
    }

    // Update forced date settings.
    foreach ($forceddatesettings as $modid => $datesettings) {
        $cm = new stdClass();
        $cm->id = $modid;
        foreach ($datesettings as $datetype => $value) {
            $cm->$datetype = $value;
        }
        // Update object in course_modules class.
        $DB->update_record('course_modules', $cm, true);
    }

    // Update mod date settings.
    foreach ($moddatesettings as $modid => $datesettings) {
        $cm = $modinfo->cms[$modid];
        $mod = report_editdates_mod_date_extractor::make($cm->modname, $course);
        if ($mod) {
            $mod->save_dates($cm, $datesettings);
        }
    }

    // Update block date settings.
    $courseblocks = $DB->get_records("block_instances",
            array('parentcontextid' => $coursecontext->id));
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

    // Commit transaction and finish up.
    $transaction->allow_commit();
    rebuild_course_cache($course->id);
    redirect($returnurl);
}

// Log.
add_to_log($course->id, 'course', 'report edit dates', "report/editdates/index.php?id=$course->id", $course->id);

// Finish setting up page.
$PAGE->set_title($course->shortname .': '. get_string('editdates' , 'report_editdates'));
$PAGE->set_heading($course->fullname);

// Displaying the form.
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->fullname));
$mform->display();
echo $OUTPUT->footer();
