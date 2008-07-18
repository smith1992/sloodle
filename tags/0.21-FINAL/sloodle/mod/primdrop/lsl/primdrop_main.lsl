// SLOODLE PRIMDROP - .32
// Sloodle - Copyright (C) 2007 Jeremy Kemp, Daniel Livingstone, Edmund Edgar, et al
// Whitepaper: http://www.sloodle.org/whitepaper.pdf

////////////
//FUNCTION
// Accepts objects and reports their properties to a web script. Rezzes objects based on their
// name. Prototype for 3D Object assignment tool.
//REQUIRES Primdrop.php and added database table
//LICENSE - GNU General Public License 3
// This program comes with ABSOLUTELY NO WARRANTY; for details see
// http://www.sloodle.com/license
// This is free software, and you are welcome to redistribute it under certain conditions.
//BASED ON Bulletin Board by Apotheus Silverman
//VERSIONS
// .31 adds multiple object rez "rez #"
// .31 wording for user prompts
////////////

// Constants

//LOCATION
string Region;
vector Where;
integer x;
integer y;
integer z;
float RezHeight=4.25;

string URL="http://www.sloodle.org/mod/sloodle/mod/primdrop/primdrop_noauth.php";
string ReportURL="http://www.sloodle.org/mod/sloodle/mod/primdrop/primdrop_report.php";
key httprequest;

// Product version
string version = ".2";

// Product name
string ApplicationName = "PrimDrop";

// Name of the notecard to give out on touch
string HelpNote = "Help";

float Timeout = 90;

// All inventory types
list InventoryTypes = [
    INVENTORY_TEXTURE,
    INVENTORY_SOUND,
    INVENTORY_OBJECT,
    INVENTORY_SCRIPT,
    INVENTORY_LANDMARK,
    INVENTORY_CLOTHING,
    INVENTORY_NOTECARD,
    INVENTORY_BODYPART
];


// Global variables
key CurrentAgent;
integer ListenHandle;
string BulletinTitle;
string BulletinItem;
list InventoryBefore;
integer RezQuant;

// Global functions

// Identify() method
// Identifies this application in public chat.
Identify() {

    llSay(0, ApplicationName + " v" + version + ": say 'help' for assistance.");

////////////////////////
}


// Help() method
// Universal help function
// Gives the inventory item with the name defined in HelpNote to the supplied key.
Help(key avatar) {
    llGiveInventory(avatar, HelpNote);
}

// GetInventoryType() method
// Returns the type of inventory (an integer value) defined by the
// inventory argument.

integer GetInventoryType(string inventory) {
    if (llGetInventoryKey(inventory) == NULL_KEY) {
        return(-1);
    } else {
        integer i;
        for (i = 0; i < llGetListLength(InventoryTypes); i++) {
            integer constant = llList2Integer(InventoryTypes, i);
            integer j;
            for (j = 0; j < llGetInventoryNumber(constant); j++) {
                if(inventory == llGetInventoryName(constant, j)) {
                    return(constant);
                }
            }
        }
    }
    return(-1);
}

// GetInventoryDirectory() method
// Returns a list of inventory names
list GetInventoryDirectory() {
    list myInventory;
    integer j;
    integer k;

    for(j = 0; j < llGetListLength(InventoryTypes); j++) {
        integer constant = llList2Integer(InventoryTypes, j);
        for(k = 0; k < llGetInventoryNumber(constant); k++) {
            myInventory += llGetInventoryName(constant, k);
        }
    }

    return(myInventory);
}

// ListDiff() method
// Returns the first item in list1 that does not exist
// in list2. If all items in list1 exist in list2, returns an empty string.
string ListDiff(list list1, list list2) {
    integer i;

    for (i = 0; i < llGetListLength(list1); i++) {
        if (llListFindList(list2, llList2List(list1, i, i)) == -1) {
            return(llList2String(list1, i));
        }
    }
    return("");
}


// States
default {
    state_entry() {
        // Initialize
        Identify();
        state idle;
    }
}

state idle {
    state_entry() {
        // Clear global vars
       // llAllowInventoryDrop(FALSE);
        llSetAlpha(0,ALL_SIDES);
        CurrentAgent = NULL_KEY;
        InventoryBefore = [];
        BulletinItem = "";
        BulletinTitle = "";

        // Initialize listen
        llListenRemove(ListenHandle);
        ListenHandle = llListen(0, "", NULL_KEY, "");
    }
    
    touch_start(integer total_number) {
        Identify();
    }
    
    listen(integer channel, string name, key id, string message)
    {

        if (llSubStringIndex(llToUpper(message),"HELP")==0)
        {
            Help(id);
        }
        if (llSubStringIndex(llToUpper(message),"DROP")==0)        {
            CurrentAgent = id;
            state bulletin;
        }
        if (llSubStringIndex(llToUpper(message),"REZ")==0)        {
          //  string RezQuantStr=
           // RezQuant = (integer)
            RezQuant=(integer)llGetSubString(message,4,llStringLength(message)); //Ignores the who command
            if (RezQuant==0)
            {
                RezQuant==1;
            }
            CurrentAgent = id;
            state rezobject;
        }
    }
}

state rezobject {
    state_entry() {
        llSay(0, "Name of the object to rez?"); 
        llSetTimerEvent(Timeout);
        llListenRemove(ListenHandle);
        ListenHandle = llListen(0, "", NULL_KEY, "");
    }
    listen(integer channel, string name, key id, string message) {
        string RequestedObject = message;
        vector scale = llGetScale();
                scale.z=scale.z+RezHeight;
        integer i;
        llSay(0,"Got it - i="+(string)i+" and Rezquant="+(string)RezQuant);
        
        for (i=0;i<RezQuant;i++) { 
        llSay(0,"i="+(string)i);
            scale.x=llFrand(10)+1;
            scale.y=llFrand(10)+1;

            if (llFrand(1.)<.5) scale.x=scale.x/-1;
            if (llFrand(1.)<.5) scale.y=scale.y/-1;        

        
            llRezObject(RequestedObject, llGetPos() + <scale.x, scale.y,scale.z> * llGetRot(), ZERO_VECTOR, llGetRot(), 0);
        llWhisper(0,RequestedObject+" placed at <"+(string)scale.x+","+(string)scale.y+","+(string)scale.z+">");
        }        
        state idle ;
    }
}

state bulletin {
    state_entry() {
        InventoryBefore = GetInventoryDirectory();
        llSetAlpha(0,ALL_SIDES);
        llAllowInventoryDrop(TRUE);
        llSay(0, "CTRL-drag an object from Inventory.");
        llSetTimerEvent(Timeout);

        // Initialize listen
        llListenRemove(ListenHandle);
        ListenHandle = llListen(0, "", NULL_KEY, "");
    }

    touch_start(integer total_number) {
        Identify();
    }

    listen(integer channel, string name, key id, string message) {
        string myMessage = llToLower(message);
        if (myMessage == "help") 
        {
            Help(id);
        } else if (id != CurrentAgent && myMessage == "drop" || myMessage == "rez")
        {
            llSay(0, "Sorry. In the middle of a transaction.");
        }
    }
    
    changed(integer change) {
        if ((change & CHANGED_INVENTORY) || (change & CHANGED_ALLOWED_DROP)) {
            // Get new inventory list
            list inventoryAfter = GetInventoryDirectory();

            // Make sure something was added
            if (llGetListLength(InventoryBefore) < llGetListLength(inventoryAfter)) {
                
                // Figure out what was added
                BulletinItem = ListDiff(inventoryAfter, InventoryBefore);

                if (BulletinItem != "") {
                    // Create new bulletin
                    llSetTimerEvent(0);
                    llSay(0, "You dropped '"+BulletinItem+"', and I updated the database. See it here: "+ReportURL+".");
                    string obj_id = (string)llGetInventoryKey(BulletinItem); 
                    string obj_name = BulletinItem; 
                    string avi_id = (string)CurrentAgent; 
                    string avi_name = llKey2Name(CurrentAgent); 
                    //$course = optional_param('course', PARAM_ALPHANUM); 
                    string region = llGetRegionName(); 
                    vector Where = llGetPos();
                    integer x = (integer)Where.x;
                    integer y = (integer)Where.y;
                    integer z = (integer)Where.z;
                    string obj_type = (string)GetInventoryType(BulletinItem); 
                    integer prim_count=llGetObjectPrimCount(llGetInventoryKey(BulletinItem));

                    string dropbox_name = llGetObjectName(); 
                    string dropbox_id = (string)llGetInventoryKey(dropbox_name);
 
                    httprequest = llHTTPRequest(URL+"?obj_id="+llEscapeURL(obj_id)+"&obj_name="+llEscapeURL(obj_name)+"&avi_id="+llEscapeURL(avi_id)+"&avi_name="+llEscapeURL(avi_name)+"&region="+llEscapeURL(region)+"&x="+(string)x+"&y="+(string)y+"&z="+(string)z+"&obj_type="+llEscapeURL(obj_type)+"&dropbox_name="+llEscapeURL(dropbox_name)+"&prim_count="+(string)prim_count,[HTTP_METHOD,"GET"],"");
                    state idle ;

                }
            }
        }
    }

    timer() {
        llSetTimerEvent(0);
        llSay(0, "TIMED OUT. Please start over.");
        state idle;
    }
}