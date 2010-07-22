/*********************************************
*  Copyrght (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
*  This script is part of the SLOODLE Project see http://sloodle.org
*
* response_handlers2.lsl
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
*
*/ 
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer ROW_CHANNEL;
string stringToPrint;
list lines;
integer numStudents;
integer totalPages;
integer index;
integer scoreboardchannel=-1;
integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
integer gameid=-1;
string myQuizName;
integer   myQuizId;
list groups;
        
integer index_teamScores;
integer index_getClassList;
integer index_selectTeams;
integer DISPLAY_DATA                                                        =-774477; //every time the display is updated, data goes on this channel
integer WEB_UPDATE_CHANNEL                                        =-64000; // data we receive from an http request from httpIn handler
integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
integer SETTEXT_CHANNEL                                                =-776644;//hover text channel
integer SOUND_CHANNEL                                                     = -34000;//sound requests
integer DISPLAY_PAGE_NUMBER_STRING                            = 304000;//page number xy_text
integer XY_TITLE_CHANNEL                                                  = 600100;//title xy_text
integer XY_TEXT_CHANNEL                                                = 100100;//display xy_channel
integer XY_DETAILS_CHANNEL                                          = 700100;//instructional xy_text
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST     = -1928374651;//translation channel
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE     = -1928374652;//translation channel

integer PRIM_PROPERTIES_CHANNEL                                =-870870;//setting highlights
integer SET_COLOR_INDIVIDUAL                                       = 8888999;//row text color channel                                                                    
integer AWARD_DATA_CHANNEL                                        =890;
integer ANIM_CHANNEL                                                      =-77664251;//animation trigger channel
integer PAGE_SIZE=10; //can display only 10 users at once.
integer SET_ROW_COLOR= 8888999;
integer PLAYERNAME=0; //constant which defines a list postion our specific data is in to make code more readable
integer PLAYERPOINTS=1; //constant which defines a list postion our specific data is in to make code more readable
integer SLOTCHANNEL=2; //constant which defines a list postion our specific data is in to make code more readable
integer AVUUID=3; //constant which defines a list postion our specific data is in to make code more readable
integer MAX_XY_LETTER_SPACE=30;
list rows;
list wNames;
string authenticatedUser;
integer counter;
string senderUuid;
string statusLine;
string connected;
vector ORANGE=<1.08262, 0.66319, 0.00000>;
vector BLACK=<0.00000, 0.00000, 0.00000>;
list awardGroups;
list courseGroups;
integer currentAwardId;
string current_grp_membership_group;//to keep track of which group we selected in group membership displaymode
integer current_grp_mbr_index; //keep track of which of group members we are viewing
list dataLines;
integer numGroups;
key owner;
string currentView;
list rows_teamScores;
list rows_getAwardGrps;
list rows_getAwardGrpMbrs;
list rows_selectTeams;
list rows_selectAward;
list rows_getClassList;
integer previousAwardId;
integer selectedAwardId=0;
integer currentIndex;
string currentGroup;
string sortMode="balance";
list pointMods=["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"]; //this is a list of values an owner can modify a users points to //["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000",
list modifyPointList; //this is a temp list that is used to store point modifications in.  When Save is pressed on a menu, these points are applied to the users point bank
integer modPoints;
string myUrl;
string displayData;
list facilitators;
integer SCOREBOARD_CHANNEL=-1;
// Translation channel that we send translation requests on
// *************************************************** TRANSLATION OUTPUT METHODS
string SLOODLE_TRANSLATE_HOVER_TEXT_BASIC = "hovertextbasic";
string  SLOODLE_TRANSLATE_LINK = "link";             // No output parameters - simply returns the translation on SLOODLE_TRANSLATION_RESPONSE link message channel
string  SLOODLE_TRANSLATE_SAY = "say";               // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_WHISPER = "whisper";       // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_SHOUT = "shout";           // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_REGION_SAY = "regionsay";  // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_OWNER_SAY = "ownersay";    // No output parameters
string  SLOODLE_TRANSLATE_DIALOG = "dialog";         // Recipient avatar should be identified in link message keyval. At least 2 output parameters: first the channel number for the dialog, and then 1 to 12 button label strings.
string  SLOODLE_TRANSLATE_LOAD_URL = "loadurl";      // Recipient avatar should be identified in link message keyval. 1 output parameter giving URL to load.
string  SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";  // 2 output parameters: colour <r,g,b>, and alpha value
string  SLOODLE_TRANSLATE_IM = "instantmessage";     // Recipient avatar should be identified in link message keyval. No output parameters.
string  SLOODLE_EOF = "sloodleeof";//end of file, should be the end of a sloodle_config file
// *************************************************** HOVER TEXT COLORS
vector     RED            = <0.77278, 0.04391, 0.00000>;//RED
vector     YELLOW         = <0.82192, 0.86066, 0.00000>;//YELLOW
vector     GREEN         = <0.12616, 0.77712, 0.00000>;//GREEN
vector     PINK         = <0.83635, 0.00000, 0.88019>;//INDIGO
vector     WHITE        = <1.000, 1.000, 1.000>;//WHITE
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
integer DEBUG=TRUE;
debug(string s){
 if (DEBUG==TRUE) llOwnerSay((string)llGetFreeMemory()+" "+llGetScriptName()+"*** "+ s);
   s="";
}

/***********************************************
*  isFacilitator()
*  |-->is this person's name in the access notecard
***********************************************/
integer isFacilitator(string avName){
    if (llListFindList(facilitators, [llStringTrim(llToLower(avName),STRING_TRIM)])==-1) return FALSE; else return TRUE;
}

/***********************************************
*  clearHighlights -- makes sure all highlight rows are set to 0 alpha
***********************************************/
clearHighlights(){
    integer c;
    for (c=0;c<=9;c++){
        llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:"+(string)(c)+"|POWER:OFF|COLOR:GREEN",NULL_KEY);
    } 
}
/****************************************************************************************************
* center(string str) displays text on the title bar 
****************************************************************************************************/
center(string str){
    integer len = llStringLength(str);
    string spaces="                    ";
    integer numSpacesForMargin= (20-len)/2;
    string margin = llGetSubString(spaces, 0, numSpacesForMargin);
    string stringToPrint = margin+str+margin;    
    llMessageLinked(LINK_SET, XY_TITLE_CHANNEL,stringToPrint,NULL_KEY);
}

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer( integer min, integer max ){
  return min + (integer)( llFrand( max - min + 1 ) );
}
/***********************************************
*  displayModMenu(string userName,integer userPoints, integer row_channel)
*  Is used to display a dialog menu so owner can modify the points awarded 
***********************************************/
displayModMenu(string name,string userPoints, string row_channel,key avKey){
                     integer points=i(userPoints);       
                     integer channel = i(row_channel);     
                     string userName   = s(name);       
                     integer rowNum =  channel-ROW_CHANNEL;
                     key av_key= k(avKey);
                     //llSay(0,"++++++++++++++ points"+(string)points+" channel "+(string)channel+" username: "+userName+" rowNum: "+(string)rowNum+ "avKey: "+(string)av_key);
                     modPoints = llList2Integer(modifyPointList,rowNum);
                     if (modPoints <0) modPoints=0;   
                     if (isFacilitator(llKey2Name(k(avKey)))==FALSE) return;                                                      
                     llDialog(k(avKey)," -~~~ Award Points: "+" ~~~-\n"+userName+"\nPoints: "+(string)modPoints, ["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"], channel);
}
/***********************************************
*  makeTransaction(string userName,integer userPoints, integer row_channel)
*  makes a transaction for the user to the current award
******************************************************/

makeTransaction(string avname,key avuuid,integer points){    
            //plugin:awards refers to awards.php in sloodle/mod/hq-1.0/plugins/awards.php              
            //send the plugin function request via the plugin_channel
            authenticatedUser= "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
            llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "awards->addTransaction"+authenticatedUser+"&sloodlemoduleid="+(string)currentAwardId+"&sourceuuid="+(string)llGetOwner()+"&avuuid="+(string)avuuid+"&avname="+llEscapeURL(llKey2Name(avuuid))+"&amount="+(string)points+"&currency=Credits&details="+llEscapeURL("Game BUZZER Points,"+llKey2Name(avuuid) ), NULL_KEY);
     
}

string sloodleserverroot;
integer sloodlecontrollerid;
string sloodlecoursename_short;
string sloodlecoursename_full;
integer sloodleid;
string scoreboardname;

 integer sloodle_handle_command(string str) {
     debug(str);         
        list bits = llParseString2List(str,["|"],[]);
        integer numbits = llGetListLength(bits);
        string name = llList2String(bits,0);
        string value1 = "";
        string value2 = "";
        if (numbits > 1) value1 = llList2String(bits,1);
        if (numbits > 2) value2 = llList2String(bits,2);
        if (name == "set:scoreboardchannel") {
                    scoreboardchannel= (integer)value1;
                  
                  
        }else
        if (name == "facilitator")facilitators+=llStringTrim(llToLower(value1),STRING_TRIM);else
        if (name =="set:sloodleserverroot") sloodleserverroot= value1; else
        if (name =="set:sloodlecontrollerid") sloodlecontrollerid= (integer)value1; else 
        if (name =="set:sloodlecoursename_short") sloodlecoursename_short= value1; else
        if (name =="set:sloodlecoursename_full") sloodlecoursename_full= value1; else
        if (name =="set:sloodleid") {
            sloodleid= (integer)value1; 
            currentAwardId=sloodleid; 
            scoreboardname= value2; 
        }else
        if (name == SLOODLE_EOF) return TRUE;
         return FALSE;
    }
    sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
        {
            
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
        }
default{
	state_entry() {
		
	}
	 link_message(integer sender_num, integer channel, string str, key id) {      

             if (channel==SLOODLE_CHANNEL_OBJECT_DIALOG){
                if (sloodle_handle_command(str)==TRUE) state ready;
            }//endif SLOODLE_CHANNEL_OBJECT_DIALOG
	 }
}        
state ready{    
    state_entry() {
    	 sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configurationreceived", [], NULL_KEY, "buzzer");
    	    llListen(scoreboardchannel, "", "", "");
    	    debug("listening to: "+(string)scoreboardchannel);
    	 llRegionSay(scoreboardchannel, "CMD:REQUEST GAME ID|UUID:"+(string)llGetKey());
        wNames=["none","none","none"];
         owner=llGetOwner();
         //llMessageLinked(LINK_SET, UI_CHANNEL, "CMD:REGISTER VIEW|INDEX:0|TOTALITEMS:0|COMMAND:cmd{index}|CHAN:channel",NULL_KEY);
         //create a random ROW_CHANNEL index within a range of 5,000,000 numbers - to avoid conflicts with other scoreboards
          //this user channel will accept messages from the owner when the owner clicks on a scoreboard row        
          ROW_CHANNEL=random_integer(-2483000,-3483000);
          integer c=0;
         //listen to all userchannels so we can detect scoreboard row clicks          
         for (c=0;c<3;c++){
            llListen(ROW_CHANNEL+c, "", "", "");  
         }//endfor
         //initialize tempory storage for row calculations          
         modifyPointList=[0,0,0];      
         //add the owner to the facilitators list
         ////llStringTrim(llToLower(avName),STRING_TRIM)] 
          facilitators+=llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM);
           
          
            authenticatedUser= "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));      
    }
    
    link_message(integer sender_num, integer channel, string str, key id) {      

            
            /*********************************
            * Handle the UI_CHANNEL 
            *********************************/
             if (channel==UI_CHANNEL){
                 
                 list dataBits = llParseString2List(str,["|"],[]);
                 string command = s(llList2String(dataBits,0));
                 
                 
                if (command=="RowEntry"){
                    integer r = i(llList2String(dataBits,2));
                    //llMessageLinked(LINK_SET, UI_CHANNEL, "cmd:RowEntry|username:"+userName+"|row:1", userKey);
                    wNames = llListReplaceList(wNames, [id], r, r);
                    
                }else
                if (command=="reset points") {
                    modifyPointList=[0,0,0];
                    wNames=["none","none","none"];
                }else
                 /***********************************************************************************
                 * Capture DISPLAY MENU - scoreboard row clicks
                 ***********************************************************************************/                              
                 if (command=="DISPLAY MENU"){                
                     //  llMessageLinked(LINK_SET,GUI_CHANNEL, "COMMAND:DISPLAY MENU|ROW:"+ (string)myRow+"|AVUUID:"+(string)llDetectedKey(0),NULL_KEY);     
                    //since the XY Display board can be used to display different lists besides the user list, we must first check which displayMode is current.                         
                    integer rowNum =i(llList2String(dataBits,1));                 
                    key av= k(llList2String(dataBits,2));
                    //make sure it was the owner who clicked on the row
                    if (isFacilitator(llKey2Name(av))){
                        
                             displayModMenu("NAME:"+llKey2Name(llList2String(wNames,rowNum)),"POINTS:0","CHANNEL:"+(string)(ROW_CHANNEL+rowNum),"AVKEY:"+(string)av);
                                     
                   }//facilitator

                 }//end command==DISPLAY MENU
             }//end UI_CHANNEL
             else 
            /*****************************************************************
            * Handle the PLUGIN_RESPONSE_CHANNEL     
            *****************************************************************/           
            if (channel==PLUGIN_RESPONSE_CHANNEL){
                dataLines = llParseStringKeepNulls(str,["\n"],[]);           
                //get status code
                list statusLine =llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
                integer status =llList2Integer(statusLine,0);
                string descripter = llList2String(statusLine,1);
                key authUserUuid = llList2Key(statusLine,6);
                string response = s(llList2String(dataLines,1));
                index = i(llList2String(dataLines,2));                 
                integer totalGroups= i(llList2String(dataLines,3));
                string data = llList2String(dataLines,4);
                
                authenticatedUser= "&sloodleuuid="+(string)authUserUuid+"&sloodleavname="+llEscapeURL(llKey2Name(authUserUuid));                             
               /*********************************
               * Handle the awards|makeTransaction response 
               *********************************/
               if (response=="awards|addTransaction"){
                    //get avUuid of avatar to make transaction for
                    key avUuid =   k(llList2String(dataLines,3));
                    //get avName
                    string avName = s(llList2String(dataLines,2));
                    //get points
                    integer points = i(llList2String(dataLines,6));
                    //search our rows for user which was updated
                    integer rowNum = llListFindList(rows_getClassList, [avName])/4;
                    //get the rowChannel of the user        
                    integer rowChannel = ROW_CHANNEL+rowNum;
                    modifyPointList=[0,0,0];
                                                                       
         }//awards|makeTransaction response
        }//channel!=PLUGIN_RESPONSE_CHANNEL
    }//linked message event
     listen(integer channel, string name, key id, string str) {   
     	debug(str);
         if (channel==scoreboardchannel){
                  list cmdList = llParseString2List(str, ["|"], []);        
                  string cmd = s(llList2String(cmdList,0));    
                  //new game message comes from scoreboard_public_data
                if (cmd=="NEW GAME"){
                      gameid = i(llList2String(cmdList,1));
                      groups = llParseString2List(s(llList2String(cmdList,2)), [","], []);
                      myQuizName =s(llList2String(cmdList,3));
                     myQuizId =i(llList2String(cmdList,4));
                      groups = llListSort(groups, 1, TRUE);
                      
                      llMessageLinked(LINK_SET, UI_CHANNEL, "CMD:GAMEID|ID:"+(string)gameid+"|groups:"+llList2CSV(groups)+"|myQuizName:"+myQuizName+"|QUIZID:"+(string)myQuizId, llGetScriptName());
                }else
                //new game message comes from scoreboard_public_data
                if (cmd=="SCOREBOARD SENDING GAME ID"){
debug("********************************"+str);
                    if (k(llList2String(cmdList,2))==llGetKey()){
                        groups = llParseString2List(s(llList2String(cmdList,3)), [","], []);
                         myQuizName =s(llList2String(cmdList,4));
                         myQuizId =i(llList2String(cmdList,5));
                         groups = llListSort(groups, 1, TRUE);
                         gameid = i(llList2String(cmdList,1));
                         debug("+++++++++++++++++++++++++++"+str);
                            llSetText("Game Id: "+(string)gameid+"\nQuiz id: "+(string)myQuizId+"\nQuiz Name: "+myQuizName, YELLOW, 1.0);
                            //send game id and groups to _getGroup script.
                          llMessageLinked(LINK_SET, UI_CHANNEL, "CMD:GAMEID|ID:"+(string)gameid+"|groups:"+llList2CSV(groups)+"|myQuizName:"+myQuizName+"|QUIZID:"+(string)myQuizId, llGetScriptName());
                    }
                }
                
              }
               if ((channel >=ROW_CHANNEL) && (channel <= ROW_CHANNEL+3)){  
               if (isFacilitator(llKey2Name(id))==FALSE) return;
                    integer rowNum = channel - ROW_CHANNEL;
                    //now using this rowNum, we can reach into our rows list, and retrieve user specific data
                            
                    //Now determine if a number was pressed, or the (~~ SAVE ~~) Button was pressed
                    if (llListFindList(["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"],[str])!=-1){                            
                        //now just modify the value.            
                        //modify points
                               
                        key avKey = llList2String(wNames,rowNum);
                         makeTransaction(llKey2Name(avKey),avKey,(integer)str);     

                    }//findlist             
                            
                    
                   
              }// ((channel >=ROW_CHANNEL) && (channel <= ROW_CHANNEL+3))
    }
}//default state
