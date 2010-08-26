// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.backpacks.balanceHud.lslp Thu Aug 26 00:16:58 Pacific Daylight Time 2010
    /*
    *  Sloodle Backpack HUD
    *  Copyright 2010 B3DMULTITECH.COM
    *  Paul Preibisch 
    *  fire@b3dmultitech.com
    *
    *  Released under the GNU GPL 3.0
    *  This script can be used in your scripts, but you must include this copyright header 
    *  as per the GPL Licence
    *  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
  
    *
    */ 
    //**********************************************************************************************//
    string sloodleserverroot = "http://christopher_flow.avatarclassroom.com";
    string sloodlepwd = "157037155";
    integer sloodlecontrollerid = 9;
    integer sloodleserveraccesslevel = 0;
    //**********************************************************************************************//
    key http;
    string SLOODLE_HQ_LINKER = "/mod/sloodle/mod/hq-1.0/linker.php";
    sendCommand(string str,string httpid){
    integer varStartIndex = llSubStringIndex(str,"&");
    string cmdStr = llGetSubString(str,0,(varStartIndex - 1));
    list cmdLine = llParseString2List(cmdStr,["->"],[]);
    string plugin = llList2String(cmdLine,0);
    string function = llList2String(cmdLine,1);
    string vars = llGetSubString(str,(varStartIndex + 1),(llStringLength(str) - 1));
    string requiredVars = ("&sloodlecontrollerid=" + ((string)sloodlecontrollerid));
    (requiredVars += ("&sloodlepwd=" + ((string)sloodlepwd)));
    (requiredVars += ("&sloodleserveraccesslevel=" + ((string)sloodleserveraccesslevel)));
    (requiredVars += ("&time_sent=" + ((string)llGetUnixTime())));
    llSetTimerEvent(20);
    key temp;
    (httpid = ((key)"http"));
    (http = llHTTPRequest((sloodleserverroot + SLOODLE_HQ_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],(((((((((sloodleserverroot + SLOODLE_HQ_LINKER) + "?") + "&plugin=") + plugin) + "&function=") + function) + requiredVars) + "&") + vars)));
    (temp = http);
    debug("******************************************************");
    debug((((((((((("********** " + llGetScriptName()) + " SENDING TO SERVER ") + plugin) + "->") + function) + " on ") + ((string)httpid)) + " id is: ") + ((string)temp)) + " *********************"));
    debug((((((((((sloodleserverroot + SLOODLE_HQ_LINKER) + "?") + "&plugin=") + plugin) + "&function=") + function) + requiredVars) + "&") + vars));
}
    debug(string str){
    if ((llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0) == PRIM_MATERIAL_FLESH)) {
        llOwnerSay(str);
    }
}
   

default {

    state_entry() {
    }

    touch_start(integer num_detected) {
        key owner = llGetOwner();
        string avname = llKey2Name(llGetOwner());
        string authenticatedUser = ((((((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(avname)) + "&sloodlemoduleid=5&avuuid=") + ((string)llGetOwner())) + "&avname=") + llEscapeURL(avname));
        sendCommand((((((("backpack->getAllBalances" + authenticatedUser) + "&sloodlemoduleid=") + ((string)5)) + "&index=") + ((string)0)) + "&sortmode=balance&gameid=1000"),NULL_KEY);
    }

    http_response(key request_id,integer status,list metadata,string body) {
        llSay(0,body);
    }
}
