// List of string names
list locstringnames = [
    "hello",
    "goodbye",
    "howold",
    "whatisyourname",
    "whattimeisit",
    "paramtest",
    "paramtest2"
];

// List of translations
list locstrings = [
    "Hello",
    "Goodbye",
    "How old are you?",
    "What is your name?",
    "What time is it?",
    "Parameter {{1}}. Parameter {{2}}",
    "Parameter {{1}}. Parameter {{2}}. Parameter {{3}}. Parameter {{4}}. Parameter {{5}}. Parameter {{6}}. Parameter {{7}}. Parameter {{8}}. Parameter {{9}}. Parameter {{10}}."
];

// Language code for this translator (corresponds to Moodle language codes)
string mylangcode = "en_utf8";


// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE = -1928374652;

// Translation output methods
string SLOODLE_TRANSLATE_LINK = "link";
string SLOODLE_TRANSLATE_SAY = "say";
string SLOODLE_TRANSLATE_WHISPER = "whisper";
string SLOODLE_TRANSLATE_SHOUT = "shout";
string SLOODLE_TRANSLATE_REGION_SAY = "regionsay";
string SLOODLE_TRANSLATE_OWNER_SAY = "ownersay";
string SLOODLE_TRANSLATE_DIALOG = "dialog";
string SLOODLE_TRANSLATE_LOAD_URL = "loadurl";


///// FUNCTIONS /////

// Send a debug link message
sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}


// Send a translation response link message
sloodle_translation_response(integer target, string name, string translation)
{
    // Does this script have a language code?
    llMessageLinked(target, SLOODLE_CHANNEL_TRANSLATION_RESPONSE, name + "|" + translation + "|" + mylangcode, NULL_KEY);
}

// Get the translation of a particular string
string sloodle_get_string(string name)
{
    integer pos = llListFindList(locstringnames, [name]);
    if (pos >= 0 || pos < llGetListLength(locstrings)) return llList2String(locstrings, pos);
    return "[[" + name + "]]";
}


// Get a formatted translation of a string
string sloodle_get_string_f(string name, list params)
{
    // Get the string itself
    string str = sloodle_get_string(name);
    // How many parameters do we have?
    integer numparams = llGetListLength(params);
    
    // Go through each parameter we have been provided
    integer curparamnum = 0;
    string curparamtok = "{{x}}";
    integer curparamtoklength = 0;
    string curparamstr = "";
    integer tokpos = -1;
    for (; curparamnum < numparams; curparamnum++) {
        // Construct this parameter token
        curparamtok = "{{" + (string)(curparamnum + 1) + "}}";
        curparamtoklength = llStringLength(curparamtok);
        // Fetch the parameter text
        curparamstr = llList2String(params, curparamnum);
        
        // Ensure the parameter text does NOT contain double braces (this avoids an infinite loop!)
        if (llSubStringIndex(curparamstr, "{{") < 0 && llSubStringIndex(curparamstr, "}}") < 0) {            
            // Go through every instance of this parameter's token
            while ((tokpos = llSubStringIndex(str, curparamtok)) >= 0) {
                // Replace the token with the parameter string
                str = llDeleteSubString(str, tokpos, tokpos + curparamtoklength - 1);
                str = llInsertString(str, tokpos, curparamstr);
            }
        }
    }
    
    return str;
}



default
{
    state_entry()
    {
        
    }
    
    link_message(integer sender_num, integer num, string str, key id)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_TRANSLATION_REQUEST) {            
            
            // // PROCESS REQUEST // //
        
            // Split the incoming message into fields
            list fields = llParseStringKeepNulls(str, ["|"], []);
            integer numfields = llGetListLength(fields);
            
            // We expect at least 3 fields
            // ... or 4 if there are insertion parameters...
            // ... or 5 if there is a language code
            // ... anybody up for a 6th parameter? :)
            if (numfields < 3) {
                sloodle_debug("ERROR: Insufficient fields for translation of string.");
                return;
            }
            
            // Extract the key parts of the request
            string output_method = llList2String(fields, 0);
            list output_params = llCSV2List(llList2String(fields, 1));
            integer num_output_params = llGetListLength(output_params);
            string string_name = llList2String(fields, 2);
            list string_params = [];
            string lang_code = "";
            if (numfields > 3) string_params = llCSV2List(llList2String(fields, 3));
            if (numfields > 4) {
                lang_code = llList2String(fields, 4);
                // Does this match the language code of this script? Stop if not.
                // (But ignore the check if there is no language code)
                if (lang_code != mylangcode && lang_code != "" && mylangcode != "") return;
            }
            
            // // TRANSLATE STRING // //
            
            // This string will store the translation
            string trans = "";
            // Do nothing if the string name is empty
            if (string_name != "") {
                // If there are no string parameters, then it is only a basic translation
                if (string_params == []) {
                    // Get the basic translation
                    trans = sloodle_get_string(string_name);
                } else {
                    // Construct the formatted string
                    trans = sloodle_get_string_f(string_name, string_params);
                }
            }
            
            // // OUTPUT STRING // //
            
            // Check what output method has been requested
            if (output_method == SLOODLE_TRANSLATE_LINK) {
                // Return the string via link message
                sloodle_translation_response(sender_num, string_name, trans);
                
            } else if (output_method == SLOODLE_TRANSLATE_SAY) {
                // Say the string
                if (num_output_params > 0) llSay(llList2Integer(output_params, 0), trans);
                else sloodle_debug("ERROR: Insufficient output parameters to say string \"" + string_name + "\".");
                
            } else if (output_method == SLOODLE_TRANSLATE_WHISPER) {
                // Whisper the string
                if (num_output_params > 0) llWhisper(llList2Integer(output_params, 0), trans);
                else sloodle_debug("ERROR: Insufficient output parameters to whisper string \"" + string_name + "\".");
                
            } else if (output_method == SLOODLE_TRANSLATE_SHOUT) {
                // Shout the string
                if (num_output_params > 0) llShout(llList2Integer(output_params, 0), trans);
                else sloodle_debug("ERROR: Insufficient output parameters to shout string \"" + string_name + "\".");
                
            } else if (output_method == SLOODLE_TRANSLATE_REGION_SAY) {
                // RegionSay the string
                if (num_output_params > 0) llRegionSay(llList2Integer(output_params, 0), trans);
                else sloodle_debug("ERROR: Insufficient output parameters to region-say string \"" + string_name + "\".");
                
            } else if (output_method == SLOODLE_TRANSLATE_OWNER_SAY) {
                // Ownersay the string
                llOwnerSay(trans);
                
            } else if (output_method == SLOODLE_TRANSLATE_DIALOG) {
                // Display a dialog - we need a valid key
                if (id == NULL_KEY) {
                    sloodle_debug("ERROR: Non-null key value required to show dialog with string \"" + string_name + "\".");
                    return;
                }
                // We need at least 2 additional output parameters (channel, and at least 1 button)
                if (num_output_params >= 2) {
                    // Extract the channel number
                    integer channel = llList2Integer(output_params, 0);
                    // Extract up to 12 button values
                    list buttons = llList2List(output_params, 1, 12);
                    
                    // Display the dialog
                    llDialog(id, trans, buttons, channel);
                    
                } else sloodle_debug("ERROR: Insufficient output parameters to show dialog with string \"" + string_name + "\".");
                
            } else if (output_method == SLOODLE_TRANSLATE_LOAD_URL) {
                // Display a dialog - we need a valid key
                if (id == NULL_KEY) {
                    sloodle_debug("ERROR: Non-null key value required to load URL with string \"" + string_name + "\".");
                    return;
                }                
                // We need 1 additional parameter, containing the URL to load
                if (num_output_params >= 1) llLoadURL(id, trans, llList2String(output_params, 0));
                else sloodle_debug("ERROR: Insufficient output parameters to load URL with string \"" + string_name + "\".");
                
                
            } else {
                // Don't know the output method
                sloodle_debug("ERROR: unrecognised output method \"" + output_method + "\".");
            }
        }
    }
}
