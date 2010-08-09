//_quizchair_datahandler
        
        key null_key = NULL_KEY;
        integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
        integer SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST=-1828374651;
        integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
        integer doRepeat = 0; // whether we should run through the questions again when we're done
        integer doDialog = 1; // whether we should ask the questions using dialog rather than chat
        integer doPlaySound = 1; // whether we should play sound
        integer doRandomize = 1; // whether we should ask the questions in random order
        key owner;
        string sloodleserverroot = "";
        integer sloodlecontrollerid = 0;
        string sloodlepwd = "";
        integer sloodlemoduleid = 0;
        integer sloodleobjectaccessleveluse = 0; // Who can use this object?
        integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?
        integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)
        integer points=10;
        integer isconfigured = FALSE; // Do we have all the configuration data we need?
        integer eof = FALSE; // Have we reached the end of the configuration data?
        integer UI_CHANNEL                                                            =89997;//UI Channel - channel used to trigger awards_notecard reading
        integer scoreboardchannel=-1;
        integer gameid=-1;
        
        string myQuizName;
        integer   myQuizId;
        integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;

        integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;
        integer SLOODLE_AWARD_CHANNEL = -3866343;
        integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
        integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
        integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;
        integer currentAwardId=-1;
        string SLOODLE_OBJECT_TYPE = "quiz-1.0";
        string SLOODLE_EOF = "sloodleeof";
        string authenticatedUser;
        string sloodle_quiz_url = "/mod/sloodle/mod/quiz-1.0/linker.php";
        
vector     RED            = <0.77278,0.04391,0.00000>;//RED
vector     ORANGE = <0.87130,0.41303,0.00000>;//orange
vector     YELLOW         = <0.82192,0.86066,0.00000>;//YELLOW
vector     GREEN         = <0.12616,0.77712,0.00000>;//GREEN
vector     BLUE        = <0.00000,0.05804,0.98688>;//BLUE
vector     PINK         = <0.83635,0.00000,0.88019>;//INDIGO
vector     PURPLE = <0.39257,0.00000,0.71612>;//PURPLE
vector     WHITE        = <1.000,1.000,1.000>;//WHITE
vector     BLACK        = <0.000,0.000,0.000>;//BLACKvector     ORANGE = <0.87130, 0.41303, 0.00000>;//orange
list colors = [RED, ORANGE,YELLOW,BLUE,PINK,PURPLE];
        integer debugCheck(){
    if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
        return TRUE;
    }
        else return FALSE;
    
}
debug(string str){
    if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
        llOwnerSay(llGetScriptName()+" "+str);
    }
}
list groups;        
        ///// FUNCTIONS /////
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

        /******************************************************************************************************************************
        * sloodle_error_code - 
        * Author: Paul Preibisch
        * Description - This function sends a linked message on the SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST channel
        * The error_messages script hears this, translates the status code and sends an instant message to the avuuid
        * Params: method - SLOODLE_TRANSLATE_SAY, SLOODLE_TRANSLATE_IM etc
        * Params:  avuuid - this is the avatar UUID to that an instant message with the translated error code will be sent to
        * Params: status code - the status code of the error as on our wiki: http://slisweb.sjsu.edu/sl/index.php/Sloodle_status_codes
        *******************************************************************************************************************************/
        sloodle_error_code(string method, key avuuid,integer statuscode){
                    llMessageLinked(LINK_SET, SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST, method+"|"+(string)avuuid+"|"+(string)statuscode, NULL_KEY);
        }       
         sloodle_debug(string msg)
        {
            llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, null_key);
        }        

   
         integer sloodle_handle_command(string str) {
         	if (str=="do:requestconfig"||str=="do:reset")llResetScript();
                list bits = llParseString2List(str,["|"],[]);
               string name=  llList2String(bits,0);
               string value1 = llList2String(bits,1);
                if (name == "set:scoreboardchannel") {
                    scoreboardchannel= (integer)value1;
                   
                    return TRUE;
                }
               
               return FALSE;
            }
        // Checks if the given agent is permitted to user this object
        // Returns TRUE if so, or FALSE if not
        integer sloodle_check_access_use(key id)
        {
            // Check the access mode
            if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP) {
                return llSameGroup(id);
            } else if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC) {
                return TRUE;
            }
            
            // Assume it's owner mode
            return (id == llGetOwner());
        }
        
        
        
        ///// TRANSLATION /////
        
        // Link message channels
        integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
        
        // Translation output methods
        string SLOODLE_TRANSLATE_WHISPER = "whisper";               // 1 output parameter: chat channel number
        string SLOODLE_TRANSLATE_SAY = "say";               // 1 output parameter: chat channel number
        string SLOODLE_TRANSLATE_OWNER_SAY = "ownersay";    // No output parameters
        string SLOODLE_TRANSLATE_DIALOG = "dialog";         // Recipient avatar should be identified in link message keyval. At least 2 output parameters: first the channel number for the dialog, and then 1 to 12 button label strings.
        string SLOODLE_TRANSLATE_LOAD_URL = "loadurl";      // Recipient avatar should be identified in link message keyval. 1 output parameter giving URL to load.
        string SLOODLE_TRANSLATE_IM = "instantmessage";     // Recipient avatar should be identified in link message keyval. No output parameters.
        
        // Send a translation request link message
        
        sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
        {
            
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
        }
        
        ///// ----------- /////
        
        
        ///// STATES /////
        
        // Waiting on initialisation
        default
        {
                on_rez(integer start_param) {
        llResetScript();
    }
            state_entry()
            {
                
                owner = llGetOwner();
                // Starting again with a new configuration
                
                isconfigured = FALSE;
                eof = FALSE;
                // Reset our data
                sloodleserverroot = "";
                sloodlepwd = "";
                sloodlecontrollerid = 0;
                sloodlemoduleid = 0;
                sloodleobjectaccessleveluse = 0;
                sloodleserveraccesslevel = 0;
     
            }
            
            link_message( integer sender_num, integer channel, string str, key id)
            {
                // Check the channel
                if (channel == SLOODLE_CHANNEL_OBJECT_DIALOG) {
                    // Split the message into lines
                    if (sloodle_handle_command(str)==TRUE) {

                        state ready;
                    }

                }//object channel
            }//linked
            changed(integer change) {
     if (change ==CHANGED_INVENTORY){         
         llResetScript();
     }//end if
    }//end changed
        }

        
        // Ready state - waiting for a user to climb aboard!
        state ready
        {
                on_rez(integer start_param) {
        llResetScript();
    }
            state_entry()
            {
                
                // This is now handled by a separate poseball
                // llSitTarget(<0,0,.5>, ZERO_ROTATION);
               llListen(scoreboardchannel, "", "", "");
                authenticatedUser= "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
            }
            
          listen(integer channel, string name, key id, string str) {
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
          }
          link_message( integer sender_num, integer chan, string str, key id)
            {
                 if (chan == SLOODLE_CHANNEL_OBJECT_DIALOG) {
                     sloodle_handle_command(str);
                 }else
                // Check the channel
                if (chan == UI_CHANNEL) {
                    // Split the message into lines
                            list cmdList = llParseString2List(str, ["|"], []);        
                            string cmd = s(llList2String(cmdList,0));
                            //this command is send in response to the linked message above: "cmd:getgameid"
                            //the game id is received from gameid.lsl script which listens to the scoreboard channel for newgame messages from the scoreboard when a new game is created
                            if (cmd=="GET GAMEID") {
                                if (gameid==-1){
                                    llRegionSay(scoreboardchannel, "CMD:REQUEST GAME ID|UUID:"+(string)llGetKey());
                                    debug("request game id: on "+(string)scoreboardchannel+"   CMD:REQUEST GAME ID|UUID:"+(string)llGetKey());
                                }
                                else {
                                    //send the game id and groups to _getGroup script.
                                    groups = llListSort(groups, 1, TRUE);
                                     llMessageLinked(LINK_SET, UI_CHANNEL, "CMD:GAMEID|ID:"+(string)gameid+"|groups:"+llList2CSV(groups)+"|myQuizName:"+myQuizName+"|QUIZID:"+(string)myQuizId, llGetScriptName());
                                    //llMessageLinked(LINK_SET, UI_CHANNEL, "CMD:GAMEID|ID:"+(string)gameid, llGetScriptName()+"|groups:"+llList2CSV(groups));

                                }
                            }//if
                         
                }//chan
        }//linked
        changed(integer change) {
     if (change ==CHANGED_INVENTORY){         
         llResetScript();
     }//end if
     if (change==CHANGED_LINK){
     	if (llAvatarOnSitTarget()==NULL_KEY) llResetScript();
     }
    }//end changed
    }//state