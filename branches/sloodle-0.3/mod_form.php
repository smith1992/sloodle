<?php
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

require_once($CFG->dirroot.'/mod/sloodle/sl_config.php');

// Sloodle module add/update form
class mod_sloodle_mod_form extends moodleform_mod {

	function definition() {

		global $CFG, $COURSE, $SLOODLE_TYPES;
		$mform    =& $this->_form;

//-------------------------------------------------------------------------------

        // Check to see if a 'type' value has been specified
        $defaulttype = optional_param('type', '', PARAM_TEXT);
        if (empty($defaulttype)) $defaulttype = SLOODLE_TYPE_CTRL;
        
        
//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Construct an associative of short to full names of Sloodle module types
        $sloodlemoduletypes = array();
        foreach($SLOODLE_TYPES as $st) {
            $sloodlemoduletypes[$st] = get_string("moduletype:$st", 'sloodle');
        }
        
        // Add the drop-down menu for module type
        $mform->addElement('select', 'type', "Sloodle Module Type", $sloodlemoduletypes);
        // Is this module being added for the first time?
        if (empty($this->instance)) {
            // Yes - set the default type 
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
        // This adds the group options. We may or may not want to use this.
        // E.g. it might be useful to restrict access to a Distributor to a single group.
		$this->standard_coursemodule_elements();
        
//-------------------------------------------------------------------------------
        // Form buttons
        $this->add_action_buttons();

	}

	function definition_after_data(){
        $mform =& $this->_form;
	    $type = &$mform->getElement('type');
        
        // We can't let the user change the module type when they are just updating
        if (!empty($this->_instance)){
            $type->freeze();
            $type->setPersistantFreeze(true);
        }
	}
    
	function data_preprocessing($default_values){
	}




}
?>