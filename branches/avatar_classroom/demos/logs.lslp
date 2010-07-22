integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
default
{
    state_entry()
    {
        llSay(0, "Hello, Avatar!");
    }

    touch_start(integer total_number)
    {
    	vector pos = llGetPos();
         integer ix=(integer)pos.x;
         integer iy=(integer)pos.y;
         integer iz=(integer)pos.z;
        string slurl = "http://slurl.com/secondlife/"+ llEscapeURL(llGetRegionName()) + "/"+(string)ix +"/"+(string)iy+"/"+(string)iz+"/";
        string authenticatedUser = "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
        string avinfo= "&avuuid="+(string)llDetectedKey(0)+"&avname="+llEscapeURL(llKey2Name(llDetectedKey(0)));
        
        llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "logs->addLog"+authenticatedUser+"&useraction="+llEscapeURL(llGetObjectDesc())+"&slurl="+slurl+avinfo, NULL_KEY);
    }
    link_message(integer sender_num, integer channel, string str, key id) {
if (channel==PLUGIN_RESPONSE_CHANNEL)
    llSay(0,"");
    }

}
