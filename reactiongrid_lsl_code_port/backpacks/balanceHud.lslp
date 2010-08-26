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
    string  sloodleserverroot = "http://christopher_flow.avatarclassroom.com"; 
    string  sloodlepwd = "157037155"; //password of the controller who's activites we wish access to
    integer sloodlecontrollerid = 9;//id of the controller
    integer sloodlemoduleid = 0;//course module id 
    integer sloodleid;//module id
    integer sloodleobjectaccessleveluse = 0; // Who can use this object?
    integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?
    integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)
    string sloodleCourseName;
    integer coursemoduleid;
    string sloodlecoursename_short="Demo";
    string sloodlecoursename_full="Sloodle Demo Course";
    //**********************************************************************************************//
    key http;
    string  SLOODLE_HQ_LINKER = "/mod/sloodle/mod/hq-1.0/linker.php";
    sendCommand(string str,string httpid){
        integer varStartIndex =llSubStringIndex(str,"&");
        //parse the plugin and function out    
        string cmdStr = llGetSubString(str, 0, varStartIndex-1);
        list cmdLine = llParseString2List(cmdStr,["->"],[]); //plugin:groups,function:checkEnrols
        //the plugin var determines what .php plugin file our function is located in ie: www.yoursite.com/moodle/mod/sloodle/plugins/general.php      
        string plugin= llList2String(cmdLine,0);
        //function is the name of the function in the file    
        string function = llList2String(cmdLine,1);
        //extra variables are all the variables passed in that are to be placed in the url request     
        string vars = llGetSubString(str,varStartIndex+1,llStringLength(str)-1);
        
        //add important sloodle variables that are required to establish a connection into SLOODLE
        string requiredVars  = "&sloodlecontrollerid=" + (string)sloodlecontrollerid;    
        requiredVars+= "&sloodlepwd=" + (string)sloodlepwd;
        requiredVars += "&sloodleserveraccesslevel=" + (string)sloodleserveraccesslevel;
        requiredVars+="&time_sent="+(string)llGetUnixTime();       
        //set timer to detect timeouts
        llSetTimerEvent(20);
        //send the request
        key temp;
        
        httpid=(key)"http";
        http = llHTTPRequest(sloodleserverroot + SLOODLE_HQ_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"],  sloodleserverroot + SLOODLE_HQ_LINKER+"?"+"&plugin="+plugin+ "&function="+function+requiredVars+"&"+vars);
        temp = http;    
         
                  //debug
        debug("******************************************************");   
        debug("********** "+llGetScriptName()+" SENDING TO SERVER "+plugin+"->"+function+" on "+(string)httpid+" id is: "+(string)temp+" *********************");                  
        debug(sloodleserverroot + SLOODLE_HQ_LINKER+"?"+"&plugin="+plugin+ "&function="+function+requiredVars+"&"+vars);
                
}//sendCommand  
    
    
    integer courseid; 
    list USER_DIALOG;
    integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
    integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
    integer SLOODLE_CHANNEL_OBJECT_CHOICE = -1639270051;
    integer USER_NOT_ENROLLED = -321;
    string  SLOODLE_EOF = "sloodleeof";
    vector  RED            = <0.77278,0.04391,0.00000>;//RED
    vector  ORANGE = <0.87130,0.41303,0.00000>;//orange
    vector  YELLOW         = <0.82192,0.86066,0.00000>;//YELLOW
    vector  GREEN         = <0.12616,0.77712,0.00000>;//GREEN
    vector  BLUE        = <0.00000,0.05804,0.98688>;//BLUE
    vector  PINK         = <0.83635,0.00000,0.88019>;//INDIGO
    vector  PURPLE = <0.39257,0.00000,0.71612>;//PURPLE
    vector  WHITE        = <1.000,1.000,1.000>;//WHITE
    vector  BLACK        = <0.000,0.000,0.000>;//BLACK
    string  hoverText="";
    integer counter=0;
    integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
    integer SOUND_ON=TRUE;
    integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
    integer USE_DID_NOT_HAVE_PERMISSION_TO_ACCESS_RESOURCE_REQUESTED = -331;
    integer AVATAR_NOT_ENROLLED= -321;
    string SOUND_NO_MONEY="Trombone";
    string SOUND_MONEY_OK="Till_With_Bell";
    string SOUND_BACKPACK_SEND="sound bleepy computer";
    string SOUND_TIMER="sound bleepy computer";
    integer OK=1;
    integer SET_TEXT=TRUE;
    integer NULL_VAL=-9988;
    list TAKERS;
    string     ITEM_NAME="";
    integer ITEM_PRICE=-9988;
    string ITEM_DETAILS;
    string     ITEM_CURRENCY="";
    string  ITEM_AUTO_PURCHASE_SETTING="null";
    list     ITEM_GIVE;
    string     SOUND_AFTER_PURCHASE="";
    string     SOUND_INTRO="";
    integer BACKPACK_ERROR = FALSE;
    list     BACKPACK_GIVE;
    list     BACKPACK_GIVE_AMOUNT;
    list BACKPACK_GIVE_DETAILS;
    list INVENTORY;
    integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
    integer DIALOG_CHANNEL;
    integer MAX_WITHDRAWS=-1;
    integer ITEM_FREQUENCY_TAKE=3000;
    string ITEM_UNIQUE;
    integer ITEM_FREQUENCY_ADD=-1;
    integer ITEM_FREQUENCY_RESET=-1;
    list USERS;
    integer NUM_WITHDRAWS=0;
    integer debugCheck(){
        if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==PRIM_MATERIAL_FLESH){
            return TRUE;
        }
            else return FALSE;
        
    }
    debug(string str){
        if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==PRIM_MATERIAL_FLESH){
            llOwnerSay(str);
        }
    }
    displayText(integer nnn){
        llSetText(ITEM_NAME+"\n("+(string)nnn+")  withdraws are left to be taken.\n Cost is: "+(string)ITEM_PRICE+" "+ITEM_CURRENCY, GREEN, 1.0);
    }
    getBalance(key user){
        if (SET_TEXT==TRUE) llSetText("Getting balance for user: "+llKey2Name(user)+", please wait...", YELLOW, 1.0);
        string authenticatedUser = "&sloodleuuid="+(string)user+"&sloodleavname="+llEscapeURL(llKey2Name(user));
         string avInfo= "&avuuid="+(string)user+"&avname="+llEscapeURL(llKey2Name(user));        
        llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "backpack->getBalance"+authenticatedUser+avInfo+"&currency="+llEscapeURL(ITEM_CURRENCY), NULL_KEY);
    }
    //gets a vector from a string
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
    
    
   
    /***********************************************
    *  random_integer()
    *  |-->Produces a random integer
    ***********************************************/ 
    integer random_integer( integer min, integer max ){
      return min + (integer)( llFrand( max - min + 1 ) );
    }
    /***********************************************
    *  getInventoryList()
    *  |-->returns a list of all inventory items
    ***********************************************/ 
    
    list getInventoryList()
    {
        list       result = [];
        integer    n = llGetInventoryNumber(INVENTORY_ALL);
     
        while(n)
            result = llGetInventoryName(INVENTORY_ALL, --n) + result;
     
        return result;
    }
    /***********************************************
    * exists_in_inventory()
    * |-->checks to see if the give items specified in 
    * the notecard actually exist in the prims inventory
    ***********************************************/ 
    integer exists_in_inventory(list items){
                list inv = getInventoryList();
                integer len = llGetListLength(items);
                integer valid_inventory = TRUE;
                integer j;
                if (len>0){
                    for (j=0; j < len; j++) {
                        if (llStringTrim(llList2String(items,j),STRING_TRIM)!=""){
                            integer found = llListFindList(inv, [llList2String(items,j)]);
                            if (found ==-1) {
                                valid_inventory = FALSE;
                                llOwnerSay("Error. Item: "+llList2String(items,j)+" was not found in this prim's inventory");                        
                            }
                        }
                    }
                }
                return valid_inventory;
    }
   
    /***********************************************
    * add_Transaction(key avuuid,string currency,integer price)()
    * |-->sends a message to /plugins/awards.php to add a transaction
    ***********************************************/ 
    addTransaction(key avuuid,string currency,integer price,string details){
            if (SET_TEXT==TRUE) llSetText("Processing transaction for: "+llKey2Name(avuuid)+", please wait", YELLOW, 1.0);
            if (SOUND_ON==TRUE)llTriggerSound("sound bleepy computer" , 1);
            string avname=llEscapeURL(llKey2Name(avuuid));
            string authenticatedUser= "&sloodleuuid="+(string)avuuid+"&sloodleavname="+avname;
            llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "awards->addTransaction"+authenticatedUser+"&avname="+avname+"&avuuid="+(string)avuuid+"&currency="+llEscapeURL(currency)+"&amount="+(string)price+"&details="+llEscapeURL(details), NULL_KEY);
    }
    /*****************************************************************************************************
    * parseVars(list vars)
    * is intended to be used with values returned from a API RESPONSE
    * All variables returned from an API response will be returned in a list in key value pairs 
    * example:
    * Vars[0] = AVUUID; Vars[1]= 14d1bf9c-82cb-499d-8cf8-d18b001803fc
    * Vars[2] = GAMEID; Vars[3]= 0
    * Vars[4] = CURRENCY; Vars[5]= Silver
    *
    * etc
    * This will enable us to easily retrieve returned variables via get(Vars,"GAMEID") function
    *****************************************************************************************************/
    list parseVars(list vars){
          
        list Vars;
        integer len= llGetListLength(vars);
        integer j=0;
        list bits;
        for (j=0;j<len;j++){
            bits = llParseString2List(llList2String(vars,j), [":"], []);
            Vars+= llList2String(bits,0);
            Vars+= llList2String(bits,1);
        }
        return Vars; 
        
    }
    /*****************************************************************************************************
    * getVar(list vars,string keyName)
    * can be used to retreive variables output by the api plugin response
    *
    * example:
    * GAMEID = (integer)getVar(API_OUTPUT,"GAMEID");
    * This will enable us to easily retrieve returned variables from our api
    *****************************************************************************************************/
    string getVar(list vars,string keyName){    
        integer found = llListFindList(vars, [keyName]);  
        if (found!=-1)
        return llList2String(vars,found+1);
        else return "null";
        
    }
   

default {
    state_entry() {

    }
    touch_start(integer num_detected) {
    key owner = llGetOwner();
        string avname = llKey2Name(llGetOwner());
        string authenticatedUser= "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(avname)+"&sloodlemoduleid=5&avuuid="+(string)llGetOwner()+"&avname="+llEscapeURL(avname);          
        sendCommand("backpack->getAllBalances"+authenticatedUser+"&sloodlemoduleid="+(string)5+"&index="+(string)0+"&sortmode=balance&gameid=1000", NULL_KEY);
    }
    http_response(key request_id, integer status, list metadata, string body) {
    	llSay(0,body);
    }
}
