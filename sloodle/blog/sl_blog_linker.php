<?php 

//We need to reference these files to use the existing Blog functions.

require_once($CFG->dirroot .'config.php');
//include_once('../../../blog/lib.php'); //lib.php file from moodle/mod/blog.
//include_once('lib.php'); //lib.php file from moodle/mod/blog.
require_once($CFG->dirroot .'/blog/lib.php');

// Here we set up the variables we are expecting to be passed.
	
	$sl_blog_subject = required_param('subject', PARAM_RAW);  //Pass the Subject to the Blog - PHP will not run if we don't get this.
	$sl_blog_summary = required_param('summary', PARAM_RAW);  // Pass the actual message - PHP will not run if we don't get this.
	
	// Later on this line will change to accept the UUID and then pull out the Moodle ID from mdl_sloodle_users
	// For now we just pass the Moodle user ID.
	
	$sl_blog_userid = required_param('user_id', PARAM_INT);  // Pass the actual message - PHP will not run if we don't get this.
	
	
// Ok so if we reach this point then all the variables have been passed from Second Life - Hurrah!

	$sl_blog_subject = addslashes(clean_text(stripslashes($sl_blog_subject), FORMAT_MOODLE));  // Strip bad tags from the subject.
	$sl_blog_summary = addslashes(clean_text(stripslashes($sl_blog_summary), FORMAT_MOODLE));  // Strip bad tags from the message.
	
//Debugging echos	
//	echo $sl_blog_subject;
//	echo $sl_blog_summary;
	echo $sl_blog_userid;

//Now we check to see if the user account actually exists!

// PA: This added for version 2 - it requires the Chat bot account be logged in to Moodle AND the chat room before it will log chat - this stops spamming.  Removed for the demo.


    if (!$blogger = get_record('user', 'id', $sl_blog_userid)) {
        error('This user does not exist!!.'); //PA: This Uses Moodle's built in Error handler - :o)
	    }

// Now lets paste in the post function as defined in blog\edit.php
// Bit's we don't need are edited out.

//function do_save($post) {
//    global $USER, $CFG, $referrer;
//    echo 'Debug: Post object in do_save function of edit.php<br />'; //debug
//    print_object($post); //debug

    if ($sl_blog_summary == '') 
    { // This will check that there IS a post to make!
        $post->error =  get_string('nomessagebodyerror', 'blog');
    } else {

        	/// Write a blog entry into database
        	$blogEntry = new object;
        
        	$blogEntry->subject = $sl_blog_subject;
        	$blogEntry->summary = $sl_blog_summary;
        	$blogEntry->module = 'blog';
        	$blogEntry->userid = $sl_blog_userid;
        	$blogEntry->format = 1;
        	$blogEntry->publishstate = 'draft';
        	$blogEntry->lastmodified = time();
        	$blogEntry->created = time();

        	// Insert the new blog entry.
       		 $entryID = insert_record('post',$blogEntry);

        	//Confirm table input
       		print 'Debug: created a new entry - entryId = '.$entryID.'<br />';
        	print 'Subject: '.$sl_blog_subject.'<br />'; 
			print 'Post: '.$sl_blog_summary.'<br />'; 
			print 'For user ID '.$sl_blog_userid.'<br />'; 
			}
?>
