// Landing zone script.
// Contributors:
//  Edmund Edgar - original design and implementation
//  Peter R. Bloomfield - updated to use new communications format

// This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

// For details about what this script is for and how it works, see the following discussion:
// http://www.sloodle.com/mod/forum/discuss.php?d=155

list avatarsDone; // a list of avatars for whom we've already sent a request.
integer clearAvatarsDoneListTiming = 300; // Clear out the list of avatars we've seen every 5 minutes

// The following will need to be set for each Moodle installation.
// It can be copied into the prim from a screen in Sloodle.
// We'll need to figure out how to manage this if a lot of different prims all need the same info.
string sloodleserverroot = "";
string pwd = "";
integer sloodle_courseid = 0;

string loginzoneurl = "/mod/sloodle/login/sl_loginzone_linker.php";

key http_id;


// Send debug info
sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}


// Tell Sloodle our position and size. 
// This will be called when we are rezzed or moved. 
// That way the server will always know where the landing zone prim is, and can give users appropriate landing zone coordinates.
send_position_to_server()
{
    string url = sloodleserverroot + loginzoneurl + "?sloodlepwd=" + pwd + "&sloodlepos=" + (string)llGetPos() + "&sloodlesize=" + (string)llGetScale()+"&sloodleregion="+llEscapeURL(llGetRegionName());

    sloodle_debug("Updating LoginZone data on server. URL= " + url);

    llHTTPRequest(url,[],"");

}

sloodle_handle_command(string str) 
{
    //llWhisper(0,"handling command "+str);
    sloodle_debug("Handling command: " + str);
    
    
    list bits = llParseString2List(str,["|"],[]);
        string name = llList2String(bits,0);
        string value = llList2String(bits,1);
        if (name == "set:sloodleserverroot") {
            sloodleserverroot = value;
        } else if (name == "set:pwd") {
            pwd = value;
            if (llGetListLength(bits) == 3) {
                pwd = pwd + "|" + llList2String(bits,2);
            }
        } else if (name == "set:sloodle_courseid") {
            sloodle_courseid = (integer)value;
        }
    

    //llWhisper(0,"DEBUG: "+sloodleserverroot+"/"+pwd+"/"+(string)sloodle_courseid);

    if ( (sloodleserverroot != "") && (pwd != "") && (sloodle_courseid != 0) ) {
        state running;
    }
}



default
{
    state_entry() {
        //llWhisper(0,"waiting for command");
        sloodle_debug("Waiting for configuration.");
        
        sloodleserverroot = "";
        pwd = "";
        sloodle_courseid= 0;
    }
    link_message(integer sender_num, integer num, string str, key id) {
        
        // Ignore the message if it was a debug message
        if (num == DEBUG_CHANNEL) return;
        
        //llWhisper(0,"got message "+(string)sender_num+str);
       // if ( (sender_num == LINK_THIS) && (num == sloodle_command_channel) ){
            sloodle_handle_command(str);
        //}   
    }
}


state running
{
    on_rez(integer param)
    {
        if ( (sloodleserverroot == "") || (pwd == "") || (sloodle_courseid == 0) ) {
            llResetScript();
        } else {
            send_position_to_server();
        }
        llVolumeDetect(TRUE); // This makes the prim phantom, so people can fall straight through it, and sets up detection of when someone collides with its edge or any point inside it.        
    }
    state_entry()
    {
        if ( (sloodleserverroot == "") || (pwd == "") || (sloodle_courseid == 0) ) {
            llResetScript();
        } else {
            send_position_to_server();
        }
        llVolumeDetect(TRUE); // This makes the prim phantom, so people can fall straight through it, and sets up detection of when someone collides with its edge or any point inside it.
        llSetTimerEvent(clearAvatarsDoneListTiming); // timeout to clear list for testing
    }

    collision_start(integer num_detected)
    {
        integer i;
        for (i = 0; i < num_detected; i++)
        {

            // only send one request per user. If we've already seen the user before since we last cleared out the list, ignore them.
            integer index = llListFindList( avatarsDone, [llDetectedName(i)] );            
            if (index == -1) { 

                // The avatar size probably isn't necessary, but we're trapping it in case it gives us a clue why although the x and y coordinates of the avatars who get teleported into the prim are exactly what we request, the z coordinate is sometimes 1 meter or so off.
                string url = sloodleserverroot + loginzoneurl + "?sloodlepwd=" + pwd+"&sloodleavname="+llEscapeURL(llDetectedName(i))+"&sloodleuuid="+(string)llDetectedKey(i)+"&sloodlepos="+(string)llDetectedPos(i);

                sloodle_debug("Reporting user entry: " + url);

                http_id = llHTTPRequest(url,[],"");
                //llWhisper(0,url); // just for testing. This contains the password, so it has to be turned off in a live installation.
                //avatarsDone += [llDetectedName(i)];

            } else {
                sloodle_debug("Ignoring avatar - already handled.");
            }
        }
    }

    // Clear out the avatar list, and set the timer to do the same thing again.
    // Probably not need in a non-demo installation.
    timer() 
    {
        avatarsDone = [];    
        llSetTimerEvent(clearAvatarsDoneListTiming);
    }

    moving_end()
    {
        send_position_to_server();
    }
    
    //http_response(key id, integer status, list metadata, string body)
    //{
    //    llOwnerSay("HTTP Response (" + (string)status + "): " + body);
    //}

}
