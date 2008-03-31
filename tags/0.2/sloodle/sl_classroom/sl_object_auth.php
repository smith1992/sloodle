<?php
    // Sloodle object authorization page
    // Allows users to authorize in-world objects to communicate with Moodle
    // Part of the Sloodle project (www.sloodle.org)
    //
    // Copyright (c) 2006-7 Sloodle
    // Released under the GNU GPL
    //
    // Contributors:
    //    Edmund Edgar - original design and implementation
    //    Peter R. Bloomfield - cleaned and updated script
    //
    
    // This script should be accessed from a web-browser.
    // There are two modes of operation.
    // Confirmation mode, and auth mode.
    //
    // In confirmation mode, the user is asked to confirm that they want to authorise a certain object.
    // These are the standard parameters:
    //
    //   sloodleobjuuid = UUID of the object to be validated
    //   sloodleobjname = name of the object to be validated (optional)
    //   sloodlechannel = channel for the XMLRPC response to the object
    //
    // Auth mode is used after the user has confirmed that they want to authorise it.
    // The is where the authorisation as carried out.
    // The following additional parameter is required to use auth mode (in addition to those above):
    //
    //   auth = the status of the authorisation ('no' = denied, 'yes' = successful)
    //
    
    // Since the script is viewed in a browser, the response is HTML format
    
    require_once('../config.php');
    
    // Make sure the user is logged-in as an administrator
    // TODO: this will need to change to apply to individual virtual classroom instances (i.e. check for teacher status)
    require_login();
    if (!isadmin()) {
        error(get_string('needadmin', 'sloodle'));
        exit();
    }

    // This is a weird way of doing things
    if (isset($_REQUEST['sloodledebug'])) require_once(SLOODLE_DIRROOT.'/sl_debug.php');
    
    // Get our Sloodle classroom library
    require_once(SLOODLE_DIRROOT.'/lib/sl_classroomlib.php');
    
    // Check for our optional parameters
    $auth = optional_param('auth', '', PARAM_RAW);
    $auth = strtolower($auth);
    
    // Check the special case that the authorization has been cancelled
    if ($auth == 'no') {
        redirect($CFG->wwwroot, get_string('objectauthcancelled','sloodle'), 5);
        exit();
    }
    
    // Check for our required parameters
    $sloodleobjuuid = required_param('sloodleobjuuid', PARAM_RAW);
    $sloodleobjname = optional_param('sloodleobjname', '', PARAM_RAW);
    $sloodlechannel = required_param('sloodlechannel', PARAM_RAW);
    
    // Construct a breadcrumb navigation menu
    $nav = '';
    $nav .= "<a href=\"{$CFG->wwwroot}/admin/module.php?module=sloodle\">Sloodle</a> -> "; // Sloodle config page
    $nav .= get_string('objectauth','sloodle'); // This page
    
    // Display the page header
    print_header(get_string('objectauth','sloodle'), get_string('objectauth','sloodle'), $nav, '', '', FALSE);
    //print_heading(get_string('objectauth','sloodle'));
    
    // Check which mode we're in
    switch ($auth) {
    case 'yes':
        // Authorization confirmed - do it
        $userid = $USER->id;
        $result = sloodle_authorize_object($sloodleobjuuid, $sloodleobjname, $userid, $sloodlechannel);
        if ($result) {
            print_heading('<span style="color:green;">'.get_string('objectauthsent','sloodle').'</span>');
        } else {
            print_heading('<span style="color:red;">'.get_string('objectauthfailed','sloodle').'</span>');
        }
        break;
        
    default:
        // Authorization needs confirmed
        ?>
<div style="text-align:center;">
 <h4>
  <?php
   print_string('confirmobjectauth','sloodle');
   echo ' ';
   helpbutton('object_authorization',get_string('objectauth','sloodle'),'sloodle');
  ?>
 </h4>
 <p>
  <?php echo get_string('objectname','sloodle') .': '. $sloodleobjname; ?><br/>
  <?php echo get_string('objectuuid','sloodle') .': '. $sloodleobjuuid; ?>
 </p>
 <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <input type="hidden" name="sloodleobjuuid" value="<?php echo $sloodleobjuuid; ?>" />
  <input type="hidden" name="sloodleobjname" value="<?php echo $sloodleobjname; ?>" />
  <input type="hidden" name="sloodlechannel" value="<?php echo $sloodlechannel; ?>" />

<?php
if (defined('SLOODLE_DEBUG') && SLOODLE_DEBUG) {
   echo "<input type=\"hidden\" name=\"sloodledebug\" value=\"true\" />";
}
?>

  <input type="submit" name="auth" value="<?php print_string('Yes','sloodle'); ?>" />
  &nbsp;&nbsp;
  <input type="submit" name="auth" value="<?php print_string('No','sloodle'); ?>" />
 </form>
</div>
        <?php
        break;
    }
    

    print_footer();
    exit();
?>
