// LSL script generated: _SLOODLE_HOUSE.demos.demo_getClassList.lslp Thu Jul 22 00:58:49 Pacific Daylight Time 2010

integer PLUGIN_CHANNEL = 998821;
default {

    state_entry() {
    }

    touch_start(integer num_detected) {
        key owner = llGetOwner();
        string avname = llKey2Name(llGetOwner());
        string authenticatedUser = (((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(avname)) + "&sloodlemoduleid=5");
        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((("user->getClassList" + authenticatedUser) + "&sloodlemoduleid=") + ((string)5)) + "&index=") + ((string)0)) + "&sortmode=balance&gameid=1000"),NULL_KEY);
    }
}
