// Sloodle configuration notecard reader
// Reads a configuration notecard and transmits the data via link messages to other scripts
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2007 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar - original design and implementation
//  Peter R. Bloomfield - cleaned-up, and updated to reset when the notecard is replaced


string sloodleserverroot = "";
string pwd = "";

integer SLOODLE_OBJECT_DIALOG_CHANNEL = -3857343;
integer SLOODLE_DO_DISPLAY_STATUS = 0;
string SLOODLE_CONFIG_NOTECARD = "sloodle_config";

integer sloodle_courseid = 0;
string sloodle_course_title = "";

key sloodle_notecard_key;
integer sloodle_notecard_line = 0;

sloodle_tell_other_scripts(string msg)
{
    sloodle_debug("notecard sending message to other scripts: "+msg);
    llMessageLinked(LINK_SET, SLOODLE_OBJECT_DIALOG_CHANNEL, msg, NULL_KEY);   
}

sloodle_debug(string msg)
{
    //llWhisper(0,msg);
}

sloodle_handle_notecard_line(string str) 
{
    list bits = llParseString2List(str,["|","\n"],[]);
    
    string name = llList2String(bits,0);
    string value = llList2String(bits,1);
    if (name == "set:sloodleserverroot") {
        sloodleserverroot = value;
    } else if (name == "set:pwd") {
        pwd = value;
    } else if (name == "set:sloodle_courseid") {
        sloodle_courseid = (integer)value;
    } else {
        sloodle_debug("ignoring notecard line "+str);
    } 
    
    //llWhisper(0,"DEBUG: "+sloodleserverroot+"/"+pwd+"/"+(string)sloodle_courseid);

    if ( (sloodleserverroot != "") && (pwd != "") && (sloodle_courseid != 0) ) {
        sloodle_debug("going to next step");
        next_step();
    } 
        
}

sloodle_handle_command(string str) 
{
    
    list bits = llParseString2List(str,["|","\n"],[]);
    
    string name = llList2String(bits,0);
    string value = llList2String(bits,1);    

    if (name == "do:reset") {
        sloodle_debug("course setup notecard resetting script");
        llResetScript();
    }

}

next_step()
{
    sloodle_tell_other_scripts("set:usingnotecard|yes");   
    sloodle_tell_other_scripts("set:sloodleserverroot|"+sloodleserverroot);
    sloodle_tell_other_scripts("set:sloodle_courseid|"+(string)sloodle_courseid);
    sloodle_tell_other_scripts("set:pwd|"+pwd);
 
    sloodle_debug("course and server set - ready for next step");        

}

default 
{
    on_rez(integer start_param)
    {
        llResetScript();
    }
    
    state_entry() 
    {
        if (llGetInventoryType(SLOODLE_CONFIG_NOTECARD) == INVENTORY_NOTECARD) { 
            sloodle_debug("starting reading notecard");
            sloodle_notecard_key = llGetNotecardLine("sloodle_config",0); // read the first line. The data_server event will get the next one,
        } else {
            sloodle_debug("No notecard called "+SLOODLE_CONFIG_NOTECARD+" found - skipping notecard configuration");
        }
    }
    
    dataserver(key requested, string data)
    {
        if ( requested == sloodle_notecard_key )  // make sure we are getting the data we want
        {
            sloodle_notecard_key = NULL_KEY;
            if ( data != EOF )
            {
                sloodle_debug(data);
                sloodle_handle_notecard_line(data);                
                sloodle_notecard_line++;
                sloodle_notecard_key = llGetNotecardLine("sloodle_config",sloodle_notecard_line);
            }
        }
    }
    
    link_message(integer sender_num, integer num, string str, key id) {
        //sloodle_debug("dialog got message "+(string)sender_num+str);
        sloodle_handle_command(str);
    }
    
    changed(integer change) {
        // If the inventory is changed, and we have a Sloodle config notecard, then use it to re-initialise
        if (change & CHANGED_INVENTORY && llGetInventoryType(SLOODLE_CONFIG_NOTECARD) == INVENTORY_NOTECARD) {
            llResetScript();
        }
    }
}