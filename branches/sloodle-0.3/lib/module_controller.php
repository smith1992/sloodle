<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the Sloodle Controller module sub-type.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    /** The Sloodle module base. */
    require_once(SLOODLE_LIBROOT.'/module_base.php');
    
    /**
    * The Sloodle Controller module class.
    * @package sloodle
    */
    class SloodleModuleController extends SloodleModule
    {
    // DATA //
    
        /**
        * The ID of the controller.
        * Corresponds to the 'id' field of the 'sloodle_controller' table.
        * @var int
        * @access public
        */
        var $ctrl_id = 0;
    
        /**
        * Indicates whether or not this controller is enabled.
        * @var bool
        * @access public
        */
        var $enabled = false;
            
        /**
        * The prim password for this controller.
        * NOTE: use {@link set_password()} accessors to ensure validity of password.
        * @var string
        * @access public
        */
        var $password = '';
                
        
    // FUNCTIONS //
    
        /**
        * Constructor
        */
        function SloodleModuleController()
        {
        }
        
        /**
        * Enables this controller
        * @return void
        */
        function enable()
        {
            $this->enabled = true;
        }
        
        /**
        * Disables this controller
        * @return void
        */
        function disable()
        {
            $this->enabled = false;
        }
                
        /**
        * Sets the prim password of this instance.
        * Also checks for validity before storing.
        * @param string $password The new prim password
        * @return bool True if successfully stored, or false if the password is invalid
        */
        function set_password($password)
        {
            // Check validity
            if (!sloodle_validate_prim_password($password)) return false;
            // Store it
            $this->password = $password;
            return true;
        }    
    }

?>