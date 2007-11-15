//////////
//
// Sloodle Blog Toolbar script
// Version: 0.9.2
//
// Originally developed by Daniel Livingstone
// Visit www.sloodle.org if you need assistance.
//
//
// Version history:
//
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
//
// This script expects a notecard called "blog_settings" to exist inside the same prim.
// The notecard should specify two items:
//  - MOODLE_ADDRESS = the address of the Moodle site to communicate with
//    (e.g. http://www.sloodle.com/)
//    (N.B. this should be the root folder of your Moodle install,
//          and MUST have a trailing forward slash /)
//  - PRIM_PASS = the site-wide "prim password" for your Moodle installation
//    (this is set in the Sloodle setup process)
//
// The 2 required lines would look like this (notice the ":=" on each line):
//
//MOODLE_ADDRESS:=http://www.sloodle.com/
//PRIM_PASS:=abcdefg1234
//
// (Remove the // at the start of each line though, as lines in the notecard which start with
//  the double-forward slash will be ignored. This lets you add comments if you need to).
//
// SECURITY NOTE:
// Make sure that the settings notecard is set to "no copy, no modify" for the next owner, otherwise people will be able to steal your prim password!
//
//
//////////


///// CONSTANTS /////
// Memory-saving hack!
key null_key = NULL_KEY;

// Name of the settings notecard
string SETTINGS_NOTECARD_NAME = "blog_settings";
// Name of each setting in the notecard
string MOODLE_ADDRESS_SETTING_NAME = "MOODLE_ADDRESS";
string PRIM_PASS_SETTING_NAME = "PRIM_PASS";
// The operators in the notecard
string ASSIGNMENT_OP = ":=";
string COMMENT_OP = "//";

// Timeout values
float CHAT_TIMEOUT = 60.0; // Time to wait for the user to chat something
float CONFIRM_TIMEOUT = 60.0; // Time to wait for the user to confirm the entry
float HTTP_TIMEOUT = 15.0; // Time to wait for an HTTP response
float DATASERVER_TIMEOUT = 10.0; // Time to wait for dataserver events (e.g. reading a notecard line)

// What chat channel should we receive user info on?
integer USER_CHAT_CHANNEL = 0;
///// --- /////


///// DATA /////
// The address of the moodle installation
string MOODLE_ADDRESS = "";
// The prim password for accessing the site
string PRIM_PASS = "";

// Relative paths to scripts for authentication and blog submission
string AUTH_SCRIPT = "mod/sloodle/login/sl_loginzone_manual_entry.php";
string BLOG_SCRIPT = "mod/sloodle/blog/sl_blog_linker.php";

// The subject and body of the blog entry
string blogsubject = "";
string blogbody = "";

// Key of the pending HTTP request for a blog entry
key httpblogrequest = null_key;

// Notecard reading values
integer notecardnumlines = 0;
integer notecardcurline = 0;
key notecardrequest_numlines = null_key;
key notecardrequest_line = null_key;

// Is the edit in confirmation mode?
// (i.e. has the entry been made, but is the user editing it to correct something?)
integer confirmationmode = FALSE;

// Is there a pending settings update to be performed?
integer updatepending = FALSE;
///// --- /////


///// FUNCTIONS /////
// Reset the entire script
resetScript()
{
    llMessageLinked(LINK_ALL_CHILDREN,1," ",NULL_KEY);
    llMessageLinked(LINK_ALL_CHILDREN,2," ",NULL_KEY);
    llResetScript();
}

// Reset the display
resetDisplay()
{
    llMessageLinked(LINK_ALL_CHILDREN,1," ",NULL_KEY);
    llMessageLinked(LINK_ALL_CHILDREN,2," ",NULL_KEY);
}

// Reset the settings values
resetSettings()
{
    MOODLE_ADDRESS = "";
    PRIM_PASS = "";
    notecardnumlines = 0;
    notecardcurline = 0;
    notecardrequest_numlines = null_key;
    notecardrequest_line = null_key;
}
// Reset the working values
resetWorkingValues()
{
    // Reset our variables
    blogsubject = "";
    blogbody = "";
    httpblogrequest = null_key;
    confirmationmode = FALSE;
    // Cancel any timer request
    llSetTimerEvent(0.0);
}
///// --- /////


/// INITIALISING STATE ///
// Trying to read settings notecard for basic configuration
default
{    
    state_entry()
    {
        // Clear any item text
        llSetText("", <0.0,0.0,0.0>, 0.0);
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
        
        // Make sure we have a settings notecard
        if (llGetInventoryType(SETTINGS_NOTECARD_NAME) != INVENTORY_NOTECARD)
        {
            llOwnerSay("ERROR: This object requires a settings notecard called \"" + SETTINGS_NOTECARD_NAME + "\". Please obtain or create the appropriate notecard for your Moodle installation (if you are a student, please ask your instructor about this).");
            state error;
        }
        
        // Start loading the notecard
        notecardrequest_numlines = llGetNumberOfNotecardLines(SETTINGS_NOTECARD_NAME);
        // Setup a timeout
        llSetTimerEvent(DATASERVER_TIMEOUT);
    }
    
    state_exit()
    {
        // Reset the notecard values
        notecardnumlines = 0;
        notecardcurline = 0;
        notecardrequest_numlines = null_key;
        notecardrequest_line = null_key;
    }
    
    dataserver(key queryid, string data)
    {
        // Was this a request for the number of lines?
        if (queryid == notecardrequest_numlines)
        {
            // Store the number of lines, and get the first line
            notecardnumlines = (integer)data;
            notecardcurline = 0;
            notecardrequest_line = llGetNotecardLine(SETTINGS_NOTECARD_NAME, 0);
            notecardrequest_numlines = null_key;
            llSetTimerEvent(DATASERVER_TIMEOUT);
            return;
        }
        
        // Make sure the request is otherwise recognised
        if (queryid != notecardrequest_line)
        {
            llOwnerSay("ERROR: Received unexpected dataserver event. Terminating.");
            state error;
            return;
        }
        
        // Get the length of the data line and the assignment operator
        integer datalen = llStringLength(data);
        integer assignlen = llStringLength(ASSIGNMENT_OP);
        
        // Make sure this is not an empty line or a comment line
        if (datalen > 0 && llSubStringIndex(data, COMMENT_OP) != 0)
        {
            // Find the first instance of the assignment operator
            integer assignpos = llSubStringIndex(data, ASSIGNMENT_OP);
            if (assignpos < 0)
            {
                llOwnerSay("ERROR: Expected assignment operator (" + ASSIGNMENT_OP + ") on line " + (string)(notecardcurline + 1) + " of settings notecard.");
                state error;
                return;
            }
            
            // Get a substring containing both sides of the assignment
            string name = llStringTrim(llGetSubString(data, 0, assignpos - 1), STRING_TRIM);
            string value = llStringTrim(llGetSubString(data, assignpos + assignlen, datalen - 1), STRING_TRIM);

            // Make sure the name is not empty
            if (name == "")
            {
                llOwnerSay("EMPTY: Empty setting name on line " + (string)(notecardcurline + 1) + " of settings notecard. Stopping.");
                state error;
                return;
            }
            
            // Make sure the value is not empty
            if (value == "")
            {
                llOwnerSay("EMPTY: Empty value on line " + (string)(notecardcurline + 1) + " of settings notecard. Stopping.");
                state error;
                return;
            }
            
            // What name is it?
            if (name == MOODLE_ADDRESS_SETTING_NAME) {
                
                // Store the Moodle address
                MOODLE_ADDRESS = value;                
            }
            else if (name == PRIM_PASS_SETTING_NAME) {
                // Store the prim password
                PRIM_PASS = value;
            }
            else {
                llOwnerSay("WARNING: Unrecognised setting on line " + (string)(notecardcurline + 1) + " of settings notecard. Continuing initialisation.");
            }
        }
        
        // Reset our dataserver request key
        notecardrequest_line = null_key;
        
        // Was this the last line?
        if (notecardcurline >= (notecardnumlines - 1)) {
            // Yes - make sure all values were set
            if (MOODLE_ADDRESS == "" || PRIM_PASS == "")
            {
                llOwnerSay("ERROR: Initialisation failed. Not all necessary values specified in settings notecard.");
                state error;
                return;
            }
            
            // Initialisation has been successful
            llOwnerSay("Initialisation successful.\nNow I need to authenticate your avatar with the external Moodle site before you can make a blog entry.");
            state authenticating;
            return;
        }
        else {
            // Get the next line
            notecardcurline++;
            notecardrequest_line = llGetNotecardLine(SETTINGS_NOTECARD_NAME, notecardcurline);
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
    }
    
    state_exit()
    {
        // Clear the text caption
        llSetText("", <0.0,0.0,0.0>, 0.0);
    }
    
    touch_start( integer num )
    {
        // Attempt initialisation
        state default;
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
        // Construct and load the authentication URL
        key owner = llGetOwner();
        string authentication_url = MOODLE_ADDRESS + AUTH_SCRIPT + "?avname=" + llEscapeURL(llKey2Name(owner)) + "&uuid=" + (string)owner;
        //llLoadURL(llGetOwner(), "Please authenticate your avatar with the Moodle installation.", authentication_url);
        // Send the URL via link message
        llMessageLinked(LINK_THIS, 0, authentication_url, owner);

        // NOTE: in future, further checking etc. may need to be done here before moving to the "ready" state.
        
        // If we are in confirmation mode, then go back to confirming the entry
        // Otherwise, just continue to the "ready" state
        if (confirmationmode) state confirm;
        else state ready;
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
    }
    
    touch_start( integer num )
    {
        // Make sure it is the owner touching the HUD
        if (llDetectedKey(0) != llGetOwner()) return;
        // Is this attached as a HUD?
        if (llGetAttached() < 30)
        {
            llOwnerSay("Please attach me as a HUD item.");
            return;
        }
        
        // Get the name of the prim that was touched
        string name = llGetLinkName(llDetectedLinkNumber(0));
        // Has the "start blog" button been clicked?
        if (name == "start_blog")
        {
            state get_subject;
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
        llListen(USER_CHAT_CHANNEL, "", llGetOwner(), "");
        
        // Set a timeout
        llSetTimerEvent(CHAT_TIMEOUT);
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
        }
    }
    
    listen( integer channel, string name, key id, string msg )
    {
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
        llListen(USER_CHAT_CHANNEL, "", llGetOwner(), "");
        
        // Set a timeout
        llSetTimerEvent(CHAT_TIMEOUT);
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
        }
    }
    
    listen( integer channel, string name, key id, string msg )
    {
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
        }
        else if (name == "cancel") {
            // The "cancel" button was touched
            llOwnerSay("Blog entry cancelled.");
            state ready;
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
        
        // Copy the blog body so we can escape one of them for transmission
        string blogbody_copy = blogbody;
        // llEscapeURL currently returns at most 254 char len string. This means we need to do a horrible work around
        string msg = "pwd="+PRIM_PASS +"&uuid="+(string)llGetOwner() +"&subject="+llEscapeURL(blogsubject) + "&summary=";
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
        httpblogrequest = llHTTPRequest(MOODLE_ADDRESS + BLOG_SCRIPT, [HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"], msg);
        
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
        llOwnerSay("ERROR: Timeout waiting for HTTP response. You may try again, or cancel.");
        state confirm;
    }
    
    http_response(key request_id, integer status, list metadata, string body)
    {
        // Make sure this is the expected HTTP response
        if (request_id != httpblogrequest) return;
        httpblogrequest = null_key;
        
        // Was the blog entry successful?
        if (body == "success") {
            // Yes
            llOwnerSay("Updated blog entry successfully.");
            // Go back to the ready state
            state ready;
        }
        else {
            // No - try re-authenticating the user
            key owner = llGetOwner();
            llOwnerSay("ERROR: Failed to add blog entry. You may need to re-authenticate your avatar with the Moodle installation:");
            state authenticating;
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
