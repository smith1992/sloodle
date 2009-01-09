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
require_once(SLOODLE_LIBROOT.'/sloodle_session.php');


/**
* Class for rendering a view of a Map module in Moodle.
* @package sloodle
*/
class sloodle_view_map extends sloodle_base_view_module
{
    /**
    * A SLOODLE map module object to wrap the basic map functionality.
    * @var SloodleModuleMap
    * @access private
    */
    var $sloodle_map_module = null;
    
    /**
    * Numeric array of locations, obtained from the {@link SloodleModuleMap} object.
    * @var array
    * @access private
    */
    var $locations = array();

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
        
        // Obtain a map module
        $tempsession = new SloodleSession(false);
        $this->sloodle_map_module = sloodle_load_module('map', $tempsession, $this->cm->id);
        if (!$this->sloodle_map_module) error('Failed to load SLOODLE map module.');
        
        // Get the locations currently on the map
        $this->locations = $this->sloodle_map_module->get_locations();
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
        
        // Get the initial coordinates and zoom values from the map module
        list($initialx, $initialy) = $this->sloodle_map_module->get_initial_coordinates();
        $initialzoom = $this->sloodle_map_module->get_initial_zoom();
        // Get the strings for showing/hiding pan and zoom controls
        $showpan = 'false'; $showzoom = 'false';
        if ($this->sloodle_map_module->check_pan_controls()) $showpan = 'true';
        if ($this->sloodle_map_module->check_zoom_controls()) $showzoom = 'true';
        // Get the string for enabling/disabling map dragging
        $allowdrag = '';
        if (!$this->sloodle_map_module->check_allow_drag()) $allowdrag = 'mapInstance.disableDragging();';
        
        // Construct code to add the initial markers to the map
        $initialmarkers = '';
        foreach ($this->locations as $loc) {
            $initialmarkers .= "mapInstance.addMarker(new Marker(yellow_markers, new XYPoint({$loc->globalx}, {$loc->globaly})));\n";
        }

        // Construct the JavaScript for our header
        $js = <<<XXXEODXXX
<script src="http://secondlife.com/apps/mapapi/" type="text/javascript"></script>

<script type="text/javascript">

// Declare our map
var mapInstance;
// Declare a variable to store marker data
var marker;

// Declare the yellow dot images
var yellow_dot_image = new Img("{$CFG->wwwroot}/mod/sloodle/map_dot_yellow.gif", 9, 9);
var yellow_icon = new Icon(yellow_dot_image);
var yellow_markers = [yellow_icon, yellow_icon, yellow_icon, yellow_icon, yellow_icon, yellow_icon];


var dblClick = function(x,y)
{
    try {

        // Go to a SLurl of the given location
        gotoSLURL(x, y);

    } catch (e) {
        alert('Sorry. The SL map API does not seem to support direct teleporting.');
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
        // Create the map and setup the interface settings
        mapInstance = new SLMap(document.getElementById('map-container'), {hasZoomControls: {$showzoom}, hasPanningControls: {$showpan}, doubleClickHandler: dblClick});
        mapInstance.centerAndZoomAtSLCoord(new XYPoint({$initialx},{$initialy}), {$initialzoom});
        {$allowdrag}

        // Put markers on each location from the database
        {$initialmarkers}
    
    } catch (e) {
       alert("An error occurred while trying to load the map: " + e.description);
    }
}

function showMapWindow(x, y, text)
{
    // Create a map window at the specified position
    try {
        mapInstance.addMapWindow(new MapWindow(text), new XYPoint(x, y));
        mapInstance.panOrRecenterToSLCoord(new XYPoint(x, y), true);
    } catch (e) {
        alert("An error occurred while trying to create a map window: " + e.description);
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

        // Render the map itself
        echo '<p style="text-align:center;">Click and drag the map to pan around.</p>';
        echo '<div id="map-container" style="width:500px; height:500px; margin-left:auto; margin-right:auto;"></div>',"\n";
        echo '<script type="text/javascript">sloodle_load_map();</script>',"\n";

        // Render a list of location links below the map, which can be clicked to see data about them
        echo "<div style=\"text-align:center;\"><ol>\n";
        foreach ($this->locations as $loc) {
            // Output the location and region name
            echo '<li>', $loc->name, " (region: {$loc->region}) \n";
            // Output a button to show it on the map
            $teleportURL = "secondlife://{$loc->region}/{$loc->localx}/{$loc->localy}/{$loc->localz}";
            $windowtext = "<b>{$loc->name}</b><br/>{$loc->description}<br/><br/><i>[<a href=&quot;{$teleportURL}&quot;>Teleport Now</a>]</i>";
            echo "<input type=\"button\" value=\"Show on map\" onclick=\"showMapWindow({$loc->globalx}, {$loc->globaly}, '{$windowtext}');\" /> \n";
            // Output a link to teleport directly to it
            echo "[<a href=\"{$teleportURL}\">Teleport Now</a>]\n";
            echo "</li>\n";
        }
        echo '</ol></div>';
    }

}


?>
