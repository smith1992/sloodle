// Sloodle web-configuration script
// In the absence of a configuration notecard, allows an object to be authorised/configured via the web
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2008 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar
//  Peter R. Bloomfield


integer SLOODLE_OBJECT_DIALOG_CHANNEL = -3857343;
string SLOODLE_CONFIG_NOTECARD = "sloodle_config";
string SLOODLE_EOF = "sloodleeof";
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;

string SLOODLE_VERSION_LINKER = "/mod/sloodle/version_linker.php";
string SLOODLE_AUTH_LINKER = "/mod/sloodle/classroom/auth_object_linker.php";
string SLOODLE_CONFIG_INTERFACE = "/mod/sloodle/classroom/configure_object.php";
string SLOODLE_CONFIG_LINKER = "/mod/sloodle/classroom/object_config_linker.php";

float SLOODLE_VERSION_MIN = 0.3; // Minimum required version of Sloodle

integer ownerlisten = 0; 
integer do_web_config = TRUE; // If false, user touches are ignored
string sloodleauthid = ""; // The ID which is passed to Moodle in the URL for the user authorisation step

key httpcheckmoodle = NULL_KEY;
key httpauthobject = NULL_KEY;
key httpconfig = NULL_KEY;

string sloodleserverroot = "";
string sloodlepwd = ""; // stores the object-specific session key (UUID|pwd)

string password = ""; // stores the self-generated part of the password


sloodle_tell_other_scripts(string msg)
{
    sloodle_debug("Web configuration sending message to other scripts: "+msg);
    llMessageLinked(LINK_SET, SLOODLE_OBJECT_DIALOG_CHANNEL, msg, NULL_KEY);   
}

sloodle_debug(string msg)
{
    //llWhisper(0,msg);
}

sloodle_handle_command(string str) 
{
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    
    string name = llList2String(bits,0);
    string value = "";
    if (numbits >= 2) llList2String(bits,1);

    // Is it a reset command?
    if (name == "do:reset") {
        sloodle_debug("Resetting configuration notecard reader");
        llResetScript();
    }
}


// Determines if the objet has a configuration notecard
// Returns true if so, or false otherwise
sloodle_has_config_notecard()
{
    return (llGetInventoryType(SLOODLE_CONFIG_NOTECARD) == INVENTORY_NOTECARD);
}

// Generate a random password string
sloodle_random_object_password()
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
    llLoadURL(av, "Use this link to configure the object.", sloodleserverroot + SLOODLE_CONFIG_INTERFACE + "?sloodleauthid=" + sloodleauthid);
}


default 
{
    on_rez(integer start_param)
    {
        llResetScript();
    }
    
    state_entry() 
    {
    }
    
    state_exit()
    {
        llSetText("", <0.0,0.0,0.0>, 0.0);
    }
    
    touch_start(integer num_detected)
    {
        // Make sure we are actually waiting to do this
        if (do_web_config == FALSE) return;
        
        // Only pay attention to the object owner
        if (llDetectedKey(0) != llGetOwner()) return;
        // Do nothing if there is a configuration script present
        if (sloodle_has_config_notecard()) return;
        
        // We can do nothing without a server root
        if (sloodleserverroot == "") {
            ownerlisten = llListen(0, "", llGetOwnerKey(), "");
            llOwnerSay("Please chat the address of your Moodle site, without a trailing slash. For example: http://www.yoursite.blah/moodle");
            llSetText("Waiting for Moodle site address.\nPlease chat it on channel 0.", <0.0,1.0,0.0>, 0.8);
            return;
        }
        
        // Start the configuration process
        state check_moodle;
    }
    
    link_message(integer sender_num, integer num, string str, key id)
    {
        // Ignore debug messages
        if (num == DEBUG_CHANNEL) return;
        // Is this an object dialog message?
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            sloodle_handle_command(str);
        }
    }
    
    changed(integer change) {
        // If the inventory is changed, and we have a Sloodle config notecard, then use it to re-initialise
        if (change & CHANGED_INVENTORY && llGetInventoryType(SLOODLE_CONFIG_NOTECARD) == INVENTORY_NOTECARD) {
            llResetScript();
        }
    }
}

// Check that the Moodle site is valid
state check_moodle
{
    state_entry()
    {
        llSetText("Checking Moodle site at:\n" + sloodleserverroot);
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
        string body = "sloodleobjuuid="+(string)+"&sloodleobjname="+llGetObjectName()+"&sloodleobjpwd="+password;
        llHTTPRequest(sloodleserverroot + SLOODLE_AUTH_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded", body);
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
        list lines = llParseStringKeepNulls(body, ["|"], []);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
        integer statuscode = (integer)llList2String(statusfields, 0);
        
        // Check the statuscode
        if (statuscode <= 0) {
            llOwnerSay("ERROR "+(string)statuscode+": object authorisation failed");
            return;
        }
        
        // Attempt to get the auth ID
        if (lines < 2) {
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
        sloodle_load_config_url();
    }
    
    state_exit()
    {
        llSetText("", <0.0,0.0,0.0>, 0.0);
        httpconfig = NULL_KEY;
    }
}

