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
 * Add label form
 *
 * @package mod_label
 * @copyright  2006 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_label_mod_form extends moodleform_mod {

    function definition() {

        $mform = $this->_form;

        $mform->addElement('header', 'generalhdr', get_string('general'));
        $this->standard_intro_elements(get_string('labeltext', 'label'));

        $this->standard_coursemodule_elements();

        // For collapsed view.
        $mform->addElement('header', 'collapsiblesettings', get_string('collapsiblesettings', 'label'));
        $mform->addElement('text', 'header', get_string('labelheader', 'label'), array('size' => LABEL_MAX_NAME_LENGTH));
        $mform->addHelpButton('header', 'labelheader', 'label');

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('header', PARAM_TEXT);
        } else {
            $mform->setType('header', PARAM_CLEANHTML);
        }

        $mform->addRule('header', get_string('maximumchars', '', LABEL_MAX_NAME_LENGTH),
                        'maxlength', LABEL_MAX_NAME_LENGTH, 'client');
        $mform->setDefault('header', (isset($this->current->name)) ? $this->current->name : '');
        $mform->disabledIf('header', 'collapsible', 'eq', '0');

        $choices = array(LABEL_COLLAPSIBLE_OFF => get_string('collapsibleoff', 'label'),
                         LABEL_COLLAPSIBLE_EXPANDED => get_string('collapsibleexpanded', 'label'),
                         LABEL_COLLAPSIBLE_COLLAPSED => get_string('collapsiblecollapsed', 'label'));
        $mform->addElement('select', 'collapsiblemode', get_string('collapsiblemode', 'label'), $choices);

//-------------------------------------------------------------------------------
// buttons
        $this->add_action_buttons(true, false, null);

    }

}
