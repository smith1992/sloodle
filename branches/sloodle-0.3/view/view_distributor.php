<?php
    /**
    * Defines a function to display information about a particular Sloodle Distributor module.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This file expects that the core Sloodle/Moodle functionality has already been included.
    
    /**
    * Displays information about the given Sloodle Distributor.
    * Note: the user should already be logged-in, with information in the $USER global.
    *
    * @uses $USER
    * @param object $cm A coursemodule object for the module being displayed
    * @param object $sloodle A database record object for a Sloodle instance
    * @param bool $showprotected True if protected data (such as prim password) should be made available
    * @return bool True if successful, or false otherwise (e.g. wrong type of module, or user not logged-in)
    */
    function sloodle_view_distributor($cm, $sloodle, $showprotected = false)
    {
        global $USER;
    
        // Check that there is valid Sloodle data
        if (empty($sloodle) || $sloodle->type != SLOODLE_TYPE_DISTRIB) return false;
        
        // Fetch the Distributor data
        if (!$distributor = get_record('sloodle_distributor', 'sloodleid', $sloodle->id)) return false;
        // Fetch a list of all distributor entries
        $entries = get_records('sloodle_distributor_entry', 'distributorid', $distributor->id, 'name');
        // If the query failed, then assume there were simply no items available
        if (!is_array($entries)) $entries = array();
        $numitems = count($entries);
        
        
        // A particular default user can be requested (by avatar name) in the HTTP parameters.
        // This could be used with a "send to this avatar" button on a Sloodle user profile.
        $defaultavatar = optional_param('defaultavatar', null, PARAM_TEXT);
        
        
        // // SEND OBJECT // //
        
        // If the user and object parameters are set, then try to send an object
        $send_user = optional_param('user', '', PARAM_TEXT);
        $send_object = optional_param('object', '', PARAM_TEXT);
        if (!empty($send_user) && !empty($send_object)) {
            // Construct and send the request
            $request = "1|OK\\nSENDOBJECT|$send_user|$send_object";
            $ok = sloodle_send_xmlrpc_message($distributor->channel, 0, $request);
            
            // What was the result?
            print_box_start('generalbox boxaligncenter boxwidthnarrow centerpara');
    		if ($ok) {
                print '<h3 style="color:green;text-align:center;">'.get_string('sloodleobjectdistributor:successful','sloodle').'</h3>';
    		} else {
                print '<h3 style="color:red;text-align:center;">'.get_string('sloodleobjectdistributor:failed','sloodle').'</h3>';
    		}
            print '<p style="text-align:center;">';
                print get_string('Object','sloodle').': '.$send_object.'<br/>';
                print get_string('uuid','sloodle').': '.$send_user.'<br/>';
                print get_string('xmlrpc:channel','sloodle').': '.$distributor->channel.'<br/>';
                print '</p>';
            print_box_end();
        }
        
        // // ----------- // //
        

        // If there are no items in the distributor, then simply display an error message
        if ($numitems < 1) print_box('<span style="font-weight:bold; color:red;">'.get_string('sloodleobjectdistributor:noobjects','sloodle').'</span>', 'generalbox boxaligncenter boxwidthnormal centerpara');
        //error(get_string('sloodleobjectdistributor:noobjects','sloodle'));
        // If there is no XMLRPC channel specified, then display a warning message
        $disabledattr = '';
        if (empty($distributor->channel)) {
            print_box('<span style="font-weight:bold; color:red;">'.get_string('sloodleobjectdistributor:nochannel','sloodle').'</span>', 'generalbox boxaligncenter boxwidthnormal centerpara');
            $disabledattr = 'disabled="true"';
        }
        
        // Construct the selection box of items
        $selection_items = '<select name="object" size="1">';
        foreach ($entries as $e) {
            $selection_items .= "<option value=\"{$e->name}\">{$e->name}</option>\n";
        }
        $selection_items .= '</select>';
        
        // Get a list of all avatars on the site
        $avatars = get_records('sloodle_users', '', '', 'avname');
        if (!$avatars) $avatars = array();
        // Construct the selection box of avatars
        $selection_avatars = '<select name="user" size="1">';
        foreach ($avatars as $a) {
            if (!empty($a->uuid)) {
                $sel = '';
                if ($a->avname == $defaultavatar) $sel = 'selected="true"';
                $selection_avatars .= "<option value=\"{$a->uuid}\" $sel>{$a->avname}</option>\n";
            }
        }
        $selection_avatars .= '</select>';
        

        // There will be 3 forms:
        //  - send to self
        //  - send to another avatar on the site
        //  - send to custom UUID
        // The first 1 will be available to any registered user whose avatar is in the database.
        // The other 2 will only be available to those with the activity management capability.
        // Furthermore, the 2nd form will only be available if there is at least 1 avatar registered on the site.

        // Start of the sending forms
        print_box_start('generalbox boxaligncenter boxwidthnormal centerpara');
        
    // // SEND TO SELF // //
        
        // Start the form
        echo '<form action="" method="POST">';
        
        // Use a table for layout
        $table_sendtoself = new stdClass();
        $table_sendtoself->head = array(get_string('sloodleobjectdistributor:sendtomyavatar','sloodle'));
        $table_sendtoself->align = array('center');
        
        // Fetch the current user's Sloodle info
        $sloodleuser = get_record('sloodle_users', 'userid', $USER->id);
        if (!$sloodleuser) {
            $table_sendtoself->data[] = array('<span style="color:red;">'.get_string('avatarnotlinked','sloodle').'</span>');
        } else {
            // Output the hidden form data
            echo <<<XXXEODXXX
 <input type="hidden" name="s" value="{$sloodle->id}">
 <input type="hidden" name="user" value="{$sloodleuser->uuid}">
XXXEODXXX;
        
            // Object selection box
            $table_sendtoself->data[] = array(get_string('selectobject','sloodle').': '.$selection_items);
            // Submit button
            $table_sendtoself->data[] = array('<input type="submit" '.$disabledattr.' value="'.get_string('sloodleobjectdistributor:sendtomyavatar','sloodle').' ('.$sloodleuser->avname.')" />');
        }
        
        // Print the table
        print_table($table_sendtoself);
        
        // End the form
        echo "</form>";
        
        
        // Only show the other options if protected items are to be shown
        if ($showprotected) {
        // // SEND TO ANOTHER AVATAR // //
            
            // Start the form
            echo '<br><form action="" method="POST">';
            
            // Use a table for layout
            $table = new stdClass();
            $table->head = array(get_string('sloodleobjectdistributor:sendtoanotheravatar','sloodle'));
            $table->align = array('center');
            
            // Do we have any avatars?
            if (count($avatars) < 1) {
                $table->data[] = array('<span style="color:red;">'.get_string('nosloodleusers','sloodle').'</span>');
            } else {
                // Output the hidden form data
                echo <<<XXXEODXXX
     <input type="hidden" name="s" value="{$sloodle->id}">
XXXEODXXX;
                // Avatar selection box
                $table->data[] = array(get_string('selectuser','sloodle').': '.$selection_avatars);
                // Object selection box
                $table->data[] = array(get_string('selectobject','sloodle').': '.$selection_items);
                // Submit button
                $table->data[] = array('<input type="submit" '.$disabledattr.' value="'.get_string('sloodleobjectdistributor:sendtoanotheravatar','sloodle').'" />');
            }
            
            // Print the table
            print_table($table);
            
            // End the form
            echo "</form>";
            
        // // SEND TO A CUSTOM AVATAR // //
            
            // Start the form
            echo '<br><form action="" method="POST">';
            
            // Use a table for layout
            $table = new stdClass();
            $table->head = array(get_string('sloodleobjectdistributor:sendtocustomavatar','sloodle'));
            $table->align = array('center');
            
            // Output the hidden form data
            echo <<<XXXEODXXX
<input type="hidden" name="s" value="{$sloodle->id}">
XXXEODXXX;
        
            // UUID box
            $table->data[] = array(get_string('uuid','sloodle').': '.'<input type="text" name="user" size="46" maxlength="36" />');
            // Object selection box
            $table->data[] = array(get_string('selectobject','sloodle').': '.$selection_items);
            // Submit button
            $table->data[] = array('<input type="submit" '.$disabledattr.' value="'.get_string('sloodleobjectdistributor:sendtocustomavatar','sloodle').'" />');
            
            // Print the table
            print_table($table);
            
            // End the form
            echo "</form>";
            
        // // ---------- // //
        }
    
        
        print_box_end();
        
        return true;
    }
    
    
?>