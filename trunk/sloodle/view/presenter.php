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

/** ID of the 'view' tab for the Presenter. */
define('SLOODLE_PRESENTER_TAB_VIEW', 1);
/** ID of the 'edit' tab for the Presenter */
define('SLOODLE_PRESENTER_TAB_EDIT', 2);
/** ID of the 'edit slide' tab for the Presenter */
define('SLOODLE_PRESENTER_TAB_EDIT_SLIDE', 3);
/** ID of the 'add slide' tab for the Presenter */
define('SLOODLE_PRESENTER_TAB_ADD_SLIDE', 3);


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
	* Our current mode of access to the Presenter.
	* This can be 'view', 'edit', or 'editslide'.
	* NOTE: 'edit' mode is for the presentation as a whole (slide order), while 'editslide' shows the slide editing form.
	* @var string
	* @access private
	*/
	var $presenter_mode = 'view';
	
	/**
	* ID of the entry we are moving.
	* @var int
	* @access private
	*/
	var $movingentryid = 0;


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
		
		// Slight hack to put this here. We need to have the permissions checked before we do this.
		// Default to view mode. Only allow other types if the user has sufficient permission
		if ($this->canedit) {
			$this->presenter_mode = optional_param('mode', 'view');
		} else {
			$this->presenter_mode = 'view';
		}
		// If we're in moving mode, then grab the entry ID
		if ($this->presenter_mode == 'moveslide') $this->movingentryid = (int)optional_param('entry', 0);

        // Should we process any incoming editing commands?
        if ($this->canedit) {

            // We may want to redirect afterwards to prevent an argument showing up in the address bar
            $redirect = false;
        
            // Are we attempting to delete an entry?
			if ($this->presenter_mode == 'deleteslide') {
				// Make sure the session key is specified and valid
				if (required_param('sesskey') != sesskey()) {
					error('Invalid session key');
					exit();
				}
				
				// Delete the slide
				$entryid = (int)required_param('entry', PARAM_INT);
				$this->presenter->delete_entry($entryid);
				
				$redirect = true;
			}
			
			// Are we relocating an entry?
			if ($this->presenter_mode == 'setslideposition') {
				$entryid = (int)required_param('entry', PARAM_INT);
				$position = (int)required_param('position', PARAM_INT);
				$this->presenter->relocate_entry($entryid, $position);
				
				$redirect = true;
			}
            
            // Has a new entry been added?
            if (isset($_REQUEST['sloodleaddentry'])) {
                $sloodleentryurl = sloodle_clean_for_db($_REQUEST['sloodleentryurl']);
                $sloodleentrytype = sloodle_clean_for_db($_REQUEST['sloodleentrytype']);
                $sloodleentryname = sloodle_clean_for_db($_REQUEST['sloodleentryname']);
                $sloodleentryposition = (int)$_REQUEST['sloodleentryposition'];

                $this->presenter->add_entry($sloodleentryurl, $sloodleentrytype, $sloodleentryname, $sloodleentryposition);
                $redirect = true;
            }

            // Has an existing entry been edited?
            if (isset($_REQUEST['sloodleeditentry'])) {
                $sloodleentryid = (int)$_REQUEST['sloodleentryid'];
                $sloodleentryurl = sloodle_clean_for_db($_REQUEST['sloodleentryurl']);
                $sloodleentrytype = sloodle_clean_for_db($_REQUEST['sloodleentrytype']);
                $sloodleentryname = sloodle_clean_for_db($_REQUEST['sloodleentryname']);
                $sloodleentryposition = (int)$_REQUEST['sloodleentryposition'];

                $this->presenter->edit_entry($sloodleentryid, $sloodleentryurl, $sloodleentrytype, $sloodleentryname, $sloodleentryposition);
                $redirect = true;
            }

            // Redirect back to self, if possible
            if ($redirect && headers_sent() == false) {
                header("Location: ".SLOODLE_WWWROOT."/view.php?id={$this->cm->id}&mode=edit");
                exit();
            }
        }            
            
    }
	
	
	/**
	* Render the View of the Presenter.
	* Called from with the {@link render()} function when necessary.
	*/
	function render_view()
	{        
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
            echo "<h2 id=\"slide\">\"<a href=\"{$currententryurl}\" title=\"".get_string('directlink', 'sloodle')."\">{$currententryname}</a>\"</h2>\n";

            // Display the presentation controls
            $strof = get_string('of', 'sloodle');
            $strviewprev = get_string('viewprev', 'sloodle');
            $strviewnext = get_string('viewnext', 'sloodle');

            echo '<p style="font-size:200%; font-weight:bold;">';
            if ($displayentrynum > 1) echo "<a href=\"?id={$this->cm->id}&sloodledisplayentry=",$displayentrynum - 1,"#slide\" title=\"{$strviewprev}\">&larr;</a>";
            else echo "<span style=\"color:#bbbbbb;\">&larr;</span>";
            echo "&nbsp;{$displayentrynum} {$strof} {$numentries}&nbsp;";
            if ($displayentrynum < $numentries) echo "<a href=\"?id={$this->cm->id}&sloodledisplayentry=",$displayentrynum + 1,"#slide\" title=\"{$strviewnext}\">&rarr;</a>";
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
            echo '<p>'.get_string('presenter:empty', 'sloodle').'</p>';
			if ($this->canedit) echo '<p>'.get_string('presenter:clickedit', 'sloodle').'</p>';
        }

        //print_box_end();
	}
	
	/**
	* Render the Edit mode of the Presenter (lists all the slides and allows re-ordering).
	* Called from with the {@link render()} function when necessary.
	*/
	function render_edit()
	{
		global $CFG;
	
		$streditpresenter = get_string('presenter:edit', 'sloodle');
		$strviewanddelete = get_string('presenter:viewanddelete', 'sloodle');
		$strnoentries = get_string('noentries', 'sloodle');
		$strdelete = get_string('delete', 'sloodle');
		$stradd = get_string('presenter:add', 'sloodle');
        $straddatend = get_string('presenter:addatend', 'sloodle');
        $straddbefore = get_string('presenter:addbefore', 'sloodle');
		$strtype = get_string('type', 'sloodle');
		$strurl = get_string('url', 'sloodle');
		$strname = get_string('name', 'sloodle');
		
		$stryes = get_string('yes');
		$strno = get_string('no');
		
		$strmove = get_string('move');
		$stredit = get_string('edit', 'sloodle');
		$strview = get_string('view', 'sloodle');
		$strdelete = get_string('delete');
		
		$strmoveslide = get_string('presenter:moveslide', 'sloodle');
		$streditslide = get_string('presenter:editslide', 'sloodle');
		$strviewslide = get_string('presenter:viewslide', 'sloodle');
		$strdeleteslide = get_string('presenter:deleteslide', 'sloodle');
		
		 // Get a list of entry URLs
        $entries = $this->presenter->get_entry_urls();
        if (!is_array($entries)) $entries = array();
        $numentries = count($entries);
		// Any images to display?
		if ($entries === false || count($entries) == 0) {
			echo '<h4 style="color:#ff0000;">'.$strnoentries.'</h4>';
		} else {
		
			// Are we being asked to confirm the deletion of a slide?
			if ($this->presenter_mode == 'confirmdeleteslide') {
				// Make sure the session key is specified and valid
				if (required_param('sesskey') != sesskey()) {
					error('Invalid session key');
					exit();
				}
				// Determine which slide is being deleted
				$entryid = (int)required_param('entry', PARAM_INT);
				
				// Make sure the specified entry is recognised
				if (isset($entries[$entryid])) {
					// Construct our links
					$linkYes = SLOODLE_WWWROOT."/view.php?id={$this->cm->id}&amp;mode=deleteslide&amp;entry={$entryid}&amp;sesskey=".sesskey();
					$linkNo = SLOODLE_WWWROOT."/view.php?id={$this->cm->id}&amp;mode=edit";
					// Check the name of the entry
					$entryname = $entries[$entryid][2];
                    if (empty($entryname)) $entryname = $entries[$entryid][0];
					
					// Output our confirmation form
					notice_yesno(get_string('presenter:confirmdelete', 'sloodle', $entryname), $linkYes, $linkNo);
					echo "<br/>";
				}
			}
			
			// Are we currently moving a slide?
			if ($this->presenter_mode == 'moveslide') {
				// Determine which slide is being moved
				$entryname = $entries[$this->movingentryid][2];
                if (empty($entryname)) $entryname = $entries[$this->movingentryid][0];

				$linkCancel = SLOODLE_WWWROOT."/view.php?id={$this->cm->id}&amp;mode=edit";
				$strcancel = get_string('cancel');
				// Display a message and an optional 'cancel' link
				print_box_start('generalbox', 'notice');
				echo "<p>", get_string('presenter:movingslide', 'sloodle', $entryname), "</p>\n";
				echo "<p>(<a href=\"{$linkCancel}\">{$strcancel}</a>)</p>\n";
				print_box_end();
			}
		
			// Setup a table object to display Presenter entries
			$entriesTable = new stdClass();
			$entriesTable->head = array(get_string('position', 'sloodle'), get_string('name', 'sloodle'), get_string('type', 'sloodle'), get_string('actions', 'sloodle'), $stradd);
			$entriesTable->align = array('center', 'left', 'left', 'center', 'center');
			$entriesTable->size = array('5%', '35%', '20%', '30%', '10%');
			
			// Go through each entry
			$numentries = count($entries);
			$entrynum = 1;
			foreach ($entries as $entryid => $entry) {
				// Create a new row for the table
				$row = array();
				
				// Extract the entry data
				$entryurl = $entry[0];
				$entrytype = $entry[1];
				$entrytypename = get_string("presenter:type:{$entrytype}", 'sloodle');
				$entryname = $entry[2];
				if (empty($entryname)) $entryname = $entryurl;
                // Construct the link to the entry source
                $entrylink = "<a href=\"{$entryurl}\" title=\"{$entryurl}\">{$entryname}</a>";
				
				// If we are in move mode, then add a 'move here' row before this slide
				if ($this->presenter_mode == 'moveslide') {
					$movelink = SLOODLE_WWWROOT."/view.php?id={$this->cm->id}&amp;mode=setslideposition&amp;entry={$this->movingentryid}&amp;position={$entrynum}";
					$movebutton = "<a href=\"{$movelink}\" title=\"{$strmove}\"><img src=\"{$CFG->pixpath}/movehere.gif\" class=\"\" alt=\"{$strmove}\" /></a>\n";
					$entriesTable->data[] = array('', $movebutton, '', '', '');

                    // If the current row belongs to the slide being moved, then emphasise it, and append (moving) to the end
                    if ($entryid == $this->movingentryid) $entrylink = "<strong>{$entrylink}</strong> <em>(".get_string('moving','sloodle').')</em>';
				}
				
				// Define our action links
				$actionBaseLink = SLOODLE_WWWROOT."/view.php?id={$this->cm->id}";
				$actionLinkMove = $actionBaseLink."&amp;mode=moveslide&amp;entry={$entryid}";
				$actionLinkEdit = $actionBaseLink."&amp;mode=editslide&amp;entry={$entryid}";
				$actionLinkView = $actionBaseLink."&amp;mode=view&amp;sloodledisplayentry={$entrynum}#slide";
				$actionLinkDelete = $actionBaseLink."&amp;mode=confirmdeleteslide&amp;entry={$entryid}&amp;sesskey=".sesskey();
				
				// Construct our list of action buttons
				$actionButtons = '';
				$actionButtons .= "<a href=\"{$actionLinkMove}\" title=\"{$strmoveslide}\"><img src=\"{$CFG->pixpath}/t/move.gif\" class=\"iconsmall\" alt=\"{$strmove}\" /></a>\n";
				$actionButtons .= "<a href=\"{$actionLinkEdit}\" title=\"{$streditslide}\"><img src=\"{$CFG->pixpath}/t/edit.gif\" class=\"iconsmall\" alt=\"{$stredit}\" /></a>\n";
				$actionButtons .= "<a href=\"{$actionLinkView}\" title=\"{$strviewslide}\"><img src=\"{$CFG->pixpath}/t/preview.gif\" class=\"iconsmall\" alt=\"{$strview}\" /></a>\n";
				$actionButtons .= "<a href=\"{$actionLinkDelete}\" title=\"{$strdeleteslide}\"><img src=\"{$CFG->pixpath}/t/delete.gif\" class=\"iconsmall\" alt=\"{$strdelete}\" /></a>\n";
                

                // Prepare the add buttons separately
                $actionLinkAdd = $actionBaseLink."&amp;mode=addslide&amp;sloodleentryposition={$entrynum}";
                $addButtons = "<a href=\"{$actionLinkAdd}\" title=\"{$straddbefore}\"><img src=\"".SLOODLE_WWWROOT."/add.png\" alt=\"{$stradd}\" /></a>\n";
				
				// Add each item of data to our table row.
				// The first items are the position and the name of the entry, hyperlinked to the resource.
				// The next is the name of the entry type.
				// The last is a list of action buttons -- move, edit, view, and delete.
                $row[] = $entrynum;
				$row[] = $entrylink;
				$row[] = $entrytypename;
				$row[] = $actionButtons;
                $row[] = $addButtons;
				
				// Add the row to our table
				$entriesTable->data[] = $row;
				$entrynum++;
			}
			
			// If we are in move mode, then add a final 'move here' row at the bottom

            // We need to add a final row at the bottom
            // Prepare the action link for this row
            $actionLinkAdd = $actionBaseLink."&amp;mode=addslide&amp;sloodleentryposition={$entrynum}";
            $addButtons = "<a href=\"{$actionLinkAdd}\" title=\"{$straddatend}\"><img src=\"".SLOODLE_WWWROOT."/add.png\" alt=\"{$stradd}\" /></a>\n";
            // It will contain a last 'add' button, and possibly a 'move here' button too (if we are in move mode)
            $movebutton = '';
			if ($this->presenter_mode == 'moveslide') {
				$movelink = SLOODLE_WWWROOT."/view.php?id={$this->cm->id}&amp;mode=setslideposition&amp;entry={$this->movingentryid}&amp;position={$entrynum}";
				$movebutton = "<a href=\"{$movelink}\" title=\"{$strmove}\"><img src=\"{$CFG->pixpath}/movehere.gif\" class=\"\" alt=\"{$strmove}\" /></a>\n";
			}
            $entriesTable->data[] = array('', $movebutton, '', '', $addButtons);
			
			print_table($entriesTable);
		}
		
	}
	
	/**
	* Render the slide editing form of the Presenter (lets you edit a single slide).
	* Called from with the {@link render()} function when necessary.
	*/
	function render_slide_edit()
    {
        // Setup variables to store the data
        $entryid = 0;
        $entryname = '';
        $entryurl = '';
        $entrytype = '';

        // Fetch a list of existing slides
        $entries = $this->presenter->get_entry_urls();
        // Check what position we are adding the new slide to
        // (default to negative, which puts it at the end)
        $position = (int)optional_param('sloodleentryposition', '-1', PARAM_INT);

        // Are we adding a slide, or editing one?
        $newslide = false;
        if ($this->presenter_mode == 'addslide') {
            // Adding a new slide
            $newslide = true;
        } else {
            // Editing an existing slide
            $entryid = (int)required_param('entry', PARAM_INT);
            // Fetch the slide details
            if (!isset($entries[$entryid])) {
                error("Cannot find entry {$entryid} in the database.");
                exit();
            }
            $entryurl = $entries[$entryid][0];
            $entrytype = $entries[$entryid][1];
            $entryname = $entries[$entryid][2];
        }

        // Fetch our translation strings
		$streditpresenter = get_string('presenter:edit', 'sloodle');
		$strviewanddelete = get_string('presenter:viewanddelete', 'sloodle');
		$strnoentries = get_string('noentries', 'sloodle');
		$strdelete = get_string('delete', 'sloodle');
		$stradd = get_string('presenter:add', 'sloodle');
		$strtype = get_string('type', 'sloodle');
		$strurl = get_string('url', 'sloodle');
		$strname = get_string('name', 'sloodle');
        $strposition = get_string('position', 'sloodle');
        $strsave = get_string('save', 'sloodle');
        $strend = get_string('end', 'sloodle');
		
		$stryes = get_string('yes');
		$strno = get_string('no');
        $strcancel = get_string('cancel');
		
		$strmove = get_string('move');
		$stredit = get_string('edit', 'sloodle');
		$strview = get_string('view', 'sloodle');
		$strdelete = get_string('delete');

        // Construct an array of available entry types, associating the identifier to the humand-readable name.
        // In future, this will be built from a list of plugins, but for now we'll hard code it.
        $availabletypes = array();
        $availabletypes['image'] = get_string('presenter:type:image','sloodle');
        $availabletypes['video'] = get_string('presenter:type:video','sloodle');
        $availabletypes['web'] = get_string('presenter:type:web','sloodle');

        // We'll post the data straight back to this page
		echo '<form action="" method="post"><fieldset style="border-style:none;">';
        // Identify the module
		echo "<input type=\"hidden\" name=\"id\" value=\"{$this->cm->id}\" />";
        // Identify the entry being edited, if appropriate
        if (!$newslide) echo "<input type=\"hidden\" name=\"sloodleentryid\" value=\"{$entryid}\" />";
        // Add boxes for the URL and name of the entry
		echo '<label for="sloodleentryname">'.$strname.': </label> <input type="text" id="sloodleentryname" name="sloodleentryname" value="'.$entryname.'" size="100" maxlength="255" /><br/><br/>'; 
		echo '<label for="sloodleentryurl">'.$strurl.': </label> <input type="text" id="sloodleentryurl" name="sloodleentryurl" value="'.$entryurl.'" size="100" maxlength="255" /><br/><br/>'; 
        // Add a selection box for the entry type
		echo '<label for="sloodleentrytype">'.$strtype.': </label> <select name="sloodleentrytype" id="sloodleentrytype" size="1">';
        foreach ($availabletypes as $typeident => $typename) {
            echo "<option value=\"{$typeident}\"";
            if ($typeident == $entrytype) echo " selected=\"selected\"";
            echo ">{$typename}</option>";
        }
		echo '</select><br/><br/>';

        // Add a selection box to let the user change the position of the entry
        echo '<label for="sloodleentryposition">'.$strposition.': </label> <select name="sloodleentryposition" id="sloodleentryposition" size="1">'."\n";
        $entrynum = 1;
        $selected = false;
        foreach ($entries as $curentryid => $curentry) {
            // Add this entry to the list
            echo "<option value=\"{$entrynum}\"";
            if ($entrynum == $position || $curentryid == $entryid) {
                echo ' selected="selected"';
                $selected = true;
            }
            echo ">{$entrynum}: {$curentry[2]}</option>\n";
            $entrynum++;
        }
        // Add an 'end' option so that the entry can be placed at the end of the presentation
        echo "<option value=\"{$entrynum}\"";
        if (!$selected) echo " selected=\"selected\"";
        echo ">--{$strend}--</option>\n";
        echo "</select><br/><br/>\n";

        // Display an appropriate submit button
        if ($newslide) echo ' <input type="submit" value="'.$stradd.'" name="sloodleaddentry" />';
        else echo ' <input type="submit" value="'.$strsave.'" name="sloodleeditentry" />';
        // Close the form
		echo '</fieldset></form>';

        // Add a button to let us cancel and go back to the main edit tab
		echo '<form action="" method="get"><fieldset style="border-style:none;">';
		echo "<input type=\"hidden\" name=\"id\" value=\"{$this->cm->id}\" />";
		echo "<input type=\"hidden\" name=\"mode\" value=\"edit\" />";
        echo "<input type=\"submit\" value=\"{$strcancel}\" />";
        echo '</fieldset></form>'; 
	}

    /**
    * Render the view of the Presenter.
    */
    function render()
    {
        global $CFG;
		
		// Setup our list of tabs
        // We will always have a view option
		$presenterTabs = array(); // Top level is rows of tabs
		$presenterTabs[0] = array(); // Second level is individual tabs in a row
		$presenterTabs[0][] = new tabobject(SLOODLE_PRESENTER_TAB_VIEW, SLOODLE_WWWROOT."/view.php?id={$this->cm->id}&amp;mode=view", get_string('view', 'sloodle'), get_string('presenter:viewpresentation', 'sloodle'), true);
		// Does the user have authority to edit this module?
		if ($this->canedit) {
			// Add the protected tab(s)
			$presenterTabs[0][] = new tabobject(SLOODLE_PRESENTER_TAB_EDIT, SLOODLE_WWWROOT."/view.php?id={$this->cm->id}&amp;mode=edit", get_string('edit', 'sloodle'), get_string('presenter:edit', 'sloodle'), true);

            // If we are editing a slide, then add the 'Edit Slide' tab
            if ($this->presenter_mode == 'editslide') {
                $presenterTabs[0][] = new tabobject(SLOODLE_PRESENTER_TAB_EDIT_SLIDE, '', get_string('editslide', 'sloodle'), '', false);
            }
            // If we are adding a slide, then add the 'Add Slide' tab
            if ($this->presenter_mode == 'addslide') {
                $presenterTabs[0][] = new tabobject(SLOODLE_PRESENTER_TAB_ADD_SLIDE, '', get_string('presenter:add', 'sloodle'), '', false);
            }
		}
		// Determine which tab should be active
		$selectedtab = SLOODLE_PRESENTER_TAB_VIEW;
		switch ($this->presenter_mode)
		{
		case 'edit': $selectedtab = SLOODLE_PRESENTER_TAB_EDIT; break;
		case 'addslide': $selectedtab = SLOODLE_PRESENTER_TAB_ADD_SLIDE; break;
		case 'editslide': $selectedtab = SLOODLE_PRESENTER_TAB_EDIT_SLIDE; break;
		case 'moveslide': $selectedtab = SLOODLE_PRESENTER_TAB_EDIT; break;
		case 'deleteslide': $selectedtab = SLOODLE_PRESENTER_TAB_EDIT; break;
		case 'confirmdeleteslide': $selectedtab = SLOODLE_PRESENTER_TAB_EDIT; break;
		}
		
		// Display the tabs
		print_tabs($presenterTabs, $selectedtab);
		echo "<div style=\"text-align:center;\">\n";
		
		// Call the appropriate render function, based on our mode
		switch ($this->presenter_mode)
		{
		case 'edit': $this->render_edit(); break;
		case 'addslide': $this->render_slide_edit(); break;
		case 'editslide': $this->render_slide_edit(); break;
		case 'moveslide': $this->render_edit(); break;
		case 'deleteslide': $this->render_edit(); break;
		case 'confirmdeleteslide': $this->render_edit(); break;
		default: $this->render_view(); break;
		}
		
		echo "</div>\n";
    }

}


?>
