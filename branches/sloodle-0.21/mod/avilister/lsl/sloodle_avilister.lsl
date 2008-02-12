// Sloodle AviLister
// When touched, lists the Moodle names of any known avatars nearby (up to a maximum of 16)
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-8 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Peter R. Bloomfield
//
//


///// CONSTANTS /////
// Timeout values
float SENSOR_TIMEOUT = 15.0; // Time to wait for a sensor
float HTTP_TIMEOUT = 10.0; // Time to wait for an HTTP response

// What channel should configuration data be received on?
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;

// Colours for different states
vector COL_NOT_INIT = <1.0,0.0,0.0>;
vector COL_READY = <1.0,1.0,1.0>;
vector COL_PROCESSING = <0.4,0.1,1.0>;

// Sensor radius (in metres) for detecting avatars
float SENSOR_RADIUS = 48.0;

///// --- /////


///// DATA /////

// The address of the moodle installation
string sloodleserverroot = "";
// The prim password for accessing the site
string sloodlepwd = "";

// Relative paths to the AviLister linker script
string LINKER_SCRIPT = "/mod/sloodle/mod/avilister/sl_avilister_linker.php";

// Keys of the pending HTTP requests
key httpavilistrequest = NULL_KEY;
///// --- /////


///// FUNCTIONS /////
// Send a debug message (requires the "sloodle_debug" script in the same link)
sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// Handle a configuration command - returns TRUE if configuration is complete, or FALSE otherwise
integer sloodle_handle_command(string str) 
{
    // Split the command into separate fields
    list bits = llParseString2List(str,["|"],[]);
    string name = llList2String(bits,0);
    string value = llList2String(bits,1);
    if (name == "set:sloodleserverroot") {
        sloodleserverroot = value;
    } else if (name == "set:pwd") {
        sloodlepwd = value;
        if (llGetListLength(bits) >= 3) {
            sloodlepwd = sloodlepwd + "|" + llList2String(bits,2);
        }
    }

    if ((sloodleserverroot != "") && (sloodlepwd != "")) return TRUE;
    return FALSE;
}

///// --- /////


/// INITIALISING STATE ///
// Waiting for configuration
default
{    
    state_entry()
    {
        // Set to our non-initialised colour initially
        llSetColor(COL_NOT_INIT, ALL_SIDES);
        sloodle_debug("Not initialised.");
    }
    
    state_exit()
    {
    }
    
    link_message(integer sender_num, integer num, string msg, key id)
    {
        // Ignore debug messages
        if (num == DEBUG_CHANNEL) return;
        // Is this a configuration message?
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Yes - handle the command, and switch states if configuration is finished
            if (sloodle_handle_command(msg)) {
                state ready;
                return;
            }
        }
    }
    
    changed( integer change )
    {
        // If there was an inventory change, then just reset
        if ((change & CHANGED_INVENTORY) == CHANGED_INVENTORY)
        {
            //updatepending = TRUE;
            llOwnerSay("Object changed... resetting.");
            llResetScript();
        }
    }
    
    attach( key av )
    {
        if (av != NULL_KEY)
            llResetScript();
    }
}


/// READY STATE ///
// Ready to be used
state ready
{    
    state_entry()
    {
        // Change colour
        llSetColor(COL_READY, ALL_SIDES);
        sloodle_debug("Ready.");
    }
    
    
    touch_start( integer num )
    {
        // Start searching for avatars
        state searching;
    }
    
    changed( integer change )
    {
        // If there was an inventory change, then just reset
        if ((change & CHANGED_INVENTORY) == CHANGED_INVENTORY)
        {
            //updatepending = TRUE;
            llOwnerSay("Object changed... resetting.");
            llResetScript();
        }
    }
    
    attach( key av )
    {
        if (av != NULL_KEY)
            llResetScript();
    }
}


/// SEARCHING ///
// Searching for nearby avatars and waiting for a response
state searching
{   
    state_entry()
    {
        // Change colour
        llSetColor(COL_PROCESSING, ALL_SIDES);
        sloodle_debug("Searching for avatars...");

        // Start a sensor scan within 
        llSensor("", NULL_KEY, AGENT, SENSOR_RADIUS, PI);
        llSetTimerEvent(SENSOR_TIMEOUT);
    }
    
    state_exit()
    {
    }

    no_sensor()
    {
        llOwnerSay("No avatars detected in range.");
        state ready;
    }

    sensor(integer total_number)
    {
        llSetTimerEvent(0.0);
        sloodle_debug("Detected avatars: " + (string)total_number);
        // Build the argument list for our HTTP request
        string arglist = "sloodlepwd=" + sloodlepwd + "&sloodleavnamelist=";
        integer i = 0;
        for (; i < total_number; i++) {
            if (i > 0) arglist += "|";
            arglist += llEscapeURL(llDetectedName(i));
        }

        // Send the HTTP request
        llOwnerSay("Requesting identification of " + (string)total_number + " avatar(s) from: " + sloodleserverroot);
        httpavilistrequest = llHTTPRequest(sloodleserverroot + LINKER_SCRIPT, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], arglist);
        llSetTimerEvent(HTTP_TIMEOUT);
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Ignore unexpected response
        if (id != httpavilistrequest) return;
        llSetTimerEvent(0.0);
        sloodle_debug("HTTP Response ("+(string)status+"): "+body);
        
        // Was the HTTP request successful?
        if (status != 200) {
            llOwnerSay("ERROR: HTTP request failed with status code " + (string)status);
            state ready;
            return;
        }
        
        // Split the response into lines and extract the status fields
        list lines = llParseStringKeepNulls(body, ["\n"], []);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
        integer statuscode = llList2Integer(statusfields, 0);
        // We expect at most 1 data line
        string dataline = "";
        if (numlines > 1) dataline = llList2String(lines, 1);
        
        // Was the status code an error?
        if (statuscode <= 0) {
            // Report the error
            llOwnerSay("Error reported by Moodle (" + (string)statuscode + "): " + dataline);
            state ready;
            return;
        }

        // Make sure some data was returned
        if (numlines < 2) {
            llOwnerSay("None of the detected avatars were recognised by Moodle.");
            state ready;
            return;
        }
        llOwnerSay("Number of avatars identified by Moodle: " + (string)(numlines - 1));
        
        // Go through each line
        integer i = 1;
        for (; i < numlines; i++) {
            // Split this line into separate fields
            list fields = llParseStringKeepNulls(llList2String(lines, i), ["|"], []);
            // Make sure there are enough fields
            if (llGetListLength(fields) >= 2) {
                // Display the data
                llOwnerSay("(SL) " + llList2String(fields, 0) + " -> " + llList2String(fields, 1));
            }
        }
        
        state ready;
    }
    
    timer()
    {
        // We have timed-out waiting for an HTTP response
        llOwnerSay("ERROR: Timeout while searching for avatars.");
        httpavilistrequest = NULL_KEY;
        state ready;
    }
    
    changed( integer change )
    {
        // If there was an inventory change, then just reset
        if ((change & CHANGED_INVENTORY) == CHANGED_INVENTORY)
        {
            llResetScript();
        }
    }
    
    attach( key av )
    {
        if (av != NULL_KEY)
            llResetScript();
    }
}

