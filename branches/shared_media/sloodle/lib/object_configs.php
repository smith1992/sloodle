<?php
class SloodleObjectConfig {

	// The main, canonical name of the object corresponding to this config
	var $primname; 

	// A name without spaces etc by which we can refer to this object.
	// Doesn't matter what as long as it's unique for the object
	var $object_code;

	// An array of other things the object could be known by. 
	// This can be passed to the rezzed, and should be rezzed in the order specified here
	// ...so if the rezzer has two matching objects, it will rez the first one.
	// NB. We may decide to generate this automatically for multiple versions if we insist on including the version number in the object name
	var $aliases = array();

	// The Sloodle tool corresponding to this object, eg. quiz-1.0.
	// Null if there isn't one.
	var $modname;

	// The Moodle module tool corresponding to this object, eg. quiz.
	// Null if there isn't one.
	var $module;

	// The instance types to which we should limit the module selection, eg. array( 'type' => SLOODLE_TYPE_PRESENTER )
	// Null if there isn't one
	var $module_filters = array();

	// The group of objects to which this one should belong.
	// Used in displaying objects in groups in the set.
	var $group;

	// Whether the object should be displayed by default.
	// Was previously controlled using a file called "noshow".
	var $show;

	// An array of groupings containing an array of SloodleInputWidget objects
	// One used for each configuration control.
	// The keys of this array should correspond to a translation which appends 'fieldset:'
	// eg. if you define a field_set called 'access', there should be a Sloodle translation key to display it called 'fieldset:access'.
	var $field_sets = array();

	// The value of the config as currently set. 
	// Needs to be filled from somewhere, eg. an object_config or layout_entry_config record
	// For a newly created config, this will be null.
	var $value = null;
		
	// static function returning an object configuration for an object with the given name, or none if none is found
	function ForObjectName($objname) {
		$allconfigs = SloodleObjectConfig::AllAvailableAsArray();
		if (isset($allconfigs[$objname])) {
			return $allconfigs[$objname];
		}
		return null;
	}

	/*
	Load an associative array of object definitions called object_configs, with the prim name as the key
	...and a SloodleObjectConfig object as the value.

	There should be a directory called object_definitions in each mod/ directory, containing .php files with the definitions..
	Each definition will define an object called $sloodleconfig.

	An object_definitions directory may contain multiple object definitions.
	Previously, we dealt with multiple object definitions by creating a mod/ directory for each object.
	Let's deprecate that approach and have each directory under mod/ represent a set of linker functionality.

	As of 2011-06-26, the old folders are still there, eg. there is a mod/quiz-1.0 and a mod/quiz_pile_on-1.0.
	This doesn't make much sense, as quiz_pile_on scripts end up calling linkers under quiz-1.0 anyhow.
	But we'll leave them for backwards compatibility until we can kill of the old HTML configuration forms.
	*/
	function AllAvailableAsArray() {

		$currentdir = dirname(__FILE__);

		$modtopdir = SLOODLE_DIRROOT.'/mod/';

		$object_configs = array();

		if (!is_dir($modtopdir)) {
			return false;
		}
		if (!$dh = opendir($modtopdir)) {
			return false;
		}
		while (($file = readdir($dh)) !== false) {
			if ($file == '.') { 
				continue;
			}
			if ($file == '..') { 
				continue;
			}
			$object_definition_dir = $modtopdir.'/'.$file.'/object_definitions';
			if ( !file_exists( $object_definition_dir ) || !is_dir($object_definition_dir) ) {
				continue;
			}
			if (!$dh2 = opendir($object_definition_dir)) {
				continue;
			}
			while (($def_include = readdir($dh2)) !== false) {
				if (!preg_match('/\.php$/', $def_include)) {
					continue;
				}
				include($object_definition_dir.'/'.$def_include);
				$object_configs[$sloodleconfig->primname] = $sloodleconfig;
			}
			closedir($dh2);
		}
		closedir($dh);

		return $object_configs;

        }

	function AllAvailableAsArrayByGroup() {

		$objectconfigsbygroup = array();
		foreach(SloodleObjectConfig::AllAvailableAsArray() as $objname => $objconfig) {
			$group = $objconfig->group;
			if (!isset($objectconfigsbygroup[$group])) {
				$objectconfigsbygroup[$group] = array();
			}
			$objectconfigsbygroup[$group][$objname] = $objconfig;
		}
		return $objectconfigsbygroup;

	}

	// This creates a config element for a non-sloodle object, allowing us to rez and derez it.
	// It is still assumed that it will have a sloodle_rezzer object script in it.
	function ForNonSloodleObjectWithName( $name ) {

		// We need a url-safe version of the name
		// J-query seems to choke on something, even if it's url-encoded.
		$encoded_name = ereg_replace("[^A-Za-z0-9]", "", $name); // Strip non-alphanumeric characters to give us a human-readable name
  		$encoded_name .= '_'.md5($name); // Append an md5sum of the original name to avoid collisions in case someone has "My Object" and "MyObject".

		$sloodleconfig = new SloodleObjectConfig();
		$sloodleconfig->name       = $encoded_name;
		$sloodleconfig->primname   = $name;
		$sloodleconfig->object_code= $encoded_name;
		$sloodleconfig->modname    = null;
		$sloodleconfig->group      = 'misc';
		$sloodleconfig->show       = false;
		$sloodleconfig->aliases    = array();
		$sloodleconfig->field_sets = array();

		return $sloodleconfig;

	}

	// returns a SloodleObjectConfig object for the specified tool (eg. chat-1.0).
	// TODO: This currently pulls the config out of a single big list.
	// The list needs to be split into each module directory, and pulled in from there.
	// This is intended to be used where we're currently include()ing a file from its mod/object_config.php
	function ForModName( $modname ) {
			
	}

	function possibleObjectNames() {
		$names = array($this->primname);
		if ( is_array($this->aliases) ) {
			foreach($this->aliases as $al) {
				array_push( $names, $al );
			}
		}
		return $names;
	}

	// return an input widget for server access level
	function access_level_server_option() {

		$ctrl = new SloodleConfigurationOptionSelectOne();
		$ctrl->fieldname = 'sloodleserveraccesslevel';
		$ctrl->title = 'accesslevelserver';
		$ctrl->description = '';
		$ctrl->options = array(
			SLOODLE_SERVER_ACCESS_LEVEL_PUBLIC => 'accesslevel:public',
			SLOODLE_SERVER_ACCESS_LEVEL_COURSE => 'accesslevel:course',
			SLOODLE_SERVER_ACCESS_LEVEL_SITE   => 'accesslevel:site',
			SLOODLE_SERVER_ACCESS_LEVEL_STAFF  => 'accesslevel:staff'
		);
		$ctrl->default = SLOODLE_SERVER_ACCESS_LEVEL_PUBLIC;
		$ctrl->type = 'radio'; // This is the recommended display type for the object.

		return $ctrl;

	}

	// return an input widget for object use
	function access_level_object_use_option() {

		$ctrl = new SloodleConfigurationOptionSelectOne();
		$ctrl->fieldname = 'sloodleobjectaccessleveluse';
		$ctrl->title = 'accesslevelobject:use';
		$ctrl->description = '';
		$ctrl->options = array(
			SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC => 'accesslevel:public',
			SLOODLE_OBJECT_ACCESS_LEVEL_GROUP  => 'accesslevel:group',
			SLOODLE_OBJECT_ACCESS_LEVEL_OWNER  => 'accesslevel:owner'
		);
		$ctrl->default = SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC;
		$ctrl->type = 'radio'; // This is the recommended display type for the object.

		return $ctrl;

	}

	// return an input widget for object control
	function access_level_object_control_option() {

		$ctrl = new SloodleConfigurationOptionSelectOne();
		$ctrl->fieldname = 'sloodleobjectaccesslevelctrl';
		$ctrl->title = 'accesslevelobject:control';
		$ctrl->description = '';
		$ctrl->options = array(
			SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC => 'accesslevel:public',
			SLOODLE_OBJECT_ACCESS_LEVEL_GROUP  => 'accesslevel:group',
			SLOODLE_OBJECT_ACCESS_LEVEL_OWNER  => 'accesslevel:owner'
		);
		$ctrl->default = SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC;
		$ctrl->type = 'radio'; // This is the recommended display type for the object.

		return $ctrl;

	}

	function course_module_select( $courseid, $val = null ) {

		if (!$options = $this->course_module_options( $courseid )) {
			return false;
		}
		$str = '<div>';
		$divider = '';
		$isfirst = true;
		foreach($options as $n => $v) {
			$isselected = ($val == $n);
			// If there's nothing selected and only one option, select that.
			if ( ($val == null) && $isfirst ) {
				$isselected = true;	
			}
			$selectedattr = $isselected ? ' checked="checked"' : '';
			$str .= '<input type="radio" name="sloodlemoduleid" '.$selectedattr.' value="'.htmlentities( $n ).'">'.htmlentities( $v ).$divider."\n";
			$divider = '<br />';
			$isfirst = false;
		}
		$str .= '</div>';
		/*
		foreach($options as $n => $v) {
			$selectedattr = ($val == $n) ? ' checked="checked"' : '';
			$str .= '<input type="radio" name="sloodlemoduleid" '.$selectedattr.'" value="'.htmlentities( $n ).'">'.htmlentities( $v ).''."<br />\n";
		}
		*/
		return $str;	
		
	}
	/*
	function course_module_select( $courseid, $val = null ) {

		if (!$options = $this->course_module_options( $courseid )) {
			return false;
		}
		$str = '<select name="sloodlemoduleid">'."\n";
		foreach($options as $n => $v) {
			$selectedattr = ($val == $n) ? ' selected ' : '';
			$str .= '<option '.$selectedattr.'value="'.htmlentities( $n ).'">'.htmlentities( $v ).'</option>'."\n";
		}
		$str .= '</select>';
		return $str;	
		
	}
	*/

	function course_module_options( $courseid )  {

		$options = array();

		$modtype = $this->module;
		if (!$modtype) {
			return false;
		}

		// Determine which course is being accessed
		//$courseid = $auth_obj->course->get_course_id();

		// We need to fetch a list of visible quizzes on the course
		// Get the ID of the chat type
		$rec = get_record('modules', 'name', $modtype);
		if (!$rec) {
			return false;
		}
		$moduleid = $rec->id;

		// Get all visible quizzes in the current course
		$recs = get_records_select('course_modules', "course = ".intval($courseid)." AND module = ".intval($moduleid)." AND visible = 1");
		if (!$recs) {
			return false;
		    //error(get_string('noquizzes','sloodle'));
		}

		foreach ($recs as $cm) {
			// Fetch the quiz instance
			$inst = get_record($modtype, 'id', $cm->instance);
			if (!$inst) {
				continue;
			}

			$skip = false;
			if (is_array($this->module_filters)) {
				foreach($this->module_filters as $n => $v) {
					if ($inst->$n != $v) {
						$skip = true;
						break;
					}
				}
			}

			if ($skip) {
				continue;
			}

			// Store the quiz details
			$options[$cm->id] = $inst->name;
		}
		// Sort the list by name
		natcasesort($options);

		return $options;
	
	}

	/*
	Return an array of configuration options related to giving someone points for interacting with the object.
	By default, this produces a single configuration option for interacting with the object.

	If your object specifies multiple kinds of interaction, you can pass them in as an array.
	By default, this will replace the default interaction, so if you want that too, put it in your array.

	The result will be something like (with the default interaction):
	Using the object gives you [   ] of the currency [               ]
	Using the object costs you [   ] of the currency [               ]

	...or with customized interactions:
	Getting an answer right gives you [   ] of the currency [               ]
	Getting an answer right costs you [   ] of the currency [               ]
	Getting an answer wrong gives you [   ] of the currency [               ]
	Getting an answer wrong costs you [   ] of the currency [               ]
	*/
	function awards_setting_options( $interactions = null ) {

		if ($interactions == null) {
			$interactions = array('default' => array('interactwithobjectplus', 'interactwithobjectminus') );
		}
		$configs = array();
		
		foreach($interactions as $interactionname => $interactionlabels) {
			$deposit_points_fieldname      = 'sloodleawardsdeposit_numpoints_'.$interactionname;
			$deposit_currency_fieldname    = 'sloodleawardsdeposit_currency_'.$interactionname;
			$withdraw_points_fieldname   = 'sloodleawardswithdraw_numpoints_'.$interactionname;
			$withdraw_currency_fieldname = 'sloodleawardswithdraw_currency_'.$interactionname;
			$configs[ $deposit_points_fieldname ]    = new SloodleConfigurationOptionText( $deposit_points_fieldname, $interactionlabels[0], '', 0, 8);
			$configs[ $deposit_currency_fieldname ]  = new SloodleConfigurationOptionCurrencyChoice( $deposit_currency_fieldname, 'currency', '', '', 8);
			$configs[ $withdraw_points_fieldname ]   = new SloodleConfigurationOptionText( $withdraw_points_fieldname, $interactionlabels[1], '', 0, 8);
			$configs[ $withdraw_currency_fieldname ] = new SloodleConfigurationOptionCurrencyChoice( $withdraw_currency_fieldname, 'currency', '', '', 8);
		}

		return $configs;

	}

        
// TODO: Write code in lib/active_object so that:
// linker script can do something like:
// $active_object->process_awards_interactions( array('default'), $USER );
// ...or
// $active_object->process_awards_interactions( array('answer_correct' ), $USER );

}

// Represents a single entry or potential entry in an object configuration.
// eg. sloodlemoduleid or sloodlerefreshtime
// 
class SloodleConfigurationOption {

	// the fieldname of the option
	var $fieldname;

	// the displayed title of the widget to be fed to get_string
	var $title;

	// the description of the widget	
	var $description;

	// a set of name-value pairs of keys and their display values
	var $options = array(); 

	// the size of the display element, if applicable
	var $size = 255;

	// the maximum number of characters allowed
	var $max_length = 255;

	// the default value. should be one of the keys listed in options.
	var $default;
	
	// the recommended display type for the object.
	// currently support radio, select
	var $ctrl;

	// the type of the object, eg. yesno, select etc.
	// TODO: It would be better if these objects could render their own html, then we wouldn't need to tell anyone the type
	var $type;

	function renderForMoodleForm() {

	}

	function renderForIUIForm() {

	}

}

class SloodleConfigurationOptionYesNo extends SloodleConfigurationOption {

	function SloodleConfigurationOptionYesNo( $fieldname, $title, $description = '', $default = 0 ) {
		$this->fieldname = $fieldname;
		$this->title = $title;
		$this->description = $description;
		$this->options = array(1 => 'Yes', 0 => 'No');
		$this->default = $default;
		$this->type = 'yesno';
	}

}

class SloodleConfigurationOptionText extends SloodleConfigurationOption {

	function SloodleConfigurationOptionText( $fieldname, $title, $description, $default = '', $length = 8 ) {
		$this->fieldname = $fieldname;
		$this->title = $title;
		$this->description = $description;
		$this->size = $length;
		$this->max_length = $length;
		$this->default= $default;
		$this->type = 'input';
	}

}

// NB This could be presented as a set of radio buttons rather than a select
class SloodleConfigurationOptionSelectOne extends SloodleConfigurationOption {

	function SloodleConfigurationOptionSelectOne( $fieldname = null, $title = null, $description = null, $length = 8, $default = null) {
		$this->fieldname = $fieldname;
		$this->title = $title;
		$this->description = $description;
		$this->options = array(0 => 'Yes', 1 => 'No');
		$this->default = $default;
		$this->type = 'radio';
	}

}

// A setting needing a choice of course modules
class SloodleConfigurationOptionCourseModuleChoice extends SloodleConfigurationOptionSelectOne {

	// The message displayed if there are no module instances available
	var $noneavailablemessage = '';

	// An array of key-value pairs for filtering the instance modules
	var $instancefilters = array();

	function SloodleConfigurationOptionText( $fieldname, $title, $description, $noneavailablemessage, $instancefilters ) {
		$this->fieldname = $fieldname;
		$this->title = $title;
		$this->description = $description;
		$this->options = $this->course_module_options_for_config();
	}




}

class SloodleConfigurationOptionCurrencyChoice extends SloodleConfigurationOption {

	function SloodleConfigurationOptionCurrencyChoice( $fieldname, $title, $description, $default = '', $length = 8 ) {

		if (!$currencies = get_records('sloodle_currency_types') ) {
			return false;
		}

		$options = array();
		foreach($currencies as $c) {
			$options[$c->id] = $c->name;
		}

		$this->fieldname = $fieldname;
		$this->title = $title;
		$this->description = $description;
		$this->size = $length;
		$this->options = $options;
		$this->max_length = $length;
		$this->default= $default;
		$this->type = 'radio';
	}

}
?>
