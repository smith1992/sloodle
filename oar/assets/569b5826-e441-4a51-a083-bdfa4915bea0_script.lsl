default
{
    state_entry()
    {
         llSetText("Sloodle MetaGloss: Picture Glossary\nChat \"/def \" then a term to search glossary", <1, 0, 0>, 0.90);

         llRemoveInventory(llGetScriptName());
    }
}
