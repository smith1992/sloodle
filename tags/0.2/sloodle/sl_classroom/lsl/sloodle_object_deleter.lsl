integer SLOODLE_CHANNEL_OBJECT_INVENTORY_VENDOR_DO_CLEANUP_ALL = -1639270022;
integer SLOODLE_CHANNEL_AVATAR_RECYCLE_BIN_MENU = -1639270031;
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;

integer listenid;
key toucheruuid;
string toucheravname;

integer SLOODLE_RESTRICT_TO_OWNER = 1;

integer handle_touch(key thistoucher) 
{

    if (toucheruuid != NULL_KEY) {
        if ( (SLOODLE_RESTRICT_TO_OWNER == 1) || (thistoucher != toucheruuid) ) {
            if (thistoucher != llGetOwner()) {
                if (SLOODLE_RESTRICT_TO_OWNER == 1) {
                    llDialog(thistoucher,"This object can only be used by its owner.",["OK"],SLOODLE_CHANNEL_AVATAR_IGNORE);
                } else {
                    //llSay(0,llKey2Name(thistoucher)+", this object is currently in use by "+llKey2Name(toucheruuid)+".");   
                    llDialog(thistoucher,"This object can only be used by its owner.",["OK"],SLOODLE_CHANNEL_AVATAR_IGNORE); 
                }
                return 0;
            }
        }
    }
    toucheruuid = thistoucher;
    toucheravname = llKey2Name(toucheruuid);
    return 1;
}

default
{
    touch_start(integer total_number)
    {
        if (handle_touch(llDetectedKey(0))) {
            toucheruuid = llDetectedKey(0);
            listenid = llListen(SLOODLE_CHANNEL_AVATAR_RECYCLE_BIN_MENU,"",toucheruuid,"");
            llDialog(toucheruuid, "Do you really want to cleanup all Sloodle objects?", ["Cleanup","Cancel"], SLOODLE_CHANNEL_AVATAR_RECYCLE_BIN_MENU);
        }
    }
    listen( integer channel, string name, key id, string message ) {
        if ( (channel == SLOODLE_CHANNEL_AVATAR_RECYCLE_BIN_MENU) && (message == "Cleanup") ) {
            llMessageLinked(LINK_ALL_OTHERS, SLOODLE_CHANNEL_OBJECT_INVENTORY_VENDOR_DO_CLEANUP_ALL,"do:cleanup_all",NULL_KEY);            
        }
    }
}

