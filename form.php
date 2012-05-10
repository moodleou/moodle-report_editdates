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
foreach (glob($CFG->dirroot . '/report/editdates/mod/*dates.php') as $filename) {
    require($filename);
}
foreach (glob($CFG->dirroot . '/report/editdates/blocks/*dates.php') as $filename) {
    require($filename);
}


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
        //global variables
        global $CFG, $DB, $COURSE, $PAGE;
        //get the form reference
        $mform =& $this->_form;
        //fetching $modinfo from the constructor custom data array
        $modinfo = $this->_customdata['modinfo'];
        //fetching $course from the constructor custom data array
        $course = $this->_customdata['course'];
        //coursecontext instance
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        //flag to check if actions buttons are required or the continue link
        $addactionbuttons = false;
        //adding course start date
        $mform->addElement('header', 'coursestartdateheader');
        $mform->addElement('date_selector', 'coursestartdate', get_string('startdate'));
        $mform->addHelpButton('coursestartdate', 'startdate');//help button for course start date
        $mform->setDefault('coursestartdate', $course->startdate);
        //if user is not capable, make it read only
        if (!has_capability('moodle/course:update', $coursecontext)) {
            $mform->hardFreeze('coursestartdate');
        } else {
            $addactionbuttons = true;
        }

        //fetching all the sections in the course
        $sections = get_all_sections($modinfo->courseid);
        $sectionname = '';
        //default -1 to display header for 0th section
        $prevsecctionnum = -1;
        //cycle through all the sections in the course
        foreach ($modinfo->sections as $sectionnum => $section) {
            $ismodadded = false;
            $sectionname = '';
            //cycle through each module in a section
            foreach ($section as $cmid) {
                //fetching the course module object from the $modinfo array.
                $cm = $modinfo->cms[$cmid];

                //no need to display/continue if this module is not visible to user
                if (!$cm->uservisible) {
                    continue;
                }
                //flag to check if user has the capability to edit this module
                $ismodreadonly = false;
                //context instance of the module
                $modulecontext = get_context_instance(CONTEXT_MODULE, $cm->id);
                //check if user has capability to edit this module
                if (!has_capability('moodle/course:manageactivities', $modulecontext)) {
                    $ismodreadonly = true;
                }

                //new section, create header
                if ($prevsecctionnum != $sectionnum) {
                    $sectionname = get_section_name($course, $sections[$sectionnum]);
                    $mform->addElement('header', $sectionname, $sectionname);
                    $prevsecctionnum = $sectionnum;
                }
                //fetching activity name with <h3> tag.
                $stractivityname = html_writer::tag('h3' , $cm->name);

                //handle site level config settings if conditional availability
                //dates are enabled or completion date is enabled
                if ( ($CFG->enableavailability
                && ( $cm->availablefrom != 0 || $cm->availableuntil != 0) )
                || ($CFG->enablecompletion && $cm->completionexpected != 0 ) ) {

                    /*
                     * either of the two settings are available for this module,
                     * add the module name header
                     */

                    $mform->addElement('static', 'modname', $stractivityname);
                    //Conditional availability
                    if ($CFG->enableavailability
                    && ( $cm->availablefrom != 0 || $cm->availableuntil != 0)) {
                        //check if available from date is set
                        if ($cm->availablefrom != 0) {
                            $elname = 'date_mod_'.$cm->id.'_availablefrom';
                            $mform->addElement('date_selector', $elname,
                            get_string('availablefrom', 'condition'),
                            array('optional'=>true));
                            $mform->setDefault($elname, $cm->availablefrom);
                            $mform->addHelpButton($elname, 'availablefrom', 'condition');
                            if ($ismodreadonly) {
                                $mform->hardFreeze($elname);
                            }
                        }
                        //check if available until date is set
                        if ($cm->availableuntil != 0) {
                            $elname = 'date_mod_'.$cm->id.'_availableuntil';
                            $mform->addElement('date_selector', $elname,
                            get_string('availableuntil', 'condition'),
                            array('optional'=>true));
                            $mform->setDefault($elname, $cm->availableuntil);
                            if ($ismodreadonly) {
                                $mform->hardFreeze($elname);
                            }
                        }
                    }
                    //Completion tracking
                    if ($CFG->enablecompletion && $cm->completionexpected != 0) {

                        $elname = 'date_mod_'.$cm->id.'_completionexpected';
                        $mform->addElement('date_selector', $elname,
                        get_string('completionexpected', 'completion'),
                        array('optional' => true));
                        $mform->addHelpButton($elname, 'completionexpected', 'completion');
                        $mform->setDefault($elname, $cm->completionexpected);
                        if ($ismodreadonly) {
                            $mform->hardFreeze($elname);
                        }
                    }
                    $ismodadded = true;
                    $addactionbuttons = true;
                } else {//call get_settings method for the acitivity/module
                    //get instance of the mod's date exractor class
                    $mod = report_editdates_mod_data_date_extractor::make($cm->modname,
                         $course);
                    if ($mod) {
                        if ($cmdatesettings = $mod->get_settings($cm)) {
                            $ismodadded = true;
                            $addactionbuttons = true;
                            //added activity name on the form
                            $mform->addElement('static', 'modname', $stractivityname);
                            foreach ($cmdatesettings as $cmdatetype => $cmdatesetting) {
                                $elname = 'date_mod_'.$cm->id.'_'.$cmdatetype;
                                $mform->addElement($cmdatesetting->type, $elname,
                                $cmdatesetting->label,
                                array('optional' => $cmdatesetting->isoptional,
                                                    'step' => $cmdatesetting->getstep));
                                $mform->setDefault($elname, $cmdatesetting->currentvalue);
                                if ($ismodreadonly) {
                                    $mform->hardFreeze($elname);
                                }
                            }
                        }
                    }
                }
            }//end of sections loop
            if (!$ismodadded && $mform->elementExists($sectionname)) {
                $mform->removeElement($sectionname);
            }
        }//end of modules loop

        /*
         * fetching all the blocks added directly under the course
         * i.e parentcontextid = coursecontextid
         */
        $courseblocks = $DB->get_records("block_instances",
        array('parentcontextid' => $coursecontext->id));

        // check capability of current user.
        $canmanagesiteblocks = has_capability('moodle/site:manageblocks', $coursecontext);

        $anyblockadded = false;
        if (isset($courseblocks) && count($courseblocks) > 0) {
            // adding header for blocks
            $mform->addElement('header', 'blockdatesection');
            // iterate though blocks array
            foreach ($courseblocks as $blockid => $block) {
                $blockdatextrator =
                    report_editdates_block_date_extractor::make($block->blockname, $course);
                if ($blockdatextrator) {
                    // create the block instance
                    $blockobj = block_instance($block->blockname, $block, $PAGE);
                    // if get_settings returns a valid array
                    if ($blockdatesettings = $blockdatextrator->get_settings($blockobj)) {
                        $anyblockadded = true;
                        $addactionbuttons = true;
                        //adding block's Title on page.
                        $mform->addElement('static', 'blocktitle', $blockobj->title);
                        foreach ($blockdatesettings as $blockdatetype => $blockdatesetting) {
                            /*
                             *  create element name '_' seperated
                             *  becuase dateselector doesn't create array for [] in name .
                             */
                            $elname = 'date_block_'.$block->id.'_'.$blockdatetype;
                            // adding element
                            $mform->addElement($blockdatesetting->type, $elname,
                            $blockdatesetting->label,
                            array('optional' => $blockdatesetting->isoptional,
                                                'step' => $blockdatesetting->getstep));
                            $mform->setDefault($elname, $blockdatesetting->currentvalue);
                            if (!$canmanagesiteblocks || !$blockobj->user_can_edit()) {
                                $mform->hardFreeze($elname);
                            }
                        }
                    }
                }
            }
        }
        if (!$anyblockadded && $mform->elementExists("blockdatesection")) {
            $mform->removeElement("blockdatesection");
        }
        //adding submit/cancel buttons @ the end of the form
        if ($addactionbuttons) {
            $this->add_action_buttons();
        } else {
            // <div> is used for center align the continue link
            $continue_url = new moodle_url('/course/view.php', array('id' => $course->id));
            $mform->addElement('html',
                "<div style=text-align:center><a href=$continue_url><b>[Continue]</b></a></div>");
        }
    }

    /// perform some extra moodle validation
    public function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);
        $errors = array();

        //fetching $modinfo from the constructor custom data array
        $modinfo = $this->_customdata['modinfo'];
        //fetching $course from the constructor custom data array
        $course = $this->_customdata['course'];
        //coursecontext instance
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        $moddatesettings = array();
        $forceddatesettings = array();
        foreach ($data as $key => $value) {
            if ($key == "coursestartdate") {        //skip if element is course start date
                continue;
            } else {
                $cmsettings = explode('_', $key);
                //array should have 4 keys
                if (count($cmsettings) == 4) {
                    //ignore 0th position, it will be 'date'
                    //1st position should be the mod type
                    //2nd will be the id of module
                    //3rd will be property of module
                    //ensure that the name is proper
                    if (isset($cmsettings['1'])
                    && isset($cmsettings['2']) && isset($cmsettings['3'])) {
                        //check if its mod date settings
                        if ($cmsettings['1'] == 'mod') {
                            /*
                             * check if config date settings are forced
                             * and this is one of the forced date setting
                             */
                            if ( ($CFG->enableavailability || $CFG->enablecompletion )
                            && ($cmsettings['3'] == "completionexpected"
                            || $cmsettings['3'] == "availablefrom"
                            || $cmsettings['3'] == "availableuntil") ) {
                                $forceddatesettings[$cmsettings['2']][$cmsettings['3']] = $value;
                            } else {
                                //it is module date setting
                                $moddatesettings[$cmsettings['2']][$cmsettings['3']] = $value;
                            }
                        }
                    }
                }
            }
        }

        //validating forced date settings
        foreach ($forceddatesettings as $modid => $datesettings) {
            //course module object
            $cm = $modinfo->cms[$modid];
            $moderrors = array();
            if (isset($datesettings['availablefrom']) && isset($datesettings['availableuntil'])
                && $datesettings['availablefrom'] != 0 && $datesettings['availableuntil'] != 0
                && $datesettings['availablefrom'] > $datesettings['availableuntil'] ) {
                $errors['date_mod_'.$modid.'_availableuntil'] =
                    get_string('badavailabledates', 'condition');
            }
        }

        //validating mod date settings
        foreach ($moddatesettings as $modid => $datesettings) {
            //course module object
            $cm = $modinfo->cms[$modid];
            $moderrors = array();

            if ($mod =
                report_editdates_mod_data_date_extractor::make($cm->modname, $course)) {
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
