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
    require_once(SLOODLE_LIBROOT.'/module_base.php');
    
    /** Distributor module. */
    include_once(SLOODLE_LIBROOT.'/module_distributor.php');
    /** Chat module. */
    include_once(SLOODLE_LIBROOT.'/module_chat.php');
    
    //... include more module types here!
    
    
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
        if (!in_array($type, $SLOODLE_MODULE_CLASS)) return false;
        // Construct the object, based on the class name in our array
        $module = new $SLOODLE_MODULE_CLASS[$type](&$_session);
        
        // Load the data from the database, if necessary
        if ($id != null) {
            if ($module->load_from_db($id)) return $module;
            return false;
        }
        
        // Everything seems OK
        return $module;
    }

?>