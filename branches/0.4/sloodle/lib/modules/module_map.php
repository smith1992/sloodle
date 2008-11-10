<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines a map resource module for Sloodle.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    /** The Sloodle module base. */
    require_once(SLOODLE_LIBROOT.'/modules/module_base.php');
    /** General Sloodle functions. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    
    /**
    * The Sloodle map resource module class.
    * @package sloodle
    */
    class SloodleModuleMap extends SloodleModule
    {
    // DATA //
    
        /**
        * Internal for Moodle only - course module instance.
        * Corresponds to one record from the Moodle 'course_modules' table.
        * @var object
        * @access private
        */
        var $cm = null;
    
        /**
        * Internal only - Sloodle module instance database object.
        * Corresponds to one record from the Moodle 'mdl_sloodle' table.
        * @var object
        * @access private
        */
        var $sloodle_instance = null;

                
        
    // FUNCTIONS //
    
        /**
        * Constructor
        */
        function SloodleModuleMap(&$_session)
        {
            $constructor = get_parent_class($this);
            parent::$constructor($_session);
        }
        
        /**
        * Loads data from the database.
        * Note: even if the function fails, it may still have overwritten some or all existing data in the object.
        * @param mixed $id The site-wide unique identifier for all modules. Type depends on VLE. On Moodle, it is an integer course module identifier ('id' field of 'course_modules' table)
        * @return bool True if successful, or false otherwise
        */
        function load($id)
        {
            // Make sure the ID is valid
            $id = (int)$id;
            if ($id <= 0) return false;
            
            // Fetch the course module data
            if (!($this->cm = get_coursemodule_from_id('sloodle', $id))) {
                sloodle_debug("Failed to load course module instance #$id.<br/>");
                return false;
            }
            // Make sure the module is visible
            if ($this->cm->visible == 0) {
                sloodle_debug("Error: course module instance #$id not visible.<br/>");
                return false;
            }
            
            // Load from the primary table: sloodle instance
            if (!($this->sloodle_instance = get_record('sloodle', 'id', $this->cm->instance))) {
                sloodle_debug("Failed to load Sloodle module with instance ID #{$cm->instance}.<br/>");
                return false;
            }
            
            return true;
        }
        
       
        /**
        * Outputs the client-side script required to setup map viewing.
        * @return void
        */
        function print_script()
        {
            // Include our external script
            echo '<script src="http://secondlife.com/apps/mapapi/" type="text/javascript"></script>',"\n";

            // Fetch the parameters which affect this map display
            $startRegion = 'virtuALBA';
            $startPosX = 128;
            $startPosY = 128;

            // Output the script load function
            echo <<<XXXEODXXX
<script type="text/javascript">

function sloodle_load_map()
{
    try {
        var mapInstance = new SLMap(document.getElementById('map-container'), {hasZoomControls: true});
        mapInstance.centerAndZoomAtSLCoord(new XYPoint(1000,1000),3);
        //mapInstance.centerAndZoomAtSLCoord(new SLPoint('{$startRegion}', {$startPosX}, {$startPosY}));
        setZoom(1);
    
    } catch (e) {
        //alert("An error occurred while trying to load the map: " + e.description);
    }
}

</script>

XXXEODXXX;
        }

        /**
        * Outputs the map itself (usually just a 'div' container).
        * @return void
        */
        function print_map()
        {
            echo '<div id="map-container" style="width:500px; height:500px; margin-left:auto; margin-right:auto;"></div>',"\n";
            echo '<script type="text/javascript">sloodle_load_map();</script>',"\n";
        } 
        
    // ACCESSORS //
    
        /**
        * Gets the name of this module instance.
        * @return string The name of this module
        */
        function get_name()
        {
            return $this->sloodle_instance->name;
        }
        
        /**
        * Gets the intro description of this module instance, if available.
        * @return string The intro description of this controller
        */
        function get_intro()
        {
            return $this->sloodle_instance->intro;
        }
        
        /**
        * Gets the identifier of the course this controller belongs to.
        * @return mixed Course identifier. Type depends on VLE. (In Moodle, it will be an integer).
        */
        function get_course_id()
        {
            return (int)$this->sloodle_instance->course;
        }
        
        /**
        * Gets the time at which this instance was created, or 0 if unknown.
        * @return int Timestamp
        */
        function get_creation_time()
        {
            return (int)$this->sloodle_instance->timecreated;
        }
        
        /**
        * Gets the time at which this instance was last modified, or 0 if unknown.
        * @return int Timestamp
        */
        function get_modification_time()
        {
            return (int)$this->sloodle_instance->timemodified;
        }
        
        
        /**
        * Gets the short type name of this instance.
        * @return string
        */
        function get_type()
        {
            return SLOODLE_TYPE_MAP;
        }

        /**
        * Gets the full type name of this instance, according to the current language pack, if available.
        * Note: should be overridden by sub-classes.
        * @return string Full type name if possible, or the short name otherwise.
        */
        function get_type_full()
        {
            return get_string('moduletype:'.SLOODLE_TYPE_MAP, 'sloodle');
        }

    }
?>
