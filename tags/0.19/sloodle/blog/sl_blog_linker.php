<?php 
// Sloodle blog linker
// Allows the Sloodle Toolbar object in Second Life to write to a user's Moodle blog
// See www.sloodle.org for more information.

// This script is expected to be accessed with the following parameters (GET or POST):
// subject = the subject line of the blog entry
// summary = the main body of the blog entry
// uuid = the SL UUID of the agent making the blog entry
// pwd = the prim password used to authenticate the blog toolbar for this site

require_once('../config.php'); // Moodle/Sloodle configuration
require_once($CFG->dirroot .'/blog/lib.php'); // Moodle blog functionality
require_once('../locallib.php'); // General Sloodle functionality
require_once('../login/sl_authlib.php'); // Sloodle authentication functions


// Ensure the prim password is valid
// (The script is automatically terminated with an error message if this fails)
sloodle_prim_require_script_authentication();
// Authenticate the user
sloodle_prim_require_user_login();

// Obtain the raw parameters for the blog entry
$sl_blog_subject = required_param('subject', PARAM_RAW);
$sl_blog_summary = required_param('summary', PARAM_RAW);

// Use the HTTP headers added by SL to get the region and position data
$region = $_SERVER['HTTP_X_SECONDLIFE_REGION'];
$region = substr ( $region,0, strpos($region, '(' ) - 1 );
$position = $_SERVER['HTTP_X_SECONDLIFE_LOCAL_POSITION'];
sscanf($position, "(%f, %f, %f)", $x, $y, $z);
$slurl = "http://slurl.com/secondlife/" .$region ."/" .$x ."/" .$y ."/" .$z;
$slurl = '<a href="' .$slurl .'">' .$region .'</a>';
// Ok so if we reach this point then all the variables have been passed from Second Life - Hurrah!

// Make all string data safe
$sl_blog_subject = addslashes(clean_text(stripslashes($sl_blog_subject), FORMAT_MOODLE));
$sl_region = addslashes(clean_text(stripslashes($region), FORMAT_MOODLE));
$sl_blog_summary = addslashes(clean_text(stripslashes($sl_blog_summary), FORMAT_MOODLE));
// Construct the final blog body	
$sl_blog_summary = "Posted from Second Life: " .$slurl ."\n\n" .$sl_blog_summary;


if ($sl_blog_summary == '') {
    // This will check that there IS a post to make!
    $post->error =  get_string('nomessagebodyerror', 'blog');
} else {
    // Write a blog entry into database
    $blogEntry = new object;
    $blogEntry->subject = $sl_blog_subject;
    $blogEntry->summary = $sl_blog_summary;
    $blogEntry->module = 'blog';
    $blogEntry->userid = $USER->id;
    $blogEntry->format = 1;
    $blogEntry->publishstate = 'site'; // 'draft' or 'site' or 'public'
    $blogEntry->lastmodified = time();
    $blogEntry->created = time();
    // Insert the new blog entry, making sure it is successful
    if ($entryID = insert_record('post',$blogEntry)) {
       print 'success';
    } else {
       print 'error';
    }
}
?>
