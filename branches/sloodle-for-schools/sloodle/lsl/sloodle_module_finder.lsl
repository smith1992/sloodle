// SLOODLE for Schools.
// This script will respond to link messages to locate and provide module and course information.
// It will examine the object's description field for course and module instance identifiers.
// It will then search the VLE website for matching course and module information.
// If found, it will send back site-specific ID numbers by link message.
//
// This script also responds to chat messages from the SLOODLE HUD.
// These messages can instruct this object to connect to a different course/module.
// When this happens, the object description is updated, and all other scripts are instructed to reset.
// The main script should automatically request new data once it has reset.
// 
//
// Copyright (c) 2010 SLOODLE community (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Peter R. Bloomfield
//

// Usage:
//  A script should send a link message on channel SLOODLE_CHANNEL_OBJECT_DIALOG (see below),
//   containing the string "module_info_request", followed by a pipe character "|", 
//   and then the expected type identifier for the module.
//
//  For example, request strings for a chatroom and for a SLOODLE presenter (respectively) might look like this:
//
//   module_info_request|chat
//
//   module_info_request|sloodle:presenter
//
//  This script will use data in the object's description field to search the VLE site.
//  If the data is found, it will send a link message back on the same channel.
//  The first line of the response will be a string saying "module_info_response".
//  The second line will contain course information, in the format "databaseid|id|fullname".
//  The third line will contain module information, in the format "databaseid|type|name".
//
//  For example, the response for a chatroom might look like this (multiple lines):
//
//   module_info_response
//   14|LANG2047|French language and culture, level 2
//   87|chat|Social chatroom
//
//  The "databaseid" values are the ones which need to be provided to linker scripts,
//   as request parameters "sloodlecourseid" and "sloodlemoduleid".
//


// DATA //

// These keys identify the HTTP requests for course and module information respectively
key httpCourse = NULL_KEY;
key httpModule = NULL_KEY;

// Course information
integer courseDatabaseID = 0;
string courseExternalID = "";
string courseFullName = "";

// Module information
integer moduleDatabaseID = 0;
string moduleType = "";
string moduleName = "";

// Search information obtained from object description
string searchCourse = "";
string searchModule = "";


// CONSTANTS //

// Should this object allow configuration changes via chat message after its initial configuration?
// This allows a tool to be changed to a different course, but could allow malicious students to disrupt it.
integer ALLOW_CONFIG_CHANGE = TRUE;

// This is the link/chat message channel used for communicating between SLOODLE scripts
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;

// These are the identifiers used in link messages relating to this script
string MSG_MODULE_INFO_REQUEST = "module_info_request"; // Requesting that the module information is retrieved
string MSG_MODULE_INFO_RESPONSE = "module_info_response"; // Response to a request for module information
string MSG_RESET = "do:reset"; // Command to reset scripts

// These are the indentifiers used in chat messages relating to this script
string MSG_CONFIG_CHANGE = "config_change"; // New configuration data is being provided via chat message

// How long should we wait for HTTP responses before giving up? (seconds)
float HTTP_TIMEOUT = 5.0;

// Periodically this script will update the description field with the latest course/module identifiers, in case anything has changed.
// This value indicates the number of seconds between each update.
float UPDATE_DELAY = 1800.0; // 1800 = half hour


// TRANSLATION AND ERROR REPORTING //

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE = -1928374652;
integer SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST = -1828374651;

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

// Send a translation request link message
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}

sloodle_error_code(string method, key avuuid,integer statuscode)
{
    llMessageLinked(LINK_SET, SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST, method+"|"+(string)avuuid+"|"+(string)statuscode, NULL_KEY);
}



// STATES //

// This is our primary state in which we listen for incoming link messages.
default
{
    state_entry()
    {
        // Listen for instructions from other SLOODLE objects
        llListen(SLOODLE_CHANNEL_OBJECT_DIALOG, "", NULL_KEY, "");
        
        // Periodically refresh our portable data
        llSetTimerEvent(UPDATE_DELAY);
    }
    
    state_exit()
    {
        llSetTimerEvent(0.0);
    }
    
    on_rez(integer start_param)
    {
        // May want to reset the script here, in case the object has been moved (via inventory) to a different region server with a different VLE.
    }

    link_message(integer sender, integer channel, string msg, key id)
    {
        // Only pay attention to the relevant channel
        if (channel != SLOODLE_CHANNEL_OBJECT_DIALOG) return;
    
        // Split the incoming message into fields, and check what the message identifier is
        list fields = llParseStringKeepNulls(msg, ["|"], []);
        integer numFields = llGetListLength(fields);
        string msgID = llList2String(fields, 0);
        if (msgID == MSG_MODULE_INFO_REQUEST)
        {
            // We are being asked for up-to-date module information.
            // Make sure we have a second field containing the module type.
            if (numFields < 2)
            {
                llSay(DEBUG_CHANNEL, "Error in link message. Expected module type identifier in second field of request message.");
                return;
            }
            
            // Get the module type from the link message fields
            moduleType = llList2String(fields, 1);
            
            // Extract our search data from the object description.
            // The course ID and the module name will be separated by a triple colon ":::".
            list descParts = llParseStringKeepNulls(llGetObjectDesc(), [":::"], []);
            integer numDescParts = llGetListLength(descParts);
            searchCourse = llList2String(descParts, 0);
            searchModule = "";
            if (numDescParts > 1) searchModule = llList2String(descParts, 1);
            
            if (searchCourse == "" || searchModule == "")
            {
                llSay(0, "Sorry, this object has not been configured properly. Configuration information is stored in the object's description.");
                return;
            }
            
            // Fetch the information
            state searching_for_module;
            return;
        }
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Do we have a valid configuration string already?
        string oldConfig = llGetObjectDesc();
        if (oldConfig != "" || llSubStringIndex(oldConfig, ":::") >= 0)
        {
            // Yes - disallow configuration changes if necessary
            if (!ALLOW_CONFIG_CHANGE) return;
        }
    
        // Only pay attention to the relevant channel
        if (channel != SLOODLE_CHANNEL_OBJECT_DIALOG) return;
        
        // TODO: should ideally do some security here to ensure unauthorised users cannot tamper with configuration
    
        // Split the incoming message into lines, and check what the identifier on the first line is
        list lines = llParseStringKeepNulls(msg, ["\n"], []);
        integer numLines = llGetListLength(lines);
        string msgID = llList2String(lines, 0);
        if (msgID == MSG_CONFIG_CHANGE)
        {
            // We are being instructed to change our configuration.
            // The second line should identify the object to be configured, and
            //  the third line should contain our new configuration data
            if (numLines < 3)
            {
                llSay(DEBUG_CHANNEL, "Error: cannot update configuration data -- not enough lines of data provided.");
                return;
            }
            
            // Ignore anything not aimed at this object
            key configRecipient = (key)llList2String(lines, 1);
            if (configRecipient != llGetKey()) return;
            
            // Make sure a valid configuration string has been provided
            string newConfig = llList2String(lines, 2);
            if (newConfig == "")
            {
                llSay(DEBUG_CHANNEL, "Error: Empty configuration provided by chat message from " + name);
                return;
            }
            if (llSubStringIndex(newConfig, ":::") < 0)
            {
                llSay(DEBUG_CHANNEL, "Error: Invalid configuration provided by chat message from " + name);
                return;
            }
            
            // Store the updated configuration and tell other scripts to reset to receive the new information
            llSetObjectDesc(newConfig);
            courseDatabaseID = 0;
            moduleDatabaseID = 0;
            llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, MSG_RESET, NULL_KEY);
            return;
        }
    }
    
    timer()
    {
        // If we have site-specific data and module type then use it to refresh our portable data
        if (courseDatabaseID <= 0 || moduleDatabaseID <= 0 || moduleType == "") return;
        
        // Course request
        string url = "sloodle://course/lookup_course.php?search=" + (string)courseDatabaseID + "&mode=databaseid";
        httpCourse = llHTTPRequest(url, [HTTP_METHOD, "GET"], "");
        // Module request
        url = "sloodle://course/lookup_module.php?";
        url += "sloodlecourseid=" + (string)courseDatabaseID;
        url += "&type=" + llEscapeURL(moduleType);
        url += "&sloodlemoduleid=" + (string)moduleDatabaseID;
        httpModule = llHTTPRequest(url, [HTTP_METHOD, "GET"], "");
    }
    
    http_response(key id, integer httpStatus, list metadata, string body)
    {
        // These requests should have transparently.
        // If something goes wrong then ignore it and try again later.
    
        integer isCourseInfo = FALSE;
        integer isModuleInfo = FALSE;
    
        // Ignore anything but our expected response
        if (id == httpCourse)
        {
            isCourseInfo = TRUE;
            httpCourse = NULL_KEY;
        } else if (id == httpModule)
        {
            isModuleInfo = TRUE;
            httpModule = NULL_KEY;
        }
        
        llSetTimerEvent(0.0);
        //llSay(DEBUG_CHANNEL, "Received HTTP response (" + (string)httpStatus + ")\n" + body);
        
        // Check the status of the response
        if (httpStatus != 200) return;
        
        // Parse the response
        list lines = llParseStringKeepNulls(body, ["\n"], []);
        integer numLines = llGetListLength(lines);
        list statusFields = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
        integer sloodleStatus = (integer)llList2String(statusFields, 0);
        if (sloodleStatus <= 0) return;
        
        // We need at least 3 lines (one giving number of results, and at least one giving us the results themselves)
        if (numLines < 3) return;
        
        // Check how many results were returned
        integer numResults = (integer)llList2String(lines, 1);
        if (numResults != 1) return;
        
        // Parse our results line
        list resultsFields = llParseStringKeepNulls(llList2String(lines, 2), ["|"], []);
        if (isCourseInfo)
        {
            if (llGetListLength(resultsFields) < 3) return;
            courseExternalID = llList2String(resultsFields, 1);
            courseFullName = llList2String(resultsFields, 2);
        }
        else if (isModuleInfo)
        {
            if (llGetListLength(resultsFields) < 3) return;
            moduleName = llList2String(resultsFields, 2);
        }
        
        // If both our http keys are null then we must have received an update from both course and module.
        // Use our data to update the object description.
        if (httpCourse == NULL_KEY && httpModule == NULL_KEY)
        {
            searchCourse = courseExternalID;
            searchModule = moduleName;
            llSetObjectDesc(searchCourse + ":::" + searchModule);
        }
    }
}

// In this state, we are searching for course and module information
state searching_for_module
{
    state_entry()
    {
        httpCourse = NULL_KEY;
        httpModule = NULL_KEY;
    
        // TODO: process course data, make request for module data, and process module data
    
        // Send a request for course information
        string url = "sloodle://course/lookup_course.php?";
        url += "search=" + llEscapeURL(searchCourse);
        url += "&mode=shortname";
        httpCourse = llHTTPRequest(url, [HTTP_METHOD, "GET"], "");
        //llSay(DEBUG_CHANNEL, "Searching for course with ID or name \"" + searchCourse + "\"");
        llSetTimerEvent(HTTP_TIMEOUT);
    }
    
    state_exit()
    {
        llSetTimerEvent(0.0);
    }
    
    timer()
    {
        // HTTP response timed-out
        llSay(0, "An HTTP timeout occurred while searching for your course. Your VLE may be offline, or OpenSim may be experiencing difficulties.");
        llSetTimerEvent(0.0);
        state default;
    }
    
    http_response(key id, integer httpStatus, list metadata, string body)
    {
        integer isCourseInfo = FALSE;
        integer isModuleInfo = FALSE;
    
        // Ignore anything but our expected response
        if (id == httpCourse)
        {
            isCourseInfo = TRUE;
            httpCourse = NULL_KEY;
        } else if (id == httpModule)
        {
            isModuleInfo = TRUE;
            httpModule = NULL_KEY;
        }
        
        llSetTimerEvent(0.0);
        //llSay(DEBUG_CHANNEL, "Received HTTP response (" + (string)httpStatus + ")\n" + body);
        
        // Check the status of the response
        if (httpStatus != 200)
        {
            sloodle_error_code(SLOODLE_TRANSLATE_SAY, NULL_KEY, httpStatus);
            state default;
            return;
        }
        
        // Parse the response
        list lines = llParseStringKeepNulls(body, ["\n"], []);
        integer numLines = llGetListLength(lines);
        list statusFields = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
        integer sloodleStatus = (integer)llList2String(statusFields, 0);
        if (sloodleStatus == 0)
        {
            sloodleStatus = -1;
            llSay(DEBUG_CHANNEL, "Empty HTTP response.");
        }
        if (sloodleStatus < 1)
        {
            sloodle_error_code(SLOODLE_TRANSLATE_SAY, NULL_KEY, sloodleStatus);
            state default;
            return;
        }
        
        // We need at least 2 lines
        if (numLines < 2)
        {
            llSay(0, "There was an unknown problem getting data from the server.");
            llSay(DEBUG_CHANNEL, "Expected at least 2 lines");
            state default;
            return;
        }
        
        // Check how many results were returned
        integer numResults = (integer)llList2String(lines, 1);
        if (numResults == 0)
        {
            if (isCourseInfo) llSay(0, "Sorry. The requested course could not be found.\n(Course: " + searchCourse + ").");
            else if (isModuleInfo) llSay(0, "Sorry. The requested module could not be found.\n(Module name: " + searchModule + ")");
            state default;
            return;
        } else if (numResults > 1) {
            if (isCourseInfo) llSay(0, "Sorry. Multiple courses with the same identifier or name were found. Please ensure each course has a unique ID number in its settings.\n(Course: " + searchCourse + ").");
            else if (isModuleInfo) llSay(0, "Sorry. Multiple modules with the same name were found. Please ensure each module in your course has a different name.\n(Module name: " + searchModule + ")");
            state default;
            return;
        }
        
        // Parse our results fields
        list resultsFields = llParseStringKeepNulls(llList2String(lines, 2), ["|"], []);
        integer numResultsFields = llGetListLength(resultsFields);
        
        // Is this course data?
        if (isCourseInfo)
        {
            if (numResultsFields < 3)
            {
                llSay(0, "Sorry. The VLE did not provide enough course data for configuring this object.");
                llSay(DEBUG_CHANNEL, "Expected 3 fields of course data but got " + (string)numResultsFields);
                state default;
                return;
            }
        
            // Process course info
            courseDatabaseID = (integer)llList2String(resultsFields, 0);
            courseExternalID = llList2String(resultsFields, 1);
            courseFullName = llList2String(resultsFields, 2);
            
            // Send a request for module information
            string url = "sloodle://course/lookup_module.php?";
            url += "sloodlecourseid=" + (string)courseDatabaseID;
            url += "&type=" + llEscapeURL(moduleType);
            url += "&name=" + llEscapeURL(searchModule);
            httpModule = llHTTPRequest(url, [HTTP_METHOD, "GET"], "");
            //llSay(DEBUG_CHANNEL, "Searching for module with name \"" + searchModule + "\"");
            llSetTimerEvent(HTTP_TIMEOUT);
        }
        else if (isModuleInfo)
        {
            if (numResultsFields < 3)
            {
                llSay(0, "Sorry. The VLE did not provide enough module data for configuring this object.");
                llSay(DEBUG_CHANNEL, "Expected 3 fields of module data but got " + (string)numResultsFields);
                state default;
                return;
            }
        
            // Process module info
            moduleDatabaseID = (integer)llList2String(resultsFields, 0);
            moduleName = llList2String(resultsFields, 2);
            
            // Send data to other script(s)
            string data = MSG_MODULE_INFO_RESPONSE;
            data += "\n" + (string)courseDatabaseID + "|" + courseExternalID + "|" + courseFullName;
            data += "\n" + (string)moduleDatabaseID + "|" + moduleType + "|" + moduleName;
            llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, data, NULL_KEY);
            
            state default;
        }
    }
}
