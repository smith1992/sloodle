/*********************************************
*  Copyrght (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
*  This script is part of the SLOODLE Project see http://sloodle.org
*
* example_plugin.lsl
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
* 
* 
*/
integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests

default {
    state_entry() {
        llOwnerSay("Example Plugin.  All commands sent to the _sloodle.api script go on channel 998822.\nAll responses come in on 998821");
    }//end state_entry
    touch_start(integer num_detected) {
        llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "PLUGIN:user,FUNCTION:getClassList\nAWARDID:"+(string)104+"\nSENDERUUID:"+(string)llGetOwner()+"|INDEX:0|SORTMODE:balance", NULL_KEY);
    }//end touch
    link_message(integer sender_num, integer channel, string str, key id) {
        if (channel==PLUGIN_RESPONSE_CHANNEL){
            llOwnerSay(str);
        }
    }//end link_message
}//end state
