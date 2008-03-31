// Sloodle MetaGloss
// Allows access to a Moodle glossary from in-world
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-7 Sloode (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Jeremy Kemp - original design and implementation
//  Peter R. Bloomfield - re-written and updated to use new communications format (Sloodle 0.2), and to standardize configuration with other tools
//


// FUNCTION
//  Listens for a command from its owner starting with "/def"
//  Send the query to Moodle script
//  The script returns the text definition
//  The prim chats the results in-world

// VERSIONS
//  1.0 - complete rewrite for Sloodle 0.2
//  0.3 - docs for NMC session
//  0.1 - Proof of concept


///// DATA /////

// ID of the Moodle course which the glossary is in
integer sloodlecourseid = 0;
// Course module ID for the glossary we are accessing
integer sloodlemoduleid = 0;

// Root address of the Moodle server
string sloodleserverroot = "";
// Prim password for authenticating this object
string sloodlepwd = "";

// Relative address of the glossary linker
string glossarylinker = "/mod/sloodle/mod/glossary/sl_glossary_linker.php";

// ID of our HTTP requests for various purposes
key httplistrequest = NULL_KEY; // List of glossaries
key httpdefrequest = NULL_KEY; // Definition lookup

// What chat channel should we receive user info on?
integer USER_CHAT_CHANNEL = 0;
// What channel should configuration data be received on?
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
// This channel will be used for the avatar setting options
integer SLOODLE_CHANNEL_AVATAR_SETTING = 1;

// How long should we wait for HTTP responses? (seconds)
float HTTP_TIMEOUT = 10.0;
// After how much inactivity should the MetaGloss automatically deactivate (to save sim processing time)? (seconds)
float INACTIVITY_TIMEOUT = 120.0;

// These lists contain all the data about different glossaries
list glossaryids = [];
list glossarynames = [];
// The currently selected glossary details
integer selectedglossaryid = 0;
string selectedglossaryname = "";
// The concept being looked-up
string lookupconcept = "";

// When the MetaGloss is in the active state, it can be triggered back to the select glossaries states.
// When this happens, the UUID of the person who triggered it will be stored here, and on arrival in the select glossaries state,
//  they will be presented with the dialog menu.
key selectoruuid = NULL_KEY;

///// FUNCTIONS /////

// Output debug info
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

// Request an updated list of glossaries
request_glossary_list_update()
{
    llSetTimerEvent(0.0);
    llSetText("Sloodle MetaGloss\nUpdating list of glossaries...", <1.0, 0.5, 0.0>, 0.9);        
    // Send a request to get a list of all glossaries
    sloodle_debug("Requesting list of glossaries");
    httplistrequest = llHTTPRequest(sloodleserverroot + glossarylinker + "?sloodlepwd=" + sloodlepwd + "&sloodlecourseid=" + (string)sloodlecourseid, [], "");
    llSetTimerEvent(HTTP_TIMEOUT);
}

// Handle a response containing a list of glossaries
// Returns TRUE if it went OK, or FALSE if no glossaries were given
integer handle_glossary_list_response(string body)
{
    // Reset our data
    glossaryids = [];
    glossarynames = [];

    // Process the response
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    integer numlines = llGetListLength(lines);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    
    // Did an error occur?
    if (statuscode <= 0) {
        string errmsg = "";
        if (numlines > 1) errmsg = llList2String(lines, 1);
        llSay(0, "Error reported when requesting list of glossaries: ("+(string)statuscode+") "+errmsg);
        return FALSE;
    }
    
    // Each line should define a glossary - go through each one
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
                glossaryids += [currentid];
                glossarynames += [currentname];
            }
        }
    }
    
    // Everything's OK if we have at least one glossary
    if (llGetListLength(glossaryids) > 0) return TRUE;
    llSay(0, "No glossaries found.");
    return FALSE;
}

// Show a menu of available glossaries to the identified avatar
show_glossary_menu( key uuid )
{
    // Make sure we have some glossaries
    integer numglossaries = llGetListLength(glossaryids);
    // Build up the text for the dialog
    string dlg = "Sloodle MetaGloss Menu\n\n";
    if (numglossaries > 0) {
        dlg += "Select a glossary, or refresh the list of glossaries.";
    } else {
        dlg += "No glossaries to choose from. Please refresh the list.";
    }
    dlg += "\n\n";
    
    // Add each glossary to the menu
    // (but only add as many as we have room for)
    integer i = 0;
    list btns = ["Refresh"];
    for (i = 0; i < numglossaries && i < 11; i++) {
        dlg += (string)(i + 1) + " = " + llList2String(glossarynames, i) + "\n";
        btns += [(string)(i + 1)];
    }
    
    // Start listening for the avatar, and show them the dialog
    llListen(SLOODLE_CHANNEL_AVATAR_SETTING, "", uuid, "");
    llDialog(uuid, dlg, btns, SLOODLE_CHANNEL_AVATAR_SETTING);
}

// Request a definition of the specified concept in the currently selected glossary
request_definition(string concept)
{
    llSetTimerEvent(0.0);
    sloodle_debug("Requesting definition of concept \""+lookupconcept+"\"");
    httpdefrequest = llHTTPRequest(sloodleserverroot + glossarylinker + "?sloodlepwd=" + sloodlepwd + "&sloodlecourseid=" + (string)sloodlecourseid + "&sloodlemoduleid=" + (string)selectedglossaryid + "&sloodleconcept=" + lookupconcept, [], "");
    llSetTimerEvent(HTTP_TIMEOUT);
}

// Handle the response from the server containing a concept definition
handle_definition_response(string body)
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
        llSay(0, "Error reported when looking-up glossaries: ("+(string)statuscode+") "+errmsg);
        return FALSE;
    }
    
    // Each other line should give a definition
    integer i = 1;
    integer numdefs = 0;
    for (i = 1; i < numlines; i++) {
        // Split this line into separate fields
        list fields = llParseStringKeepNulls(llList2String(lines,i), ["|"], []);
        // Make sure we have enough fields
        if (llGetListLength(fields) >= 2) {
            // Extract the data
            string currentterm = llList2String(fields, 0);
            string currentdef = llList2String(fields, 1);
            // If both items are valid, then output the data
            if (currentterm != "" && currentdef != "") {
                llSay(0, currentterm + " = " + currentdef);
                numdefs++;
            }
        }
    }
    
    // Report a problem if no definitions were found
    if (numdefs == 0) llSay(0, "No definitions found for concept \""+lookupconcept+"\".");
}


///// STATES /////

// Uninitialised... awaiting configuration
default
{
    state_entry()
    {
        llSetText("Sloodle MetaGloss\nWaiting for configuration", <1.0,0.0,0.0>, 1.0);
        
        // Reset everything
        sloodlecourseid = 0;
        sloodlemoduleid = 0;
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
                if (sloodlemoduleid != 0) state active;
                else state update_glossary_list;
                return;
            }
        }
    }
}


// Update list of glossaries
state update_glossary_list
{
    state_entry()
    {
        selectoruuid = NULL_KEY;
        request_glossary_list_update();
    }
    
    state_exit()
    {
        llSetTimerEvent(0.0);
        // Reset the HTTP request ID so we know we're not currently trying to retrieve a list of glossaries
        httplistrequest = NULL_KEY;
    }
    
    touch_start(integer num_detected)
    {
        // Make sure we're not already trying to get a list of glossaries
        if (httplistrequest == NULL_KEY) {
            // Try updating our list again
            request_glossary_list_update();
        }
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Ignore unexpected response
        if (id != httplistrequest) return;
        sloodle_debug("HTTP Response ("+(string)status+"): "+body);
        // Reset the HTTP request ID so we know we're not currently trying to retrieve a list of glossaries
        httplistrequest = NULL_KEY;
        
        // Make sure it was a successful response
        if (status != 200) {
            llSay(0, "ERROR: HTTP request for list of glossaries responded with status " + (string)status);
            return;
        }
        // Make sure it's not an empty response
        if (body == "") {
            llSay(0, "ERROR: HTTP request for list of glossaries responded with empty body.");
            return;
        }
        
        // Handle the response
        if (handle_glossary_list_response(body)) {
            // Updated OK... let the user select their glossary
            llSay(0, "Successfully updated list of glossaries.");
            state select_glossary;
            return;
        }
    }
    
    timer()
    {
        llSetTimerEvent(0.0);
        llSay(0, "ERROR: timeout while waiting for HTTP response with list of glossaries. Touch me to try again.");
        // Reset the HTTP request ID so we know we're not currently trying to retrieve a list of glossaries
        httplistrequest = NULL_KEY;
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
}

// Let the user select a glossary from the list
state select_glossary
{
    state_entry()
    {
        llSetText("Sloodle MetaGloss\nNo glossary selected. Click me for glossary menu.", <0.0,0.0,1.0>, 1.0);
        
        // Have we started with somebody already needing the menu?
        if (selectoruuid != NULL_KEY) {
            show_glossary_menu(selectoruuid);
            selectoruuid = NULL_KEY;            
        }
    }
    
    touch_start(integer num_detected)
    {
        show_glossary_menu(llDetectedKey(0));
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel
        if (channel = SLOODLE_CHANNEL_AVATAR_SETTING) {
            // Check the contents of the message
            msg = llToLower(msg);
            if (msg == "refresh") {
                // We are to refresh the list of glossaries
                state update_glossary_list;
                return;
            }
            
            // Maybe it's a number
            integer num = (integer)msg;
            // Ignore it is it's invalid
            if (num <= 0 || num > llGetListLength(glossaryids)) return;
            
            // Fetch the appropriate name and id from our list
            selectedglossaryid = llList2Integer(glossaryids, (num - 1));
            selectedglossaryname = llList2String(glossarynames, (num - 1));
            state active;
        }
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
}

// Inactive - not listening, but ready
state inactive
{
    state_entry()
    {
        selectoruuid = NULL_KEY;
        llSetText("Sloodle MetaGloss: " + selectedglossaryname + "\nCurrently inactive. Click me to activate.", <0.0,0.4,0.0>, 1.0);
    }
    
    touch_start(integer num_detected)
    {
        state active;
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
}

// Active - listening and giving definitions
state active
{
    state_entry()
    {
        llSetTimerEvent(0.0);
        selectoruuid = NULL_KEY;
        llSetText("Sloodle MetaGloss: " + selectedglossaryname + "\nType \"/def \" then a word or phrase to search the glossary.", <0.0,1.0,0.0>, 1.0);
        // Listen for anybody on channel 0
        llListen(0, "", NULL_KEY, "");
        llSetTimerEvent(INACTIVITY_TIMEOUT);
    }
    
    listen(integer channel, string name, key id, string msg)
    {  
        // Check the incoming channel
        if (channel == 0) {
            // Check to see if this starts with "/def "
            if (llToLower(llGetSubString(msg, 0, 4)) != "/def ") return;
            // Start the lookup
            lookupconcept = llGetSubString(msg, 5, -1);
            state lookup;
            return;
        }
    }
    
    touch_start(integer num_detected)
    {
        // Somebody wants to re-select the glossary
        selectoruuid = llDetectedKey(0);
        state select_glossary;
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
    
    timer()
    {
        llSay(0, "Deactivating due to inactivity.");
        state inactive;
    }
}

// Looking up a concept in the glossary
state lookup
{
    state_entry()
    {
        // Make sure the concept is valid
        if (lookupconcept == "") {
            state active;
            return;
        }
        
        llSetText("Sloodle MetaGloss: " + selectedglossaryname + "\nLooking up term \""+lookupconcept+"\". Please wait.", <1.0,1.0,0.0>, 1.0);
        
        // Dispatch the request
        request_definition(lookupconcept);
    }
    
    state_exit()
    {
        lookupconcept = "";
        httpdefrequest = NULL_KEY;
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Ignore unexpected response
        if (id != httpdefrequest) return;
        sloodle_debug("HTTP Response ("+(string)status+"): "+body);
        httpdefrequest = NULL_KEY;
        
        // Make sure it was a successful response
        if (status != 200) {
            llSay(0, "ERROR: HTTP request for definition responded with status " + (string)status);
            return;
        }
        // Make sure it's not an empty response
        if (body == "") {
            llSay(0, "ERROR: HTTP request for definition responded with empty body.");
            return;
        }
        
        // Handle the response
        handle_definition_response(body);
        state active;
    }
    
    timer()
    {
        llSetTimerEvent(0.0);
        llSay(0, "Timeout while looking-up concept \""+lookupconcept+"\". Please try again later.");
        state active;
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
}


