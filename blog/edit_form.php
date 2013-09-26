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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class blog_edit_form extends moodleform {
    public $modnames = array();

    function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;

        $entry = $this->_customdata['entry'];
        $courseid = $this->_customdata['courseid'];
        $modid = $this->_customdata['modid'];
        $summaryoptions = $this->_customdata['summaryoptions'];
        $attachmentoptions = $this->_customdata['attachmentoptions'];
        $sitecontext = $this->_customdata['sitecontext'];

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'subject', get_string('entrytitle', 'blog'), array('size' => 60, 'maxlength' => 128));
        $mform->addElement('editor', 'summary_editor', get_string('entrybody', 'blog'), null, $summaryoptions);

        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('emptytitle', 'blog'), 'required', null, 'client');
        $mform->addRule('subject', get_string('maximumchars', '', 128), 'maxlength', 128, 'client');

        $mform->setType('summary_editor', PARAM_RAW);
        $mform->addRule('summary_editor', get_string('emptybody', 'blog'), 'required', null, 'client');

        $mform->addElement('filemanager', 'attachment_filemanager', get_string('attachment', 'forum'), null, $attachmentoptions);

        //disable publishstate options that are not allowed
        $publishstates = array();
        $i = 0;

        foreach (blog_entry::get_applicable_publish_states() as $state => $desc) {
            $publishstates[$state] = $desc;   //no maximum was set
            $i++;
        }

        $mform->addElement('select', 'publishstate', get_string('publishto', 'blog'), $publishstates);
        $mform->addHelpButton('publishstate', 'publishto', 'blog');
        $mform->setDefault('publishstate', 0);

        if (!empty($CFG->usetags)) {
            $mform->addElement('header', 'tagshdr', get_string('tags', 'tag'));
            $mform->addElement('tags', 'tags', get_string('tags'));
        }

        $allmodnames = array();

        if (!empty($CFG->useblogassociations)) {
            // Create a new array for all context where that the user is allowed to associate entries.
            $permittedcontexts = array('' => '');

            foreach (get_courses() as $course) {
                $coursecontext = context_course::instance($course->id);
                if (has_capability('moodle/blog:associatecourse', $coursecontext) && is_enrolled($coursecontext)) {
                    $permittedcontexts[$coursecontext->id] = $course->fullname;
                    foreach ($coursecontext->get_child_contexts() as $childcontext) {
                        if ($childcontext->contextlevel == CONTEXT_MODULE
                                && has_capability('moodle/blog:associatemodule', $childcontext)) {
                            $cm = get_coursemodule_from_id(null, $childcontext->instanceid);
                            $permittedcontexts[$childcontext->id] = '- '.$cm->name;
                        }
                    }
                 }
            }

            // If there are any allowed contexts (more than the empty first option), show the select element and set the default option.
            if (count($permittedcontexts) > 1) {
                $mform->addElement('header', 'assochdr', get_string('associations', 'blog'));
                $associationselect = $mform->addElement('select', 'assoc', get_string('association', 'blog'), $permittedcontexts);

                if (!empty($entry->modassoc)) {
                    $mform->setDefault('assoc', $entry->modassoc);
                } else if (!empty($entry->courseassoc)) {
                    $mform->setDefault('assoc', $entry->courseassoc);
                } else if (!empty($modid)) {
                    $modulecontext = context_module::instance($modid);
                    $mform->setDefault('assoc', $modulecontext->id);
                } else if (!empty($courseid)) {
                    $coursecontext = context_course::instance($courseid);
                    $mform->setDefault('assoc', $coursecontext->id);
                }
            }
        }

        $this->add_action_buttons();
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHANUMEXT);
        $mform->setDefault('action', '');

        $mform->addElement('hidden', 'entryid');
        $mform->setType('entryid', PARAM_INT);
        $mform->setDefault('entryid', $entry->id);

        $mform->addElement('hidden', 'modid');
        $mform->setType('modid', PARAM_INT);
        $mform->setDefault('modid', $modid);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $courseid);
    }

    function validation($data, $files) {
        global $CFG, $DB, $USER;

        // Before validation starts: Change the name of the association element to either modassoc or courseassoc, depending on 
        // what kind of context is chosen.
        // This is not strictly validation, but needs to be performed before validation therefore
        // definition_before_data() can't be used.

        $mform =& $this->_form;
        $associationselect = $mform->getElement('assoc');
        $selectedvalue = $associationselect->getValue();
        if ($selectedvalue[0] != '') {
            $selectedcontext = context::instance_by_id($selectedvalue[0]);
            if ($selectedcontext->contextlevel == CONTEXT_MODULE) {
                $associationselect->setName('modassoc');
            } else if ($selectedcontext->contextlevel == CONTEXT_COURSE) {
                $associationselect->setName('courseassoc');
            }
        }

        $errors = array();

        // validate course association
        if (!empty($data['courseassoc'])) {
            $coursecontext = context::instance_by_id($data['courseassoc']);

            if ($coursecontext->contextlevel != CONTEXT_COURSE) {
                $errors['courseassoc'] = get_string('error');
            }
        }

        // validate mod association
        if (!empty($data['modassoc'])) {
            $modcontextid = $data['modassoc'];
            $modcontext = context::instance_by_id($modcontextid);

            if ($modcontext->contextlevel == CONTEXT_MODULE) {
                // get context of the mod's course
                $coursecontext = $modcontext->get_course_context(true);

                // ensure only one course is associated
                if (!empty($data['courseassoc'])) {
                    if ($data['courseassoc'] != $coursecontext->id) {
                        $errors['modassoc'] = get_string('onlyassociateonecourse', 'blog');
                    }
                } else {
                    $data['courseassoc'] = $coursecontext->id;
                }
            } else {
                $errors['modassoc'] = get_string('error');
            }
        }

        if ($errors) {
            return $errors;
        }
        return true;
    }
}
