string sloodleserverroot;
string opwd;
integer opwdcode;
integer command_channel = -3857343;
integer sloodle_courseid;
key controller_object = NULL_KEY;

sloodle_debug(string msg) 
{
   // llWhisper(0,msg);
}

sloodle_tell_other_scripts_in_prim(string msg)
{
    sloodle_debug("sending message to other scripts: "+msg);
    llMessageLinked(LINK_THIS, command_channel, msg, NULL_KEY);   
}

default
{
    
    on_rez(integer start_param) 
    {
        if (start_param > 0) {
            sloodleserverroot = "";
            opwd = "";
            opwdcode = 0;
            sloodle_courseid = 0;
            sloodle_debug("got start param "+(string)start_param+" - resetting sloodleserverroot etc");
        }
        opwdcode = start_param;
        llListen( command_channel, "", NULL_KEY, "" );
    }

    listen(integer channel, string name, key id, string message)
    {
        if (channel == command_channel) {
            list msgparts = llParseString2List(message,["|"],[]);
            key recipient = llList2Key(msgparts,0);
            if (recipient == llGetKey()) { // if the message is for us
                if (controller_object == NULL_KEY) { // controller object not yet defined
                    // controller should tell us the first 4 digits of the pin number it passed us.
                    string instructiontype = llList2String(msgparts,1);
                    string instructioncontent = llList2String(msgparts,2);
                    if (instructiontype == "set:controllercode") { // CHEK
                        // TODO: Check first 4 digits of opwd
                        string trustmecode = llGetSubString((string)opwdcode,0,3);
                        if (trustmecode == instructioncontent) {
                            controller_object = id;
                            opwd = (string)controller_object+"|"+(string)opwdcode;
                            
                        } else {
                            sloodle_debug("failed to set controller id: failed to match "+opwd+" against "+instructioncontent);
                        }
                        

                    }
                    llListen( command_channel, "", controller_object, "" ); // from now on, only listen to the controller
                    sloodle_debug("set contrller uuid to "+(string)controller_object);
                } else if (id == controller_object) { // message from controller
                    string instructiontype = llList2String(msgparts,1);
                    string instructioncontent = llList2String(msgparts,2);
                    
                    //llWhisper(0,"TODO: Process message"+message);
                    if ( instructiontype == "set:sloodleserverroot" ) {
                        sloodleserverroot = instructioncontent;
                        sloodle_debug("Set sloodle server root to "+sloodleserverroot);
                        sloodle_tell_other_scripts_in_prim("set:pwd|"+opwd);
                        sloodle_tell_other_scripts_in_prim("set:sloodleserverroot|"+sloodleserverroot);
                        
                    } else if (instructiontype == "set:sloodle_courseid") {
                        sloodle_courseid = (integer)instructioncontent;
                        sloodle_tell_other_scripts_in_prim("set:sloodle_courseid|"+(string)sloodle_courseid);
                    } else if (instructiontype == "CLEANUP") {
                        llDie();
                    } else {
                        sloodle_debug("sloodle_slave_object ignoring msg "+message);
                    }
                }
            }
        } // ignore other objects
    }
}

