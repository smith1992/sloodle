// Sloodle Presenter (for Sloodle 0.4)
// Lets the educator display a presentation of images. videos and webpages hosted on the web.
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2008 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Peter R. Bloomfield
//

integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
string SLOODLE_SLIDESHOW_LINKER = "/mod/sloodle/mod/presenter-1.0/linker.php";
string SLOODLE_EOF = "sloodleeof";
list parcelInfo; //var for parcel owner 
integer MENU_CHANNEL;
string SLOODLE_OBJECT_TYPE = "presenter-1.0";

integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;

string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodlemoduleid = 0;
integer sloodleobjectaccesslevelctrl = 1; // Who can control this object?

integer isconfigured = FALSE; // Do we have all the configuration data we need?
integer eof = FALSE; // Have we reached the end of the configuration data?

key httpentries = NULL_KEY; // Request for list of entries
list entrytypes = []; // List of current entry types
list entryurls = []; // List of current entry URLs
list entrynames = []; // List of current entry names
integer currententry = 0; // Array ID identifying which entry in the lists (entrytypes and entryurls) we are currently viewing


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
//requestConfigData = 0 this is set to 1 after the presenter has been deeded - afterwhich if "update" is 
//selected, the presenter will go back into the default state and re-request config data.  This is necessary since the teacher may change prentations the presenter is pointing to via the web.  
// Send a translation request link message
integer requestConfigData=0; 
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}

///// ----------- /////


///// FUNCTIONS /////
//just returns a random integer - used for setting channels
integer random_integer( integer min, integer max ){
  return min + (integer)( llFrand( max - min + 1 ) );
}

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
    // Figure out what type to use
    string typename = llList2String(entrytypes, currententry);
    string type = "";
    if (typename == "image") type = "image/*";
    else if (typename == "video") type = "video/*";
    else if (typename == "audio") type = "audio/*";
    else type = "text/html";

    // Set the parcel media
    llParcelMediaCommandList([
        PARCEL_MEDIA_COMMAND_TYPE, type,
        PARCEL_MEDIA_COMMAND_URL, llList2String(entryurls, currententry)
    ]);
}

// Move to the next image
next_image()
{
    currententry = ((currententry + 1) % llGetListLength(entryurls));
    sloodle_update_hover_text();
    update_image_display();
}

// Move to the previous image
previous_image()
{
    currententry = currententry - 1;
    if (currententry < 0) currententry = llGetListLength(entryurls) - 1;
    sloodle_update_hover_text();
    update_image_display();
}

// Update the hover text to indicate which image is being displayed
sloodle_update_hover_text()
{
    sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT, [<0.0, 1.0, 0.0>, 1.0], "showingentryname", [(currententry + 1), llGetListLength(entryurls), llList2String(entrynames, currententry)], NULL_KEY, "presenter");
    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "showingentryurl", [(currententry + 1), llGetListLength(entryurls), llList2String(entryurls, currententry)], NULL_KEY, "presenter");
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
        if (requestConfigData==1){
         llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", NULL_KEY);                 
        }
        requestConfigData=1;
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
                    state checkParcelOwner;
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
state checkParcelOwner
{
    state_entry() {
        MENU_CHANNEL = random_integer(10000,20000); //set channel for config menu
        parcelInfo = llGetParcelDetails(llGetPos(), [PARCEL_DETAILS_OWNER]);
        if (llList2Key(parcelInfo,0) != llGetOwner()){
            string errorMessage ="This Presenter MUST be deeded to the Parcel Owner for it to display your presentation";
            llSetText(errorMessage, <0.92748, 0.00000, 0.42705>,100 );
            llDialog(llGetOwner(),errorMessage, ["help"], MENU_CHANNEL);
        } else {
            llSay(0,"Presenter Parcel Settings are Correct! Retreiving Slides..");
            state setMediaTexture;
        }
        llListen(MENU_CHANNEL, "", llGetOwner(), "");
        llSetTimerEvent(300);
        
    }
    listen(integer channel, string name, key id, string message) {
        if (channel == MENU_CHANNEL){
            if (message=="help"){
                llGiveInventory(id,"Presenter Help");

            }
        }
    
    }
    touch_start(integer num_detected) {
        parcelInfo = llGetParcelDetails(llGetPos(), [PARCEL_DETAILS_OWNER]);
        if (llList2Key(parcelInfo,0) != llGetOwner()){
            llDialog(llGetOwner(),"This Presenter MUST be deeded to the Parcel Owner for it to display your presentation", ["help"], MENU_CHANNEL);
        } else {
            llSay(0,"Presenter Parcel Settings are Correct! Retreiving Slides..");
            state setMediaTexture;
        }
    }
    timer() {
        state checkParcelOwnerAgain;
    }
    state_exit() {
    llSetText("",<0.92748, 0.00000, 0.42705>, 100);
    }


}

state checkParcelOwnerAgain
{
    state_entry() {
        state checkParcelOwner;
    }

}

state setMediaTexture
{
    state_entry() {
                
                llParcelMediaCommandList([PARCEL_MEDIA_COMMAND_TEXTURE,llGetInventoryKey("sloodle_presenter_texture")]); //set texture to presenter texture
                llSay(0,"Parcel Media texture set to sloodle_presenter_texture");
                state requestdata;                
    }
}
state requestdata
{
    state_entry()
    {
        string body = "sloodlecontrollerid=" + (string)sloodlecontrollerid;
        body += "&sloodlepwd=" + sloodlepwd;
        body += "&sloodlemoduleid=" + (string)sloodlemoduleid;
        
        llSetText("Requesting list of entries...", <0.0, 0.0, 1.0>, 0.8);
        httpentries = llHTTPRequest(sloodleserverroot + SLOODLE_SLIDESHOW_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
        llSetTimerEvent(8.0);
    }
    
    state_exit()
    {
        llSetTimerEvent(0.0);
        llSetText("", <0.0, 0.0, 0.0>, 0.0);
    }
    
    timer()
    {
        llSay(0, "Timeout waiting for list of entries");
        state error;
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Is this the expected data?
        if (id != httpentries) return;
        httpentries = NULL_KEY;
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
        
        // Check if we have some more lines
        if (llGetListLength(lines) == 1) {
            llSay(0, "No images to display.");
            state error;
            return;
        }
        
        // Add each line to our lists of entries
        entryurls = [];
        entrytypes = [];
        entrynames = [];
        integer i = 0;
        list fields = [];
        for (i = 1; i < numlines; i++) {
            fields = llParseString2List(llList2String(lines, i), ["|"], []);
            if (llGetListLength(fields) >= 2) {
                entrytypes += [llList2String(fields, 0)];
                entryurls += [llList2String(fields, 1)];        
            }
        }
        
        state running;
    }
}

state error
{
    state_entry()
    {
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT, [<1.0, 0.0, 0.0>, 1.0], "errorstate", [], NULL_KEY, "presenter");
        
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
        currententry = 0;
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
        if (buttonname == "next") {
            next_image();
        } else if (buttonname == "previous") {
            previous_image();
        } else if (buttonname == "reset") {
            currententry = 0;
            sloodle_update_hover_text();
            update_image_display();
        } else if (buttonname == "update") {
            requestConfigData=1; // in default data state request config data to ensure
            //controller settings proliferate into SL - ie: someone changed the presentation this presenter is pointing to via the web
            state default;
        }
    }
    
    on_rez(integer par) { llResetScript(); }
}

