default
{
    state_entry()
    {
         llSetText("Touch me to start web-configuration", <0, 1, 0>, 1);

         llRemoveInventory(llGetScriptName());
    }
}
