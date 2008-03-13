//By Jeremy Kabumpo


default
{
    state_entry()
    {
    }

    touch_start(integer total_number)
    {
      llLoadURL(llDetectedKey(0), "PrimDrop Report Page", "http://www.sloodle.com/mod/sloodle/mod/primdrop/primdrop_report.php");
    }
}