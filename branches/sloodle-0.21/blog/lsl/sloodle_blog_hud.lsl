// Sloodle Blog HUD
// Allows SL users in-world to write to their Moodle blog
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-8 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Daniel Livingstone
//  Edmund Edgar
//  Peter R. Bloomfield
//

// Version history:
//
// 1.1 - added channel menu and "ready" display
// 1.0 - updated to use new communications and avatar registration methods for Sloodle 0.2
// 0.9.2 - Corrected the reset calls from 0.9.1 to use "attach" event, and changed authentication link to a chat message
// 0.9.1 - Added appropriate reset calls for whenever the HUD object gets attached to the HUD
// 0.9 - Rewritten by Peter Bloomfield to allow full notecard initialisation
// 0.8 - Toolbar 2 in 1 - merge with gesture toolbar. Yikes.
// 0.7 - Textures from Jeremy Kemp, Authentication improvements
// 0.6 - Did some stuff... I forget
// 0.5 - Improved blogging. Also links to new simple authentication system.
// 0.4 - can't quite recall what I did
// 0.3 - adding in auto-update
//     - rewriting some code, to simplify things and allow for more control via buttons
// 0.2 - uses Edmund Earp's authentication data
//      - No longer asks user to set ID in notecard, but user
//        must be authenticated to use this successfully
// 0.1 - based on sloodle chat 0.72. DL
//


///// CONSTANTS /////
// Memory-saving hack!
key null_key = NULL_KEY;

// Timeout values
float CHAT_TIMEOUT = 600.0; // Time to wait for the user to chat something
float CONFIRM_TIMEOUT = 600.0; // Time to wait for the user to confirm the entry
float HTTP_TIMEOUT = 15.0; // Time to wait for an HTTP response

// What channel should configuration data be received on?
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
// What link channel should be used to communicate URLs?
integer SLOODLE_CHANNEL_OBJECT_LOAD_URL = -1639270041;
// What channel should we listen on for avatar dialogs?
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;


// Commands to other parts of the object
string SLOODLE_CMD_BLOG = "blog";
string SLOODLE_CMD_CHANNEL = "channel";
// Command statuses
string SLOODLE_CMD_READY = "ready";
string SLOODLE_CMD_NOTREADY = "notready";
string SLOODLE_CMD_ERROR = "error";
string SLOODLE_CMD_SUBJECT = "subject";
string SLOODLE_CMD_BODY = "body";
string SLOODLE_CMD_CONFIRM = "confirm";
string SLOODLE_CMD_SENDING = "sending";

///// --- /////


///// DATA /////

// What chat channel should we receive user info on?
integer user_chat_channel = 0;
// Handle for the current "listen" command for user dialog
integer user_listen_handle = 0;

// The address of the moodle installation
string sloodleserverroot = "";
// The prim password for accessing the site
string sloodlepwd = "";

// Relative paths to scripts for authentication and blog submission
string REG_SCRIPT = "/mod/sloodle/login/sl_reg_linker.php";
string BLOG_SCRIPT = "/mod/sloodle/blog/sl_blog_linker.php";

// The subject and body of the blog entry
string blogsubject = "";
string blogbody = "";

// Keys of the pending HTTP requests for a blog entry, and for avatar registration
key httpblogrequest = null_key;
key httpregrequest = null_key;

// Is the edit in confirmation mode?
// (i.e. has the entry been made, but is the user editing it to correct something?)
integer confirmationmode = FALSE;

// Is there a pending settings update to be performed?
integer updatepending = FALSE;
///// --- /////


///// FUNCTIONS /////
// Send a debug message (requires the "sloodle_debug" script in the same link)
sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, null_key);
}

// Reset the entire script
resetScript()
{
    llMessageLinked(LINK_ALL_CHILDREN,1," ",null_key);
    llMessageLinked(LINK_ALL_CHILDREN,2," ",null_key);
    llResetScript();
}

// Reset the display
resetDisplay()
{
    llMessageLinked(LINK_ALL_CHILDREN,1," ",null_key);
    llMessageLinked(LINK_ALL_CHILDREN,2," ",null_key);
}

// Reset the settings values
resetSettings()
{
    sloodleserverroot = "";
    sloodlepwd = "";
}

// Reset the working values
resetWorkingValues()
{
    // Reset our variables
    blogsubject = "";
    blogbody = "";
    httpblogrequest = null_key;
    httpregrequest = null_key;
    confirmationmode = FALSE;
    // Cancel any timer request
    llSetTimerEvent(0.0);
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

// Show the menu for which chat channel to listen on
// (Shows menu to owner, and starts listening for owner messages)
show_channel_menu()
{
    llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", llGetOwner(), "");
    llDialog(llGetOwner(), "Currently using channel: " + (string)user_chat_channel + ".\nPlease select which channel you would like to use:", ["0", "1", "2", "Cancel"], SLOODLE_CHANNEL_AVATAR_DIALOG);
}

// Handle a channel change message
handle_channel_change(integer ch)
{
    // Make sure it's a valid number
    if (ch >= 0 || ch <= 2) {
        // Store the new channel number and change the "listen" command
        user_chat_channel = ch;
        llListenRemove(user_listen_handle);
        user_listen_handle = llListen(user_chat_channel, "", llGetOwner(), "");
        // Update the display
        llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, SLOODLE_CMD_BLOG + "|" + SLOODLE_CMD_CHANNEL + "|" + (string)ch, NULL_KEY);
    }
}

///// --- /////


/// INITIALISING STATE ///
// Waiting for configuration
default
{    
    state_entry()
    {
        // Clear any item text
        llSetText("", <0.0,0.0,0.0>, 0.0);
        // Reset our channel number display
        handle_channel_change(0);
        
        
        // Make sure this is attached as a HUD object
        if (llGetAttached() < 30)
        {
            llOwnerSay("ERROR: Please attach me as a HUD object.");
            state error;
        }
        
        llOwnerSay("Initialisising...");
        // Reset all values
        resetDisplay();
        resetSettings();
        resetWorkingValues();
        updatepending = FALSE;
        
        // Update the display to say "not ready"
        llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, SLOODLE_CMD_BLOG + "|" + SLOODLE_CMD_NOTREADY, NULL_KEY);
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
                state authenticating;
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
            resetScript();
        }
    }
    
    attach( key av )
    {
        if (av != NULL_KEY)
            resetScript();
    }
}


/// ERROR STATE ///
// Initialisation and/or authentication has failed.
// Object can be clicked to retry setup.
state error
{    
    state_entry()
    {
        // Reset all values
        resetSettings();
        resetWorkingValues();
        // Inform the user that they can retry the setup process
        llOwnerSay("Touch me to retry the setup process.");
        llSetText("Initialisation/authentication failed.\nTouch me to retry the setup process.", <1.0,0.0,0.0>, 1.0);
        
        // Update the display to say "error"
        llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, SLOODLE_CMD_BLOG + "|" + SLOODLE_CMD_ERROR, NULL_KEY);
    }
    
    state_exit()
    {
        // Clear the text caption
        llSetText("", <0.0,0.0,0.0>, 0.0);
    }
    
    touch_start( integer num )
    {
        // Attempt initialisation
        resetScript();
    }
    
    changed( integer change )
    {
        // If there was an inventory change, then just reset
        if ((change & CHANGED_INVENTORY) == CHANGED_INVENTORY)
        {
            //updatepending = TRUE;
            llOwnerSay("Object changed... resetting.");
            resetScript();
        }
    }
    
    attach( key av )
    {
        if (av != NULL_KEY)
            resetScript();
    }
}


/// AUTHENTICATING ///
// Trying to authenticate the avatar
state authenticating
{   
    state_entry()
    {
        llSetTimerEvent(0.0); // Avoid any unexpected timeouts
        
        // Send a request to the registration linker
        key owner = llGetOwner();
        sloodle_debug("Sending registration request.");
        httpregrequest = llHTTPRequest(sloodleserverroot + REG_SCRIPT + "?sloodlepwd="+sloodlepwd+"&sloodleuuid="+(string)owner+"&sloodleavname="+llEscapeURL(llKey2Name(owner)), [], "");
        // Start a timer
        llSetTimerEvent(HTTP_TIMEOUT);
    }
    
    state_exit()
    {
        llSetTimerEvent(0.0);
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Ignore unexpected response
        if (id != httpregrequest) return;
        sloodle_debug("HTTP Response ("+(string)status+"): "+body);
        
        // Was the HTTP request successful?
        if (status != 200) {
            llOwnerSay("ERROR: failed to check avatar registration due to HTTP request failure.");
            state error;
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
            llOwnerSay("User registration error (" + (string)statuscode + "): " + dataline);
            state error;
            return;
        }
        
        // Was the user already fully registered?
        if (statuscode == 301) {
            // Nothing to do...
            llOwnerSay("Your avatar is authenticated. Please continue.");
        } else {
            // Provide the user a URL with which to authenticate their registration
            llOwnerSay("Your avatar has not yet been authenticated. Please use this URL to login to Moodle.");
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_LOAD_URL, dataline, llGetOwner());
        }
        
        // If we are in confirmation mode, then go back to confirming the entry
        // Otherwise, just continue to the "ready" state
        if (confirmationmode) state confirm;
        else state ready;
    }
    
    timer()
    {
        // We have timed-out waiting for an HTTP response
        llOwnerSay("ERROR: Timeout waiting for HTTP response. Moving to error state.");
        state error;
    }
    
    changed( integer change )
    {
        // If there was an inventory change, then just reset
        if ((change & CHANGED_INVENTORY) == CHANGED_INVENTORY)
        {
            //updatepending = TRUE;
            llOwnerSay("Object changed... resetting.");
            resetScript();
        }
    }
    
    attach( key av )
    {
        if (av != NULL_KEY)
            resetScript();
    }
}


/// READY ///
// Ready to start blogging
state ready
{   
    state_entry()
    {
        // Is there a pending update?
        if (updatepending)
        {
            llOwnerSay("Object changed... resetting.");
            resetScript();
        }
        
        // Reset all of the working values and display from the last blog entry
        resetDisplay();
        resetWorkingValues();
        
        // Update display to say "ready"
        llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, SLOODLE_CMD_BLOG + "|" + SLOODLE_CMD_READY, NULL_KEY);
    }
    
    touch_start( integer num )
    {
        // Make sure it is the owner touching the HUD
        if (llDetectedKey(0) != llGetOwner()) return;
                
        // Get the name of the prim that was touched
        string name = llGetLinkName(llDetectedLinkNumber(0));
        if (name == "start_blog") {
            state get_subject;
        } else if (name == "channel" || name == "channel_num") {
            show_channel_menu();
        }
    }
    
    listen( integer channel, string name, key id, string msg )
    {
        // Check which channel this is coming in on
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Make sure it's from the owner and that the message is not empty
            if (id != llGetOwner() || msg == "") return;
            // Update our channel number
            handle_channel_change((integer)msg);
            return;
        }
    }
    
    changed( integer change )
    {
        // If there was an inventory change, then just reset
        if ((change & CHANGED_INVENTORY) == CHANGED_INVENTORY)
        {
            //updatepending = TRUE;
            llOwnerSay("Object changed... resetting.");
            resetScript();
        }
    }
    
    attach( key av )
    {
        if (av != NULL_KEY)
            resetScript();
    }
}


/// GET SUBJECT ///
// Listen for the subject of the blog
state get_subject
{   
    state_entry()
    {
        // Disable any existing timeout event
        llSetTimerEvent(0.0);
        
        // Listen for chat messages from the owner of this object
        llOwnerSay("Please chat the subject line of your blog entry.");
        user_listen_handle = llListen(user_chat_channel, "", llGetOwner(), "");
        
        // Set a timeout
        llSetTimerEvent(CHAT_TIMEOUT);

        // Update display to say "subject"
        llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, SLOODLE_CMD_BLOG + "|" + SLOODLE_CMD_SUBJECT, NULL_KEY);
    }
    
    state_exit()
    {
        // Cancel the timeout if necessary
        llSetTimerEvent(0.0);
    }
    
    timer()
    {
        // We have timed-out waiting for a response
        llOwnerSay("Timeout waiting for the blog subject. Cancelling blog entry.");
        state ready;
    }
    
    touch_start( integer num )
    {
        // Make sure it was the owner who touched the object
        if (llDetectedKey(0) != llGetOwner()) return;
        // Get the name of the touched prim
        string name = llGetLinkName(llDetectedLinkNumber(0));
        
        // Was the "cancel" button touched?
        if (name == "cancel") {
            // The "cancel" button was touched
            llOwnerSay("Blog entry cancelled.");
            state ready;
        } else if (name == "channel" || name == "channel_num") {
            show_channel_menu();
        }
    }
    
    listen( integer channel, string name, key id, string msg )
    {
        // Check which channel this is coming in on
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Make sure it's from the owner and that the message is not empty
            if (id != llGetOwner() || msg == "") return;
            // Update our channel number
            handle_channel_change((integer)msg);
            return;
            
        } else if (channel == user_chat_channel) {
            // Make sure the message is not empty
            if (msg == "") {
                llOwnerSay("The blog subject cannot be empty. Please enter it again.");
                return;
            }
            
            // Store and display the blog subject
            blogsubject = msg;
            llMessageLinked(LINK_ALL_CHILDREN, 1, msg, NULL_KEY);

            // Are we in confirmation mode?
            if (confirmationmode) {
                // Yes - go back to the confirmation state
                state confirm;
            } else {
                // Get the body of the blog now
                state get_body;
            }
            return;
        }
    }
    
    changed( integer change )
    {
        // If there was an inventory change, then just reset
        if ((change & CHANGED_INVENTORY) == CHANGED_INVENTORY)
        {
            //updatepending = TRUE;
            llOwnerSay("Object changed... resetting.");
            resetScript();
        }
    }
    
    attach( key av )
    {
        if (av != NULL_KEY)
            resetScript();
    }
}


/// GET BODY ///
// Listen for the body of the blog
state get_body
{   
    state_entry()
    {
        // Disable any existing timeout event
        llSetTimerEvent(0.0);
        
        // Listen for chat messages from the owner of this object
        llOwnerSay("Please chat the body of your blog entry.");
        user_listen_handle = llListen(user_chat_channel, "", llGetOwner(), "");
        
        // Set a timeout
        llSetTimerEvent(CHAT_TIMEOUT);

        // Update display to say "body"
        llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, SLOODLE_CMD_BLOG + "|" + SLOODLE_CMD_BODY, NULL_KEY);
    }
    
    state_exit()
    {
        // Cancel the timeout if necessary
        llSetTimerEvent(0.0);
    }
    
    timer()
    {
        // We have timed-out waiting for a response
        llOwnerSay("Timeout waiting for the blog body. Cancelling blog entry.");
        state ready;
    }
    
    touch_start( integer num )
    {
        // Make sure it was the owner who touched the object
        if (llDetectedKey(0) != llGetOwner()) return;
        // Get the name of the touched prim
        string name = llGetLinkName(llDetectedLinkNumber(0));
        
        // Was the "cancel" button touched?
        if (name == "cancel") {
            // The "cancel" button was touched
            llOwnerSay("Blog entry cancelled.");
            state ready;
        } else if (name == "channel" || name == "channel_num") {
            show_channel_menu();
        }
    }
    
    listen( integer channel, string name, key id, string msg )
    {
        // Check which channel this is coming in on
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Make sure it's from the owner and that the message is not empty
            if (id != llGetOwner() || msg == "") return;
            // Update our channel number
            handle_channel_change((integer)msg);
            return;
            
        } else if (channel == user_chat_channel) {
            // Make sure the message is not empty
            if (msg == "") {
                llOwnerSay("The blog body cannot be empty. Please enter it again.");
                return;
            }
            
            // Store and display the blog subject
            blogbody = msg;
            llMessageLinked(LINK_ALL_CHILDREN, 2, msg, NULL_KEY);
            
            // Confirm the blog entry now
            state confirm;
            return;
        }
    }
    
    changed( integer change )
    {
        // If there was an inventory change, then just reset
        if ((change & CHANGED_INVENTORY) == CHANGED_INVENTORY)
        {
            //updatepending = TRUE;
            llOwnerSay("Object changed... resetting.");
            resetScript();
        }
    }
    
    attach( key av )
    {
        if (av != NULL_KEY)
            resetScript();
    }
}


/// CONFIRM ///
// Confirm that the blog entry is correct
state confirm
{   
    state_entry()
    {
        // Disable any existing timeout event
        llSetTimerEvent(0.0);
        
        // We are in confirmation mode
        // (i.e. if the subject is edited, come straight back here instead of re-entering the body)
        confirmationmode = TRUE;
        // Ask the user to confirm the entry details
        llOwnerSay("Please review your entry, and click \"Save Changes\" to send your entry to the website, or \"Cancel\" if not.");
        
        // Set a timeout
        llSetTimerEvent(CONFIRM_TIMEOUT);

        // Update display to say "confirm"
        llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, SLOODLE_CMD_BLOG + "|" + SLOODLE_CMD_CONFIRM, NULL_KEY);
    }
    
    state_exit()
    {
        // Cancel the timeout if necessary
        llSetTimerEvent(0.0);
    }
    
    touch_start( integer num )
    {
        // NOTE:
        // Future work here is to add ability to go back and edit subject or body of post
        
        // Make sure it was the owner who touched the object
        if (llDetectedKey(0) != llGetOwner()) return;
        // Get the name of the touched prim
        string name = llGetLinkName(llDetectedLinkNumber(0));
       
        // What was touched?
        if (name == "send") {
            // The "send" button was touched
            // Make sure both parts are filled in
            if ( blogsubject == "" || blogbody == "") {
                llOwnerSay("ERROR: You cannot send a blog entry with an empty title or body.");
                state ready;
                return;
            }
            // Send it
            state send;
        } else if (name == "cancel") {
            // The "cancel" button was touched
            llOwnerSay("Blog entry cancelled.");
            state ready;
        } else if (name == "channel" || name == "channel_num") {
            show_channel_menu();
        }
    }
    
    listen( integer channel, string name, key id, string msg )
    {
        // Check which channel this is coming in on
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Make sure it's from the owner and that the message is not empty
            if (id != llGetOwner() || msg == "") return;
            // Update our channel number
            handle_channel_change((integer)msg);
            return;
            
        }
    }
    
    timer()
    {
        // We have timed-out waiting for confirmation
        llOwnerSay("Timeout waiting for confirmation. Cancelling blog entry.");
        state ready;
    }
    
    changed( integer change )
    {
        // If there was an inventory change, then just reset
        if ((change & CHANGED_INVENTORY) == CHANGED_INVENTORY)
        {
            //updatepending = TRUE;
            llOwnerSay("Object changed... resetting.");
            resetScript();
        }
    }
    
    attach( key av )
    {
        if (av != NULL_KEY)
            resetScript();
    }
}


/// SEND ///
// Send the blog entry to the server
state send
{   
    state_entry()
    {
        // Disable any existing timeout event
        llSetTimerEvent(0.0);
        
        llOwnerSay("Thank you! Please wait while the entry is sent...");
        
        // Update display to say "sending"
        llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, SLOODLE_CMD_BLOG + "|" + SLOODLE_CMD_SENDING, NULL_KEY);
        
        // Copy the blog body so we can escape one of them for transmission
        string blogbody_copy = blogbody;
        // llEscapeURL currently returns at most 254 char len string. This means we need to do a horrible work around
        string msg = "sloodlepwd="+sloodlepwd+"&sloodleuuid="+(string)llGetOwner() +"&sloodleblogsubject="+llEscapeURL(blogsubject) + "&sloodleblogbody=";
        integer len = llStringLength(blogbody_copy);
        integer i;
        integer STEP = 84; // see http://www.lslwiki.com/lslwiki/wakka.php?wakka=llEscapeURL and comments
        for (i=0;i<len;i+=STEP)
        {
            integer end = i + 83;
            if (end > len)
            {
                end = len;
            }
            msg = msg + llEscapeURL(llGetSubString(blogbody_copy,i,end));
        }
        sloodle_debug("Sending request to update blog.");
        httpblogrequest = llHTTPRequest(sloodleserverroot + BLOG_SCRIPT, [HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"], msg);
        
        // Set a timeout event
        llSetTimerEvent(HTTP_TIMEOUT);
    }
    
    state_exit()
    {
        // Cancel the timeout if necessary
        llSetTimerEvent(0.0);
    }
    
    timer()
    {
        // We have timed-out waiting for an HTTP response
        llSetTimerEvent(0.0);
        llOwnerSay("ERROR: Timeout waiting for HTTP response. You may try again, or cancel.");
        state confirm;
    }
    
    http_response(key request_id, integer status, list metadata, string body)
    {
        // Make sure this is the expected HTTP response
        if (request_id != httpblogrequest) return;
        httpblogrequest = null_key;
        
        sloodle_debug("HTTP Response ("+(string)status+"): "+body);
        
        // Was the HTTP request successful?
        if (status != 200) {
            llOwnerSay("ERROR: failed to update blog due to HTTP request failure. You may try again or cancel.");
            state confirm;
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
        
        // Check the status code
        if (statuscode <= -300 && statuscode > -400) {
            // It is a user authentication error - attempt re-authentication
            llOwnerSay("Failed to update blog due to a user authentication error (" + (string)statuscode + "): " + dataline);
            state authenticating;
            return;
            
        } else if (statuscode <= 0) {
            // Don't know what kind of error it was
            llOwnerSay("ERROR (" + (string)statuscode + "): " + dataline + "\nYou may try again or cancel.");
            state confirm;
            return;
        }
        
        // If we get here, then it must have been successful
        llOwnerSay("Updated blog entry successfully.");
        state ready;
    }
    
    changed( integer change )
    {
        // If there was an inventory change, then just reset
        if ((change & CHANGED_INVENTORY) == CHANGED_INVENTORY)
        {
            //updatepending = TRUE;
            llOwnerSay("Object changed... resetting.");
            resetScript();
        }
    }
    
    attach( key av )
    {
        if (av != NULL_KEY)
            resetScript();
    }
}
