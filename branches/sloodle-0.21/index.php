<?php
    /**
    * Sloodle module instance view placeholder script.
    *
    * A temporary placeholder to give information when a user attempts to browse Sloodle module instances.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */

    // Include the Sloodle configuration
    require_once('config.php');
    
    // Get the localization strings
    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
    
    // Display the header
    print_header_simple($strsloodles, "", $strsloodles, "", "", true, "");

    // Display the Sloodle version
    print_heading(get_string('sloodleversion','sloodle').': '.(string)SLOODLE_VERSION, 'center', 3);
    // Get and display the module version number (from version.php)
    $sloodlemodule = get_record('modules', 'name', 'sloodle');
    $releasenum = 0;
    if ($sloodlemodule !== FALSE) $releasenum = $sloodlemodule->version;
    print_heading(get_string('releasenum','sloodle').': '.(string)$releasenum, 'center', 5);
    
    // Display a help button regarding the version numbers
    echo '<div style="text-align:center;">(';
    helpbutton('version_numbers', get_string('help:versionnumbers', 'sloodle'), 'sloodle', true, true);
    echo ')</div>';
?>

<br/>
<div style="border:1px solid #bababa; padding:8px;">
<!-- Text taken from "mod.html" -->
<?php print_string('mod.html:placeholder1', 'sloodle'); ?><br/><br/>
<?php print_string('mod.html:placeholder2', 'sloodle'); ?><br/>
<?php print_string('mod.html:placeholder3', 'sloodle'); ?> <a href="http://www.sloodle.org/" title="<?php print_string('clicktovisitsloodle.org', 'sloodle'); ?>">www.sloodle.org</a><br/><br/>
<?php print_string('mod.html:placeholder4', 'sloodle'); ?><br/>
<!-- ..... -->
</div>

<?php
    // Display the footer
    print_footer();
?>
