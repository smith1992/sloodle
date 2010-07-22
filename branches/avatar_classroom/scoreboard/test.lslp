 /*********************************************
 *  Copyright (c) 2009 Paul Preibisch
 *  Released under the GNU GPL 3.0
 *  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
 *  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
 * 
 *
 *  This script is part of the SLOODLE Project see http://sloodle.org
 *
 *  Copyright:
 *  Paul G. Preibisch (Fire Centaur in SL)
 *  fire@b3dMultiTech.com  
 *
 * API DEMO OBJECT.lsl 
 * 
 * PURPOSE
 *  This script is part of the SLOODLE HQ.
 *  
 /**********************************************************************************************/
 key owner;
 // *************************************************** HOVER TEXT VARIABLES
 integer DISPLAY_DATA                                                        =-774477; //every time the display is updated, data goes  on this channel
 integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
 integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
 integer SLOODLE_CHANNEL_TRANSLATION_REQUEST     = -1928374651;//translation channel
 integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE     = -1928374652;//translation channel
 integer SLOODLE_CHANNEL_OBJECT_DIALOG                     = -3857343;//configuration channel
 
 /***********************************************
 *  getFieldData-- returns data from a table based on the column name
 ***********************************************/
 string getFieldData(list tableRowData, string colName){
    return llList2String(tableRowData,llListFindList(tableRowData, [colName])+1);
 } 
 /***********************************************************************************************
*  s()  k() i() and v() are used so that sending messages is more readable by humans.  
* Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All these functions do is strip off the text before the ":" char and return a string
***********************************************************************************************/
string s (string ss){
    return llList2String(llParseString2List(ss, [":"], []),1);
}//end function
key k (string kk){
    return llList2Key(llParseString2List(kk, [":"], []),1);
}//end function
integer i (string ii){
    return llList2Integer(llParseString2List(ii, [":"], []),1);
}//end function
vector v (string vv){
    return llList2Vector(llParseString2List(vv, [":"], []),1);
}//end function
 
 makeTransaction(string avname,key avuuid,integer points){    
     key owner = llGetOwner();
        string authenticatedUser= "&sloodleuuid="+(string)owner+"&sloodleavname="+llEscapeURL(llKey2Name(owner));
            //plugin:awards refers to awards.php in sloodle/mod/hq-1.0/plugins/awards.php              
            //send the plugin function request via the plugin_channel
            llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "awards->makeTransaction"+authenticatedUser+"&sloodleid="+(string)2+"&sourceuuid="+(string)owner+"&avuuid="+(string)avuuid+"&avname="+llEscapeURL(avname)+"&points="+(string)points+"&details="+llEscapeURL("owner modified ipoints,OWNER:"+llKey2Name(owner)+",SCOREBOARD:"+(string)llGetKey()+",SCOREBOARDNAME:"+llGetObjectName()), NULL_KEY);
            llSay(0, "awards->makeTransaction"+authenticatedUser+"&sloodleid="+(string)2+"&sourceuuid="+(string)owner+"&avuuid="+(string)avuuid+"&avname="+llEscapeURL(avname)+"&points="+(string)points+"&details="+llEscapeURL("owner modified ipoints,OWNER:"+llKey2Name(owner)+",SCOREBOARD:"+(string)llGetKey()+",SCOREBOARDNAME:"+llGetObjectName()));     
}
 
 
 /* &&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
 *
 *  default state
 *  In this state we wait until the rest of the scripts in this object init
 *
 * &&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&& */
 default{
     on_rez(integer start_param) {
                  owner = llGetOwner();
     }
    link_message(integer sender_num, integer channel, string str, key id) {
        list dataLines=llParseString2List(str, ["\n"],[]);
        list cmdLine = llParseString2List(str, ["|"],[]);
        string cmd=s(llList2String(cmdLine,0));
        if (channel==PLUGIN_RESPONSE_CHANNEL){
            if (cmd=="API READY") state go;
        }//endif channel=PLUGIN_RESPONSE_CHANNEL
    }//end linked_message event
    /***********************************************
    *  changed event
    *  |-->Every time the inventory changes, reset the script
    *        
    ***********************************************/
    changed(integer change) {
         if (change ==CHANGED_INVENTORY){         
             llResetScript();
         }//endif
     }//end changed state
 }//end default state
 
 
    
 state go{
     on_rez(integer start_param) {
         llResetScript();
     }
    touch_start(integer num_detected) {
     string authenticatedUser= "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
         //llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "awards->sendUrl"+authenticatedUser+"&url=http://sim5468.agni.lindenlab.com:12046/cap/a8b877c2-efe2-1602-888f-d79061e62c2e", NULL_KEY);
         llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "awards->callScript"+authenticatedUser+"&url=http://sim5468.agni.lindenlab.com:12046/cap/a8b877c2-efe2-1602-888f-d79061e62c2e&DATA=hi", NULL_KEY);
    }
    state_entry() {
        llSay(0,"ready");
    }
     link_message(integer sender_num, integer channel, string str, key id) {
         
             if (channel==PLUGIN_RESPONSE_CHANNEL){
                list dataLines = llParseStringKeepNulls(str,["\n"],[]);           
                //get status code
                list statusLine =llParseString2List(llList2String(dataLines,0),["|"],[]);
                integer status =llList2Integer(statusLine,0);
                string descripter = llList2String(statusLine,1);
               llSay(0,str);
               
             }//end if channel==PLUGIN_RESPONSE_CHANNEL
       }//end link_message
        
    /***********************************************
    *  changed event
    *  |-->Every time the inventory changes, reset the script
    *        
    ***********************************************/
    changed(integer change) {
         if (change ==CHANGED_INVENTORY){         
             llResetScript();
         }//endif
     }//end changed state
     
     
 }//end state