<?php
/**
* Defines a class for viewing the SLOODLE Map module in Moodle.
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
* Class for rendering a view of a Map module in Moodle.
* @package sloodle
*/
class sloodle_view_map extends sloodle_base_view_module
{

    /**
    * Constructor.
    */
    function sloodle_view_map()
    {
    }

    /**
    * Processes request data to determine which Map is being accessed.
    */
    function process_request()
    {
        // Process the basic data
        parent::process_request();
        // Nothing else to get just now
    }

    /**
    * Process any form data which has been submitted.
    */
    function process_form()
    {
    }

    /**
    * Print the page header (requires special scripts to be added into the header)
    */
    function print_header()
    {
        //// TEMPORARY VALUES FOR TESTING ////
        $startRegion = 'virtuALBA';
        $startPosX = 128;
        $startPosY = 128;

        // Construct the JavaScript for our header
        $js = <<<XXXEODXXX
<script src="http://secondlife.com/apps/mapapi/" type="text/javascript"></script>

<script type="text/javascript">

function sloodle_load_map()
{
    try {
        var mapInstance = new SLMap(document.getElementById('map-container'), {hasZoomControls: true});
        mapInstance.centerAndZoomAtSLCoord(new XYPoint(1000,1000),3);
        //mapInstance.centerAndZoomAtSLCoord(SLPoint('{$startRegion}', {$startPosX}, {$startPosY}));
        //setCurrentZoomLevel(1);
    
    } catch (e) {
       //alert("An error occurred while trying to load the map: " + e.description);
    }
}


</script>
XXXEODXXX;

        // Print the header, including our JavaScript
        $editbuttons = '';
        if ($this->canedit) $editbuttons = update_module_button($this->cm->id, $this->course->id, get_string('modulename','sloodle'));
        $navigation = "<a href=\"index.php?id={$this->course->id}\">".get_string('modulenameplural','sloodle')."</a> ->";
        print_header_simple(format_string($this->sloodle->name), "", "$navigation ".format_string($this->sloodle->name), "", $js, true, "", navmenu($this->course, $this->cm));
    }

    /**
    * Render the view of the Map.
    */
    function render()
    {
        global $CFG;

        echo '<div id="map-container" style="width:500px; height:500px; margin-left:auto; margin-right:auto;"></div>',"\n";
        echo '<script type="text/javascript">sloodle_load_map();</script>',"\n";
    }

}


?>
