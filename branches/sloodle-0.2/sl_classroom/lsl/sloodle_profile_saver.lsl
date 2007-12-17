// Sloodle classroom profile saver
// Gathers classroom setup data, and sends it to the server
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-7 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar - original design and implementation
//  Peter R. Bloomfield - updated to handle new communications format (Sloodle 0.2)
//

string baseurl = "";
key http_id = NULL_KEY;

integer SLOODLE_CHANNEL_OBJECT_PROFILE_SAVER_LIST_INVENTORY = -1639270011;
integer SLOODLE_CHANNEL_OBJECT_PROFILE_SAVER_DO_SAVE = -1639270012;
integer SLOODLE_CHANNEL_OBJECT_INVENTORY_VENDOR_LIST_INVENTORY = -1639270021;

list inventory = [];

sloodle_debug(string str) 
{
   llMessageLinked(LINK_THIS, DEBUG_CHANNEL, str, NULL_KEY);
}

default
{   
    on_rez(integer start_param)
    {
        llResetScript();
    }   
    link_message(integer sender_num, integer num, string str, key id) {
        if (num == SLOODLE_CHANNEL_OBJECT_PROFILE_SAVER_DO_SAVE) {
            baseurl = str;
            sloodle_debug("profile saver got message" + str);
            state save;
        } else if (str == "do:reset") {
            llResetScript(); 
        }
    }    
}

state save
{       
    state_entry() {        
        // request an inventory list
        llMessageLinked(LINK_ALL_OTHERS,SLOODLE_CHANNEL_OBJECT_INVENTORY_VENDOR_LIST_INVENTORY,"list:inventory",NULL_KEY);
    }
    link_message(integer sender_num, integer num, string str, key id) {
        
        // Ignore anything on the debug channel
        if (num == DEBUG_CHANNEL) return;
        
        if (num ==  SLOODLE_CHANNEL_OBJECT_PROFILE_SAVER_LIST_INVENTORY) {
            inventory = llCSV2List(str);
            sloodle_debug( "got list " + llDumpList2String(inventory,"|") +" with "+(string)llGetListLength(inventory)+" items");
 
            if (llGetListLength(inventory) < 1) {
                sloodle_debug("no inventory items found");
            } else {
                sloodle_debug("profile saver in starting sensor");        
                llSensor("", NULL_KEY, ACTIVE | PASSIVE | SCRIPTED, 20, PI); // scan for obejcts within 96 metres. Anything more than this will not be saved for now. Alternative would be to have the server send a message to all objects telling them to report home.
            }
        }
    }

    sensor(integer num_detected) {
        
        vector thispos = llGetPos();

        integer i;
        integer numfound = 0;
        integer ts = llGetUnixTime(); // make a timestamp to identify this saved set. // Once we've sent requests for all the objects, we'll send this, along with the number of objects we sent, to let the server know we're done.
        string postdata; 
        string savemestring = "";
 
        sloodle_debug("found "+(string)num_detected+" objects");
 
        for (i=0; i<num_detected; i++) {
            string name = llDetectedName(i);
            key uuid = llDetectedKey(i);
            if (llListFindList(inventory,[name]) == -1) { // is it in this object's inventory? if not, ignore it 
                sloodle_debug("Ignoring item "+name+" ("+(string)uuid+") which is not in my inventory.");
            } else {
                numfound++;
                vector savemepos = llDetectedPos(i) - thispos;      
                integer savemeentryid = 0; //object_entry_id_for_uuid(uuid);
                if (i > 0) savemestring += "||";
                savemestring += llEscapeURL(name)+"|"+(string)savemepos;
            }
        }
        if (numfound == 0) {
            llWhisper(0,"No objects found to save.");
            //state menu;    
        } else {
            // tell the server we're done
            string url = baseurl+"&sloodleentries="+savemestring;   
            sloodle_debug("sending request"+url);
            http_id = llHTTPRequest(url,[],"");                      
        }
        
    }
    no_sensor() {
        llWhisper(0,"No objects found to save - no_sensor ran.");
        state default;   
    }
    http_response(key request_id, integer status, list metadata, string body) {        
        //sloodle_debug("got response"+body);
        if(status < 400) {
            if (request_id == http_id) {
                
                // Split the response by lines, then extract the status fields
                list lines = llParseStringKeepNulls(body, ["\n"], []);
                list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
                integer statuscode = llList2Integer(statusfields, 0);
                // If there is a data line, then it is probably an error message
                string errormsg = "";
                if (llGetListLength(lines) > 1) errormsg = llList2String(lines,1);
                
                // Check the status code
                if (statuscode > 0) {
                    // Success
                    llSay(0, "Profile has been saved.");
                } else {
                    // An error occurred
                    llSay(0, "Error " + (string)statuscode + " occurred while trying to save the profile. " + errormsg);
                }
            }
        }
        state default;
    }      
}