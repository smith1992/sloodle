<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the base class for Sloodle modules.
    * (Each module is effectively a sub-type of the Moodle module).
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    /**
    * The base Sloodle module class.
    * @package sloodle
    */
    class SloodleModule
    {
    // DATA //
        
        /**
        * The Sloodle instance number.
        * Corresonds to the 'id' field of the "sloodle" table.
        * Has value 0 if not yet set.
        * @var int
        * @access private
        */
        var $id = 0;
    
        /**
        * The name of this instance.
        * This name is defined by the person who created the instance.
        * Empty if not yet set.
        * @var string
        * @access public
        */
        var $name = '';
        
        /**
        * The intro text of this instance.
        * This intro (or description) is defined by the person who created the instance.
        * Empty if not yet set.
        * @var string
        * @access public
        */
        var $intro = '';
        
        /**
        * The shortname of this Sloodle module type.
        * This type is selected when the instance is first created, and cannot later be changed.
        * Empty if not yet set.
        * @var string
        * @access public
        */
        var $type = '';
                
        
    // FUNCTIONS //
    
        /**
        * Constructor
        */
        function SloodleModule()
        {
        }
                
        /**
        * Gets the full type name of this instance, according to the current language pack
        * @return string
        */
        function get_type_full()
        {
            return get_string("moduletype:{$this->type}", 'sloodle');
        }
    }

?>