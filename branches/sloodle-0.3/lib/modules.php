<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file brings all of the Sloodle module classes together.
    * It also provides functionality to create and load different module types.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    
    /** Base module. */
    require_once(SLOODLE_LIBROOT.'/modules/module_base.php');
    
    // Now we will go through every file in the "lib/modules" folder which starts "module_", and include it.
    $MODULESPATH = SLOODLE_LIBROOT.'/modules';
    $modulefiles = sloodle_get_files($MODULESPATH, false);
    // Go through each file
    if (is_array($modulefiles)) {
    	foreach ($modulefiles as $mf) {
    		// Does this filename start with "module_" and end with ".php"?
    		if (strcasecmp(substr($mf, 0, 7), 'module_') == 0 && strcasecmp(substr($mf, -4), '.php') == 0) {
    			// Yes - include it
    			@include_once($mf);
    		}
    	}
    }
    
    // We will store an associative array of module types to module class names
    // e.g. { 'chat'=>'SloodleModuleChat', 'distributor'=>'SloodleModuleDistributor', ...}
    global $SLOODLE_MODULE_CLASS;
    $SLOODLE_MODULE_CLASS = array();
    // Go through each declared class
    $allclasses = get_declared_classes();
    foreach ($allclasses as $c) {
        // Is this class a subclass of the Sloodle Module type?
        if (strcasecmp(get_parent_class($c), 'SloodleModule') == 0) {
            // Fetch the type name
            $curtype = call_user_func(array($c, 'get_type'));
            if (!empty($curtype)) {
                // Add it to our array with its type
                $SLOODLE_MODULE_CLASS[$curtype] = $c;
            }
        }
    }
    
    
    /**
    * Constructs a appropriate Sloodle module object based on the named type.
    * @param string $type The type of module to construct - typically a short name, such as 'chat' or 'blog'
    * @param SloodleSession &$_session The {@link SloodleSession} object to pass to the module on construction, or just null
    * @param mixed $id The identifier of the module instance to load from the database (or null if there is no module data)
    * @return SloodleModule|bool Returns the cosntructed module object, or false if it fails
    */
    function sloodle_load_module($type, &$_session, $id = null)
    {
        global $SLOODLE_MODULE_CLASS;
        
        // Abort if the type is not recognised
        if (!array_key_exists($type, $SLOODLE_MODULE_CLASS)) {
            sloodle_debug("Module load failed - type \"$type\" not recognised.<br/>");
            return false;
        }
        // Construct the object, based on the class name in our array
        $module = new $SLOODLE_MODULE_CLASS[$type]($_session);
        
        // Load the data from the database, if necessary
        if ($id != null) {
            if ($module->load($id)) return $module;
            sloodle_debug("Failed to load module data from database with ID $id.<br/>");
            return false;
        }
        
        // Everything seems OK
        return $module;
    }

?>
