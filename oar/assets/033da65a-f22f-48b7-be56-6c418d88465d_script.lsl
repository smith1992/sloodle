

default
{
    state_entry()
    {
        llSetText("Click to get your FREE SLOODLE Toolbar", <0.,1.0,0.>, 0.9);
    }
    
    on_rez(integer param)
    {
        llResetScript();
    }
    
    touch_start(integer total_number)
    {
        llGiveInventory(llDetectedKey(0), "Sloodle Lite Toolbar v1.4");
        llGiveInventory(llDetectedKey(0), "Sloodle Toolbar v1.4");
    }
}

