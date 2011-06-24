<?php
    
    /**
    * This file is part of SLOODLE Tracker.
    * Copyright (c) 2009 Sloodle
    *
    * SLOODLE Tracker is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 3 of the License, or
    * (at your option) any later version.
    *
    * SLOODLE Tracker is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * If not, see <http://www.gnu.org/licenses/>
    *
    * Contributors:
    * Peter R. Bloomfield  
    * Julio Lopez (SL: Julio Solo)
    * Michael Callaghan (SL: HarmonyHill Allen)
    * Kerri McCusker  (SL: Kerri Macchi)
    *
    * A project developed by the Serious Games and Virtual Worlds Group.
    * Intelligent Systems Research Centre.
    * University of Ulster, Magee	
    */
    
    /** The Sloodle module base. */
    require_once(SLOODLE_LIBROOT.'/modules/module_base.php');
    /** General Sloodle functions. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    
    /**
    * The Sloodle SLOODLE Tracker module class.
    * @package sloodle
    */
    class SloodleModuleTracker extends SloodleModule
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
        function SloodleModuleTracker(&$_session)
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
        * Gets a list of all tools for this SLOODLE Tracker.
        * @return an array of strings, each string containing the name of one tool.
        */
        function get_objects()
        {
            // Get all tool record entries for this SLOODLE Tracker, sorted by name
            $recs = get_records('sloodle_activity_tool', 'trackerid', $this->sloodle_instance->id, 'name');
            if (!$recs) return array();
            // Convert it to an array of strings
            $entries = array();
            foreach ($recs as $r) {
                $entries[] = stripslashes($recs->name);
            }
            
            return $entries;
        }
        
        
        /**
        * Sets the list of tools/tasks for this SLOODLE Tracker.
        * @return bool True if successful, or false if not
        */
        function record_object($uuid,$name,$type,$trackerid,$description,$taskname)
        {
            $timestamp = time();
            
            $result = true;
            
            $entry = get_record('sloodle_activity_tool', 'uuid', $uuid);
            // The tool doesn't exist in the database
            if (!$entry) {
            
            	// Construct the new record
            	$entry = new stdClass();
           		$entry->uuid = $uuid;
            	$entry->name = $name;
            	$entry->type = $type;
            	$entry->trackerid = $trackerid;
            	$entry->description = $description;
            	$entry->taskname = $taskname;
            	$entry->timeupdated = $timestamp;
         	
            	// Insert it
            	$entry->id = insert_record('sloodle_activity_tool', $entry);
            	if (!$entry->id) $result = false;
            }
            // The tool already exists, it has to be updated
            else{
            	$entry->name = $name;
            	$entry->type = $type;
            	$entry->trackerid = $trackerid;
            	$entry->description = $description;
            	$entry->taskname = $taskname;
            	$entry->timeupdated = $timestamp;
	            
	            // Update it
            	if (!update_record('sloodle_activity_tool', $entry)) $result = false;
        	}
            return $result;
        }
       
       
        /**
        * Sets the actions completed by an avatar in Second Life. An avatar interacts with an object in SL, and this action is recorded
        * @param $trackerid: The site-wide unique identifier for this Second Life Tracker module
        * @param $objuudi: The SL unique identifier for the object/tool (task)
        * @param $avuuid: The SL unique identifier for the avatar
        * @return bool True if successful, or false if not
        */
        function record_action($trackerid,$objuuid,$avuuid)
        {
            $timestamp = time();
            
            $result = true;
            // Has the avatar already interact with this object?
            $entry = get_record('sloodle_activity_tracker', 'objuuid', $objuuid, 'avuuid',$avuuid);
            // If not, the new action is recorded
            if (!$entry) {

	            // Construct the new record
	            $entry = new stdClass();
	            $entry->objuuid = $objuuid;
	            $entry->avuuid = $avuuid;
	            $entry->trackerid = $trackerid;
	         	$entry->timeupdated = $timestamp;
	            
	            // Insert it
	            $entry->id = insert_record('sloodle_activity_tracker', $entry);
	            if (!$entry->id) $result = false;
            }
            // If yes, "old" interaction is updated
        	else{
                $entry->trackerid = $trackerid;        	 
        	    $entry->timeupdated = $timestamp;
				if (!update_record('sloodle_activity_tracker', $entry)) $result = false;
			}
            return $result;
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
            return SLOODLE_TYPE_TRACKER;
        }

        /**
        * Gets the full type name of this instance, according to the current language pack, if available.
        * Note: should be overridden by sub-classes.
        * @return string Full type name if possible, or the short name otherwise.
        */
        function get_type_full()
        {
            return get_string('moduletype:'.SLOODLE_TYPE_TRACKER, 'sloodle');
        }

    }
?>
