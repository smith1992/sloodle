// Sloodle translation script for the Distributor 1.0 tool.
// The list of string names should be the same on all language translations of this script.
// The names should correspond directly to translations in the "locstrings" list.
//
// This script is part of the Sloodle project.
// Copyright (c) 2008 Sloodle (various contributors)
// Released under the GNU GPL v3
//
// Contributors:
//  Peter R. Bloomfield
//

// Note: where a translation string contains {{x}} (where x is a number),
//  it means that a parameter can be inserted. Please make sure to include these
//  parameters in the appropriate location in your translation.
// It may be sensible to add comments after your string to indicate what its parameters mean.
// NOTE: parameter numbering starts at 0 (unlike previous versions, which started at 1).

// Translations can be requested by sending a link message on the SLOODLE_CHANNEL_TRANSLATION_REQUEST channel.
// It is advisable simply to use the "sloodle_translation_request" function provided in this script.


///// TRANSLATION /////

// Language code for this translator (corresponds to Moodle language codes)
string mylangcode = "en_utf8";


// List of string names - do not translate this
list locstringnames = [
    "openingxmlrpc",
    "establishingconnection",
    "dialog:distributorcommandmenu",
    "dialog:distributorobjectmenu",
    "dialog:distributorobjectmenu:cmd"
];

// List of translations - translate these, but do not change their order
list locstrings = [
    "Opening XMLRPC channel...",
    "Establishing connection with outside server...",
    "Sloodle Distributor.\nSelect an action:\n\n{{0}} = Reconnect\n{{1}} = Reset\n{{2}} = Shutdown", // Each parameter is a button label
    "Sloodle Distributor.\n\n{{0}}", // The parameter should be a set of button labels and object names, e.g. "1 = WebIntercom, 2 = MetaGloss"
    "Sloodle Distributor.\n\n{{0}}{{1}} = Command menu" // As above, but the second parameter gives the command menu button label
];

///// ----------- /////


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
string SLOODLE_TRANSLATE_LOAD_URL = "loadurl";      // Recipient avatar should be identified in link message keyval.
string SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";  // 2 output parameters: colour <r,g,b>, and alpha value


///// FUNCTIONS /////


// Send a translation request link message
// (Here for reference only)
// Parameter: output_method = should identify an output method, as given by the "SLOODLE_TRANSLATE_..." constants above
// Parameter: output_params = a list of parameters which controls the output, such as chat channel or buttons for a dialog
// Parameter: string_name = the name of the localization string to output
// Parameter: string_params = a list of parameters which will be included in the translated string (or an empty list if none)
// Parameter: keyval = a key to send in the link message
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params), keyval);
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

// Send a debug link message
sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
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
        curparamtok = "{{" + (string)(curparamnum) + "}}";
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


///// STATES /////

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
            if (numfields > 3) {
                string string_param_text = llList2String(fields, 3);
                if (string_param_text != "") string_params = llCSV2List(string_param_text);
            }
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
                if (llGetListLength(string_params) == 0) {
                    // Get the basic translation
                    trans = sloodle_get_string(string_name);
                } else {
                    // Construct the formatted string
                    trans = sloodle_get_string_f(string_name, string_params);
                }
            }
            
            // If the translation is empty, then do nothing
            if (trans == "") return;
            
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
            
            } else if (output_method == SLOODLE_TRANSLATE_HOVER_TEXT) {
                // We need 1 additional parameter, containing the URL to load
                if (num_output_params >= 2) llSetText(trans, (vector)llList2String(output_params, 0), (float)llList2String(output_params, 1));
                else sloodle_debug("ERROR: Insufficient output parameters to show hover text with string \"" + string_name + "\".");
                
            } else {
                // Don't know the output method
                sloodle_debug("ERROR: unrecognised output method \"" + output_method + "\".");
            }
        }
    }
}
