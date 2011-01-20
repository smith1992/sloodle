// LSL script generated: avatar_classroom2.secondlife_lsl_code_port.demos.maketrans.lslp Wed Aug 25 13:52:43 Pacific Daylight Time 2010

integer PLUGIN_CHANNEL = 998821;
default {

    state_entry() {
    }

    touch_start(integer num_detected) {
        key owner = llGetOwner();
        string avname = llKey2Name(llGetOwner());
        string authenticatedUser = (((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(avname)) + "&sloodlemoduleid=5");
        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((((("awards->makeTransaction" + authenticatedUser) + "&sourceuuid=") + ((string)owner)) + "&avuuid=") + ((string)owner)) + "&avname=") + llEscapeURL(avname)) + "&gameid=1000&points=1000"),NULL_KEY);
    }
}
