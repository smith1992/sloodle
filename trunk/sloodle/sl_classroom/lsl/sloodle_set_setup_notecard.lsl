// SL Sloodle Server / Course Setup
// Copyright Edmund Edgar, 2007-04-18

// This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

// This script allows the administrator to configure the Moodle server, authenticate the object and choose the course to use. 
// Once everything has been configured, it sends linked messages to other scripts in the same prim telling them the above.
// The code here was originally used by the Sloodle Box.
// It's been separated out here so that you can use the objects on their own, without rezzing them using the box.

string sloodleserverroot = ""; //"http://moodle.edochan.com";
string pwd = "";

integer object_dialog_channel = -3857343;
//integer avatar_dialog_channel = 3857343;
//integer avatar_chat_channel = 1;

integer SLOODLE_DO_DISPLAY_STATUS = 1;

key SLOODLE_TEXTURE_TOUCH_TO_SET = "618aeb0c-ac25-7010-741c-ba9954c2d09b";
key SLOODLE_TEXTURE_READY = "a9bbca6f-4fd6-6cc5-e13b-3faffb4f4672";

string SLOODLE_CONFIG_NOTECARD = "sloodle_config";

//key http_id; 
//integer listen_id;

string toucheravname;
key toucheruuid;

integer sloodle_courseid = 0;
string sloodle_course_title = "";

key sloodle_notecard_key;
integer sloodle_notecard_line = 0;

sloodle_tell_other_scripts(string msg)
{
    sloodle_debug("notecard sending message to other scripts: "+msg);
    llMessageLinked(LINK_SET, object_dialog_channel, msg, NULL_KEY);   
}

sloodle_debug(string msg)
{
    //llWhisper(0,msg);
}

sloodle_set_text(string msg) 
{
    if (SLOODLE_DO_DISPLAY_STATUS == 1) {
        llSetText(msg,<0,0,1>,1);     
    }
}

sloodle_display_server_course_status()
{
    if (sloodleserverroot == "") {
        sloodle_set_text("Moodle URL not set.");
    } else if (pwd == "") {
        sloodle_set_text(sloodleserverroot + "\nWaiting for authorization.");    
    } else if (sloodle_courseid == 0) {
        sloodle_set_text(sloodleserverroot + "\nNeed to select course...");      
    } else {
        sloodle_set_text(sloodleserverroot + "\n" + sloodle_course_title);
    }
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
    
    sloodle_display_server_course_status();

    sloodle_tell_other_scripts("set:usingnotecard|yes");   
    sloodle_tell_other_scripts("set:sloodleserverroot|"+sloodleserverroot);
    sloodle_tell_other_scripts("set:sloodle_courseid|"+(string)sloodle_courseid);
    sloodle_tell_other_scripts("set:pwd|"+pwd);
    sloodle_tell_other_scripts("set:toucheruuid|"+(string)toucheruuid);
 
    sloodle_debug("course and server set - ready for next step");        

}

default 
{
    on_rez(integer start_param)
    {
        sloodle_set_text("");
        llResetScript();
    }    
    state_entry() 
    {
        if (llGetInventoryType(SLOODLE_CONFIG_NOTECARD) == INVENTORY_NOTECARD) { 
            sloodle_debug("starting reading notecard");
            sloodle_display_server_course_status();
            sloodle_notecard_key = llGetNotecardLine("sloodle_config",0); // read the first line. The data_server event will get the next one,
        } else {
            sloodle_debug("No notecard called "+SLOODLE_CONFIG_NOTECARD+" found - skipping notecard configuration");
        }
    }
    touch_start(integer total_number)
    {
        //toucheruuid = llDetectedKey(0);
        //toucheravname = llDetectedName(0);
        //sloodle_display_server_course_status();
        //sloodle_notecard_key = llGetNotecardLine("sloodle_config",0); // read the first line. The data_server event will get the next one,        
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
}