//_getGroup.lsl
//gets a vector from a string
integer ANIM_CHANNEL=-99;
integer XY_FLAG_CHANNEL = -92811;
integer listenHandle;
integer listenHandle2;
list groups;
key sitter;
integer gameid;
string myQuizName;
integer myQuizId;


integer scoreboardchannel;
            integer sloodlemoduleid;
            string sloodlepwd;
            string sloodleserverroot;
integer DISPLAY_CHANNEL=-870881;            
vector     RED            = <0.77278,0.04391,0.00000>;//RED
vector     ORANGE = <0.87130,0.41303,0.00000>;//orange
vector     YELLOW         = <0.82192,0.86066,0.00000>;//YELLOW
vector     GREEN         = <0.12616,0.77712,0.00000>;//GREEN
vector     BLUE        = <0.00000,0.05804,0.98688>;//BLUE
vector     PINK         = <0.83635,0.00000,0.88019>;//INDIGO
vector     PURPLE = <0.39257,0.00000,0.71612>;//PURPLE
vector     WHITE        = <1.000,1.000,1.000>;//WHITE
vector     BLACK        = <0.000,0.000,0.000>;//BLACKvector     ORANGE = <0.87130, 0.41303, 0.00000>;//orange
list colors = [GREEN, YELLOW, ORANGE, BLUE, RED, PINK,PURPLE];
integer debugCheck(){
    if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
        return TRUE;
    }
        else return FALSE;
    
}
     setFlag(string cmd){
                                    if (cmd=="down"){
                                        //http://www.freesound.org/samplesViewSingle.php?id=14609
                                        llTriggerSound("flag_down", 1.0);
                                        llMessageLinked(LINK_SET,ANIM_CHANNEL,"p0", NULL_KEY);
                                        llMessageLinked(LINK_SET, GROUP_CHANNEL, "CMD:set color|"+(string)WHITE, NULL_KEY);
                                        llMessageLinked(LINK_SET, XY_FLAG_CHANNEL,"", NULL_KEY);
                                        return;
                                    }
                                    else{
                                        llTriggerSound("flag_up", 1.0);
                                        llMessageLinked(LINK_SET, XY_FLAG_CHANNEL,cmd, NULL_KEY);
                                        llMessageLinked(LINK_SET,ANIM_CHANNEL,"p1", NULL_KEY);
                                    }
                                        setColor(cmd);
                                }

reinitialise()
        {
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", NULL_KEY);
            llResetScript();
}
debug(string str){
    if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==PRIM_MATERIAL_FLESH){
        llOwnerSay(str);
    }
}
vector getVector(string vStr){
        vStr=llGetSubString(vStr, 1, llStringLength(vStr)-2);
        list vStrList= llParseString2List(vStr, [","], ["<",">"]);
        vector output= <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2)>;
        return output;
}//end getVector
rotation getRot(string vStr){
        vStr=llGetSubString(vStr, 1, llStringLength(vStr)-2);
        list vStrList= llParseString2List(vStr, [","], ["<",">"]);
        rotation output= <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2),llList2Float(vStrList,3)>;
        return output;
}//end getRot
integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
integer SLOODLE_CHANNEL_OBJECT_DIALOG                   = -3857343;//configuration channel
/***********************************************
*  isFacilitator()
*  |-->is this person's name in the access notecard
***********************************************/
integer isFacilitator(string avName){
    if (llListFindList(facilitators, [llStringTrim(llToLower(avName),STRING_TRIM)])==-1) return FALSE; else return TRUE;
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
    integer p = llSubStringIndex(vv, ":");
    string vString = llGetSubString(vv, p+1, llStringLength(vv));
    return getVector(vString);
}//end function
rotation r (string rr){
    integer p = llSubStringIndex(rr, ":");
    string rString = llGetSubString(rr, p+1, llStringLength(rr));
    return getRot(rString);
}//end function
integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
list myGroups;
string SLOODLE_EOF = "sloodleeof";
integer sloodlecontrollerid;
string sloodlecoursename_short;
string sloodlecoursename_full;
integer sloodleid;
string scoreboardname;
integer currentAwardId;
integer GROUP_CHANNEL= -91123421;
string myGroupName;
string currentAwardName;
list facilitators;


        // Configure by receiving a linked message from another script in the object
        // Returns TRUE if the object has all the data it needs
        integer sloodle_handle_command(string str) {
            if (str=="do:requestconfig"||str=="do:reset")llResetScript();
            list bits = llParseString2List(str,["|"],[]);
           string name=  llList2String(bits,0);
           string value1 = llList2String(bits,1);
            
            if (name == "set:sloodleserverroot") sloodleserverroot = value1; else 
            if (name == "set:sloodlepwd") sloodlepwd = value1;
            if (name == "set:sloodlecontrollerid") sloodlecontrollerid = (integer)value1; else
            if (name == "set:quizmoduleid") sloodlemoduleid = (integer)value1;else
            if (name == "set:scoreboardchannel") scoreboardchannel= (integer)value1;else
            if (name == "set:scoreboard") {
                currentAwardId = (integer)value1;
           }                      
            else
            if (name == SLOODLE_EOF) return TRUE;
            return FALSE;                                           
           
        }
        


setColor(string grpName){
//search through myGroups and assign color
groups= llListSort(groups, 1, TRUE);
    integer found = llListFindList(groups, [llStringTrim(grpName,STRING_TRIM)]);
    vector color;
    if (found==-1) color=WHITE;
    else color = getVector(llList2String(colors, found));
    //send to flag.lsl
    llMessageLinked(LINK_SET, GROUP_CHANNEL, "CMD:set color|"+(string)color, NULL_KEY);    
}

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer( integer min, integer max ){
 return min + (integer)( llFrand( max - min + 1 ) );
}
integer MENU_CHANNEL;
default {
    on_rez(integer start_param) {
        llResetScript();
    }
    state_entry() {
        llUnSit(llGetLinkKey(9));
        llMessageLinked(LINK_SET, GROUP_CHANNEL, "CMD:set text|text:|"+(string)YELLOW, NULL_KEY);
        MENU_CHANNEL= random_integer(-30000,-20000);
        
            llMessageLinked(LINK_SET, GROUP_CHANNEL, "CMD:set color|"+(string)WHITE, NULL_KEY);    
        facilitators+=llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM);
        //MENU_CHANNEL = random_integer(-321111,-2112);
        llListen(MENU_CHANNEL, "", llGetOwner(), "");
    }
  
    link_message(integer sender_num, integer channel, string str, key id) {
        if (channel==UI_CHANNEL){
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd= s(llList2String(cmdList,0));
            string button = s(llList2String(cmdList,1));
            //check to see if any commands are currently being processed
            //comes from scoreboard_public_data
            if (cmd=="GAMEID"){
            	debug(str);
                gameid=i(llList2String(cmdList,1));
                groups=llParseString2List(s(llList2String(cmdList,2)),[","],[]);
                myQuizName =s(llList2String(cmdList,3));
                myQuizId =i(llList2String(cmdList,4));
                integer len = llGetListLength(groups);
                integer j;
                list tmpGrps;
                for (j=0;j<len;j++){
                    tmpGrps += llStringTrim(llList2String(groups,j), STRING_TRIM);
                }
                groups = tmpGrps;
                
            }//
            else
            if (cmd=="BUTTON PRESS"){
                key userKey=k(llList2String(cmdList,2));
                    sitter=llAvatarOnSitTarget();
                debug(" my groups are: "+llList2CSV(myGroups)+" groups are: "+llList2CSV(groups)+" sitter is: "+(string)sitter);
                if (button=="btn_flag"||button=="c:0,r:0,d,c:-92811,charPos:0"){
                
                    if (sitter==NULL_KEY) return;
                    if (userKey!=sitter&&userKey!=llGetOwner())return;   
                        llMessageLinked(LINK_SET, GROUP_CHANNEL, "CMD:set text|text:"+llKey2Name(sitter)+"|"+(string)YELLOW, NULL_KEY);
                        llListenRemove(listenHandle );
                        llListenRemove(listenHandle2 );
                        listenHandle = llListen(MENU_CHANNEL, "", sitter, "");
                        listenHandle2 = llListen(MENU_CHANNEL+1, "", sitter, "");                     
                        if (userKey!=sitter&&userKey!=llGetOwner()) return;
                            list menu;
                            if (llGetListLength(myGroups)>0) menu+="Leave group";
                           // if ((llGetListLength(groups)-llGetListLength(myGroups))>0) menu+="Join Group";
                           if ((llGetListLength(myGroups))==0) menu+="Join Group";
                                llDialog(userKey, "Group Menu\n\n"+llKey2Name(userKey)+", is a member of: "+llList2CSV(myGroups),menu, MENU_CHANNEL);

                }//button
            }//cmd
            else
            if (cmd=="sitter") sitter=id;          
        }//ui_channel
        else
        if (channel==SLOODLE_CHANNEL_OBJECT_DIALOG){
                sloodle_handle_command(str);
            }//endif SLOODLE_CHANNEL_OBJECT_DIALOG
         else
         if (channel==PLUGIN_RESPONSE_CHANNEL){
                    list dataLines = llParseStringKeepNulls(str,["\n"],[]);           
                    //get status code
                    list statusLine =llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
                    integer status =llList2Integer(statusLine,0);
                    string descripter = llList2String(statusLine,1);
                    string response = s(llList2String(dataLines,1));
                    integer totalGrps;   
                    
                     if (str=="do:requestconfig"){
                         llResetScript();
                     }
                     else        
                if (response=="user|removeGrpMbr"){
                        if (status==1){                        
                                                        integer j;
                            integer len = llGetListLength(myGroups);
                            list tmpGrps;
                            for (j=0;j<len;j++){
                                tmpGrps+=llStringTrim(llList2String(myGroups,j), STRING_TRIM);
                            }
                            myGroups = tmpGrps;
                            string gname = llList2String(dataLines,2);
                            integer found = llListFindList(myGroups, [llStringTrim(gname,STRING_TRIM)]);
                            //llSay(0,"searched for: "+llStringTrim(gname,STRING_TRIM)+ " Found is: "+(string)found+" mygroups are: "+llList2CSV(myGroups)); 
                            if (found!=-1){
                                myGroups=  llDeleteSubList(myGroups, found, found);
                            //    llSay(0,"now Found is: "+(string)found+" mygroups are: "+llList2CSV(myGroups));
                            }
                            if (llGetListLength(myGroups)==0) {
                                myGroupName = "";
                                setFlag("down");
                                llMessageLinked(LINK_SET,DISPLAY_CHANNEL,"CMD:COLOR|name:btn_desk|"+(string)WHITE,NULL_KEY);
                                return;
                            }
                            else{
                                 myGroupName =llList2String(myGroups,0);
                                 llMessageLinked(LINK_SET,DISPLAY_CHANNEL,"CMD:COLOR|name:btn_desk|"+(string)YELLOW,NULL_KEY);                                 
                                // llSay(0,"my new group name is:: "+(string)myGroupName+" mygroups are: "+llList2CSV(myGroups));
                            }   
                            setFlag(myGroupName); 
                            return;                         
                        }//endif status==1
                    }//  if (response=="user|removeGrpMbr")  
                    else    
                     if (response=="user|addGrpMbr"){
                        if (status==1){                        
                                string gname = s(llList2String(dataLines,2));
                                myGroups+= gname; 
                                myGroupName =llList2String(myGroups,0);
                                setFlag(myGroupName);
                                 if (llGetListLength(myGroups)>1)llMessageLinked(LINK_SET,DISPLAY_CHANNEL,"CMD:COLOR|name:btn_desk|"+(string)YELLOW,NULL_KEY); else
                                llMessageLinked(LINK_SET,DISPLAY_CHANNEL,"CMD:COLOR|name:btn_desk|"+(string)WHITE,NULL_KEY);
                            return; 
                        }//endif status==1
                    }//  if (response=="user|removeGrpMbr")  
                    else                          
                    if (response=="groups|getUsersGrps"){
                        if (status==1){
                            totalGrps =   i(llList2String(dataLines,2));                   
                            string avname = s(llList2String(dataLines,3));
                            key avuuid = k(llList2String(dataLines,4));
                            myGroups = llList2List(dataLines,5,llGetListLength(dataLines)-1);                            
                            integer len = llGetListLength(myGroups);                  
                            integer j;
                            list tmpMyGroups;                    
                            for (j=0;j<len;j++){
                                tmpMyGroups+=llStringTrim(s(llList2String(myGroups,j)),STRING_TRIM);
                            }
                                myGroups=tmpMyGroups;
                                if (llGetListLength(myGroups)>1)llMessageLinked(LINK_SET,DISPLAY_CHANNEL,"CMD:COLOR|name:btn_desk|"+(string)YELLOW,NULL_KEY); else
                                llMessageLinked(LINK_SET,DISPLAY_CHANNEL,"CMD:COLOR|name:btn_desk|"+(string)WHITE,NULL_KEY);
                                //llMessageLinked(LINK_SET, GROUP_CHANNEL, "CMD:set text|text:"+llKey2Name(sitter)+"'s groups are: "+llList2CSV(myGroups)+"|"+(string)YELLOW, NULL_KEY);
                                myGroupName=llList2String(myGroups,0);                               
                                setFlag(myGroupName);
                                return;
                        }//status
                        else
                        if (status==-55000){
                            string avname = s(llList2String(dataLines,2));
                            key avuuid = k(llList2String(dataLines,3));                         
                                string authenticatedUser = "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
                                llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "groups->addToRandomGrp"+authenticatedUser+"&sourceuuid="+(string)avuuid+"&avuuid="+(string)avuuid+"&avname="+llEscapeURL(llKey2Name(avuuid)),NULL_KEY);
                                 
                        }//status
                    }//response
                    else 
                    if (response=="groups|addToRandomGrp"){                       
                            key avuuid = k(llList2String(dataLines,2));
                            string avname =llKey2Name(avuuid);
                            myGroupName= s(llList2String(dataLines,3));
                            myGroups+=myGroupName;
                            setFlag(myGroupName);
                            debug("added to random group my group name is: "+myGroupName);
                            return;
                    }//response
               }//channel
               
      }//linked
        listen(integer channel, string name, key id, string str) {
        if (channel==MENU_CHANNEL){
            if (str=="Leave group"){
                integer len =llGetListLength(myGroups); 
                if (len==1){
                  string authenticatedUser = "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
                if (sitter!=NULL_KEY)
                    llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "user->removeGrpMbr"+authenticatedUser+"&sloodlemoduleid="+(string)currentAwardId+"&grpname="+llEscapeURL(llList2String(myGroups,0))+"&avuuid="+(string)sitter+"&avname="+llEscapeURL(llKey2Name(sitter)), NULL_KEY);
                }else
                if (len>1)
                    llDialog(id, "Which group should "+llKey2Name(sitter)+" leave?",myGroups, MENU_CHANNEL);
                    
                else llDialog(id, "Sorry, "+llKey2Name(sitter)+" doesn't belong to any groups yet!", [], MENU_CHANNEL);
            }
            else 
            if (str=="Join Group"){
                list tmpGrps;
                integer len;
                integer j;
                string grpName;
                len= llGetListLength(groups);
                for (j=0;j<len;j++){
                    grpName = llList2String(groups,j);
                    if (llListFindList(myGroups, [grpName])==-1)tmpGrps+=grpName;        
                }
                if (llGetListLength(tmpGrps)>0)
                    llDialog(id, "Which group do you want to join?\nAvailable groups are: "+llList2CSV(tmpGrps),tmpGrps, MENU_CHANNEL+1);
                else llDialog(id, "Sorry, there are no groups you can join", [], MENU_CHANNEL+1);
            }
            else if (llListFindList(myGroups, [str])!=-1){
                //remove group
                string authenticatedUser = "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
                llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "user->removeGrpMbr"+authenticatedUser+"&sloodlemoduleid="+(string)currentAwardId+"&grpname="+llEscapeURL(str)+"&avuuid="+(string)sitter+"&avname="+llEscapeURL(llKey2Name(sitter)), NULL_KEY);
            }
        }//menu_channel
        else
        if (channel==MENU_CHANNEL+1){
            if (llListFindList(groups, [str])!=-1){
                //add group
                string authenticatedUser = "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
                llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "user->addGrpMbr"+authenticatedUser+"&sloodlemoduleid="+(string)currentAwardId+"&grpname="+llEscapeURL(str)+"&avuuid="+(string)sitter+"&avname="+llEscapeURL(llKey2Name(sitter)), NULL_KEY);
            }
        }
    }
}//state
        