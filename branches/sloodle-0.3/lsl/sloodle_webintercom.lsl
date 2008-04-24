// Sloodle WebIntercom (version 0.9, for Sloodle 0.3)
// Links in-world SL (text) chat with a Moodle chatroom
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-7 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Paul Andrews & Daniel Livingstone - initial design and implementation
//  Jeremy Kemp - added SLurls, message formatting, and beeps
//  Edmund Edgar - added authentication, ability to select chatroom, and to inherit settings from parent
//  Peter R. Bloomfield - updated to use new communications format (Sloodle 0.2)
//

// This script communicates with "mod/sloodle/mod/chat/sl_webintercom_linker.php"

// VERSIONS:
//  0.78 Moodle notification of SL entry/exit - JK
//  0.76 Inbound and Outbound beep all - JK
//  0.74 Added Moodle chat URL to starting notes, cleaned up chatter, cleaned SLURL maker - JK
//  0.72 reset when notecard is changed, to auto reload Moodle and chatroom data. - DL
//  0.7 added?
//  0.6 changes - changed the /SLURL to /slurl to avoid clashes with other gestures. - PA

string CHAT_ID = "2";

string linker_script = "/mod/sloodle/mod/chat/linker.php";

string SoundFile; //Sound file in object (if one exists)

string sloodleserverroot = "http://moodle19.avid-insight.co.uk";
string pwd = "555333111";
//integer sloodle_courseid = 0;
integer sloodlecontrollerid = 3;

string MOODLE_NAME="(SL)";

list chatroomids;
list chatroomnames;

//----------
//SLURL maker variables
vector Where;
string Name;
string SLURL;
integer X;
integer Y;
integer Z;
string RevisedMessage;
integer location;

//-----------
//Sloodle chat variables
list menu1=["YES"];
list menu2=["STOP","Continue"];
list menu_other=["Accept","Cancel"];
list unique_names;
list keys;
integer listenID;
integer part;
integer active;
integer CHANNEL = -67999;
string text;
string COL_START = "";
string COL_END = ""; 
string CODE_END = "";
integer total_len = 0;

key httprequest;
integer message_id = 0; // last message cc'd from moodle


sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// configure by receiving a linked message from another script in the object
sloodle_handle_command(string str) 
{
    //llWhisper(0, "handling command " +str);    
    list bits = llParseString2List(str,["|"],[]);
        string name = llList2String(bits,0);
        string value = llList2String(bits,1);
        if (name == "set:chatroomid") {
            CHAT_ID=value;
        } else if (name == "set:sloodleserverroot") {
            sloodleserverroot = value;
        } else if (name == "set:pwd") {
            pwd = value;
            if (llGetListLength(bits) == 3) {
                pwd = pwd + "|" + llList2String(bits,2);
            }
        } else if (name == "set:sloodlecontrollerid") {
            sloodlecontrollerid = (integer)value;
        }
    

    //llWhisper(0,"DEBUG: "+sloodleserverroot+"/"+pwd+"/"+(string)sloodlecontrollerid);

    if ( (sloodleserverroot != "") && (pwd != "") && (sloodlecontrollerid != 0) ) {
        state default;
    }
}

sloodle_init()
{
    //llWhisper(0,"initializing");    
    if ( (sloodleserverroot == "") || (pwd == "") || (sloodlecontrollerid == 0) ) {
        state sloodle_wait_for_configuration;
    }
}



default
{
    on_rez( integer param)
    {
        sloodle_init();
    }    
    
    state_entry()
    {     
        sloodle_init();

        llSetTimerEvent(0); // clear timer in case
        llSetTexture("059eb6eb-9eef-c1b5-7e95-a4c6b3e5ed9a",ALL_SIDES);
        unique_names = [];
        part = 0;
        llSetText("off",<1,1,1>,1);
        SoundFile = llGetInventoryName(INVENTORY_SOUND, 0);

    }
        
    touch_start( integer total_number)   {
        if ( ( llDetectedKey(0) == llGetOwner() ) && (CHAT_ID == "") ) {
            state select_chatroom;
        }
        if (llDetectedKey(0) == llGetOwner() )  {
            llListenRemove(listenID);
            listenID = llListen(CHANNEL,"",llGetOwner(),"");
            llDialog(llGetOwner(),"Click START to start recording.",menu1,CHANNEL);
            llSetTimerEvent(10);
        }
    }
    
    listen( integer channel, string name, key id, string message)    {
        //llOwnerSay(message);
        if (message == "YES") {
          llSay(0,"Chat logging is on!");
            llSay(0,"Join this Moodle chat at "+sloodleserverroot+"/mod/chat/view.php?id="+CHAT_ID);
            llSay(0,"Touch logger to record your chat.");
            llSetText("Chat logging is on!",<0,0,0>,1.0);
            llMessageLinked(LINK_THIS,part,"START",NULL_KEY);
            llSleep(0.1);
            llSetTimerEvent(0);
            state logging;
        }
    }

    timer() {
        llSetTimerEvent(0);
        llListenRemove(listenID);
        llOwnerSay("Dialog has timed out. Touch logger again to use.");
    }
    
}

state logging   {
    on_rez( integer param)   {
        // goto state default - TO FINISH
        llOwnerSay("chat logging off");
        state default;
    }
    
    state_entry()   {
        llSetTexture("d3c9180a-1703-3a84-8dcd-e3aa6306a343",ALL_SIDES);
        listenID = llListen(0,"",NULL_KEY,"");
        llListen(CHANNEL,"",NULL_KEY,"");  // listen for commands
        keys = [llGetOwner()];
        unique_names = [llKey2Name(llGetOwner())];
        text = "Recording: " + llList2String(unique_names,0);
        llSetText(text, <1,0.2,0.2>,1);
        total_len = 0;
        llSetTimerEvent(12);
    }
    
    touch_start( integer total_number)   {
        integer i;
        
        for (i=0; i < total_number; i++)    {

            if (llDetectedKey(i) == llGetOwner())  {
                llDialog(llGetOwner(),"\nStop logging?",menu2,CHANNEL);
            }
            else    {
                // Add name of avatar touching to the list
                llDialog(llDetectedKey(i),"\nMay I record your chat?",menu_other,CHANNEL);
            }
        }
    }
    
    listen( integer channel, string name, key id, string message)    {

        if (llGetOwnerKey(id) != id)    { // only true for avatars! Will ignore all object chat
            //llOwnerSay("Attempted spoofing");
            return;
        }
        
        if (channel == CHANNEL)     {
            if (message == "STOP" && id == llGetOwner() )   {    
                llSay(0,"Chat logging is now off!");
                llMessageLinked(LINK_THIS,part,"SEND",NULL_KEY);
                state default;
            }
            // double check that this message is genuinely from avatar not spoofed
            if (message == "Accept" && name == llKey2Name(id) )     {
                llSetTimerEvent(10);
                
                if(llListFindList(unique_names,[name]) == -1)    { 
                    unique_names += (unique_names=[]) + unique_names + name; // hack for faster code on wiki
                    keys += (keys=[]) + keys + id;
                    llSay(0,"Now recording " + name);
                    text = text + "\n " + name;
                    llSetText(text, <1,0.2,0.2>,1);
                }
            }
        }
        else    {
            integer i;
            string speech;
            i = llListFindList(unique_names,[name]  );
            
            if (i != -1)   {
/////////////SLURL MAKER
                if(message == "/slurl")     {        
                    Name = llGetRegionName();
                    Where = llGetPos();
                    X = (integer)Where.x;
                    Y = (integer)Where.y;
                    Z = (integer)Where.z;
                    // I don't replace any spaces in Name with %20 and so forth.
                    SLURL = "http://slurl.com/secondlife/" + Name + "/" + (string)X + "/" + (string)Y + "/" + (string)Z + "/?title=" + Name;
                    message = SLURL;                
                }
///////////////SLURL MAKER                   
                httprequest = llHTTPRequest(sloodleserverroot+linker_script+"?sloodlemoduleid="+CHAT_ID+"&sloodlepwd="+pwd+"&sloodlecontrollerid="+(string)sloodlecontrollerid+"&sloodleuuid="+(string)id+"&sloodleavname="+llEscapeURL(name)+"&message="+llEscapeURL(MOODLE_NAME + " " +name +": "+ message),[HTTP_METHOD,"GET"],"");
                // llSay(0, httprequest); // DEBUG PURPOSES
            }   
        }
    }
    
    timer()     {
        httprequest = llHTTPRequest(sloodleserverroot+linker_script+"?sloodlemoduleid="+CHAT_ID+"&sloodlepwd="+pwd+"&sloodlecontrollerid="+(string)sloodlecontrollerid,[HTTP_METHOD,"GET"],"");
        // llSay(0, httprequest); // DEBUG PURPOSES
        // llSay(0, URL); // DEBUG PURPOSES
        // FINDINGS:  the timer is working, the request is being properly constructed, and the SLoodle server does send back the proper info from the PHP script. Look further below...
    }
    
    http_response( key id,integer status, list meta, string body)
    {
        // Make sure the request worked
        if (status != 200) {
            sloodle_debug("Failed HTTP response. Status: " + (string)status);
        }
        // Is it the request we were after?
        if (httprequest == id)
        {
            // Make sure there is a body to the request
            if (llStringLength(body) == 0) return;
            // Debug output:
            sloodle_debug("Receiving chat data:\n" + body);
            
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
            
            // We will use these to store each item of data
            integer msgnum = 0;
            string name = "";
            string text = "";
            
            // Every other line should define a chat message "id|name|text"
            // Start at the line after the status line
            integer i = 1;
            for (i = (numlines - 1); i > 0; i--) {
                // Get all the different fields for this line
                list fields = llParseStringKeepNulls(llList2String(lines,i),["|"],[]);
                // Make sure we have enough fields
                if (llGetListLength(fields) >= 3) {
                    // Extract each item of data
                    msgnum = llList2Integer(fields,0);
                    name = llList2String(fields,1);
                    text = llList2String(fields,2);
                    
                    // Make sure this is a new message
                    if (msgnum > message_id) {
                        message_id = msgnum;
                        // Make sure this wasn't an SL message originally
                        if (llSubStringIndex(text, MOODLE_NAME) != 0) {
                            // Is this a Moodle beep?
                            if (llSubStringIndex(text, "beep ") == 0) {
                                // Yes - play a beep sound
                                llStopSound();
                                if (SoundFile == "") 
                                { // There is no sound file in inventory - plsy default
                                    llPlaySound("34b0b9d8-306a-4930-b4cd-0299959bb9f4", 1.0);
                                } else { // Play the included one
                                    llPlaySound(SoundFile, 1.0);
                                }
                            }
                            // Finally... just an ordinary chat message... output it
                            llSay(0, name + ": " + text);
                        }
                    }
                }
            }
        }
    }
}

state select_chatroom
{
    state_entry() {
        if (sloodlecontrollerid == 0) {
            state sloodle_wait_for_configuration;
        } else {
            // fetch list of chatrooms
            httprequest = llHTTPRequest(sloodleserverroot+linker_script+"?sloodlecontrollerid="+(string)sloodlecontrollerid+"&sloodlepwd="+pwd,[HTTP_METHOD,"GET"],"");
        }
    }
    http_response( key request_id, integer status, list metadata, string body) {
        sloodle_debug("HTTP Response ("+(string)status+"): " + body);

        if(status < 400) {
            if (request_id == httprequest) {
                sloodle_debug("Receiving new list of chatrooms:\n" + body);
            
                chatroomids = [];
                chatroomnames = [];
                
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
                    llSay(0, msg);
                    return;
                }
                
                // Every other line should define a chatroom "id|name"
                // Start at the line after the status line
                integer i = 1;
                for (i = 1; i < numlines; i++) {
                    // Get all the data fields for this line
                    list fields = llParseString2List(llList2String(lines,i),["|"],[]);
                    // Make sure we have enough fields
                    if (llGetListLength(fields) >= 2) {
                        // Store each item of data
                        chatroomids = chatroomids + [llList2Integer(fields, 0)];
                        chatroomnames = chatroomnames + [llList2String(fields, 1)];
                    }
                }

            }
        } else {
            sloodle_debug("Failed HTTP response. Status: " + (string)status);
        }
        
        // Display the chatroom menu
        integer chatroomcount = 0;
        list crmenu = ["Cancel"];
        string crmenustring = "";
        for (chatroomcount = 1; chatroomcount <= llGetListLength(chatroomnames); chatroomcount++) {
            integer crindex = chatroomcount - 1;
            crmenu = crmenu + [(string)chatroomcount];
            crmenustring = crmenustring + "\n"+(string)chatroomcount+": "+llList2String(chatroomnames,crindex);
        }
        listenID = llListen(CHANNEL,"",llGetOwner(),"");
        llDialog(llGetOwner(),"\nChoose your chatroom:\n"+crmenustring,crmenu,CHANNEL);        
    } 
    listen( integer lchannel, string name, key id, string message) {

        if (lchannel == CHANNEL) {
            if (message == "Cancel") {
                state default;
            }
            integer choicenum = (integer)message;
            integer choiceindex = choicenum - 1;
            CHAT_ID = (string)llList2Integer(chatroomids,choiceindex);
            llOwnerSay("Using chatroom "+CHAT_ID);
            state default;
        }
    }    
}

state sloodle_wait_for_configuration
{
    state_entry() {
        //llWhisper(0,"waiting for command");
    }
    link_message( integer sender_num, integer num, string str, key id) {
        if (num == DEBUG_CHANNEL) return; // Ignore debug messages
        //llWhisper(0,"got message "+(string)sender_num+str);
       // if ( (sender_num == LINK_THIS) && (num == sloodle_command_channel) ){
            sloodle_handle_command(str);
        //}   
    }
}
