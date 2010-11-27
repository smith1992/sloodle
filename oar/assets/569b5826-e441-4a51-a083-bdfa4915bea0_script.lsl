default
{
    state_entry()
    {
         llSetText("Sloodle MetaGloss: Picture Glossary\nChat \"/def \" then a term to search glossary", <1, 0, 0>, 0.90);

         llRemoveInventory(llGetScriptName());
    }
}
// Please leave the following line intact to show where the script lives in Subversion:
// SLOODLE LSL Script Subversion Location: mod/glossary-1.0/sloodle_glossary_explainer.lsl
