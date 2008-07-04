<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines structures for managing Sloodle layout profiles.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    
    /**
    * Stores data for a single entry in a layout profile.
    * Used with {@link SloodleCourse}
    * @package sloodle
    */
    class SloodleLayoutEntry
    {
    // DATA //
    
        /**
        * The name of the object this entry represents.
        * @var string
        * @access public
        */
        var $name = '';
        
        /**
        * The position of the object, as a 3d vector, in string format "<x,y,z>"
        * @var string
        * @access public
        */
        var $position = '<0.0,0.0,0.0>';
        
        /**
        * The rotation of the object, as a 3d vector of Euler angles, in string format "<x,y,z>"
        * @var string
        * @access public
        */
        var $rotation = '<0.0,0.0,0.0>';
        
        
    // FUNCTIONS //
    
        /**
        * Sets the position as separate X,Y,Z components
        * @param float $x The X component to set
        * @param float $y The Y component to set
        * @param float $z The Z component to set
        * @return void
        */
        function set_position_xyz($x, $y, $z)
        {
            $this->position = "<$x,$y,$z>";
        }
        
        /**
        * Sets the rotation as separate X,Y,Z components
        * @param float $x The X component to set
        * @param float $y The Y component to set
        * @param float $z The Z component to set
        * @return void
        */
        function set_rotation_xyz($x, $y, $z)
        {
            $this->rotation = "<$x,$y,$z>";
        }
        
    }
    

?>