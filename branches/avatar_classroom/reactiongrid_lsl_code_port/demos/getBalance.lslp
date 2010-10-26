    /*
    *  Sloodle Backpack Vendor
    *  Copyright 2010 B3DMULTITECH.COM
    *  Paul Preibisch 
    *  fire@b3dmultitech.com
    *
    *  Released under the GNU GPL 3.0
    *  This script can be used in your scripts, but you must include this copyright header 
    *  as per the GPL Licence
    *  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
    *  
    *  
    ******************************************************************************************* 
    *  NOTECARD SETUP
    *    
    *  Just generate an HQ Notecard, and place the following underneath
    *
    *  name|necklace
    *  price|100
    *  currency|Silver Coins
    *  autopurchase|false
    *  backpack_item|necklace|1
    *  give|necklace notecard
    *  give|necklace
    *  playafterpurchase|
    *  playonclick|
    *  sloodleeof
    *      
    ******************************************************************************************* 
    *  NOTECARD DESCRIPTION
    *
    *  name = this is the item name that this prim is selling
    *  price  = Price of the item
    *  currency  = Currency that the user must have in order to purchase 
    *  autopurchase  = when autopurchase  = true, item will be automatically purchased if user has enough units of the currency specified in their sloodle  back pack
    *  backpack_item = This is a new feature - when specified, x units of the item will be added to the users sloodle backpack upon purchase
    *  give = the items to give after purchase
    *  playafterpuchase = the sound to play after a purchase is made
    *  playonclick=the sound to play when user clicks the prim
    ******************************************************************************************* 
    *  PRIM SETUP
    *  
    *  In order to operate, the prim should have the following contents:
    *      
    *  1) sloodle_config
    *  2) 0sloodle_api.lsl
    *  3) 1sloodle_api.lsl
    *  4) sloodle_setup_notecard.lsl
    *  5) vendor.lsl                               
    *  6) loadingcomplete.wav
    *  7) STARTINGUP.wav
    *  9) Till With Bell.wav
    *  10) Trobone.wav
    *  11) _rotator.lsl
    *          
    *******************************************************************************************
    *                      
    *  DESCRIPTION
    *
    *  Allows Teacher to setup a Vender
    *
    *  When Clicked by an avatar, it will check the avatars backpack balance for the [currency].
    *  If the user has enough of that currency to purchase the item, two things can happen:
    *
    *  1) If the [autopurchase] setting has been set in the notecard to TRUE, then 
    *     all of the "give" items in the notecard will be given to the avatar - if they exist in the prims contents
    *
    *  2) If [autopurchase] has been set to false, the the user will be given a dialog menu asking if they want to purchase
    *     the item for the given [price]
    *
    *  After purchase, a [playafterpurchase] sound will be played to the avatar - this is useful for role playing games where additional
    *  audible instructions may be needed, or for special effects
    *
    *  There is also a setting to play an intro sound when the item is origionally clicked on - to set this
    *  change the [playonclick] setting in the notecard
    *
    *  To use this 
    *
    */ 
      
    
    
    
    integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
    integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
    integer SLOODLE_CHANNEL_OBJECT_CHOICE = -1639270051;
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
    integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
    integer AVATAR_NOT_REGISTERED = -331;
    string SOUND_NO_MONEY="Trombone";
    string SOUND_MONEY_OK="Till_With_Bell";
    string SOUND_BACKPACK_SEND="sound bleepy computer";
    integer OK=1;
    integer NULL_VAL=-9988;
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
    integer DIALOG_CHANNEL;
    
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
    
    
    sloodle_handle_command(string str) 
    {
    
        list bits = llParseString2List(str,["|"],[]);
        integer numbits = llGetListLength(bits);    
        string name = llList2String(bits,0);
        string value = "";
        string val1="";
         val1 = llList2String(bits,1);
        string val2="";
        string val3="";
        if (numbits > 2) val2= llList2String(bits,2);  
        if (numbits > 3) val3= llList2String(bits,3);
        // Check the command
        if (name == "do:reset") {
            // Reset
            debug("Resetting configuration notecard reader");
            llResetScript();
        } else 
        if (name == "do:requestconfig") llResetScript(); else
        if (name == "name") ITEM_NAME = val1; else
        if (name == "price") {
            ITEM_PRICE = (integer)val1; 
            ITEM_DETAILS= val2;
        }
        else
        if (name == "currency") ITEM_CURRENCY= val1; else
        if (name == "autopurchase") ITEM_AUTO_PURCHASE_SETTING = val1; else
        if (name == "give") {
            llOwnerSay("Reading item: "+val1);ITEM_GIVE += val1; 
        }
        else
        if (name == "backpack_item") {
            llOwnerSay("Reading backpack_item: "+val1);
            BACKPACK_GIVE += val1;            
            if (val2=="") BACKPACK_ERROR = TRUE; else BACKPACK_GIVE_AMOUNT +=(integer)val2;           
            BACKPACK_GIVE_DETAILS += val3;  
        } else
        if (name == "playafterpurchase") SOUND_AFTER_PURCHASE= val1; else
        if (name == "playonclick") SOUND_INTRO= val1; 
        if (str==SLOODLE_EOF) state check;
         
        
    }
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
    * help(key userKey)
    * |-->sends an instant message to userKey explaining this prims function 
    ***********************************************/ 
    help(key userKey){
        string outStr="\nThis Prim sells items.\n";
        outStr+="Item Name: "+ ITEM_NAME+"\n";
        outStr+="Item Price: "+(string)ITEM_PRICE+"\n";
        outStr+="Item Currency: "+ ITEM_CURRENCY+"\n";
        if (userKey==llGetOwner()){
        outStr+="Item Auto Purchase Setting: "+ (string)ITEM_AUTO_PURCHASE_SETTING+"\n";
        }
        integer j;
        integer len = llGetListLength(ITEM_GIVE);
        outStr+="\nContents to give on purchase:\n";
        outStr+="======================================================\n";        
            for (j=0;j<len;j++){            
            outStr+=llList2String(ITEM_GIVE,j)+"\n";
            }
            //display backpack items that will be given on successful purchase
            len = llGetListLength(BACKPACK_GIVE);
            if (len>0){
                outStr+="\n\nItems to be placed in Sloodle Backpack on Purchase:"+"\n";        
                outStr+="======================================================\n";         
                for (j=0;j<len;j++){
                    outStr+=llList2String(BACKPACK_GIVE,j)+ ", amount: "+llList2String(BACKPACK_GIVE_AMOUNT,j)+" details:" +llList2String(BACKPACK_GIVE_DETAILS,j)+"\n";            
                }
            }
        llInstantMessage(userKey, outStr);
    }
    /***********************************************
    * add_Transaction(key avuuid,string currency,integer price)()
    * |-->sends a message to /plugins/awards.php to add a transaction
    ***********************************************/ 
    addTransaction(key avuuid,string currency,integer price,string details){
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
    default{
        state_entry() {
            ITEM_GIVE=[];
            llTriggerSound("STARTINGUP", 1.0);
        }
        
        link_message(integer sender_num, integer channel, string str, key id) {          
            if (channel==SLOODLE_CHANNEL_OBJECT_DIALOG){
                // Split the message into lines
                sloodle_handle_command(str);
            }
        }
        
    }
    state check{
        on_rez(integer start_param) {
            llResetScript();
        }
        state_entry() {
            llTriggerSound("loadingcomplete", 1.0);
                if (ITEM_NAME !="" && ITEM_PRICE !=NULL_VAL && BACKPACK_ERROR==FALSE && ITEM_CURRENCY !="" && ITEM_AUTO_PURCHASE_SETTING!="null"){
                    integer valid_give_items=TRUE;
                    integer valid_sounds=TRUE;            
                    valid_give_items = exists_in_inventory(ITEM_GIVE);
                    list soundCheckList;
                    if (SOUND_AFTER_PURCHASE!="")soundCheckList+=SOUND_AFTER_PURCHASE;
                    if (SOUND_INTRO!="")soundCheckList+=SOUND_INTRO;
                    if (llGetListLength(soundCheckList)>0){
                        valid_sounds = exists_in_inventory(soundCheckList); 
                    }            
                    if (valid_sounds && valid_give_items) state ready;
                }else{
                    
                    llOwnerSay("Errors present in the configuration notecard. I am expecting these values:");
                    
                    if (ITEM_NAME!="") llOwnerSay("name|"+ITEM_NAME+" (correct)"); else llOwnerSay("name|"+ITEM_NAME+" (please add an item name)");
                    if (ITEM_DETAILS=="") llOwnerSay("price|"+(string)ITEM_PRICE+"|DETAILS (incorrect)"); 
                    if (ITEM_PRICE==NULL_VAL) llOwnerSay("price| (please fix - add a price!!!)");
                    if (ITEM_CURRENCY=="") llOwnerSay("currency|"+ITEM_CURRENCY+" (please add an item currency)");
                    if (BACKPACK_ERROR==TRUE) llOwnerSay("backpack_item_give settings are incorrect - please make sure you use the format: backpack_item_give|currency name|ammount");
                    state default;
                }
                //check to see if items specified in notecard exist in inventory
                
        }
        changed(integer change) {
            if (change== CHANGED_INVENTORY) { // and it was a link change
               
             llResetScript();
            }//endif
        }
    }
    state ready{
        on_rez(integer start_param) {
            llResetScript();
        }
        state_entry()
        {
            llSay(0,"Ready");
            //define random dialog channel for dialog messags
            DIALOG_CHANNEL = random_integer(-300000,-900000);
            llListen(DIALOG_CHANNEL, "", "", "");
            //display help
            help(llGetOwner());
            //load inventory list
                  
        }
    
        touch_start(integer total_number)
        {
            string authenticatedUser = "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
            string avInfo= "&avuuid="+(string)llDetectedKey(0)+"&avname="+llEscapeURL(llKey2Name(llDetectedKey(0)));        
            llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "backpack->getBalance"+authenticatedUser+avInfo+"&currency="+llEscapeURL(ITEM_CURRENCY), NULL_KEY);
        }
        link_message(integer sender_num, integer channel, string str, key id) {
            if (channel==PLUGIN_RESPONSE_CHANNEL) {                             
                list dataLines = llParseStringKeepNulls(str,["\n"],[]);           
                //get status code
                list statusLine =llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
                integer status =llList2Integer(statusLine,0);
                string descripter = llList2String(statusLine,1);
                list sideEffects =llParseString2List(llList2String(statusLine,2), [","], []);
                string response =llList2String(statusLine,3);
                integer timeSent=llList2Integer(statusLine,4);
                integer timeRecvt=llList2Integer(statusLine,5);
                key uuidSent= llList2Key(statusLine,6);
                list cmdList = llParseString2List(str, ["|"], []);
                //get all the variables returned from the api
                list vars = llList2List(dataLines, 1, llGetListLength(dataLines)-1);
                //add variables to key / value array 
                list OUTPUT_VARS= parseVars(vars);
                //****************************************************************************************
                // GETBALANCE
                //****************************************************************************************           
                if (response=="backpack->getBalance"){
                    integer balance = (integer)getVar(OUTPUT_VARS,"BALANCE");

                 /* possible responses are:
                        *
                        *******************************************************************        
                        * Avatar Exists and is Linked
                        *         
                        * 1|OK||backpack->getBalance||1282193897|14d1bf9c-82cb-499d-8cf8-d18b001803fc
                        * RESPONSE:backpack|getBalance
                        * AVUUID:14d1bf9c-82cb-499d-8cf8-d18b001803fc
                        * CURRENCY:Silver
                        * BALANCE:40
                        *
                        *******************************************************************        
                        * Avatar Doesn't Exist
                        * 
                        * -331|USER_AUTH||backpack->getBalance||1282193937|14d1bf9c-82cb-499d-8cf8-d18b001803fc
                        * RESPONSE:backpack|getBalance
                        * AVUUID:14d1bf9c-82cb-499d-8cf8d-d18b001803fc
                        * 
                        */                
                    if (status==AVATAR_NOT_REGISTERED){
                        llSay(0,"Sorry "+llKey2Name(uuidSent)+" but it appears your avatar is not registered in the course.");
                        return;                
                    }
                    else
                    if (status==OK){
                  
                        if (ITEM_PRICE<=balance){
                              
                          ITEM_AUTO_PURCHASE_SETTING=llToLower(ITEM_AUTO_PURCHASE_SETTING);
                            if (ITEM_AUTO_PURCHASE_SETTING=="true"){
                              
                                addTransaction(uuidSent,ITEM_CURRENCY,ITEM_PRICE,ITEM_DETAILS);
                            }else
                            if (llStringTrim(ITEM_AUTO_PURCHASE_SETTING,STRING_TRIM)=="false"){
                              
                                //give dialong
                                string msg = "You have "+(string)balance+"\n\n";
                                msg += "Would you like to purchase: "+ITEM_NAME +" for "+(string)ITEM_PRICE+" "+ITEM_CURRENCY+" "+"?";
                                llDialog(uuidSent, msg, ["Yes","No"], DIALOG_CHANNEL);
                            }
                        }else {
                            llDialog(uuidSent, "I'm sorry, but you don't have enough "+ITEM_CURRENCY+ "!\n You have: "+(string)balance +" "+ITEM_CURRENCY+"\n and you need "+(string)(ITEM_PRICE-balance)+" more "+ITEM_CURRENCY, ["Ok"], DIALOG_CHANNEL);
                            llTriggerSound(SOUND_NO_MONEY, 1.0);
                            return;
                        }                
                    } //status               
                }//response
                //****************************************************************************************
                // ADDTRANSACTION
                //****************************************************************************************
                if (response=="awards->addTransaction"){
                    /* possible responses are:
                        *
                        *******************************************************************        
                        * Avatar Exists and is Linked
                        *         
                        * 1|OK||awards->addTransaction||1282207434|14d1bf9c-82cb-499d-8cf8-d18b001803fc
                        * RESPONSE:awards|addTransaction
                        * AVNAME:Fire2 Centaur
                        * AVUUID:14d1bf9c-82cb-499d-8cf8-d18b001803fc
                        * GAMEID:0
                        * CURRENCY:Silver
                        * BALANCE:190
                        *
                        ******************************************************************/
                        integer balance = (integer)getVar(OUTPUT_VARS,"BALANCE");

                        string OUTPUT_CURRENCY=getVar(OUTPUT_VARS,"CURRENCY");
                    if (status==OK){
                        //addTransaction response results after an addTransaction completes
                        //addTransaction response is initiated in one of two ways - either we are withdrawing currency
                        //due to a purchase, or we are conduting a "backpack_give" -- which is an option of this script.
                        //ie: teachers dont need to actually give real SL items upon purchase, they can instead
                        // give a "virtual virtual" item to a student - in their moodle inventory.
                        // example, say you wanted students to purchase "a necklace" with 10 seashells
                        // you could put a picture of a necklace on this prim, and then if the user had 10 seashells in their
                        // moodle backpack, and purchased the necklace, then you could just put a "necklace" item into the moodle backpack
                        // and not even give a necklace in SL. Thus, the necklace would only exist in the moodle backpack, and not in SL
                        //
                        // We would use addTransaction to give the necklace to the moodle backpack, and that would result
                        // in an addTransaction result, so - to avoid an addtransaction loop we just need to check to make sure that the output currency is not the same
                        //as this items currency - as it wouldn' make sense to subtract a currency, and then backpack_give the same currency again!
                        //therefore the backpack_give currency will always be different that our items currency, 
                        //so we can safely avoid an addTransaction loop by checking against our items currency.
                        if (OUTPUT_CURRENCY==ITEM_CURRENCY){
                            llTriggerSound(SOUND_MONEY_OK, 1.0);
                            integer len = llGetListLength(ITEM_GIVE);
                            if (len>0){
                                integer j=0;
                                for (j=0;j<len;j++){
                                    llGiveInventory(uuidSent, llList2String(ITEM_GIVE,j));
                                    llInstantMessage(uuidSent,"Sending you "+llList2String(ITEM_GIVE,j) +" Please accept the inventory item.");                                
                                }
                            }
                            len = llGetListLength(BACKPACK_GIVE);
                            if (len>0){
                                integer j=0;
                                for (j=0;j<len;j++){
                                    string msg = "Adding "+llList2String(BACKPACK_GIVE_AMOUNT,j)+" "+llList2String(BACKPACK_GIVE,j)+" to your Sloodle Backpack, please wait.... ";
                                    llInstantMessage(uuidSent,msg);       
                                    addTransaction(uuidSent,llList2String(BACKPACK_GIVE,j),llList2Integer(BACKPACK_GIVE_AMOUNT,j),llList2String(BACKPACK_GIVE_DETAILS,j));
                                   llTriggerSound(SOUND_BACKPACK_SEND, 1.0);
                                  
                                } 
                            }
                            llInstantMessage(uuidSent,"Your new balance is: "+(string)balance+" "+OUTPUT_CURRENCY);
                        }
                        else{
                            string msg = "We just added "+OUTPUT_CURRENCY+" to your Sloodle Backpack! You now have: "+(string)balance+" "+"!";
                             llDialog(uuidSent, msg, ["Thanks!"], -99);
                            llInstantMessage(uuidSent,msg);        
                        }
                    
                    }   
                }
            }
        }
            listen(integer channel, string name, key id, string str) {
                if (channel==DIALOG_CHANNEL){
                    if (str=="Yes"){
                        addTransaction(id,ITEM_CURRENCY,ITEM_PRICE*-1,ITEM_DETAILS);
                    }
                
                }
            
            }
            changed(integer change) {
            if (change== CHANGED_INVENTORY) { // and it was a link change
               
             llResetScript();
            }//endif
        }
   }