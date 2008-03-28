<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the Sloodle Distributor module sub-type.
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
    * The Sloodle Distributor module class.
    * @package sloodle
    */
    class SloodleModuleDistributor extends SloodleModule
    {
    // DATA //
    
        /**
        * The ID of the distributor.
        * Corresponds to the 'id' field of the 'sloodle_distributor' table.
        * @var int
        * @access public
        */
        var $distrib_id = 0;
    
        /**
        * Timestamp of when this distributor was last updated.
        * @var int
        * @access public
        */
        var $timeupdated = 0;
                
        
    // FUNCTIONS //
    
        /**
        * Constructor
        */
        function SloodleModuleDistributor()
        {
        }
        
        /**
        * Set the update time to the current timestamp
        * @return void
        */
        function use_current_time()
        {
            $this->timeupdated = time();
        }
        
        /**
        * Gets a list of all entries for this Distributor.
        * @return array An array of strings, each string containing the name of an object in this Distributor.
        */
        function get_entries()
        {
            // Get all distributor record entries for this distributor, sorted alphabetically
            $recs = get_records('sloodle_distributor_entry', 'distributorid', $this->distrib_id, 'name');
            if (!$recs) return array();
            // Convert it to an array of strings
            $entries = array();
            foreach ($recs as $r) {
                $entries[] = $recs->name;
            }
            
            return $entries;
        }
        
        /**
        * Request that the specified object be sent to the specified avatar.
        * @param string $objname Name of the object to send
        * @param string $uuid UUID of the avatar to send the object to
        * @return bool True if successful, or false if not.
        */
        function send_object($objname, $uuid)
        {
            // Check that the object exists in this distributor
            if (!record_exists('sloodle_distributor_entry', 'distributorid', $this->distrib_id, 'name', $objname)) return false;
            // Send the XMLRPC request
            //...
        }
    }


?>