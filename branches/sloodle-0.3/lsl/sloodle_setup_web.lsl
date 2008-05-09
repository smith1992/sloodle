// Sloodle web-configuration script
// In the absence of a configuration notecard, allows an object to be authorised/configured via the web
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2008 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Peter R. Bloomfield


integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
string SLOODLE_CONFIG_NOTECARD = "sloodle_config";
string SLOODLE_EOF = "sloodleeof";
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;

string SLOODLE_VERSION_LINKER = "/mod/sloodle/version_linker.php";
string SLOODLE_AUTH_LINKER = "/mod/sloodle/classroom/auth_object_linker.php";
string SLOODLE_CONFIG_INTERFACE = "/mod/sloodle/classroom/configure_object.php";
string SLOODLE_CONFIG_LINKER = "/mod/sloodle/classroom/object_config_linker.php";

float SLOODLE_VERSION_MIN = 0.3; // Minimum required version of Sloodle

key httpcheckmoodle = NULL_KEY;
key httpauthobject = NULL_KEY;
key httpconfig = NULL_KEY;

string sloodleserverroot = "";
string sloodlepwd = ""; // stores the object-specific session key (UUID|pwd)
string sloodleauthid = ""; // The ID which is passed to Moodle in the URL for the user authorisation step

string password = ""; // stores the self-generated part of the password


sloodle_tell_other_scripts(string msg)
{
    sloodle_debug("Web configuration sending message to other scripts: "+msg);
    llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, msg, NULL_KEY);   
}

sloodle_debug(string msg)
{
    //llWhisper(0,msg);
}

// Determines if the objet has a configuration notecard
// Returns true if so, or false otherwise
integer sloodle_has_config_notecard()
{
    return (llGetInventoryType(SLOODLE_CONFIG_NOTECARD) == INVENTORY_NOTECARD);
}

// Generate a random password string
string sloodle_random_object_password()
{
    return (string)(10000 + (integer)llFrand(999989999)); // Gets a random integer between 10000 and 999999999
}

// Show a menu letting the user choose between configuring the object, and downloading the configuration into SL
sloodle_show_config_menu(key av)
{
    llDialog(av, "What would you like to do?\n\n0 = Configure object\n1 = Download configuration", ["0", "1"], SLOODLE_CHANNEL_AVATAR_DIALOG);
    llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", av, "0");
    llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", av, "1");
}

// Load the configuration URL
sloodle_load_config_url(key av)
{
    llLoadURL(av, "Use this link to configure the object.", sloodleserverroot + SLOODLE_CONFIG_INTERFACE + "?sloodleauthid=" + sloodleauthid + "&sloodleobjtype=" + llGetObjectDesc());
}


default
{    
    state_entry() 
    {
        // Listen for anything on the object dialog channel
        llSetText("", <0.0,0.0,0.0>, 0.0);
        llListen(SLOODLE_CHANNEL_OBJECT_DIALOG, "", NULL_KEY, "");
    }
    
    state_exit()
    {
        llSetText("", <0.0,0.0,0.0>, 0.0);
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel
        if (channel == 0 || channel == 1) {
            // Ignore anybody but the owner
            if (id != llGetOwner()) return;
            // If the message starts with "http" then store it as the Moodle address
            msg = llStringTrim(msg, STRING_TRIM);
            if (llSubStringIndex(msg, "http") == 0) {
                sloodleserverroot = msg;
                state check_moodle;
                return;
            }
            
        } else if (channel == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Ignore anything not owned by the same person
            if (llGetOwnerKey(id) != llGetOwner()) return;
            // If the message starts with "http" then store it as the Moodle address
            msg = llStringTrim(msg, STRING_TRIM);
            if (llSubStringIndex(msg, "http") == 0) {
                sloodleserverroot = msg;
            }
        }
    }
    
    touch_start(integer num_detected)
    {
        // Only pay attention to the object owner
        if (llDetectedKey(0) != llGetOwner()) return;
        // Do nothing if there is a configuration script present
        if (sloodle_has_config_notecard()) return;
        
        // We can do nothing without a server root
        if (sloodleserverroot == "") {
            llListen(0, "", llGetOwner(), "");
            llListen(1, "", llGetOwner(), "");
            llOwnerSay("Please chat the address of your Moodle site, without a trailing slash. For example: http://www.yoursite.blah/moodle");
            llSetText("Waiting for Moodle site address.\nPlease chat it on channel 0 or 1.", <0.0,1.0,0.0>, 0.8);
            return;
        }
        
        // Start the configuration process
        state check_moodle;
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
    
    link_message(integer sender_num, integer num, string sval, key kval)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Is it a reset command?
            if (sval == "do:reset") {
                llResetScript();
                return;
            }
        }
    }
}

// Check that the Moodle site is valid
state check_moodle
{
    state_entry()
    {
        llSetText("Checking Moodle site at:\n" + sloodleserverroot, <0.0, 1.0, 0.0>, 0.8);
        httpcheckmoodle = llHTTPRequest(sloodleserverroot + SLOODLE_VERSION_LINKER, [HTTP_METHOD, "GET"], "");
    }
    
    state_exit()
    {
        llSetText("", <0.0,0.0,0.0>, 0.0);
        httpcheckmoodle = NULL_KEY;
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Make sure it's the response we're expecting
        if (id != httpcheckmoodle) return;
        httpcheckmoodle = NULL_KEY;
        // Check the status code
        if (status != 200) {
            llOwnerSay("ERROR: HTTP response gave status code " + (string)status);
            return;
        }
        
        // Split the response into lines and get the status line info
        list lines = llParseStringKeepNulls(body, ["\n"], []);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
        integer statuscode = (integer)llList2String(statusfields, 0);
        
        // Make sure the status code was OK
        if (statuscode == -106) {
            llOwnerSay("ERROR -106: the Sloodle module is not installed on the specified Moodle site (" + sloodleserverroot + ")");
            return;
        } else if (statuscode <= 0) {
            llOwnerSay("ERROR "+(string)statuscode+": failed to check compatibility with server");
            return;
        }
        
        // Make sure we have enough other data
        if (numlines < 2) {
            llOwnerSay("ERROR: badly formatted response from server");
            return;
        }
        
        // Extract the Sloodle version number
        list datafields = llParseStringKeepNulls(llList2String(lines, 1), ["|"], []);
        float installedversion = (float)llList2String(datafields, 0);
        
        // Check compatibility
        llOwnerSay("Sloodle version installed on server: " + (string)installedversion);
        if (installedversion < SLOODLE_VERSION_MIN) {
            llOwnerSay("ERROR: you required at least Sloodle version " + (string)SLOODLE_VERSION_MIN + " to be installed on your server to use this object.");
            return;
        }
        
        // Initiate object authorisation
        state auth_object_initial;
    }
    
    touch_start(integer num_detected)
    {
        // Revert to the default state if the owner touched
        if (llDetectedKey(0) != llGetOwner()) return;
        state default;
    }
    
    link_message(integer sender_num, integer num, string sval, key kval)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Is it a reset command?
            if (sval == "do:reset") {
                llResetScript();
                return;
            }
        }
    }
}

// Initial object authorisation (stores details in the database)
state auth_object_initial
{
    state_entry()
    {
        // We can skip this stage if a starting parameter was provided,
        //  as that will be our password
        if (llGetStartParameter()) {
            // Store the password
            password = (string)llGetStartParameter();
            sloodlepwd = (string)llGetKey() + "|" + password;
            // Immediately move to the configuration stage
            state configure_object;
            return;
        }
    
        llSetText("Initiating object authorisation...", <0.0, 1.0, 0.0>, 0.8);
        // Generate a random password
        password = sloodle_random_object_password();
        sloodlepwd = (string)llGetKey() + "|" + password;
        // Initiate the object authorisation
        string body = "sloodleobjuuid="+(string)llGetKey()+"&sloodleobjname="+llGetObjectName()+"&sloodleobjpwd="+password;
        httpauthobject = llHTTPRequest(sloodleserverroot + SLOODLE_AUTH_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
    }
    
    state_exit()
    {
        llSetText("", <0.0,0.0,0.0>, 0.0);
        httpauthobject = NULL_KEY;
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Make sure this is the response we're expecting
        if (id != httpauthobject) return;
        if (status != 200) {
            llOwnerSay("ERROR: HTTP response gave status code " + (string)status);
            return;
        }
        
        // Split the response into lines
        list lines = llParseStringKeepNulls(body, ["\n"], []);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
        integer statuscode = (integer)llList2String(statusfields, 0);
        
        // Check the statuscode
        if (statuscode <= 0) {
            llOwnerSay("ERROR "+(string)statuscode+": object authorisation failed");
            return;
        }
        
        // Attempt to get the auth ID
        if (numlines < 2) {
            llOwnerSay("ERROR: server response too short");
            return;
        }
        sloodleauthid = llList2String(lines, 1);
        
        // Start the configuration
        state configure_object;
    }
    
    touch_start(integer num_detected)
    {
        // Revert to the default state if the owner touched
        if (llDetectedKey(0) != llGetOwner()) return;
        state default;
    }
    
    link_message(integer sender_num, integer num, string sval, key kval)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Is it a reset command?
            if (sval == "do:reset") {
                llResetScript();
                return;
            }
        }
    }
}


// Send the user to the configuration page on the Moodle site.
// (That page will present authorisation options as necessary).
// If touched, ask the user if they want the URL again, or to download the configuration.
state configure_object
{
    state_entry()
    {
        llSetText("Waiting for configuration.\nTouch me for a URL, or to download the configuration.", <0.0,1.0,0.0>, 0.8);
        // Load the URL immediately 
        sloodle_load_config_url(llGetOwner());
    }
    
    state_exit()
    {
        llSetText("", <0.0,0.0,0.0>, 0.0);
        httpconfig = NULL_KEY;
    }
    
    touch_start(integer num_detected)
    {
        // Ignore anybody but the owner
        if (llDetectedKey(0) != llGetOwner()) return;
        // Present the menu of configuration options
        sloodle_show_config_menu(llGetOwner());
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG)
        {
            // Check it's the owner talking to us
            if (id != llGetOwner()) return;
            // What was the message?
            if (msg == "0") {
                // Load the configuration URL
                sloodle_load_config_url(llGetOwner());
            } else if (msg == "1") {
                // Download the configuration from the site
                httpconfig = llHTTPRequest(sloodleserverroot + SLOODLE_CONFIG_LINKER + "?sloodlepwd="+sloodlepwd+"&sloodleauthid="+sloodleauthid, [HTTP_METHOD, "GET"], "");
            }
        }
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Is this the response we're expecting?
        if (id == httpconfig) {
            httpconfig = NULL_KEY;
            // Check the return code
            if (status != 200) {
                llOwnerSay("ERROR: HTTP response failed with status code " + (string)status);
                return;
            }
            
            // Split the response into lines
            list lines = llParseStringKeepNulls(body, ["\n"], []);
            integer numlines = llGetListLength(lines);
            // Fetch the status line
            list statusfields = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
            integer statuscode = (integer)llList2String(statusfields, 0);
            if (statuscode <= 0) {
                llOwnerSay("ERROR " + (string)statuscode + ": object configuration failed");
                return;
            }
            
            // Indicate that we are sending configuration data
            llSetText("Sending configuration data...", <0.0, 1.0, 0.0>, 0.8);
            
            // This will be our buffer of configuration commands
            string cmdbuffer = "";
            integer maxbufferlength = 1024;
            integer cmdbufferlength = 0;
            
            // Add the servert address and password in as the first commands
            cmdbuffer = "set:sloodleserverroot|"+sloodleserverroot+"\nset:sloodlepwd|" + sloodlepwd + "\n";
            cmdbufferlength = llStringLength(cmdbuffer);
            
            // Go through each data line
            integer linenum = 1;
            string cmd = "";
            integer cmdlen = 0;
            for (; linenum < numlines; linenum++) {
                // This should be "name|value" format, so just prefix it with "set:"
                cmd = "set:" + llList2String(lines, linenum) + "\n";
                cmdlen = llStringLength(cmd);
                // Ignore lengths of less than 5
                if (cmdlen >= 5) {
                    // If the addition of this command will overflow the buffer, then send the buffer first
                    if ((cmdbufferlength + cmdlen) > maxbufferlength) {
                        sloodle_tell_other_scripts(cmdbuffer);
                        cmdbuffer = "";
                        cmdbufferlength = 0;
                    }
                    // Add the current command to the buffer
                    cmdbuffer += cmd;
                    cmdbufferlength += cmdlen;
                }
            }
            
            // If there is anything left in the buffer, then send it
            if (cmdbufferlength > 0) {
                sloodle_tell_other_scripts(cmdbuffer);
                cmdbuffer = "";
                cmdbufferlength = 0;
            }
            
            // After a brief pause, send the EOF command
            llSleep(0.15);
            sloodle_tell_other_scripts(SLOODLE_EOF);
            
            // We're now finished
            state idle;
        }
    }
    
    link_message(integer sender_num, integer num, string sval, key kval)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Is it a reset command?
            if (sval == "do:reset") {
                llResetScript();
                return;
            }
        }
    }
}


// In this state, the script has either finished, or been instructed not to execute
// It will only respond to a reset command
state idle
{
    state_entry()
    {
    }
    
    on_rez(integer par)
    {
        llResetScript();
    }
    
    link_message(integer sender_num, integer num, string sval, key kval)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Is it a reset command?
            if (sval == "do:reset") {
                llResetScript();
                return;
            }
        }
    }
}

