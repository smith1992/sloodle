<?php
/**
* Defines a class for viewing the SLOODLE Presenter module in Moodle.
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
/** The SLOODLE Session data structures */
require_once(SLOODLE_LIBROOT.'/sloodle_session.php');



/**
* Class for rendering a view of a Presenter module in Moodle.
* @package sloodle
*/
class sloodle_view_presenter extends sloodle_base_view_module
{

    /**
    * Constructor.
    */
    function sloodle_view_presenter()
    {
    }

    /**
    * Processes request data to determine which Presenter is being accessed.
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
    * Render the view of the Presenter.
    */
    function render()
    {
        global $CFG;
        
        // Construct a dummy session and a Slideshow object
        $session = new SloodleSession(false);
        $presenter = new SloodleModulePresenter($session);
        if (!$presenter->load($this->cm->id)) return false;
        
        // The name, type and description of the module should already be displayed by the main "view.php" script.
        // In edit mode, we will display a form to let the user add/delete URLs.
        
        // Display a box containing our stuff
        echo "<div style=\"text-align:center;\">\n";
        
        
        
        // Should we process any incoming editing commands?
        if ($this->canedit) {
        
            // We need to check the incoming request parameters for deletion and re-ordering of entries.
            foreach ($_REQUEST as $reqname => $reqvalue) {

                // Moving up (forward)
                $pos = strpos($reqname, 'sloodleentryup');
                if ($pos === 0) {
                    $presenter->move_entry((int)substr($reqname, 14), true);
                }

                // Moving down (back)
                $pos = strpos($reqname, 'sloodleentrydown');
                if ($pos === 0) {
                    $presenter->move_entry((int)substr($reqname, 16), false);
                }

                // Deletion
                $pos = strpos($reqname, 'sloodledeleteentry');
                if ($pos === 0) {
                    //echo "Deleting entry #".substr($reqname, 18)."<hr>";
                    $presenter->delete_entry((int)substr($reqname, 18));
                }
            }
            
            // Has an image been added?
            if (isset($_REQUEST['sloodleaddentry'])) {
                $presenter->add_entry($_REQUEST['sloodleentryurl'], $_REQUEST['sloodleentrytype']);
            }
        }            
            
        // Get a list of entry URLs
        $entries = $presenter->get_entry_urls();
        if (!is_array($entries)) $entries = array();
        $numentries = count($entries);
        // Open the presentation box
        //print_box_start('generalbox boxaligncenter boxwidthwide');

        // Was a specific entry requested? This is the number of entry within the presentation, NOT entry ID.
        // They start at 1 and go up from there within each presentation.
        if (isset($_REQUEST['sloodledisplayentry'])) {
            $displayentrynum = (int)$_REQUEST['sloodledisplayentry'];
            if ($displayentrynum < 1 || $displayentrynum > $numentries) $displayentrynum = 1;
        } else {
            $displayentrynum = 1;
        }
        
        // Do we have any entries to work with?
        if ($numentries > 0) {
            // Yes - go through them to figure out which entries to display
            $currententryid = 0;
            $currententryurl = '';
            $currententrytype = '';
            $entrynum = 1;
            foreach ($entries as $entryid => $entry) {
                // Check if this is our current entry
                if ($entrynum == $displayentrynum) {
                    $currententryid = $entryid;
                    $currententryurl = $entry[0];
                    $currententrytype = $entry[1];
                }

                $entrynum++;
            }
    
            // Display the entry header
            echo "<div style=\"text-align:center;\">";
            echo "<h1>Presenter</h1>\n";
            
            // Display the entry itself
            switch ($currententrytype) {
            case 'web':
                // Display web content in an iFrame
                echo "<iframe src=\"{$currententryurl}\" style=\"width:512px; height:512px;\"></iframe>";
                break;

            case 'image':
                echo "<img src=\"{$currententryurl}\" />";
                break;

            case 'video':
                echo <<<XXXEODXXX
    <embed src="{$currententryurl}" align="center" autoplay="true" controller="true" width="512" height="512" scale="aspect" />
XXXEODXXX;
                break;

            default:
                echo '<p style="font-size:150%; font-weight:bold; color:#880000;">Unknown entry type: ', $currententrytype, '</p>';
                break;
            }


            // Display the presentation controls
            echo '<p style="font-size:250%; font-weight:bold;">';
            if ($displayentrynum > 1) echo "<a href=\"?id={$this->cm->id}&sloodledisplayentry=",$displayentrynum - 1,"\" title=\"View previous entry\">&larr;</a>";
            else echo "<span style=\"color:#bbbbbb;\">&larr;</span>";
            echo "&nbsp;{$displayentrynum} of {$numentries}&nbsp;";
            if ($displayentrynum < $numentries) echo "<a href=\"?id={$this->cm->id}&sloodledisplayentry=",$displayentrynum + 1,"\" title=\"View next entry\">&rarr;</a>";
            else echo "<span style=\"color:#bbbbbb;\">&rarr;</span>";
            echo "</p>\n";


            // Display a direct link to the media
            echo "<p>If you cannot see the above entry, try this <a href=\"{$currententryurl}\">direct link</a> instead.</p>";
            echo "</div>";
    
        } else {
            echo '<p>No entries in presentation.</p>';
        }

        //print_box_end();
        
        echo '<p>&nbsp;</p>';
        
        // Should we display an editing form?
        if ($this->canedit) {
            // Start a box containing the form
            echo '<hr>';
            print_box_start('generalbox boxaligncenter boxwidthwide');
            echo '<h1>Edit Presentation</h1><br/><h3>View and delete entry links</h3>'; // TODO: use language pack!
            // Any images to display?
            if ($entries === false || count($entries) == 0) {
                echo '<h4 style="color:#ff0000;">No entries found</h4>'; // TODO: use language pack!
            } else {
                echo '<form action="" method="get"><fieldset>';
                echo "<input type=\"hidden\" name=\"id\" value=\"{$this->cm->id}\" />";
                echo '<table style="text-align:left; border-collapse:collapse; margin-left:auto; margin-right:auto;">';
                // Go through each image we have, and display the link along with a delete button
                $numentries = count($entries);
                $entrynum = 0;
                foreach ($entries as $entryid => $entry) {
                    echo '<tr><td style="border:solid 1px #000000;">';
                    echo "<a href=\"{$entry[0]}\">{$entry[0]}</a> ";
                    echo " <i>(type: {$entry[1]})</i> ";
                    echo '</td><td style="border:solid 1px #000000;">';

                    echo " <input type=\"submit\" value=\"&uarr;\" name=\"sloodleentryup{$entryid}\" ";
                    if ($entrynum == 0) echo " disabled=\"true\" ";
                    echo " /> ";

                    echo " <input type=\"submit\" value=\"&darr;\" name=\"sloodleentrydown{$entryid}\" ";
                    if (($entrynum + 1) >= $numentries) echo " disabled=\"true\" ";
                    echo " /> ";

                    echo " <input type=\"submit\" value=\"Delete\" name=\"sloodledeleteentry{$entryid}\" /> ";
                    echo " </td></tr>\n";
                    
                    $entrynum++;
                }
                echo '</table><br/><br/></fieldset></form>';
            }
            
            // Display a form for adding an image
            echo '<h3>Add entry</h3>'; // TODO: use language pack!
            echo '<form action="" method="post">';
            echo "<input type=\"hidden\" name=\"id\" value=\"{$this->cm->id}\" />";
            echo '<label for="sloodleentryurl">URL: </label> <input type="text" id="sloodleentryurl" name="sloodleentryurl" value="" size="50" maxlength="255" /><br/>'; 
            echo '<label for="sloodleentrytype">Type: </label> <select name="sloodleentrytype" id="sloodleentrytype" size="1">';
            echo '<option value="image">Image</option>';
            echo '<option value="video">Video</option>';
            echo '<option value="web" selected="selected">Web Page</option>';
            echo '</select>';
            echo ' <input type="submit" value="Add" name="sloodleaddentry" />'; 
            echo '</fieldset></form>';
            
            print_box_end();
        }
        
        echo "</div>\n";
    }

}


?>
