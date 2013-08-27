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

require_once($CFG->libdir.'/formslib.php');
require_once(dirname(__FILE__) . '/lib.php');


/**
 * This is form to display the modules for editdates reports
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_editdates_form extends moodleform {
    /**
     * @see lib/moodleform#definition()
     */
    public function definition() {
        global $CFG, $COURSE, $DB, $PAGE;
        $mform = $this->_form;

        $modinfo       = $this->_customdata['modinfo'];
        $course        = $this->_customdata['course'];
        $activitytype  = $this->_customdata['activitytype'];

        $coursehasavailability = !empty($CFG->enableavailability);
        $coursehascompletion   = !empty($CFG->enablecompletion) && !empty($course->enablecompletion);

        // Context instance of the course.
        $coursecontext = context_course::instance($course->id);

        // Store current activity type.
        $mform->addElement('hidden', 'activitytype', $activitytype);
        $mform->setType('activitytype', PARAM_PLUGIN);

        // Add action button to the top of the form.
        $addactionbuttons = false;
        $this->add_action_buttons();

        // Course start date.
        $mform->addElement('header', 'coursestartdateheader', get_string('coursestartdateheader', 'report_editdates'));
        $mform->setExpanded('coursestartdateheader', false);
        $mform->addElement('date_selector', 'coursestartdate', get_string('startdate'));
        $mform->addHelpButton('coursestartdate', 'startdate');
        $mform->setDefault('coursestartdate', $course->startdate);

        // If user is not capable, make it read only.
        if (!has_capability('moodle/course:update', $coursecontext)) {
            $mform->hardFreeze('coursestartdate');
        } else {
            $addactionbuttons = true;
        }

        // Var to count the number of elements in the course/sections.
        // It will be used to decide whether to show save action button
        // at the bottom of the form page.
        $elementadded = 0;

        // Default -1 to display header for 0th section.
        $prevsectionnum = -1;

        // Cycle through all the sections in the course.
        $cms = $modinfo->get_cms();
        foreach ($modinfo->get_sections() as $sectionnum => $section) {
            $ismodadded = false;
            $sectionname = '';

            // Cycle through each module in a section.
            foreach ($section as $cmid) {
                $cm = $cms[$cmid];

                // No need to display/continue if this module is not visible to user.
                if (!$cm->uservisible) {
                    continue;
                }

                // If activity filter is on, then filter module by activity type.
                if ($activitytype && $cm->modname != $activitytype) {
                    continue;
                }

                // Check if the user has capability to edit this module settings.
                $modulecontext = context_module::instance($cm->id);
                $ismodreadonly = !has_capability('moodle/course:manageactivities', $modulecontext);

                // New section, create header.
                if ($prevsectionnum != $sectionnum) {
                    $sectionname = get_section_name($course, $modinfo->get_section_info($sectionnum));
                    $headername = 'section' . $sectionnum . 'header';
                    $mform->addElement('header', $headername, $sectionname);
                    $mform->setExpanded($headername, false);
                    $prevsectionnum = $sectionnum;
                }

                // Display activity name.
                $iconmarkup = html_writer::empty_tag('img', array(
                        'src' => $cm->get_icon_url(), 'class' => 'activityicon', 'alt' => ''));
                $stractivityname = html_writer::tag('strong' , $iconmarkup . $cm->name);
                $mform->addElement('static', 'modname' . $cm->id, $stractivityname);
                $isdateadded = false;

                // Call get_settings method for the acitivity/module.
                // Get instance of the mod's date exractor class.
                $mod = report_editdates_mod_date_extractor::make($cm->modname, $course);
                if ($mod && ($cmdatesettings = $mod->get_settings($cm))) {
                    // Added activity name on the form.
                    foreach ($cmdatesettings as $cmdatetype => $cmdatesetting) {
                        $elname = 'date_mod_'.$cm->id.'_'.$cmdatetype;
                        $mform->addElement($cmdatesetting->type, $elname,
                                $cmdatesetting->label, array(
                                'optional' => $cmdatesetting->isoptional,
                                'step' => $cmdatesetting->getstep));
                        $mform->setDefault($elname, $cmdatesetting->currentvalue);
                        if ($ismodreadonly) {
                            $mform->hardFreeze($elname);
                        }
                        $elementadded++;

                        $isdateadded = true;
                    }
                }

                // Conditional availability.
                if ($coursehasavailability) {
                    // Check if available from date is set.
                    $elname = 'date_mod_'.$cm->id.'_availablefrom';
                    $mform->addElement('date_time_selector', $elname,
                            get_string('availablefrom', 'condition'),
                            array('optional'=>true));
                    $mform->setDefault($elname, $cm->availablefrom);
                    $mform->addHelpButton($elname, 'availablefrom', 'condition');
                    if ($ismodreadonly) {
                        $mform->hardFreeze($elname);
                    }
                    $elementadded++;

                    // Check if available until date is set.
                    $elname = 'date_mod_'.$cm->id.'_availableuntil';
                    $mform->addElement('date_time_selector', $elname,
                            get_string('availableuntil', 'condition'),
                            array('optional'=>true));
                    $mform->setDefault($elname, $cm->availableuntil);
                    if ($ismodreadonly) {
                        $mform->hardFreeze($elname);
                    }
                    $elementadded++;

                    $isdateadded = true;
                }

                // Completion tracking.
                if ($coursehascompletion) {
                    $elname = 'date_mod_'.$cm->id.'_completionexpected';
                    $mform->addElement('date_selector', $elname,
                            get_string('completionexpected', 'completion'),
                            array('optional' => true));
                    $mform->addHelpButton($elname, 'completionexpected', 'completion');
                    $mform->setDefault($elname, $cm->completionexpected);
                    if ($ismodreadonly) {
                        $mform->hardFreeze($elname);
                    }
                    $elementadded++;

                    $isdateadded = true;
                }

                if ($isdateadded) {
                    $ismodadded = true;
                    $addactionbuttons = true;
                } else {
                    $mform->removeElement('modname' . $cm->id);
                }
            } // End of modules loop.

            if (!$ismodadded && $mform->elementExists($sectionname)) {
                $mform->removeElement($sectionname);
            }
        } // End of sections loop.

        // Fetching all the blocks added directly under the course.
        // That is, parentcontextid = coursecontextid.
        $courseblocks = $DB->get_records('block_instances', array('parentcontextid' => $coursecontext->id));

        // Check capability of current user.
        $canmanagesiteblocks = has_capability('moodle/site:manageblocks', $coursecontext);

        $anyblockadded = false;
        if ($courseblocks) {
            // Header for blocks.
            $mform->addElement('header', 'blockdatesection');

            // Iterate though blocks array.
            foreach ($courseblocks as $blockid => $block) {
                $blockdatextrator = report_editdates_block_date_extractor::make($block->blockname, $course);
                if ($blockdatextrator) {
                    // Create the block instance.
                    $blockobj = block_instance($block->blockname, $block, $PAGE);
                    // If get_settings returns a valid array.
                    if ($blockdatesettings = $blockdatextrator->get_settings($blockobj)) {
                        $anyblockadded = true;
                        $addactionbuttons = true;
                        // Adding block's Title on page.
                        $mform->addElement('static', 'blocktitle', $blockobj->title);
                        foreach ($blockdatesettings as $blockdatetype => $blockdatesetting) {
                            $elname = 'date_block_'.$block->id.'_'.$blockdatetype;
                            // Add element.
                            $mform->addElement($blockdatesetting->type, $elname,
                                    $blockdatesetting->label,
                                    array('optional' => $blockdatesetting->isoptional,
                                    'step' => $blockdatesetting->getstep));
                            $mform->setDefault($elname, $blockdatesetting->currentvalue);
                            if (!$canmanagesiteblocks || !$blockobj->user_can_edit()) {
                                $mform->hardFreeze($elname);
                            }
                            $elementadded++;
                        }
                    }
                }
            }
        }
        if (!$anyblockadded && $mform->elementExists('blockdatesection')) {
            $mform->removeElement('blockdatesection');
        }

        // Adding submit/cancel buttons @ the end of the form.
        if ($addactionbuttons && $elementadded > 0) {
            $this->add_action_buttons();
        } else {
            // Remove top action button.
            $mform->removeElement('buttonar');
        }
    }

    public function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);

        $modinfo = $this->_customdata['modinfo'];
        $course = $this->_customdata['course'];
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

        $moddatesettings = array();
        $forceddatesettings = array();
        foreach ($data as $key => $value) {
            if ($key == "coursestartdate") {
                continue;
            }

            $cmsettings = explode('_', $key);
            // The array should have 4 keys.
            if (count($cmsettings) != 4) {
                continue;
            }

            // Ignore 0th position, it will be 'date'
            // 1st position should be the mod type
            // 2nd will be the id of module
            // 3rd will be property of module
            // ensure that the name is proper.
            if (isset($cmsettings['1']) && isset($cmsettings['2']) && isset($cmsettings['3'])) {
                // Check if its mod date settings.
                if ($cmsettings['1'] == 'mod') {
                    // Check if config date settings are forced
                    // and this is one of the forced date setting.
                    if (($CFG->enableavailability || $CFG->enablecompletion )
                            && in_array($cmsettings['3'], array('completionexpected', 'availablefrom', 'availableuntil'))) {
                        $forceddatesettings[$cmsettings['2']][$cmsettings['3']] = $value;
                    } else {
                        // It is module date setting.
                        $moddatesettings[$cmsettings['2']][$cmsettings['3']] = $value;
                    }
                }
            }
        }

        $cms = $modinfo->get_cms();

        // Validating forced date settings.
        foreach ($forceddatesettings as $modid => $datesettings) {
            // Course module object.
            $cm = $cms[$modid];
            $moderrors = array();
            if (isset($datesettings['availablefrom']) && isset($datesettings['availableuntil'])
                    && $datesettings['availablefrom'] != 0 && $datesettings['availableuntil'] != 0
                    && $datesettings['availablefrom'] > $datesettings['availableuntil'] ) {
                $errors['date_mod_'.$modid.'_availableuntil'] =
                    get_string('badavailabledates', 'condition');
            }
        }

        // Validating mod date settings.
        foreach ($moddatesettings as $modid => $datesettings) {
            // Course module object.
            $cm = $cms[$modid];
            $moderrors = array();

            if ($mod = report_editdates_mod_date_extractor::make($cm->modname, $course)) {
                $moderrors = $mod->validate_dates($cm, $datesettings);
                if (!empty($moderrors)) {
                    foreach ($moderrors as $errorfield => $errorstr) {
                        $errors['date_mod_'.$modid.'_'.$errorfield] = $errorstr;
                    }
                }
            }
        }

        return $errors;
    }
}
