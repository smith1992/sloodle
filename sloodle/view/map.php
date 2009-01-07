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
        global $CFG;
        //// TEMPORARY VALUES FOR TESTING ////
        $startRegion = 'virtuALBA';
        $startPosX = 128;
        $startPosY = 128;

        // Construct the JavaScript for our header
        $js = <<<XXXEODXXX
<script src="http://secondlife.com/apps/mapapi/" type="text/javascript"></script>

<script type="text/javascript">

// Declare our map
var mapInstance;
// Declare our images
// Regular yellow dot
var yellow_dot_image = new Img("{$CFG->wwwroot}/mod/sloodle/map_dot_yellow.gif", 9, 9);
var yellow_icon = new Icon(yellow_dot_image);
var yellow_markers = [yellow_icon, yellow_icon, yellow_icon, yellow_icon, yellow_icon, yellow_icon];
// Yellow dot with a plus sign on it
var yellow_dot_plus_image = new Img("{$CFG->wwwroot}/mod/sloodle/map_dot_yellow_plus.gif", 9, 9);
var yellow_plus_icon = new Icon(yellow_dot_plus_image);
var yellow_plus_markers = [yellow_plus_icon, yellow_plus_icon, yellow_plus_icon, yellow_plus_icon, yellow_plus_icon, yellow_plus_icon];



var dblClick = function(x,y)
{
    try {

        var elemActionCheckPosition = document.getElementById('action_checkposition');
        var elemActionPlaceMarker = document.getElementById('action_placemarker');
        var elemActionPlaceWindow = document.getElementById('action_placewindow');
        var elemActionPlaceMarkerWindow = document.getElementById('action_placemarkerwindow');

   
        if (elemActionPlaceMarker.checked) {
            var marker = new Marker(yellow_markers, new XYPoint(x, y));
            mapInstance.addMarker(marker);

        } else if (elemActionPlaceWindow.checked) {
            var elemText = document.getElementById('placewindowtext');
            var mapWindow = new MapWindow(elemText.value);
            mapInstance.addMapWindow(mapWindow, new XYPoint(x, y));

        } else if (elemActionPlaceMarkerWindow.checked) {
            var marker = new Marker(yellow_plus_markers, new XYPoint(x, y));
            mapInstance.addMarker(marker);
            var elemText = document.getElementById('placemarkerwindowtext');
            var mapWindow = new MapWindow(elemText.value);
            mapInstance.addMarker(marker, mapWindow);
        
        } else if (elemActionCheckPosition.checked) {
            var elemX = document.getElementById('xcoord');
            var elemY = document.getElementById('ycoord');
            elemX.value = x;
            elemY.value = y;
        }

    } catch (e) {
        //alert(e.description);
    }
}

var sglClick = function(x,y)
{
    try {
        var elemX = document.getElementById('xcoord');
        var elemY = document.getElementById('ycoord');

        elemX.value = x;
        elemY.value = y;
    } catch (e) {
    }
}

function sloodle_load_map()
{

    try {

        mapInstance = new SLMap(document.getElementById('map-container'), {hasZoomControls: false, hasPanningControls: false, doubleClickHandler: dblClick});
        mapInstance.centerAndZoomAtSLCoord(new XYPoint(1000,1000), 1);


    
    } catch (e) {
       //alert("An error occurred while trying to load the map: " + e.description);
    }
}

function jumpMap()
{
    try {
        var elemX = document.getElementById('xcoord');
        var elemY = document.getElementById('ycoord');

        mapInstance.centerAndZoomAtSLCoord(new XYPoint(elemX.value, elemY.value), 1);
    } catch (e) {
        alert('Error occurred: ' + e.description);
    }
}


function zoomIn()
{
    mapInstance.zoomIn();

    try {
        var elemZoomIn = document.getElementById('zoomin');
        var elemZoomOut = document.getElementById('zoomout');
        
        elemZoomIn.disabled = false;
        elemZoomOut.disabled = false;

        if (mapInstance.getCurrentZoomLevel() == 1) elemZoomIn.disabled = true;
    } catch (e) {
    }
}

function zoomOut()
{
    mapInstance.zoomOut();

    try {
        var elemZoomIn = document.getElementById('zoomin');
        var elemZoomOut = document.getElementById('zoomout');
        
        elemZoomIn.disabled = false;
        elemZoomOut.disabled = false;

        if (mapInstance.getCurrentZoomLevel() == 6) elemZoomOut.disabled = true;
    } catch (e) {
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

echo <<<XXXEODXXX

<div style="text-align:center;">
Click and drag the map to pan around.<br/>
Zoom:
<input type="button" value="+" style="font-size:24px; font-weight:bold;" onclick="zoomIn();" id="zoomin" />
<input type="button" value="-" style="font-size:24px; font-weight:bold;" onclick="zoomOut();" id="zoomout" />
</div>
XXXEODXXX;

        echo '<div id="map-container" style="width:500px; height:500px; margin-left:auto; margin-right:auto;"></div>',"\n";
        echo '<script type="text/javascript">sloodle_load_map();</script>',"\n";


        echo <<<XXXEODXXX
<div style="text-align:center;">

<table style="border-style:none; margin-left:auto; margin-right:auto; width:50%; text-align:left;"><tr><td>
<form action="" method="get" onsubmit="return false;">
<fieldset>
<p>Select an action to perform, enter any required data, and then double-click the map (note: any changes you make are currently TEMPORARY only... they will not appear for anybody else, and will be lost if you navigate to another page).</p>

<input type="radio" checked="checked" id="action_checkposition" name="action" value="checkposition" title="Double click on the map to check the exact coordinates of that position" />
Check Position
<br/>

<input type="radio" id="action_placemarker" name="action" value="placemarker" title="Double click on the map to place a simpler marker" />
Place Marker
<br/>

<input type="radio" id="action_placewindow" name="action" value="placewindow" title="Double click on the map to place a window with text in it" />
Place Text Window: 
<input type="text" id="placewindowtext" name="placewindowtext" size="50" maxlength="255" value="This text will appear in a map window" />
<br/>

<input type="radio" id="action_placemarkerwindow" name="action" value="placemarkerwindow" title="Double click on the map to place a marker which opens a text window when clicked" />
Place Marker Window: 
<input type="text" id="placemarkerwindowtext" name="placemarkerwindowtext" size="50" maxlength="255" value="This text will appear when a marker is clicked" />
<br/>

</fieldset>
</form>
</td></tr>

<tr><td>
<form action="" method="get" onsubmit="return false;">
<fieldset>
<p>Enter a region position to jump to it on the map. When using the "Check Position" action above, the double-clicked location will appear in these boxes.</p>
<label for="xcoord">X: </label> <input type="text" size="14" id="xcoord" name="xcoord" /><br/>
<label for="ycoord">Y: </label> <input type="text" size="14" id="ycoord" name="ycoord" /><br/>
<input type="submit" value="Jump" onclick="jumpMap(); return false;" />
</fieldset>
</form>


</td></tr>
</table>

</div>
XXXEODXXX;
    }

}


?>
