<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the primary API class, SloodleSession.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    /**
    * The primary API class, which manages all other parts.
    * @package sloodle
    */
    class SloodleSession
    {
    // DATA //
        
        /**
        * Incoming HTTP request.
        * @var SloodleRequest
        * @access public
        */
        var $request = null;
    
        /**
        * Outgoing response - can be rendered to HTTP or as a string.
        * @var SloodleResponse
        * @access public
        */
        var $response = null;
        
        /**
        * Current user information.
        * @var SloodleUser
        * @access public
        */
        var $user = null;
        
        /**
        * The Sloodle course structure for the course this session is accessing
        * @var SloodleCourse
        * @access public
        */
        var $course = null;
        
        /**
        * The Sloodle module this session relates to, if any.
        * Note: this may be the base Sloodle module class, or a derivative.
        * @var SloodleModule
        * @access public
        */
        var $module = null;
        
        
    // FUNCTIONS //
    
        /**
        * Constructor - initialises members
        */
        function SloodleModule()
        {
        }
        
        
    }

?>