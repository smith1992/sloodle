//////////
//
// Sloodle Choice Back-End
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) Sloodle 2008 (various contributors)
// Released under the GNU GPL
//
//
// Constributors:
//  Peter R. Bloomfield - original design and implementation
//
//
// This script is designed to provide the back-end functionality of the Sloodle Choice tool.
// It should be housed in an object with the usual notecard and slave configuration scripts.
// Front-end scripts can be developed separately, and *must* be in a separate prim, linked to
//  the one which this back-end is in.
//
// The back-end handles all of the Moodle interactions, and processing the requests/responses.
// It will communicate with the front-end script(s) via link message.
//
////////////


///// DATA /////

// Root address of the Moodle server
string sloodleserverroot = "";
// Prim password for authenticating this object
string sloodlepwd = "";

// ID of the Moodle course which the choice is in is in
integer sloodlecourseid = 0;
// Course module ID for the choice we are accessing
integer sloodlemoduleid = 0;
// Name of the module we are accessing
string modulename = "";

// What channel should configuration data be received on?
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
// What channel should choice control operate over?
integer SLOODLE_CHANNEL_OBJECT_CHOICE = -1639270051;
// This channel will be used for the avatar setting options in chat
integer SLOODLE_CHANNEL_AVATAR_SETTING = 1;
// This channel will be used for the avatar dialogs
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
// Relative path to the choice linker script from the Moodle root
string SLOODLE_CHOICE_LINKER_SCRIPT = "/mod/sloodle/mod/choice/sl_choice_linker.php";

// How long should we wait for HTTP responses? (seconds)
float HTTP_TIMEOUT = 10.0;
integer HTTP_TIMEOUT_INT = 10;
// Timeout for rezzing
float REZ_TIMEOUT = 5.0;

// Object name prefix for Sloodle Choice frontend objects
string FRONTEND_PREFIX = "sloodle_choice_frontend:";

// Various menu option texts
string MENU_RESET = "Reset";
string MENU_SETUP = "Setup";
string MENU_NONE = "None";
string MENU_CANCEL = "Cancel";
string MENU_PREVIOUS = "<<";
string MENU_NEXT = ">>";
// Current menu page number (total 12 items per menu, but 2 reserved for next/previous buttons)
integer menupagenumber = 0;
// List of frontend object names
list frontendobjects = [];


// ID of our HTTP requests for various purposes
key httplistrequest = NULL_KEY; // List of available choices
key httpstatusrequest = NULL_KEY; // Status of specific choice
key httpselectionrequest = NULL_KEY; // Select of an option

// These are the timestamps for when individual requests occur
integer timestatusrequest = 0;
integer timeselectionrequest = 0;
// The UUID of the person we are currently requesting a selection for
key selectingavatar = NULL_KEY;

// These lists contain all the data about different available choices
list choiceids = [];
list choicenames = [];
// This list contains all of our pending selection requests. Each entry is 2 elements: avatar UUID, and selection id (integer)
list pendingselections = [];

// ID of a dialog listen command
integer listenid = 0;


///// FUNCTIONS /////

// Output debug information to a debug script
sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// Handle a command received from another script
// Returns TRUE if completely configured, or FALSE otherwise
integer sloodle_handle_command(string str) 
{
    list bits = llParseString2List(str,["|"],[]);
    string name = llList2String(bits,0);
    string value = llList2String(bits,1);
    if (name == "set:moduleid") {
        sloodlemoduleid = (integer)value;
    } else if (name == "set:sloodleserverroot") {
        sloodleserverroot = value;
    } else if (name == "set:pwd") {
        sloodlepwd = value;
        if (llGetListLength(bits) == 3) {
            sloodlepwd += "|" + llList2String(bits,2);
        }
    } else if (name == "set:sloodle_courseid") {
        sloodlecourseid = (integer)value;
    }
    
    // Are we configured?
    if (sloodleserverroot != "" && sloodlepwd != "" && sloodlecourseid != 0) return TRUE;
    return FALSE;
}

// Reset the whole script
resetScript()
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:reset", NULL_KEY);
    llMessageLinked(LINK_ALL_OTHERS, SLOODLE_CHANNEL_OBJECT_CHOICE, "reset", NULL_KEY);
    llResetScript();
}

// Send a message to the front-end of the choice
notify_frontend(string str, key k)
{
    llMessageLinked(LINK_ALL_OTHERS, SLOODLE_CHANNEL_OBJECT_CHOICE, str, k);
}

// Show the idle menu to the specified avatar
show_idle_menu(key id)
{
    // Present the menu
    llDialog(id, "Sloodle Choice Menu\n\nWhat would you like to do?", [MENU_SETUP, MENU_RESET], SLOODLE_CHANNEL_AVATAR_DIALOG);
    // Listen for response
    llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", NULL_KEY, "");
}

// Update the list of frontend objects
update_frontend_list()
{
    // Clear the existing list
    frontendobjects = [];
    // Go through each object in the inventory
    integer num = llGetInventoryNumber(INVENTORY_OBJECT);
    string name = "";
    integer i = 0;
    integer prefixlen = llStringLength(FRONTEND_PREFIX);
    for (; i < num; i++) {
        // Check if this is a frontend object
        name = llGetInventoryName(INVENTORY_OBJECT, i);
        if (llSubStringIndex(name, FRONTEND_PREFIX) == 0) {
            frontendobjects += [llGetSubString(name, prefixlen, -1)];
        }
    }
    
}

// Show the frontend selection menu, at the current page (menupagenumber)
show_frontend_menu(key id)
{
    // Perform basic validation of the menu page number
    if (menupagenumber < 0) menupagenumber = 0;
    // Check how many frontend objects we have
    integer numobjects = llGetListLength(frontendobjects);
    if (numobjects == 0) {
        llDialog(id, "Sloodle Choice Frontend Menu\nSorry, there are no frontends available. Select \"None\" to go ahead without rezzing a front-end, or \"Cancel\" to return to idle state.", [MENU_NONE, MENU_CANCEL], SLOODLE_CHANNEL_AVATAR_IGNORE);
        return;
    }
    
    // Check how many pages we ought to have
    integer totalnumpages = numobjects / 9;
    
    // Construct the list of buttons
    //...
    // Display the menu
    //...
    
    // Listen for response
    llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", NULL_KEY, "");
}

// Send a request for a list of choices
request_choice_list()
{
    // Request a list of choices
    llSetTimerEvent(0.0);
    string url = sloodleserverroot + SLOODLE_CHOICE_LINKER_SCRIPT + "?sloodlepwd=" + sloodlepwd + "&sloodlecourseid=" + (string)sloodlecourseid;
    sloodle_debug("Requesting list of choices with URL: " + url);
    httplistrequest = llHTTPRequest(url, [], "");
    llSetTimerEvent(HTTP_TIMEOUT);
}

// Handle an HTTP response containing a list of available choices
integer handle_choice_list_response(string body)
{
    // Reset our values
    choiceids = [];
    choicenames = [];
    
    // Process the response
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    integer numlines = llGetListLength(lines);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    
    // Did an error occur?
    if (statuscode <= 0) {
        string errmsg = "";
        if (numlines > 1) errmsg = llList2String(lines, 1);
        llSay(0, "Error reported when requesting list of choices: ("+(string)statuscode+") "+errmsg);
        return FALSE;
    }
    
    // Each line should define a choice - go through each one
    integer i = 1;
    for (i = 1; i < numlines; i++) {
        // Split this line into separate fields
        list fields = llParseStringKeepNulls(llList2String(lines,i), ["|"], []);
        // Make sure we have enough fields
        if (llGetListLength(fields) >= 2) {
            // Extract the data
            integer currentid = llList2Integer(fields, 0);
            string currentname = llList2String(fields, 1);
            // If both items are valid, then store the data
            if (currentid > 0 && currentname != "") {
                choiceids += [currentid];
                choicenames += [currentname];
            }
        }
    }
    
    // Everything's OK if we have at least one glossary
    if (llGetListLength(choiceids) > 0) return TRUE;
    llSay(0, "No choice modules found.");
    return FALSE;
}

// Show a menu of available chocies to the identified avatar
show_choice_menu( key uuid )
{
    // Make sure we have some choice modules
    integer numchoices = llGetListLength(choiceids);
    // Build up the text for the dialog
    string dlg = "Sloodle Choice Menu\n\n";
    if (numchoices > 0) {
        dlg += "Select a choice, or refresh the list of choices.";
    } else {
        dlg += "No choice modules to choose from. Please refresh the list.";
    }
    dlg += "\n\n";
    
    // Add each choice to the menu
    // (but only add as many as we have room for)
    integer i = 0;
    list btns = ["Refresh"];
    for (i = 0; i < numchoices && i < 11; i++) {
        dlg += (string)(i + 1) + " = " + llList2String(choicenames, i) + "\n";
        btns += [(string)(i + 1)];
    }
    
    // Start listening for the avatar, and show them the dialog
    llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", uuid, "");
    llDialog(uuid, dlg, btns, SLOODLE_CHANNEL_AVATAR_DIALOG);
}

// Request a status update of the specified choice module
request_status_update(integer id)
{
    // Is a request already in progress?
    if (httpstatusrequest != NULL_KEY) {
        // Yes - has the request time expired?
        if ((timestatusrequest + HTTP_TIMEOUT_INT) >= llGetUnixTime()) {
            // No - keep waiting
            sloodle_debug("Existing status request is still current... waiting...");
            return;
        }
    }

    sloodle_debug("Requesting new status update of choice #" + (string)id);
    httpstatusrequest = llHTTPRequest(sloodleserverroot + SLOODLE_CHOICE_LINKER_SCRIPT + "?sloodlepwd=" + sloodlepwd + "&sloodlecourseid=" + (string)sloodlecourseid + "&sloodlemoduleid=" + (string)sloodlemoduleid, [], "");
    timestatusrequest = llGetUnixTime();
}

// Process the next item in the selection queue
process_selection_queue()
{
    // Is a request already in progress?
    if (httpstatusrequest != NULL_KEY) {
        // Yes - has the request time expired?
        if ((timeselectionrequest + HTTP_TIMEOUT_INT) >= llGetUnixTime()) {
            // No - keep waiting
            sloodle_debug("Existing selection request is still current... waiting...");
            return;
        }
    }

    // Force us to ignore any existing selection (which may have failed)
    httpselectionrequest = NULL_KEY;
    
    // Check that there are items to process
    integer queuelength = llGetListLength(pendingselections) / 2;
    if (queuelength == 0) return;
    
    // Get the first items
    sloodle_debug("Processing first item on pending selection queue. Queue length: " + (string)queuelength);
    selectingavatar = (string)llList2Key(pendingselections, 0);
    integer optionid = llList2Integer(pendingselections, 1);
    // Remove the first item
    pendingselections = llDeleteSubList(pendingselections, 0, 1);
    
    // Make a request
    if (selectingavatar != NULL_KEY) {
        httpselectionrequest = llHTTPRequest(sloodleserverroot + SLOODLE_CHOICE_LINKER_SCRIPT + "?sloodlepwd=" + sloodlepwd + "&sloodlecourseid=" + (string)sloodlecourseid + "&sloodlemoduleid=" + (string)sloodlemoduleid + "&sloodleuuid=" + (string)selectingavatar + "&sloodleoptionid=" + (string)optionid, [], "");
        timestatusrequest = llGetUnixTime();
    }
}

// Request a selection of the specified choice module
request_selection(key avatar, integer optionid)
{
    // Add the selection to the queue, and continue processing it
    pendingselections += [avatar];
    pendingselections += [optionid];
    process_selection_queue();
}

// Handle a response to a status request
handle_status_response(string body)
{
    // Process the response
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    integer numlines = llGetListLength(lines);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    
    // Did an error occur?
    if (statuscode <= 0) {
        string errmsg = "";
        if (numlines > 1) errmsg = llList2String(lines, 1);
        sloodle_debug("Error reported when requesting status of a choice module: ("+(string)statuscode+") "+errmsg);
        return FALSE;
    }
    
    // Some useful strings
    string optionid = "";
    string optiontext = "";
    string optionsels = "";
    
    // Go through each line
    integer i = 1;
    for (i = 1; i < numlines; i++) {
        // Split this line into separate fields
        list fields = llParseStringKeepNulls(llList2String(lines,i), ["|"], []);
        
        // Make sure we have enough fields
        if (llGetListLength(fields) >= 1) {
            // Determine the type of line, and output each item of data as we hit it
            string cmd = llList2String(fields, 0);
            if (cmd == "choice_name") {
                modulename = llList2String(fields, 1);
            } else if (cmd == "choice_text") {
                notify_frontend("question|" + llList2String(fields, 1), NULL_KEY);
                
            } else if (cmd == "option") {
                optionid = llList2String(fields, 1);
                optiontext = llList2String(fields, 2);
                optionsels = llList2String(fields, 3);
                notify_frontend("option|" + optionid + "|" + optiontext + "|" + optionsels, NULL_KEY);
                
            } else if (cmd == "num_unanswered") {
                notify_frontend("num_unanswered|" + llList2String(fields, 1), NULL_KEY);
                
            } else if (cmd == "accepting_answers") {
                notify_frontend("accepting_answers|" + llList2String(fields, 1), NULL_KEY);
            }
        }
    }
    
    // Notify the frontend that the update has completed
    notify_frontend("update_complete", NULL_KEY);
}

// Handle a response to a selection request
handle_selection_response(string body)
{
    // Process the response
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    integer numlines = llGetListLength(lines);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    
    // Did an error occur?
    if (statuscode <= 0) {
        string errmsg = "";
        if (numlines > 1) errmsg = llList2String(lines, 1);
        sloodle_debug("Error reported when requesting selection of a choice module: ("+(string)statuscode+") "+errmsg);
    }
    
    // Whether it was an error or not, report to the frontend
    notify_frontend("selection_response|" + (string)statuscode, selectingavatar);
    
    // Now perform a status update
    request_status_update(sloodlemoduleid);
}



// The default 'uninitialised' state.
// While in this state, the script is waiting for configuration
default
{
    state_entry()
    {
        // Indicate the status
        sloodle_debug("Waiting for configuration...");
        llSetText("Waiting for configuration...", <1.0, 0.5, 0.0>, 0.5);
        
        // Reset everything
        sloodlecourseid = 0;
        sloodlemoduleid = 0;
        modulename = "";
        sloodleserverroot = "";
        sloodlepwd = "";
    }
    
    state_exit()
    {
    }
    
    link_message(integer sender_num, integer num, string msg, key id)
    {
        // Ignore debug messages
        if (num == DEBUG_CHANNEL) return;
        
        // Is this an object dialog message?
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Handle the command
            if (sloodle_handle_command(msg)) {
                // Has the module already been specified?
                //if (sloodlemoduleid != 0) state active;
                //else state update_choice_list;
                state idle;
                return;
            }
        }
    }
    
    touch_start(integer num_detected)
    {
        if (llDetectedKey(0) == llGetOwner()) resetScript();
    }
}


// In this state, the object is initialised, but not doing anything
// The owner can touch it to select options
state idle
{
    state_entry()
    {
        // Indicate the status
        sloodle_debug("Idle.");
        llSetText("Initialised.\nTouch me for setup/reset menu.", <1.0, 0.5, 0.0>, 0.5);
    }
    
    touch_start(integer num_detected)
    {
        // Ignore anybody but the owner
        if (llDetectedKey(0) != llGetOwner()) {
            llSay(0, "Sorry " + llDetectedName(0) + ". Only the owner can perform the setup process.");
            return;
        }
        
        // Show the menu
        show_idle_menu();
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel number
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Check what the message was
            if (msg == MENU_SETUP) {
                // Start the setup process
                state select_frontend;
                retunr;
            } else if (msg == MENU_RESET) {
                // Reset the whole thing
                resetScript();
            }
        }
    }
}


// Select and rez a front-end display
state select_frontend
{
    state_entry()
    {
        // Indicate the status
        sloodle_debug("Selecting frontend.");
        llSetText("Selecting frontend.\nSelect a frontend from the menu.\n(Or touch me to see the menu again)", <1.0, 0.5, 0.0>, 0.5);
        
        // Reset and show the frontend menu
        menupagenumber = 0;
        show_frontend_menu();
    }
    
    touch_start(integer num_detected)
    {
        // Ignore anybody but the owner
        if (llDetectedKey(0) != llGetOwner()) {
            llSay(0, "Sorry " + llDetectedName(0) + ". Only the owner can select a frontend.");
            return;
        }
        
        // Reset and show the menu
        menupagenumber = 0;
        show_frontend_menu();
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check channel
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
        
            // Check for expected standard messages
            if (msg == MENU_PREVIOUS) {
                // Move to the previous menu page and show the menu again
                if (currentmenupage > 0) currentmenupage -= 1;
                else currentmenupage = 0;
                show_frontend_menu();
                return;
            } else if (msg == MENU_NEXT) {
                // Move to the next menu page and show the menu again
                if (currentmenupage >= 0) currentmenupage += 1;
                else currentmenupage = 1;
                show_frontend_menu();
                return;
            } else if (msg == MENU_NONE) {
                // Do not rez a frontend at all -- just move on
                state update_choice_list;
                return;
            } else {
                // Convert the message to an integer
                integer optionnum = (integer)msg;
                // The menu items should have been numbered from 1, so ignore anything <= 0 or bigger than the list length
                if (optionnum <= 0 || optionnum > llGetListLength(frontendobjects)) {
                    llOwnerSay("Invalid selection. Please try again.");
                    return;
                }
                // The list of items is numbered from 0, so subtract one
                optionnum -= 1;
                
                // Attempt to rez the selected item above this one
                string objname = FRONTEND_PREFIX + llList2String(frontendobjects, optionnum);
                if (llGetInventoryType(objname) != INVENTORY_OBJECT) {
                    llOwnerSay("Error: frontend object \"" + objname + "\" not found. Please try again.");
                    return;
                }
                vector myscale = llGetScale();
                vector pos = llGetPos();
                pos.z += (myscale.z / 2.0);
                // Note: we give it a non-zero starting parameter so it knows it was automatically rezzed.
                //  It should be programmed to delete itself on unlinking if automatically rezzed.
                sloodle_debug("Rezzing object: " + objname);
                llRezObject(objname, pos, <0.0,0.0,0.0>, ZERO_ROTATION, -123);
                // Start the rez timeout (in case rezzing fails)
                llSetTimerEvent(REZ_TIMEOUT);
            }
            
        
            // Move on to selecting the choice
            state update_choice_list;
            return;
        }
    }
    
    timer()
    {
        llSetTimerEvent(0.0);
        llOwnerSay("Frontend failed to rez. Touch me to try again.");
        sloodle_debug("Object rezzing timeout.");
    }
    
    object_rez(key id)
    {
        // A frontend was just rezzed
        // De-link any existing frontend
        sloodle_debug("Object rezzed. Breaking existing links.");
        llBreakAllLinks();
        // Link with the new item
        sloodle_debug("Creating new link.");
        llCreateLink(id, TRUE);
    }
}


// In this state, the script will retrieve a list of available choices
state update_choice_list
{
    state_entry()
    {
        // Indicate the status
        sloodle_debug("Updating list of choices.");
        llSetText("Fetching list of choices...", <1.0, 0.5, 0.0>, 0.5);
        request_choice_list();
    }
    
    timer()
    {
        // Timeout
        llSetTimerEvent(0.0);
        httplistrequest = NULL_KEY;
        llSay(0, "Timeout while waiting for Moodle to respond with list of choices. Touch me to try again.");
    }
    
    touch_start(integer num_detected)
    {
        // Only respond to the owner
        if (llDetectedKey(0) == llGetOwner()) {
            // Has a request timed-out?
            if (httplistrequest == NULL_KEY) {
                // Retry
                request_choice_list();
            } else {
                llOwnerSay("Waiting for response from Moodle. Please wait.");
            }
        } else {
            llSay(0, "Sorry " + llDetectedName(0) + ", only my owner can control me.");
        }
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Make sure this is the expected ID
        if (id != httplistrequest) return;
        httplistrequest = NULL_KEY;
        // Check the status code
        if (status != 200) {
            llOwnerSay("ERROR: HTTP response status code " + (string)status + ". Touch me to try again.");
            return;
        }
        
        // Handle the response
        if (handle_choice_list_response(body)) {
            // Success - move on to the next state
            state select_choice;
        } else {
            // No choices available
            llOwnerSay("ERROR: no available choices reported by Moodle. Touch me to try again.");
        }
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
}


// In this state, the owner is given a menu of available choice modules to choose from
state select_choice
{
    state_entry()
    {
        // Indicate the status
        sloodle_debug("Selecting choice.");
        llSetText("Selecting choice.\nSelect a choice from the menu.\n(Or touch me to see the menu again)", <1.0, 0.5, 0.0>, 0.5);
        
        // Show a list of choice modules to the owner
        menupagenumber = 0;
        show_choice_menu(llGetOwner());
    }
    
    touch_start(integer num_detected)
    {
        // If the owner touched us, then show the choice module menu
        if (llDetectedKey(0) == llGetOwner()) {
            menupagenumber = 0;
            show_choice_menu(llGetOwner());
        }
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel
        if (channel = SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Make sure it was the owner
            if (id != llGetOwner()) return;
        
            // Check for standard messages
            if (msg == MENU_PREVIOUS) {
                // Move back to the previous menu page, and show the menu again
                if (menupagenumber > 0) menupagenumber -= 1;
                else menupagenumber = 0;
                show_choice_menu(llGetOwner());
                return;
                
            } else if (msg == MENU_NEXT) {
                // Move forward to the next menu page, and show the menu again
                if (menupagenumber >= 0) menupagenumber += 1;
                else menupagenumber = 0;
                show_choice_menu(llGetOwner());
                return;
                
            } else if (msg == MENU_CANCEL) {
                // Go back to the idle state
                state idle;
                return;
            }
            
            // Maybe it's a number
            integer num = (integer)msg;
            // Ignore it if it's invalid
            if (num <= 0 || num > llGetListLength(choiceids)) {
                llOwnerSay("Invalid choice number. Please try again.");
                return;
            }
            
            // Fetch the appropriate name and id from our list
            sloodlemoduleid = llList2Integer(choiceids, (num - 1));
            modulename = llList2String(choicenames, (num - 1));
            state active;
        }
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
}


// In this state, the choice is active and ready run the communications
state active
{
    state_entry()
    {
        // Tell the frontend to reset
        notify_frontend("reset", NULL_KEY);
        // First, let's request an initialy status update of the choice
        request_status_update(sloodlemoduleid);
        // Set a general timer running to make periodic checks of the system
        llSetTimerEvent(10.0);
    }
    
    state_exit()
    {
        // Tell the frontend to reset
        notify_frontend("reset", NULL_KEY);
    }
    
    timer()
    {
        // If there is a pending selection queue, then make sure it gets processed
        if (llGetListLength(pendingselections) > 0) process_selection_queue();
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Check what response this is
        if (id == httpstatusrequest) {
            sloodle_debug("HTTP response - choice status");
            // Request for a status update
            httpstatusrequest = NULL_KEY;
            timestatusrequest = 0;
            // Check the status code
            if (status != 200) {
                sloodle_debug("ERROR: HTTP response for choice status gave status code " + (string)status);
                return;
            }
            // Handle the response
            handle_status_response(body);
            
            return;
            
        } else if (id == httpselectionrequest) {
            sloodle_debug("HTTP response - choice selection");
            // Request to make a selection
            httpselectionrequest = NULL_KEY;
            timeselectionrequest = 0;
            // Check the status code
            if (status != 200) {
                // Report the problem to the front-end
                sloodle_debug("ERROR: HTTP response for choice selection gave status code " + (string)status);
                notify_frontend("", selectingavatar);
                selectingavatar = NULL_KEY;
                
            } else {
                // Handle the response
                handle_selection_response(body);
            }
            
            // Keep processing the selection queue
            selectingavatar = NULL_KEY;
            process_selection_queue();
            return;
        }
    }
    
    touch_start(integer num_detected)
    {
        // Ignore anybody but the owner
        if (llDetectedKey(0) != llGetOwner()) return;
        // Show a menu offering the owner to reset
        llListenRemove(listenid);
        listenid = llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", llGetOwner(), "");
        llDialog(llGetOwner(), "Would you like to reset this choice tool?", ["Reset", "Cancel"], SLOODLE_CHANNEL_AVATAR_DIALOG);
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel
        if (channel = SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Check the contents of the message
            msg = llToLower(msg);
            if (msg == "reset") {
                // We are to reset the entire script
                resetScript();
                return;
            }
            
            // Cancel the listen
            llListenRemove(listenid);
        }
    }
    
    link_message(integer sender_num, integer num, string str, key id)
    {
        // Received a link message - check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_CHOICE) {
            // Split the message into parts (separated by a pipe character)
            list parts = llParseStringKeepNulls(str, ["|"], []);
            integer numparts = llGetListLength(parts);
            string cmd = llList2String(parts, 0);
            
            // Check what the command is
            if (cmd == "selection_request" && id != NULL_KEY) {
                // Request the given selection
                request_selection(id, (integer)llList2String(parts,1));
            }
        }
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
}

