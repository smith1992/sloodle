// Sloodle Inventory Box
// A basic inventory-giver object, but which with varying levels of access.
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2008 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Peter R. Bloomfield - original design and implementation
//

// There are several levels of access, which can be chosen at initial configuration time:
//  public = anybody can use it
//  group = only avatars in the same SL group can use it
//  site = only avatars registered on the site can use it
//  course = only avatars enrolled on a particular course can use it


// The "public" and "group" modes do not require configuration.
// However, the "site" and "course" modes require configuration either
//  from a Sloodle Set, or from an internal "sloodle_config" notecard.
// "site" mode requires access to the "sl_avilister_linker.php" script.
// "course" mode requires access to the "sl_enrol_linker.php" script.


///// DATA /////

// Sloodle settings
string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecourseid = 0;
string avilisterlinkerscript = "/mod/sloodle/mod/avilister/sl_avilister_linker.php";
string enrollinkerscript = "/mod/sloodle/login/sl_enrol_linker.php";

// Sloodle communication channels
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_SETTING = 1;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;

// List of objects currently in the box
list myobjects = [];
// List of items to ignore (i.e. not make available upon request)
list ignoreobjects = ["sloodle_inventory_box", "sloodle_setup_notecard", "sloodle_slave_object", "sloodle_debug", "sloodle_debug_say", "sloodle_config", "sloodle_config_sample"];

// Strided array of data items regarding current users
// Each block contains: uuid, listen, page, timestamp
//  uuid = avatar requesting menu
//  listen = the handle of the listen command for this user's response
//  page = number of menu page currently being displayed
//  timestamp = time of last activity
list currentusers = [];

// Timeout values (seconds)
float TIMEOUT_HTTP = 10.0;
integer TIMEOUT_USER_DIALOG = 20;
// How often to check for timed-out users (# seconds between each check)
float USER_PURGE_TIME = 5.0;


// Menu option texts
string MENU_SKIP = "Skip";
string MENU_RESET = "Reset";
string MENU_SETUP = "Setup";
string MENU_CANCEL = "Cancel";
string MENU_PUBLIC = "Public";
string MENU_GROUP = "Group";
string MENU_SITE = "Site";
string MENU_COURSE = "Course";
string MENU_PREVIOUS = "<<";
string MENU_NEXT = ">>";

// Access levels
integer ACCESS_PUBLIC = 1;
integer ACCESS_GROUP = 2;
integer ACCESS_SITE = 3;
integer ACCESS_COURSE = 4;

// Request descriptors
string REQUEST_DESC_SITE = "SITE_QUERY";
string REQUEST_DESC_COURSE = "COURSE_QUERY";

// Currently enabled access level
integer myaccesslevel = 1; // Defaults to public, but can be changed upon startup

// When we arrive in setup mode, should the setup menu be immediately displayed?
integer immediate_setup = FALSE;


///// FUNCTIONS /////

// Send a debug message to the linked debug script (if there is one)
sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// Configure by receiving a linked message from another script in the object
sloodle_handle_command(string str)
{
    list bits = llParseString2List(str,["|"],[]);
    string name = llList2String(bits,0);
    string value = llList2String(bits,1);
    if (name == "set:sloodleserverroot") {
        sloodleserverroot = value;
    } else if (name == "set:pwd") {
        sloodlepwd = value;
        if (llGetListLength(bits) == 3) {
            sloodlepwd = sloodlepwd + "|" + llList2String(bits,2);
        }
    } else if (name == "set:sloodle_courseid") {
        sloodlecourseid = (integer)value;
    }
}

// Reset the script and related scripts
resetScript()
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:reset", NULL_KEY);
    llResetScript();
}

// Refresh list of available objects
refresh_object_list()
{
    sloodle_debug("Refreshing object list.");
    // Clear the list
    myobjects = [];
    // Get the number of items we have
    integer numitems = llGetInventoryNumber(INVENTORY_ALL);
    // Go through each one
    integer i = 0;
    string name = "";
    for (; i < numitems; i++) {
        // Make sure this is not on the ignore list
        name = llGetInventoryName(INVENTORY_ALL, i);
        if (llListFindList(ignoreobjects, [name]) < 0) {
            myobjects += [name];
        }
    }
    
    sloodle_debug("Available inventory items: " + (string)llGetListLength(myobjects));    
}

// Request a site query
request_site_query(key uuid)
{
    // Construct the URL
    string url = sloodleserverroot + avilisterlinkerscript;
    // Construct the body
    string body = "sloodlepwd=" + sloodlepwd;
    body += "&sloodleuuid=" + (string)uuid;
    body += "&sloodlerequestdesc=" + REQUEST_DESC_SITE;
    // Send the request
    llHTTPRequest(url, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
}

// Request a course query
request_course_query(key uuid)
{
    // Construct the URL
    string url = sloodleserverroot + enrollinkerscript;
    // Construct the body
    string body = "sloodlepwd=" + sloodlepwd;
    body += "&sloodlemode=check-enrolled";
    body += "&sloodlecourseid=" + (string)sloodlecourseid;
    body += "&sloodleuuid=" + (string)uuid;
    body += "&sloodlerequestdesc=" + REQUEST_DESC_COURSE;
    // Send the request
    llHTTPRequest(url, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
}

// Show the specified page of objects to the specified user
// (usernum identifies the position of the UUID in "current_users")
show_object_menu(integer usernum, integer pagenum)
{
    // First, let's check that the usernumber is ok
    if (usernum < 0 || (usernum + 3) > llGetListLength(currentusers)) {
        sloodle_debug("Requested to show object menu for invalid user #" + (string)usernum);
        return;
    }
    
    // Get the UUID of the user
    key uuid = llList2Key(currentusers, usernum);
    if (uuid == NULL_KEY) {
        sloodle_debug("Null UUID for user " + (string)usernum);
        return;
    }
    
    // Start our list of buttons
    list buttons = [];
    
    // Get the number of items available
    integer numitems = llGetListLength(myobjects);
    if (numitems <= 0) {
        buttons = [MENU_CANCEL];
        if (uuid == llGetOwner()) buttons += [MENU_SETUP];
        llDialog(uuid, "Sorry. There are no items available.", buttons, SLOODLE_CHANNEL_AVATAR_DIALOG);
        return;
    }
    
    // Determine the number of pages (we can display 9 objects per page (the other 3 buttons are reserved for "PREVIOUS", "CANCEL", and "NEXT" buttons)
    integer numpages = ((numitems - 1) / 9) + 1;
    // Now check that the pagenumber is valid
    if (pagenum < 0) pagenum = 0;
    else if (pagenum >= numpages) pagenum = numpages - 1;
    
    // Replace the "Cancel" button with a "Setup" button for the object owner
    if (uuid == llGetOwner()) buttons = [MENU_PREVIOUS, MENU_SETUP, MENU_NEXT];
    else buttons = [MENU_PREVIOUS, MENU_CANCEL, MENU_NEXT];
    // Construct the text for the dialog
    string msg = "Sloodle Inventory Box.\nPlease select an item from below, or use the buttons to navigate between pages of objects.\n";
    msg += "(Currently showing page " + (string)(pagenum + 1) + " of " + (string)numpages + ")\n\n";
    // Go through each available item
    integer i = 0;
    integer itemnum = pagenum * 9;
    for (; i < 9 && itemnum < numitems; i++, itemnum++) {
        // Add a button and a note to the dialog text
        buttons += [(string)(itemnum + 1)];
        msg += (string)(itemnum + 1) + " = " + llList2String(myobjects, itemnum) + "\n";
    }
    
    // Display the dialog
    llDialog(uuid, msg, buttons, SLOODLE_CHANNEL_AVATAR_DIALOG);
    // Update the page number and last-action time
    currentusers = llListReplaceList(currentusers, [pagenum, llGetUnixTime()], usernum + 2, usernum + 3);
    
}

// Add a user to the list
add_user(key id)
{
    // Check that this user is not already on the list
    integer pos = llListFindList(currentusers, [id]);
    if (pos >= 0) {
        // Update the timestamp
        currentusers = llListReplaceList(currentusers, [llGetUnixTime()], pos + 3, pos + 3);
    } else {
        // Start listening for the user
        integer listenhandle = llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", id, "");
        // Append the basic information
        currentusers += [id, listenhandle, 0, llGetUnixTime()];
        pos = llListFindList(currentusers, [id]);
    }
    
    // Display the menu at the start
    show_object_menu(pos, 0);
}

// Show the setup menu to the specified avatar
show_setup_menu(key id)
{
    // Construct the information for our dialog
    list buttons = [MENU_PUBLIC, MENU_GROUP, MENU_RESET];
    string msg = "You can select which access-level you would like to allow for this inventory box, or you can reset it completely.";
    // Check that the object is fully configured
    if ((sloodleserverroot != "") && (sloodlepwd != "") && (sloodlecourseid != 0)) {
        // Only allow these buttons if the object is fully configured
        buttons += [MENU_COURSE, MENU_SITE];
    } else {
        // Only show this information if the object is *not* fully configured
        msg += "\nNOTE: \"site\" and \"course\" access levels are not possible because this object is not fully configured. Please either delete and re-rez this object from your Sloodle Set, or provide a \"sloodle_config\" notecard and reset this object.";
    }
    
    // Show the basic configuration menu
    llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", id, "");
    llDialog(id, msg, buttons, SLOODLE_CHANNEL_AVATAR_DIALOG);
}


///// STATES /////

// Uninitialised - waiting for configuration
default
{
    state_entry()
    {
        llSetText("Waiting for basic configuration.\nTouch me for a menu.", <1.0,0.0,0.0>, 0.9);
        // Reset our values
        sloodleserverroot = "";
        sloodlepwd = "";
        sloodlecourseid = 0;
    }
    
    on_rez(integer start_param)
    {
        resetScript();
    }
    
    touch_start(integer num_detected)
    {
        // Ignore anybody but the owner
        if (llDetectedKey(0) != llGetOwner()) {
            llSay(0, "Sorry " + llDetectedName(0) + ". Only the object owner can do the configuration.");
            return;
        }
        
        // Show the basic configuration menu
        llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", llDetectedKey(0), "");
        llDialog(llDetectedKey(0), "This object does have its basic configuration yet. You can skip this if you only want to use \"public\" or \"group\" access levels. You can also reset the item.", [MENU_SKIP,MENU_RESET,MENU_CANCEL], SLOODLE_CHANNEL_AVATAR_DIALOG);
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Ignore anybody but the owner
            if (id != llGetOwner()) return;
            // Check the message
            if (msg == MENU_SKIP) {
                // Skip the basic configuration - go to setup
                immediate_setup = TRUE;
                state setup;
                return;
            } else if (msg == MENU_RESET) {
                // Totally reset the object
                resetScript();
            } else if (msg == MENU_CANCEL) {
                // Cancel... do nothing
            }
            
            return;
        }
    }
    
    link_message(integer sender_num, integer num, string str, key id)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            sloodle_handle_command(str);

            // If have all our settings, then move on
            if ((sloodleserverroot != "") && (sloodlepwd != "") && (sloodlecourseid != 0)) {
                state setup;
            }
        }
    }
}

// Setup process - owner must select which access level they want
state setup
{
    state_entry()
    {
        llSetText("Awaiting owner setup.\nTouch me for menu.", <1.0,0.5,0.0>, 1.0);
        
        if (immediate_setup) {
            show_setup_menu(llGetOwner());
        }
        immediate_setup = FALSE;
    }
    
    state_exit()
    {
        llSetText("", <0.0,0.0,0.0>, 0.0);
    }
    
    touch_start(integer num_detected)
    {
        // Ignore anybody but the owner
        if (llDetectedKey(0) != llGetOwner()) {
            llSay(0, "Sorry " + llDetectedName(0) + ". Only the object owner can do the setup process.");
            return;
        }
        
        show_setup_menu(llDetectedKey(0));
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Ignore anybody but the owner
            if (id != llGetOwner()) return;
            // Check the message
            if (msg == MENU_RESET) {
                // Totally reset the object
                resetScript();
            
            } else if (msg == MENU_PUBLIC) {
                // Allow public access
                myaccesslevel = ACCESS_PUBLIC;
                state ready;
                return;
            
            } else if (msg == MENU_GROUP) {
                // Group-level access
                myaccesslevel = ACCESS_GROUP;
                state ready;
                return;
                
            } else if ((sloodleserverroot != "") && (sloodlepwd != "") && (sloodlecourseid != 0)) {
                // Only respond to these if the object is fully configured
                if (msg == MENU_SITE) {
                    // Site-level access
                    myaccesslevel = ACCESS_SITE;
                    state ready;
                    return;
                    
                } else if (msg == MENU_COURSE) {
                    // Course-level access
                    myaccesslevel = ACCESS_COURSE;
                    state ready;
                    return;
                }
            }
            
            return;
        }
    }    
}

// Ready for use - will respond to clicks
state ready
{
    state_entry()
    {
        // Refresh the list of objects
        refresh_object_list();
    
        // Determine what hover text to display, based on site, course, and access level
        string hovertext = "Ready. Touch me to select inventory items.";
        if (myaccesslevel == ACCESS_PUBLIC) {
            hovertext += "\nPUBLIC: anybody can use this object.";
        } else if (myaccesslevel == ACCESS_GROUP) {
            hovertext += "\nGROUP: only members of the same group can use this object.";
        } else if (myaccesslevel == ACCESS_SITE) {
            hovertext += "\nSITE: only registered avatars may use this object\n[site: " + sloodleserverroot + "]";
        } else if (myaccesslevel == ACCESS_COURSE) {
            hovertext += "\nCOURSE: only registered and enrolled users may use this object\n[site: " + sloodleserverroot + "]\n[course #" + (string)sloodlecourseid + "]";
        }
    
        // Set the hover text
        llSetText(hovertext, <0.0,1.0,0.0>, 1.0);
        
        // Clear the list of current users
        currentusers = [];
        // Start the timeout for purging users
        llSetTimerEvent(USER_PURGE_TIME);
    }
    
    touch_start(integer num_detected)
    {
        // Go through each user
        integer i = 0;
        integer pos = 0;
        for (; i < num_detected; i++) {
            // Is this user already on our list?
            pos = llListFindList(currentusers, [llDetectedKey(i)]);
            if (pos >= 0) {
                // Yes - simply re-display their most recent menu page
                show_object_menu(pos, llList2Integer(currentusers, pos + 2));
                
            } else {
                // No - check the access requirements
                if (myaccesslevel == ACCESS_PUBLIC) {
                    // Public - add them happily!
                    add_user(llDetectedKey(i));
                } else if (myaccesslevel == ACCESS_GROUP) {
                    // Check group membership
                    if (llSameGroup(llDetectedKey(i))) {
                        add_user(llDetectedKey(i));
                    } else {
                        llDialog(llDetectedKey(i), "Sorry. You are not in the correct group to use this object.", [MENU_CANCEL], SLOODLE_CHANNEL_AVATAR_IGNORE);
                    }
                } else if (myaccesslevel == ACCESS_SITE) {
                    // Request confirmation of site-level authorisation
                    request_site_query(llDetectedKey(i));
                } else if (myaccesslevel == ACCESS_COURSE) {
                    // Request confirmation of course enrolment
                    request_course_query(llDetectedKey(i));
                }
            }
        }
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            sloodle_debug("Heard avatar dialog message: " + msg);
        
            // Make sure the avatar is recognised
            integer pos = llListFindList(currentusers, [id]);
            if (pos < 0) {
                sloodle_debug("User not recognised. Ignoring message.");
                return;
            }
            
            // Should the user be removed following this?
            integer remove_user = FALSE;
            
            // Check the message
            if (msg == MENU_SETUP && id == llGetOwner()) {
                // Go back to setup mode
                immediate_setup = TRUE;
                state setup;
                return;
                
            } else if (msg == MENU_CANCEL) {
                // Cancel the request, and remove the user
                remove_user = TRUE;
                
            } else if (msg == MENU_PREVIOUS) {
                // Request the previous menu page
                integer curpagenum = llList2Integer(currentusers, pos + 2);
                if (curpagenum < 0) curpagenum = 0;
                else curpagenum -= 1;
                // Display it (this function will validate and re-store the page number)
                show_object_menu(pos, curpagenum);
            
            } else if (msg == MENU_NEXT) {
                // Request the next menu page
                integer curpagenum = llList2Integer(currentusers, pos + 2);
                if (curpagenum < 0) curpagenum = 0;
                curpagenum += 1;
                // Display it (this function will validate and re-store the page number)
                show_object_menu(pos, curpagenum);
            
            } else {
                // May be an object request - remove user after this
                remove_user = TRUE;
                
                // This message may be a number - get it
                integer msgnum = (integer)msg;
                // We number the items on the dialog from 1
                if (msgnum > 0) {
                    // The list is 0-based
                    msgnum -= 1;
                    // Make sure it's a valid number
                    if (msgnum < llGetListLength(myobjects)) {
                        // Attempt to give the inventory item
                        llGiveInventory(id, llList2String(myobjects, msgnum));
                    } else {
                        llDialog(llList2Key(currentusers, pos), "Sorry. I could not find that object. It may have been removed recently.", [MENU_CANCEL], SLOODLE_CHANNEL_AVATAR_IGNORE);
                    }
                }
            }
            
            // Do we need to remove the user now?
            if (remove_user) {
                // Cancel the listen first
                llListenRemove(llList2Integer(currentusers, pos + 1));
                // Now remove the list items
                currentusers = llDeleteSubList(currentusers, pos, pos + 3);
            }
        }
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Check the status
        if (status != 200) {
            sloodle_debug("HTTP request failed with status " + (string)status);
            return;
        }
        
        // Make sure the response was not empty
        if (body == "") {
            sloodle_debug("ERROR: empty HTTP response body.");
            return;
        }
        sloodle_debug("HTTP Response: " + body);
        
        // Split the response into lines, and extract the status fields
        list lines = llParseStringKeepNulls(body, ["\n"], []);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
        integer numstatusfields = llGetListLength(statusfields);
        integer statuscode = llList2Integer(statusfields, 0);
        
        // Was an error reported?
        if (statuscode <= 0) {
            string errmsg = "";
            if (numlines > 1) errmsg = llList2String(lines, 1);
            sloodle_debug("ERROR ("+(string)statuscode+"): " + errmsg);
            return;
        }
        
        // Check that we have enough status fields
        if (numstatusfields < 7) {
            sloodle_debug("ERROR - insufficient status fields: " + llList2String(lines, 0));
            return;
        }
        
        // Extract the additional information
        string request_descriptor = llList2String(statusfields, 3);
        key uuid = (key)llList2String(statusfields, 6);
        if (uuid == NULL_KEY) {
            sloodle_debug("ERROR: no key (or invalid key) provided in UUID field of HTTP response");
            return;
        }
        
        // This value will indicate whether or not the person has been authorized
        integer user_authorized = FALSE;

        // Check what the response is
        if (request_descriptor == REQUEST_DESC_SITE) {
            // Checking for site registration
            // They are registered if the AviLister returned their details as "avatar_name|moodle_name"
            if (numlines >= 2) {
                list dataparts = llParseStringKeepNulls(llList2String(lines, 1), ["|"], []);
                if (llGetListLength(dataparts) > 1) {
                    if (llList2String(dataparts, 1) != "") {
                        user_authorized = TRUE;
                    }
                }
            }
            
            // Inform the user if they have been reject
            if (!user_authorized) {
                llDialog(uuid, "Sorry. Your avatar needs to be registered with a Moodle account at " + sloodleserverroot + " in order to use this object.", [MENU_CANCEL], SLOODLE_CHANNEL_AVATAR_IGNORE);
            }
            
        } else if (request_descriptor == REQUEST_DESC_COURSE) {
            // Checking for course enrolment
            // Very simple - if there is a 1 on the data line, then the user is enrolled
            if (numlines >= 2) {
                if (llList2String(lines, 1) == "1") {
                    user_authorized = TRUE;
                }
            }
            
            // Inform the user if they have been reject
            if (!user_authorized) {
                llDialog(uuid, "Sorry. Your avatar needs to be registered with a Moodle account at " + sloodleserverroot + ", and your Moodle account needs to be enrolled in course #" + (string)sloodlecourseid + ", in order to use this object.", [MENU_CANCEL], SLOODLE_CHANNEL_AVATAR_IGNORE);
            }
            
        } else {
            sloodle_debug("Unrecognised request descriptor \"" + request_descriptor + "\"");
            return;
        }
        
        // If the user was authorized, then add them
        if (user_authorized) {
            add_user(uuid);
        }
    }
    
    timer()
    {
        // Step through the whole list, looking for timed-out users
        integer numusers = llGetListLength(currentusers);
        if (numusers == 0) return;
        integer i = 0;
        integer curtime = llGetUnixTime();
        integer numremoved = 0;
        sloodle_debug("Scanning user list for expired entries.");
        for (; i < numusers; i += 4) {
            // Has the last action time, plus the timeout, already passed?
            if ((llList2Integer(currentusers, i + 3) + TIMEOUT_USER_DIALOG) < curtime) {
                numremoved++;
                // Cancel the listen first
                llListenRemove(llList2Integer(currentusers, i + 1));
                // Now remove the list items
                currentusers = llDeleteSubList(currentusers, i, i + 3);
            }
        }
        
        sloodle_debug("Done. Number removed: " + (string)numremoved);
    }
    
    changed(integer ch)
    {
        if (ch & CHANGED_INVENTORY) refresh_object_list();
    }
}
