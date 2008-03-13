<?php
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

require_once($CFG->dirroot.'/mod/sloodle/sl_config.php');
require_once(SLOODLE_LIBROOT.'/sl_generallib.php');

// Sloodle module add/update form
class mod_sloodle_mod_form extends moodleform_mod {

	function definition() {

		global $CFG, $COURSE, $SLOODLE_TYPES, $SLOODLE_TYPE_CTRL;
		$mform    =& $this->_form;

//-------------------------------------------------------------------------------

        // Check to see if a 'type' value has been specified
        $defaulttype = optional_param('type', '', PARAM_TEXT);
        if (empty($defaulttype)) $defaulttype = 0;
        
        
//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Check if this course already has a control center type in it
        if (!sloodle_course_has_control_center($COURSE->id)) {
            // No control center
            $mform->addElement('hidden', 'forcecontrolcenter', 1);
            $forcecontrolcenter = true;
        
            // The user must first add a control center to this module
            $mform->addElement('select', 'type', "Sloodle Module Type", array($SLOODLE_TYPE_CTRL => get_string("moduletype:$SLOODLE_TYPE_CTRL", 'sloodle')));
            $mform->setDefault('type', $SLOODLE_TYPE_CTRL);
            $mform->disabledIf('type', 'forcecontrolcenter', 'eq', 1);
            
        } else {
            // The user can add any regular type they like (except control center)
            $mform->addElement('hidden', 'forcecontrolcenter', 0);
            $forcecontrolcenter = false;
            
            // Construct an associative of short to full names of Sloodle module types
            $sloodlemoduletypes = array();
            foreach($SLOODLE_TYPES as $st) {
                $sloodlemoduletypes[$st] = get_string("moduletype:$st", 'sloodle');
            }
            
            $mform->addElement('select', 'type', "Sloodle Module Type", $sloodlemoduletypes);
            $mform->setDefault('type', $defaulttype);
        }

//-------------------------------------------------------------------------------
        
        // Make a text box for the name of the module
        $mform->addElement('text', 'name', get_string('name', 'sloodle'), array('size'=>'64'));
        // Make it text type
		$mform->setType('name', PARAM_TEXT);
        // Set a client-size rule that an entry is required
		$mform->addRule('name', null, 'required', null, 'client');

        // Create an HTML editor for module description (intro text)
		$mform->addElement('htmleditor', 'intro', get_string('description'));
        // Make it raw type (so the HTML isn't filtered out)
		$mform->setType('intro', PARAM_RAW);
        // Make it required
		$mform->addRule('intro', get_string('required'), 'required', null, 'client');
        // Provide an HTML editor help button
        $mform->setHelpButton('intro', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');


//-------------------------------------------------------------------------------

        // Only make the security stuff available if we are adding a control center
        if ($forcecontrolcenter) {
            // Header for security section
            $mform->addElement('header', 'security', "Security");

            // Checkbox for using site password
            $mform->addElement('checkbox', 'usesitepassword', "Use default site-wide Prim Password");
            
            // Textbox for local prim password
            $mform->addElement('text', 'password', "Prim Password", array('size'=>'9'));
            $mform->setType('password', PARAM_INT);
            //$mform->addRule('password', null, 'required', null, 'client');
            $mform->addRule('password', null, 'numeric', null, 'client');
            $mform->disabledIf('password', 'usesitepassword', 'checked');        
            $mform->setDefault('password', mt_rand(10000000, 999999999));
        }
        
//-------------------------------------------------------------------------------
        // This adds the group options. We may or may not want to use this.
        // E.g. it might be useful to restrict access to a Distributor to a single group.
		$this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // Form buttons
        $this->add_action_buttons();

	}

	function definition_after_data(){
	}
    
	function data_preprocessing($default_values){
	}




}
?>