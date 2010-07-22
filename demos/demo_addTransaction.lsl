// LSL script generated: _SLOODLE_HOUSE.demos.demo_addTransaction.lslp Thu Jul 22 00:58:49 Pacific Daylight Time 2010

integer PLUGIN_CHANNEL = 998821;
default {

    state_entry() {
    }

    touch_start(integer num_detected) {
        key owner = llGetOwner();
        string avname = llKey2Name(llGetOwner());
        string authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(avname));
        string currency = llGetObjectDesc();
        if ((currency == "")) (currency = "Credits");
        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((((("awards->addTransaction" + authenticatedUser) + "&avname=") + llEscapeURL(avname)) + "&avuuid=") + ((string)llGetOwner())) + "&currency=") + llEscapeURL(currency)) + "&amount=30"),NULL_KEY);
    }
}
