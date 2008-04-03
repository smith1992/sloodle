<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines a chat module for Sloodle.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    /** The Sloodle module base. */
    require_once(SLOODLE_LIBROOT.'/module_base.php');
    /** General Sloodle functions. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    
    /**
    * The Sloodle chat module class.
    * @package sloodle
    */
    class SloodleModuleChat extends SloodleModule
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
        * Internal only - Moodle chat module instance database object.
        * Corresponds to one record from the Moodle 'chat' table.
        * @var object
        * @access private
        */
        var $moodle_chat_instance = null;

                
        
    // FUNCTIONS //
    
        /**
        * Constructor
        */
        function SloodleModuleChat()
        {
        }
        
        /**
        * Loads data from the database.
        * Note: even if the function fails, it may still have overwritten some or all existing data in the object.
        * @param mixed $id The site-wide unique identifier for all modules. Type depends on VLE. On Moodle, it is an integer course module identifier ('id' field of 'course_modules' table)
        * @return bool True if successful, or false otherwise
        */
        function load_from_db($id)
        {
            // Make sure the ID is valid
            if (!is_int($id) || $id <= 0) return false;
            
            // Fetch the course module data
            if (!($this->cm = get_record('course_modules', 'id', $id))) return false;
            // Load from the primary table: chat instance
            if (!($this->moodle_chat_instance = get_record('chat', 'id', $this->cm->instance))) return false;
            
            return true;
        }
        
        
        /**
        * Gets a recent history of messages from the chatroom.
        * @param int $time How far back to search the database (in seconds) (default: 1 minute)
        * @return array A numeric array of {@link SloodleChatMessage} object, in order of oldest to newest
        */
        function get_chat_history($time = 60)
        {
            // Calculate the earliest acceptable timestamp
            $earliest = time() - $time;
            // Get all message records for this chatroom
            $recs = get_records_select('chat_messages', "`chatid` = {$this->moodle_chat_instance->id} AND `timestamp` >= $earliest", 'timestamp ASC');
            if (!$recs) return array();
            
            // We'll need to lookup all the user data.
            // Cache the user records so we don't need to duplicate searches
            $usercache = array();
            
            
            // Prepare an array of chat message objects
            $chatmessages = array();
            // Go through each result
            foreach ($recs as $r) {
                // We need to get the author's name
                $author_name = '';
                if (isset($usercache[$r->userid])) {
                    // We already have the data cached
                    $author_name = $usercache[$r->userid];
                } else {
                    // We need to lookup for more data
                    $curuser = get_record('user', 'id', $r->userid);
                    if (!$curuser) $usercache[$r->userid] = '???';
                    else $usercache[$r->userid] = $curuser->firstname.' '.$curuser->lastname;
                }
                
                // Construct and add a message object
                $chatmessages[] = new SloodleChatMessage($r->message, $author_name, (int)$r->userid, $r->timestamp);
            }
            
            return $chatmessages;
        }
        
        
        /**
        * Adds a new chat message.
        * <b>Note:</b> if the $author parameter is omitted or invalid, then the function will attempt to use the {@link SloodleUser} member
        * of the current {@link SloodleSession} object;
        * If that is unavailable, then it will try to use the user currently 'logged-in' to the VLE (i.e. the $USER variable in Moodle).
        * If all else fails, it will attempt to attribute the message to the guest user.
        * @param string $message The text of the message.
        * @param mixed $author ID of the author. Type depends on VLE.
        * @param int $timestamp Timestamp of the message. If omitted or <= 0 then the current timestamp is used
        * @return bool True if successful, or false otherwise
        */
        function add_message($message, $author = null, $timestamp = null)
        {
            global $USER;
            
            // Ignore empty messages
            if (empty($message)) return false;
            // Make sure the message is safe
            $message = addslashes(clean_text(stripslashes($message)));
            
            // Is the author ID invalid?
            if (!is_int($author) || $author <= 0) {
                // Yes
                $author = null;
                
                // Do we have a session parameter?
                if (!is_null($this->_session)) {
                    // Attempt to get the current Sloodle user data
                    if (!is_null($this->_session->user) && $this->_session->user->get_user_id() > 0) {
                        // Store it
                        $author = $this->_session->user->get_user_id();
                    }
                }
                
                // Did that fail?
                if (is_null($author)) {
                    // Yes - try use to currently logged-in user
                    if (isset($USER) && $USER->id > 0) {
                        $author = $USER->id;
                    } else {
                        // Just use the guest account
                        $guest = guest_user();
                        $author = $guest->id;
                    }
                }
            }
            // Prepare the timestamp variable if necessary
            if (is_null($timestamp)) $timestamp = time();
            
            // Create a chat message record object
            $rec = new stdClass();
            $rec->chatid = $this->moodle_chat_instance->id;
            $rec->userid = $author;
            $rec->message = $message;
            $rec->timestamp = $timestamp;
            // Attempt to insert the chat message
            $result = insert_record('chat_messages', $rec);
            if (!$result) return false;
            
            // We successfully added a chat message
            // If possible, add an appropriate side effect code to our response
            if (!is_null($this->_session)) {
                $this->_session->response->add_side_effect(10101);
            }
            
            return true;
        }
        
        
        
        
        
    // ACCESSORS //
    
        /**
        * Gets the name of this module instance.
        * @return string The name of this controller
        */
        function get_name()
        {
            return $this->moodle_chat_instance->name;
        }
        
        /**
        * Gets the intro description of this module instance, if available.
        * @return string The intro description of this controller
        */
        function get_intro()
        {
            return $this->moodle_chat_instance->intro;
        }
        
        /**
        * Gets the identifier of the course this controller belongs to.
        * @return mixed Course identifier. Type depends on VLE. (In Moodle, it will be an integer).
        */
        function get_course_id()
        {
            return (int)$this->moodle_chat_instance->course;
        }
        
        /**
        * Gets the time at which this instance was created, or 0 if unknown.
        * @return int Timestamp
        */
        function get_creation_time()
        {
            return 0;
        }
        
        /**
        * Gets the time at which this instance was last modified, or 0 if unknown.
        * @return int Timestamp
        */
        function get_modification_time()
        {
            return $this->moodle_chat_instance->timemodified;
        }
        
        
        /**
        * Gets the short type name of this instance.
        * @return string
        */
        function get_type()
        {
            return 'chat';
        }

        /**
        * Gets the full type name of this instance, according to the current language pack, if available.
        * Note: should be overridden by sub-classes.
        * @return string Full type name if possible, or the short name otherwise.
        */
        function get_type_full()
        {
            return get_string('modulename', 'chat');
        }

    }
    
    
    /**
    * Represents a single chat message
    * @package sloodle
    */
    class SloodleChatMessage
    {
        /**
        * Constructor - initialises members.
        * @param string $message The chat message
        * @param string $author_name The name of the author
        * @param mixed $author_id The ID of the author
        * @param int $timestamp The timestamp of the message
        */
        function SloodleChatMessage($message, $author_name, $author_id, $timestamp)
        {
            $this->message = $message;
            $this->author_name = $author_name;
            $this->author_id = $author_id;
            $this->timestamp = $timestamp;
        }
        
        /**
        * Accessor - set all members in a single call.
        * @param string $message The chat message
        * @param string $author_name The name of the author
        * @param mixed $author_id The ID of the author
        * @param int $timestamp The timestamp of the message
        */
        function set($message, $author_name, $author_id, $timestamp)
        {
            $this->message = $message;
            $this->author_name = $author_name;
            $this->author_id = $author_id;
            $this->timestamp = $timestamp;
        }
    
        /**
        * The text of the message.
        * @var string
        * @access public
        */
        var $message = '';
        
        /**
        * Name of the message author.
        * @var string
        * @access public
        */
        var $author_name = '';
        
        /**
        * Unique ID of the message author.
        * Type depends on VLE. In Moodle, it is an integer corresponding to the 'id' field of the 'user' table.
        * @var mixed
        * @access public
        */
        var $author_id = 0;
        
        /**
        * Timestamp of the message.
        * @var int
        * @access public
        */
        var $timestamp = 0;
    }


?>