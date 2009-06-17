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
    * A Presenter object (secondary table).
    * @var object
    * @access private
    */
    var $presenter = null;


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
        // Construct a dummy session and load the Presenter object
        $session = new SloodleSession(false);
        $this->presenter = new SloodleModulePresenter($session);
        if (!$this->presenter->load($this->cm->id)) return false;
    }

    /**
    * Process any form data which has been submitted.
    */
    function process_form()
    {
        global $CFG;

        // Should we process any incoming editing commands?
        if ($this->canedit) {

            // We may want to redirect afterwards to prevent an argument showing up in the address bar
            $redirect = false;
        
            // We need to check the incoming request parameters for deletion and re-ordering of entries.
            foreach ($_REQUEST as $reqname => $reqvalue) {

                // Moving up (forward)
                $pos = strpos($reqname, 'sloodleentryup');
                if ($pos === 0) {
                    $this->presenter->move_entry((int)substr($reqname, 14), true);
                    $redirect = true;
                }

                // Moving down (back)
                $pos = strpos($reqname, 'sloodleentrydown');
                if ($pos === 0) {
                    $this->presenter->move_entry((int)substr($reqname, 16), false);
                    $redirect = true;
                }

                // Deletion
                $pos = strpos($reqname, 'sloodledeleteentry');
                if ($pos === 0) {
                    //echo "Deleting entry #".substr($reqname, 18)."<hr>";
                    $this->presenter->delete_entry((int)substr($reqname, 18));
                    $redirect = true;
                }
            }
            
            // Has an image been added?
            if (isset($_REQUEST['sloodleaddentry'])) {
                // Perform some validation
                $sloodleentryurl = sloodle_clean_for_db($_REQUEST['sloodleentryurl']);
                $sloodleentrytype = sloodle_clean_for_db($_REQUEST['sloodleentrytype']);
                $sloodleentryname = sloodle_clean_for_db($_REQUEST['sloodleentryname']);

                $this->presenter->add_entry($sloodleentryurl, $sloodleentrytype, $sloodleentryname);
                $redirect = true;
            }

            // Redirect back to self, if possible
            if ($redirect && headers_sent() == false) {
                header("Location: ".SLOODLE_WWWROOT."/view.php?id={$this->cm->id}");
                exit();
            }
        }            
            
    }


    /**
    * Render the view of the Presenter.
    */
    function render()
    {
        global $CFG;
        
        
        // The name, type and description of the module should already be displayed by the main "view.php" script.
        // In edit mode, we will display a form to let the user add/delete URLs.
        
        // Display a box containing our stuff
        echo "<div style=\"text-align:center;\">\n";        
        
        // Get a list of entry URLs
        $entries = $this->presenter->get_entry_urls();
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
            $currententryname = '';
            $entrynum = 1;
            foreach ($entries as $entryid => $entry) {
                // Check if this is our current entry
                if ($entrynum == $displayentrynum) {
                    $currententryid = $entryid;
                    $currententryurl = $entry[0];
                    $currententrytype = $entry[1];
                    $currententryname = $entry[2];
                }

                $entrynum++;
            }

            // If the entry name is empty, then take it from the URL
            if (empty($currententryname)) {
                // Take everything after the final forward slash from the URL (unless it's at the end).
                // This should give us the filename if there is one, or the domain name otherwise.
                $tempurl = trim($currententryurl);
                $slashpos = strrpos($tempurl, '/', 1);
                if ($slashpos === false) $currententryname = $tempurl;
                else $currententryname = substr($tempurl, $slashpos + 1);
            }
    
            // Display the entry header
            echo "<div style=\"text-align:center;\">";
            echo "<h2>\"<a href=\"{$currententryurl}\" title=\"".get_string('directlink', 'sloodle')."\">{$currententryname}</a>\"</h2>\n";

            // Display the presentation controls
            $strof = get_string('of', 'sloodle');
            $strviewprev = get_string('viewprev', 'sloodle');
            $strviewnext = get_string('viewnext', 'sloodle');

            echo '<p style="font-size:200%; font-weight:bold;">';
            if ($displayentrynum > 1) echo "<a href=\"?id={$this->cm->id}&sloodledisplayentry=",$displayentrynum - 1,"\" title=\"{$strviewprev}\">&larr;</a>";
            else echo "<span style=\"color:#bbbbbb;\">&larr;</span>";
            echo "&nbsp;{$displayentrynum} {$strof} {$numentries}&nbsp;";
            if ($displayentrynum < $numentries) echo "<a href=\"?id={$this->cm->id}&sloodledisplayentry=",$displayentrynum + 1,"\" title=\"{$strviewnext}\">&rarr;</a>";
            else echo "<span style=\"color:#bbbbbb;\">&rarr;</span>";
            echo "</p>\n";

            // Get the frame dimensions for this Presenter
            $framewidth = $this->presenter->get_frame_width();
            $frameheight = $this->presenter->get_frame_height();            

            // Display the entry itself
            switch ($currententrytype) {
            case 'web':
                // Display web content in an iFrame
                echo "<iframe src=\"{$currententryurl}\" style=\"width:{$framewidth}px; height:{$frameheight}px;\"></iframe>";
                break;

            case 'image':
                echo "<img src=\"{$currententryurl}\" />";
                break;

            case 'video':
                echo <<<XXXEODXXX
    <embed src="{$currententryurl}" align="center" autoplay="true" controller="true" width="{$framewidth}" height="{$frameheight}" scale="aspect" />
XXXEODXXX;
                break;

            default:
                echo '<p style="font-size:150%; font-weight:bold; color:#880000;">',get_string('unknowntype','sloodle'),': ', $currententrytype, '</p>';
                break;
            }

            // Display a direct link to the media
            echo "<p>";
            print_string('trydirectlink', 'sloodle', $currententryurl);
            echo "</p>\n";
            echo "</div>";
    
        } else {
            echo '<p>'.get_string('noentries', 'sloodle').'</p>';
        }

        //print_box_end();
        
        echo '<p>&nbsp;</p>';
        
        // Should we display an editing form?
        if ($this->canedit) {
            $streditpresenter = get_string('presenter:edit', 'sloodle');
            $strviewanddelete = get_string('presenter:viewanddelete', 'sloodle');
            $strnoentries = get_string('noentries', 'sloodle');
            $strdelete = get_string('delete', 'sloodle');
            $stradd = get_string('presenter:add', 'sloodle');
            $strtype = get_string('type', 'sloodle');
            $strurl = get_string('url', 'sloodle');
            $strname = get_string('name', 'sloodle');

            // Start a box containing the form
            echo '<hr>';
            print_box_start('generalbox boxaligncenter boxwidthwide');
            echo "<h1>{$streditpresenter}</h1><br/><h3>{$strviewanddelete}</h3>\n";
            // Any images to display?
            if ($entries === false || count($entries) == 0) {
                echo '<h4 style="color:#ff0000;">'.$strnoentries.'</h4>';
            } else {
                echo '<form action="" method="get"><fieldset>';
                echo "<input type=\"hidden\" name=\"id\" value=\"{$this->cm->id}\" />";
                echo '<table style="text-align:left; border-collapse:collapse; margin-left:auto; margin-right:auto;">';
                // Go through each image we have, and display the link along with a delete button
                $numentries = count($entries);
                $entrynum = 0;
                foreach ($entries as $entryid => $entry) {
                    // Extract the entry data
                    $entryurl = $entry[0];
                    $entrytype = $entry[1];
                    $entrytypename = get_string("presenter:type:{$entrytype}", 'sloodle');
                    $entryname = $entry[2];
                    if (empty($entryname)) $entryname = $entryurl;

                    // Output the entry in the table
                    echo '<tr><td style="border:solid 1px #000000;">';
                    echo "<a href=\"{$entryurl}\" title=\"{$entryurl}\">{$entryname}</a> ";
                    echo " <i>({$entrytypename})</i> ";
                    echo '</td><td style="border:solid 1px #000000;">';

                    echo " <input type=\"submit\" value=\"&uarr;\" name=\"sloodleentryup{$entryid}\" ";
                    if ($entrynum == 0) echo " disabled=\"true\" ";
                    echo " /> ";

                    echo " <input type=\"submit\" value=\"&darr;\" name=\"sloodleentrydown{$entryid}\" ";
                    if (($entrynum + 1) >= $numentries) echo " disabled=\"true\" ";
                    echo " /> ";

                    echo " <input type=\"submit\" value=\"{$strdelete}\" name=\"sloodledeleteentry{$entryid}\" /> ";
                    echo " </td></tr>\n";
                    
                    $entrynum++;
                }
                echo '</table><br/><br/></fieldset></form>';
            }
            
            // Display a form for adding an image
            echo "<h3>{$stradd}</h3>";
            echo '<form action="" method="post">';
            echo "<input type=\"hidden\" name=\"id\" value=\"{$this->cm->id}\" />";
            echo '<label for="sloodleentryurl">'.$strurl.': </label> <input type="text" id="sloodleentryurl" name="sloodleentryurl" value="" size="50" maxlength="255" /><br/>'; 
            echo '<label for="sloodleentryname">'.$strname.': </label> <input type="text" id="sloodleentryname" name="sloodleentryname" value="" size="50" maxlength="255" /><br/>'; 
            echo '<label for="sloodleentrytype">'.$strtype.': </label> <select name="sloodleentrytype" id="sloodleentrytype" size="1">';
            echo '<option value="image">'.get_string('presenter:type:image','sloodle').'</option>';
            echo '<option value="video">'.get_string('presenter:type:video','sloodle').'</option>';
            echo '<option value="web" selected="selected">'.get_string('presenter:type:web','sloodle').'</option>';
            echo '</select>';
            echo ' <input type="submit" value="'.$stradd.'" name="sloodleaddentry" />'; 
            echo '</fieldset></form>';
            
            print_box_end();
        }
        
        echo "</div>\n";
    }

}


?>
