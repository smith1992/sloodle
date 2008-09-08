// Sloodle Slideshow (for Sloodle 0.3)
// Lets the educator display a slideshow of images hosted on the web.
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2008 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Peter R. Bloomfield
//

integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
string SLOODLE_SLIDESHOW_LINKER = "/mod/sloodle/mod/slideshow-1.0/linker.php";
string SLOODLE_EOF = "sloodleeof";

string SLOODLE_OBJECT_TYPE = "slideshow-1.0";

integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;

string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodlemoduleid = 0;
integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?

integer isconfigured = FALSE; // Do we have all the configuration data we need?
integer eof = FALSE; // Have we reached the end of the configuration data?

key httpimageurls = NULL_KEY; // Request for list of image URLs
list imageurls = []; // List of current image URLs
integer currentimage = 0; // Array ID identifying which URL in imageurls we are currently on


///// TRANSLATION /////

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE = -1928374652;

// Translation output methods
string SLOODLE_TRANSLATE_LINK = "link";             // No output parameters - simply returns the translation on SLOODLE_TRANSLATION_RESPONSE link message channel
string SLOODLE_TRANSLATE_SAY = "say";               // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_WHISPER = "whisper";       // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_SHOUT = "shout";           // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_REGION_SAY = "regionsay";  // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_OWNER_SAY = "ownersay";    // No output parameters
string SLOODLE_TRANSLATE_DIALOG = "dialog";         // Recipient avatar should be identified in link message keyval. At least 2 output parameters: first the channel number for the dialog, and then 1 to 12 button label strings.
string SLOODLE_TRANSLATE_LOAD_URL = "loadurl";      // Recipient avatar should be identified in link message keyval. 1 output parameter giving URL to load.
string SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";  // 2 output parameters: colour <r,g,b>, and alpha value
string SLOODLE_TRANSLATE_IM = "instantmessage";     // Recipient avatar should be identified in link message keyval. No output parameters.

// Send a translation request link message
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}

///// ----------- /////


///// FUNCTIONS /////

sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// Configure by receiving a linked message from another script in the object
// Returns TRUE if the object has all the data it needs
integer sloodle_handle_command(string str) 
{
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    
    if (numbits > 1) value1 = llList2String(bits,1);
    if (numbits > 2) value2 = llList2String(bits,2);
    
    if (name == "set:sloodleserverroot") sloodleserverroot = value1;
    else if (name == "set:sloodlepwd") {
        // The password may be a single prim password, or a UUID and a password
        if (value2 != "") sloodlepwd = value1 + "|" + value2;
        else sloodlepwd = value1;
        
    } else if (name == "set:sloodlecontrollerid") sloodlecontrollerid = (integer)value1;
    else if (name == "set:sloodlemoduleid") sloodlemoduleid = (integer)value1;
    else if (name == "set:sloodleobjectaccesslevelctrl") sloodleobjectaccesslevelctrl = (integer)value1;
    else if (name == SLOODLE_EOF) eof = TRUE;
    
    return (sloodleserverroot != "" && sloodlepwd != "" && sloodlecontrollerid > 0 && sloodlemoduleid > 0);
}

// Checks if the given agent is permitted to control this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_ctrl(key id)
{
    // Check the access mode
    if (sloodleobjectaccesslevelctrl == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP) {
        return llSameGroup(id);
    } else if (sloodleobjectaccesslevelctrl == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC) {
        return TRUE;
    }
    
    // Assume it's owner mode
    return (id == llGetOwner());
}

// Update the image display (change the parcel media URL).
// Does nothing if the current image ID is invalid.
update_image_display()
{

    // Set the parcel media
    llParcelMediaCommandList([
        PARCEL_MEDIA_COMMAND_STOP,
        PARCEL_MEDIA_COMMAND_URL, llList2String(imageurls, currentimage),
        PARCEL_MEDIA_COMMAND_PLAY
    ]);
}

// Move to the next image
next_image()
{
    currentimage = ((currentimage + 1) % llGetListLength(imageurls));
    update_image_display();
}

// Move to the previous image
previous_image()
{
    currentimage = currentimage - 1;
    if (currentimage < 0) currentimage = llGetListLength(imageurls) - 1;
}

// Update the hover text to indicate which image is being displayed
sloodle_update_hover_text()
{
    llSetText("Showing image " + (string)currentimage + " of " + (string)llGetListLength(imageurls), <0.0, 1.0, 0.0>, 1.0);
}


// Default state - waiting for configuration
default
{
    state_entry()
    {
        // Starting again with a new configuration
        llSetText("", <0.0,0.0,0.0>, 0.0);
        isconfigured = FALSE;
        eof = FALSE;
        // Reset our data
        sloodleserverroot = "";
        sloodlepwd = "";
        sloodlecontrollerid = 0;
        sloodlemoduleid = 0;
        sloodleobjectaccesslevelctrl = 0;
    }
    
    link_message( integer sender_num, integer num, string str, key id)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Split the message into lines
            list lines = llParseString2List(str, ["\n"], []);
            integer numlines = llGetListLength(lines);
            integer i = 0;
            for (; i < numlines; i++) {
                isconfigured = sloodle_handle_command(llList2String(lines, i));
            }
            
            // If we've got all our data AND reached the end of the configuration data, then move on
            if (eof == TRUE) {
                if (isconfigured == TRUE) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configurationreceived", [], NULL_KEY, "");
                    state requestdata;
                } else {
                    // Go all configuration but, it's not complete... request reconfiguration
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configdatamissing", [], NULL_KEY, "");
                    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:reconfigure", NULL_KEY);
                    eof = FALSE;
                }
            }
        }
    }
    
    touch_start(integer num_detected)
    {
        // Attempt to request a reconfiguration
        if (llDetectedKey(0) == llGetOwner()) {
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", NULL_KEY);
        }
    }
}

state requestdata
{
    state_entry()
    {
        string body = "sloodlecontrollerid=" + (string)sloodlecontrollerid;
        body += "&sloodlepwd=" + sloodlepwd;
        body += "&sloodlemoduleid=" + (string)sloodlemoduleid;
        
        llSetText("Requesting list of images...", <0.0, 0.0, 1.0>, 0.8);
        httpimageurls = llHTTPRequest(sloodleserverroot + SLOODLE_SLIDESHOW_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
        llSetTimerEvent(8.0);
    }
    
    state_exit()
    {
        llSetTimerEvent(0.0);
        llSetText("", <0.0, 0.0, 0.0>, 0.0);
    }
    
    timer()
    {
        llSay(0, "Timeout waiting for list of images");
        state error;
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Is this the expected data?
        if (id != httpimageurls) return;
        httpimageurls = NULL_KEY;
        // Make sure the request worked
        if (status != 200) {
            sloodle_debug("Failed HTTP response. Status: " + (string)status);
            return;
        }

        // Make sure there is a body to the request
        if (llStringLength(body) == 0) return;
        
        // Split the data up into lines
        list lines = llParseStringKeepNulls(body, ["\n"], []);  
        integer numlines = llGetListLength(lines);
        // Extract all the status fields
        list statusfields = llParseStringKeepNulls( llList2String(lines,0), ["|"], [] );
        // Get the statuscode
        integer statuscode = llList2Integer(statusfields,0);
        
        // Was it an error code?
        if (statuscode <= 0) {
            string msg = "ERROR: linker script responded with status code " + (string)statuscode;
            // Do we have an error message to go with it?
            if (numlines > 1) {
                msg += "\n" + llList2String(lines,1);
            }
            sloodle_debug(msg);
            return;
        }
        
        // Put each data line into the list of image URLs
        if (llGetListLength(lines) == 1) {
            llSay(0, "No images to display.");
            state error;
            return;
        }
        imageurls = llList2List(imageurls, 1, -1);
        
        state running;
    }
}

state error
{
    state_entry()
    {
        llSetText("Error state - touch me to reset", <1.0, 0.0, 0.0>, 1.0);
    }
    
    state_exit()
    {
        llSetText("", <0.0, 0.0, 0.0>, 0.0);
    }
    
    touch_start(integer num)
    {
        llResetScript();
    }
}

state running
{
    state_entry()
    {
        // Start from the first image
        currentimage = 0;
        sloodle_update_hover_text();
        update_image_display();
    }
    
    state_exit()
    {
        llSetText("", <0.0, 0.0, 0.0>, 0.0);
    }
    
    touch_start(integer num)
    {
        // Find out what was touched
        string buttonname = llGetLinkName(llDetectedLinkNumber(0));
        if (buttonname = "next") {
            next_image();
        } else if (buttonname = "previous") {
            previous_image();
        } else if (buttonname = "reset") {
            currentimage = 0;
            sloodle_update_hover_text();
            update_image_display();
        }
    }
    
    on_rez(integer par) { llResetScript(); }
}
