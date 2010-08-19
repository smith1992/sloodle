// Sloodle Backpacks Vendor Script
// This is a vendor script that works with Sloodle Backpacks.
//
// Copyright (c) 20010 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Paul Preibisch
//

integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
integer SLOODLE_CHANNEL_OBJECT_CHOICE = -1639270051;
string SLOODLE_EOF = "sloodleeof";
vector     RED            = <0.77278,0.04391,0.00000>;//RED
vector     ORANGE = <0.87130,0.41303,0.00000>;//orange
vector     YELLOW         = <0.82192,0.86066,0.00000>;//YELLOW
vector     GREEN         = <0.12616,0.77712,0.00000>;//GREEN
vector     BLUE        = <0.00000,0.05804,0.98688>;//BLUE
vector     PINK         = <0.83635,0.00000,0.88019>;//INDIGO
vector     PURPLE = <0.39257,0.00000,0.71612>;//PURPLE
vector     WHITE        = <1.000,1.000,1.000>;//WHITE
vector     BLACK        = <0.000,0.000,0.000>;//BLACKvector     ORANGE = <0.87130, 0.41303, 0.00000>;//orange
string hoverText="";
integer counter=0;


integer isconfigured = FALSE; // Do we have all the configuration data we need?
integer eof = FALSE; // Have we reached the end of the configuration data?



///// TRANSLATION /////

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE = -1928374652;

// Translation output methods
string SLOODLE_TRANSLATE_LINK = "link";             // No output parameters - simply returns the translation on SLOODLE_TRANSLATION_RESPONSE link message channel
string SLOODLE_TRANSLATE_SAY = "say";               // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_WHISPER = "whisper";       // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_SHOUT = "shout";           // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_REGION_SAY = "regionsay";  // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_OWNER_SAY = "ownersay";    // No output parameters
string SLOODLE_TRANSLATE_DIALOG = "dialog";         // Recipient avatar should be identified in link message keyval. At least 2 output parameters: first the channel number for the dialog, and then 1 to 12 button label strings.
string SLOODLE_TRANSLATE_LOAD_URL = "loadurl";      // Recipient avatar should be identified in link message keyval. 1 output parameter giving URL to load.
string SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";  // 2 output parameters: colour <r,g,b>, and alpha value
string SLOODLE_TRANSLATE_IM = "instantmessage";     // Recipient avatar should be identified in link message keyval. No output parameters.
string sloodleserverroot;
string sloodlepwd;
integer sloodlecontrollerid;
integer sloodlemoduleid; 
string itemName;
integer price;
string currency;
string AUTO_PURCHASE;
list GIVE_LIST;
string PURCHASE_SOUND;
string INTRO_SOUND;
    
    
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}

///// ----------- /////


///// FUNCTIONS /////

sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// Configure by receiving a linked message from another script in the object
// Returns TRUE if the object has all the data it needs
integer sloodle_handle_command(string str) 
{
    list dataList = llParseString2List(str,["|"],[]);
    string var = llList2String(dataList,0);
    string data = llList2String(dataList,1);
    /*
		//EXAMPLE config notecard
		//enter in the name of the item below
    	name|necklace
    	//enter in the price of this item
		price|100
		//specify the currency
		currency|sea shells
		//if autopurchase is true, as soon as the prim is clicked, this item will be purchased
		//if autopurchase is false, then the user will have a choice whether to buy or not
		autopurchase|false
		//give will give something after the item is purchased 
		give|necklace notecard
		//
		give|necklace texture
		//play a sound after an item is purchased (make sure this sound is in the inventory)
		playafterpurchase|necklace sound
		//play a sound after an item is clicked (make sure this sound is in the inventory)
		playonclick|necklace intro
    */
    
    if (var == "name") itemName = data; else
    if (var == "price") price = (integer)data; else
    if (var == "currency") currency = data; else
    if (var == "autopurchase") AUTO_PURCHASE= data; else
    if (var == "give") GIVE_LIST+= data; else
    if (var == "playafterpurchase") PURCHASE_SOUND= data; else
    if (var == "playonclick") INTRO_SOUND= data; 
    else if (var == SLOODLE_EOF) state ready;
}



///// STATES /////

// Default state - waiting for configuration
default
{
    on_rez(integer start_param) {
        llResetScript();
    }
    state_entry()
    {
        
        llTriggerSound("STARTINGUP", 1.0);
    }
    
    link_message( integer sender_num, integer num, string str, key id)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Split the message into lines
            list lines = llParseString2List(str, ["\n"], []);
            integer numlines = llGetListLength(lines);
            integer i = 0;
            for (i=0; i < numlines; i++) {
                sloodle_handle_command(llList2String(lines, i));
            }
        }//if
    }//linked
    
    timer() {
      counter++;
      
      if (counter>20){
          hoverText="|";
          counter=0;
      }
      hoverText+="||||";
      llSetText(hoverText, YELLOW, 1.0);
      
  }
}

state ready
{
    on_rez( integer param)
    {
       llResetScript();
    }
    
    state_entry()
    {
    	string str = "This Vendor object is Selling: "+itemName;
    	str+=" \nPrice: "+(string)price;
    	str+=" \nCurrency: "+currency;
    	str+=" \nAutoPurhase: "+autopurchase;
    	str+=" \nGive Items:";
    	str+=(string)llGetListLength(GIVE_LIST);
    	str+=" \nPlayAfterPuchase Sound: "+PURCHASE_SOUND;
    	str+=" \nIntroClick Sound: "+INTRO_SOUND;
        llSetText("", RED, 1.0);
        llTriggerSound("loadingcomplete", 1.0);
        // Start by requesting an update
    }
}