<?php
    // This file is originally part of the SLOODLE project (www.sloodle.org)
    // Modified for the SLOODLE for Schools project.
   
    /**
    * This file defines a data structure which SLOODLE can use to access course data.
    * A lot of the purpose it served in SLOODLE is redundant in SLOODLE for Schools, so a lot of code has been removed.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008-10 Sloodle community (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
   
    /** Include the general Sloodle library. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    /** Include the Sloodle controller structure. */
    require_once(SLOODLE_LIBROOT.'/controller.php');
    /** Include the layout profile management stuff. */
    require_once(SLOODLE_LIBROOT.'/layout_profile.php');
   
    /**
    * The Sloodle course data class
    * @package sloodle
    */
    class SloodleCourse
    {
    // DATA //
   
        /**
        * The database object of the course to which this object relates.
        * Corresponds to the "course" table in Moodle.
        * Is null if not yet set
        * @var object
        * @access private
        */
        var $course_object = null;
   
        // Removed members:
        //var $sloodle_course_data = null;
        //var $controller = null;
       
       
    // FUNCTIONS //
   
        /**
        * Constructor
        */
        function SloodleCourse()
        {
            $this->controller = new SloodleController();
        }
       
        /**
        * Determines whether or not course data has been loaded.
        * @return bool
        */
        function is_loaded()
        {
            if (empty($this->course_object)) return false;
            return true;
        }
       
        /**
        * Gets the identifier of the course in the VLE.
        * @return mixed Course identifier. Type depends on VLE. (In Moodle, it will be an integer).
        */
        function get_course_id()
        {
            return (int)$this->course_object->id;
        }
       
        /**
        * Gets the VLE course object.
        * WARNING: this should only be used when ABSOLUTELY necessary.
        * The contents are specific to the VLE.
        * @return mixed Type and content depends upon VLE. In Moodle, it is an object representing a record from the 'course' table.
        */
        function get_course_object()
        {
            return $this->course_object;
        }
       
       
        /**
        * Gets the short name of this course in the VLE.
        * @return string Shortname of this course.
        */
        function get_short_name()
        {
            return $this->course_object->shortname;
        }
       
        /**
        * Gets the full name of this course in the VLE.
        * @return string Fullname of this course.
        */
        function get_full_name()
        {
            return $this->course_object->fullname;
        }
       
        /**
        * Determines whether or not the course is available.
        * Checks that the course has not been disabled or hidden etc..
        * @return bool True if the course is available
        */
        function is_available()
        {
            // Check visbility
            if (empty($this->course_object->visible)) return false;
           
            return true;
        }
       
        /**
        * Reads fresh data into the structure from the database.
        * Fetches Moodle and Sloodle data about the course specified.
        * If necessary, it creates a new Sloodle entry with default settings.
        * Returns true if successful, or false on failure.
        * @param mixed $course Either a unique course ID, or a course data object. If the former, then VLE course data is read from the database. Otherwise, the data object is used as-is.
        * @return bool
        */
        function load($course)
        {
            // Reset everything
            $this->course_object = null;
       
            // Check what we are dealing with
            if (is_int($course)) {
                // It is a course ID - make sure it's valid
                if ($course <= 0) return false;
                // Load the course data
                $this->course_object = get_record('course', 'id', $course);
                if (!$this->course_object) {
                    $this->course_object = null;
                    return false;
                }
            } else if (is_object($course)) {
                // It is an object - make sure it has an ID
                if (!isset($course->id)) return false;
                $this->course_object = $course;
            } else {
                // Don't know what it is - do nothing
                return false;
            }
            
            return true;
        }
       
        /**
        * Writes current Sloodle course data back to the database.
        * Requires that a course structure has already been retrieved.
        * @return bool True if successful, or false on failure
        */
        function write()
        {
            if (empty($this->course_object) || $this->course_object->id <= 0) return false;
            return update_record('course', $this->course_object);
        }
       
        /**
        * Gets an array associating layout ID's to names
        * @return array
        */
        function get_layout_names()
        {
            // Fetch the layout records
            $layouts = get_records('sloodle_layout', 'course', $this->course_object->id, 'name');
            if (!$layouts) return array();
            // Construct the array of names
            $layout_names = array();
            foreach ($layouts as $l) {
                $layout_names[$l->id] = $l->name;
            }
           
            return $layout_names;
        }


        /**
        * Gets all the entries in the named layout.
        * @param string $name The name of the layout to query
        * @return array|bool A numeric array of {@link SloodleLayoutEntry} objects if successful, or false if the layout does not exist
        */
        function get_layout_entries($name)
        {
            // Attempt to find the relevant layout
            $layout = get_record('sloodle_layout', 'course', $this->course_object->id, 'name', $name);
            if (!$layout) return false;
           
            return $this->get_layout_entries_for_layout_id($layout->id);
        }

        /**
        * Gets all the entries in the named layout.
        * @param string $name The name of the layout to query
        * @return array|bool A numeric array of {@link SloodleLayoutEntry} objects if successful, or false if the layout does not exist
        */
        function get_layout_entries_for_layout_id($id)
        {
            // Fetch all entries
            $recs = get_records('sloodle_layout_entry', 'layout', $id);
            if (!$recs) return array();
           
            // Construct the array of SloodleLayoutEntry objects
            $entries = array();
            foreach ($recs as $r) {
                $entry = new SloodleLayoutEntry($r);
                $entries[] = $entry;
            }
           
            return $entries;
        }

        /**
        * Gets SloodleLayout objects for all the layouts in the course.
        */
        function get_layouts() {
            // Fetch the layout records
            $layoutrecs = get_records('sloodle_layout', 'course', $this->course_object->id, 'name');
            $layouts = array();

            if (!$layoutrecs) return array();

            foreach ($layoutrecs as $r) {
                $layouts[] = new SloodleLayout($r);
            }

            return $layouts;
        }
       
        /**
        * Deletes the named layout.
        * @param string $name The name of the layout to delete
        * @return void
        */
        function delete_layout($name)
        {
            // Attempt to find the relevant layout
            $layout = get_record('sloodle_layout', 'course', $this->course_object->id, 'name', $name);
            if (!$layout) return;
           
            // Delete all related entries
            delete_records('sloodle_layout_entry', 'layout', $layout->id);
            // Delete the layout itself
            delete_records('sloodle_layout', 'course', $this->course_object->id, 'name', $name);
        }
       
        /**
        * Save the given entries in the named profile.
        * @param string $name The name of the layout to query
        * @param array $entries A numeric array of {@link SloodleLayoutEntry} objects to store
        * @param bool $add (Default: false) If true, then the entries will be added to the layout instead of replacing existing entries
        * @return bool True if successful, or false otherwise
        */
        function save_layout($name, $entries, $add = false)
        {
            // Attempt to find the relevant layout
            $layout = get_record('sloodle_layout', 'course', $this->course_object->id, 'name', $name);
            $lid = 0;
            if ($layout) {
               $lid = $layout->id;
            }

            return $this->save_layout_by_id($lid, $name, $entries, $add);
        }
         
        /**
        * Save the given entries in the profile specified by id.
        * @param string $id The id of the layout to query. 0 to add a new layout.
        * @param string $name The new name of the layout.  
        * @param array $entries A numeric array of {@link SloodleLayoutEntry} objects to store
        * @param bool $add (Default: false) If true, then the entries will be added to the layout instead of replacing existing entries
        * @return bool True if successful, or false otherwise
        */
        function save_layout_by_id($id, $name, $entries, $add = false)
        {
            // Attempt to find the relevant layout
            if ($id > 0) {

/*
                // Delete all existing entries if necessary
                // This will happen when we save
                // TODO: make add-only functionality for backwards compatibility
                if (!$add) {
                        delete_records('sloodle_layout_entry', 'layout', $layout->id);
                }
*/

                $layout = $this->get_layout($id);
                $layout->name = $name;
                $layout->timeupdated = time();
                $layout->entries = $entries;
                $layout->populate_entries_from_active_objects(); // where the records have objectuuids set, copy their settings
                if (!$layout->update()) {
                   return false;
                }
                $this->layout = $layout;
            } else {
                $layout = new SloodleLayout();
                $layout->name = $name;
                $layout->course = $this->course_object->id;
                $layout->timeupdated = time();
                $layout->entries = $entries;
                $layout->populate_entries_from_active_objects();
                $layout->id = $layout->insert();  #insert_record('sloodle_layout', $layout);
                if (!$layout->id) return false;
                $this->layout = $layout;
            }
           
/*
            // This should have been done by the layout
            // Insert each new entry
            $success = true;
            foreach ($entries as $e) {
                $rec = new stdClass();
                $rec->layout = $layout->id;
                $rec->name = $e->name;
                $rec->position = $e->position;
                $rec->rotation = $e->rotation;

                // TODO EDE: If there's an objectuuid for the entry, copy the entries from the active object table to the layout config table
                if ($objectuuid != '') {
                   $rec->copy_active_object_with_uuid($e->objectuuid);
                }
               
                $entry_id = insert_record('sloodle_layout_entry', $rec);
               
            }
*/
           
            return $layout->id;

        }

        function get_layout($layoutid) {

            $rec = get_record('sloodle_layout', 'course', $this->course_object->id, 'id', $layoutid);
            return new SloodleLayout($rec);

        }
   
    }
?>
