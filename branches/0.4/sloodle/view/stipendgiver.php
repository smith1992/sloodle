<?php
/**
* Defines a class for viewing the SLOODLE Distributor module in Moodle.
* Derived from the module view base class.
*
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
*
* @contributor Peter R. Bloomfield
*/

/** The base module view class */
require_once(SLOODLE_DIRROOT.'/view/base/base_view_module.php');



/**
* Class for rendering a view of a Distributor module in Moodle.
* @package sloodle
*/
class sloodle_view_stipendgiver extends sloodle_base_view_module
{
    /**
    * SLOODLE data about a Distributor, retrieved directly from the database (table: sloodle_distributor)
    * @var object
    * @access private
    */
    var $stipend = false;


    /**
    * Constructor.
    */
    function sloodle_base_view_module()
    {
    }

    /**
    * Processes request data to determine which Distributor is being accessed.
    */
    function process_request()
    {
        // Process the basic data
        parent::process_request();
        // Grab the STIPEND data
        if (!$this->stipend = get_record('sloodle_stipendgiver', 'sloodleid', $this->sloodle->id)) error('Failed to get SLOODLE Stipend data.');
    }

    /**
    * Process any form data which has been submitted.
    */
    function process_form()
    {
    }


    /**
    * Render the view of the Stipend Giver.
    */
    function render()              
    {
        global $CFG, $USER;
    
        // Fetch a list of all distributor entries
        //$table, $field='', $value='', $sort='', $fields='*'
          print '<h3 style="color:green;text-align:center;">H</h3><br><br>';
            
        $entries = get_records('sloodle_stipendgiver_transactions', 'stipendgiverid', $this->stipend->id, 'receiverid,receivername');
        // If the query failed, then assume there were simply no items available
        if (!is_array($entries)) $entries = array();
        $numitems = count($entries);
        
            
            print_box_start('generalbox boxaligncenter boxwidthnarrow centerpara');
            foreach ($entries as $entry)
                print '<h3 style="color:green;text-align:center;">'.$entry->receivername.'</h3><br><br>';
            
             
            print_box_end();
        
        
        // // ----------- // //
        

        // If there are no items in the distributor, then simply display an error message
        if ($numitems < 1) print_box('<span style="font-weight:bold; color:red;">'.get_string('sloodleobjectstipend:nostipends','sloodle').'</span>', 'generalbox boxaligncenter boxwidthnormal centerpara');
        //error(get_string('sloodleobjectdistributor:noobjects','sloodle'));
        // If there is no XMLRPC channel specified, then display a warning message
                  
        
    }

}


?>
