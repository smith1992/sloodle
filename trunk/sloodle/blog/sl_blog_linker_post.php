<?php 

//We need to reference these files to use the existing Blog functions.

require_once('../config.php');
require_once($CFG->dirroot .'/blog/lib.php');
require_once('../locallib.php');

// Going to do own version of authentication here - as I'll be POSTING data
// instead of using GET
require_once('../login/sl_authlib.php'); // for authentication functions

	function post_sloodle_prim_require_script_authentication() {
	// Check the prim is allowed to talk to us.
	// Right now we're doing this using a password, but in future we may also want to let the administrator keep a list of authenticated object uuids.

		$pwd = htmlspecialchars($_POST['pwd']);
		
		$errors = array();

		if (preg_match('/^(.*?)\|(\d\d*)$/',$pwd, $matches)) {
			// first, see if we can validate based on a numerical code set for that object
			//$headers = apache_request_headers();
			//$objuuid = $headers['X-SecondLife-Object-Key'];
			$objuuid = $matches[1];
			$objpwd = $matches[2];
			$entry = get_record('sloodle_active_object','uuid',$objuuid);
			if ( ($entry->pwd != null) && ($entry->pwd == $objpwd) ) {
				return true;
			}
		}

		// if that fails, fall back on the legacy method of having a single pwd= string for all objects
		if ($pwd == null) {
			sloodle_prim_render_error('Prim password missing. Could not verify that the object sending this request was allowed to talk to me.');
			exit;
		} else if ($pwd != sloodle_prim_password()) {
			sloodle_prim_render_error('Sloodle Prim Password did not match the one set in the sloodle module configuration'.'objuuid was '.$objuuid." and pwd was $pwd and entry pwd was ".$entry->pwd);
			exit;
		}

		return true;

	}

	function post_sloodle_prim_require_user_login() {
	// Use the URL parameters to create a global USER object
	// Return an error if we fail.
		
		list($u,$errors) = post_sloodle_prim_user_login();
		if ($u == null) {
			sloodle_prim_render_errors($errors);
			exit;
		}

	}

	function post_sloodle_prim_user_login() {
	// Use the URL parameters to create a global USER object
	// Return the global USER object (or null) and an array of errors.

		global $USER;

		// If we can find got an avatar uuid, we'll use it.
		// Otherwise, use the avatar name.
		$uuid = htmlspecialchars($_POST['uuid']); //optional_param('uuid',null,PARAM_RAW);
		$avname = htmlspecialchars($_POST['avname']); //optional_param('avname',null,PARAM_RAW);

		$errors = array();

		$u = null;

		if ( ($uuid != null) && ($uuid != '') ) {
			$u = get_record('sloodle_users', 'uuid', $uuid);
		}

		if (!$u) {

			if ( ($avname != null) && ($avname != '') ) {
				$u = get_record('sloodle_users', 'avname', $avname);
			}
			if ($u) {
				// Must be the first time the user has come here through SL.
				// Stick their avatar uuid in the database.
				$u->uuid = $uuid;
				$u->avname = $avname;
				$updated = update_record('sloodle_users', $u);
				if (!$updated) {
					$errors[] = 'Could not update user table with avatar uuid';
				}
			} else {

				// not found - register them automatically, if set
				if (sloodle_is_automatic_registration_on()) {
					$ok = sloodle_register_user($uuid,$avname);
					$u = get_record('sloodle_users', 'uuid', $uuid);
				}

				$errors[] = 'User not found';
			}
		}

		// set global USER variable
		if ($u != null) {
			$USER = get_complete_user_data('id',$u->userid);
		}

		return array($USER,$errors);

	}


// Authentication checks
// to use URL like:
// http://www.sloodle.com/sl_blog_linker.php?subject=test1&summary=test2&uuid=d42ec4be-f746-429c-9b45-fae849792065&pwd=drUs3-9dE

// Is the prim signed? (does it have the correct password?)
	$pass = post_sloodle_prim_require_script_authentication();
	if (! $pass) {
		print 'password error<br>'; //debug output
		exit;
	}


// Authenticate the user
post_sloodle_prim_require_user_login();


// Here we set up the variables we are expecting to be passed.
	$sl_blog_subject = htmlspecialchars($_POST['subject']);  //required_param('subject', PARAM_RAW);  //Pass the Subject to the Blog - PHP will not run if we don't get this.
	$sl_blog_summary = htmlspecialchars($_POST['summary']);  //required_param('summary', PARAM_RAW);  // Pass the actual message - PHP will not run if we don't get this.
	
	//debug
	//echo 	$sl_blog_summary .'/n';

	// Use the HTTP headers added by SL to get the region and position data
	$region = $_SERVER['HTTP_X_SECONDLIFE_REGION'];
	$region = substr ( $region,0, strpos($region, '(' ) - 1 );
	$position = $_SERVER['HTTP_X_SECONDLIFE_LOCAL_POSITION'];
	sscanf($position, "(%f, %f, %f)", $x, $y, $z);
	$slurl = "http://slurl.com/secondlife/" .$region ."/" .$x ."/" .$y ."/" .$z;
	$slurl = '<a href="' .$slurl .'">' .$region .'</a>';
	// Ok so if we reach this point then all the variables have been passed from Second Life - Hurrah!

	$sl_blog_subject = addslashes(clean_text(stripslashes($sl_blog_subject), FORMAT_MOODLE));  // Strip bad tags from the subject.
	$sl_region = addslashes(clean_text(stripslashes($region), FORMAT_MOODLE));
	$sl_blog_summary = addslashes(clean_text(stripslashes($sl_blog_summary), FORMAT_MOODLE));  // Strip bad tags from the message.
	
	$sl_blog_summary = "Posted from Second Life: " .$slurl ."\n\n" .$sl_blog_summary;
	
//Debugging echos	
//	echo $sl_blog_subject;
//	echo $sl_blog_summary;
//	echo $sl_blog_userid;


//Now we check to see if the user account actually exists!
//    if (!$blogger = get_record('user', 'id', $sl_blog_userid)) {
//        error('This user does not exist!!.'); //PA: This Uses Moodle's built in Error handler - :o)
//	    }

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
        	$blogEntry->userid = $USER->id;  // was: $sl_blog_userid;
        	$blogEntry->format = 1;
        	$blogEntry->publishstate = 'draft';
        	$blogEntry->lastmodified = time();
        	$blogEntry->created = time();

        	// Insert the new blog entry.
       		 $entryID = insert_record('post',$blogEntry);

        	//Confirm table input
		print 'success';
       		//print 'Debug: created a new entry - entryId = '.$entryID.'<br />';
        	//print 'Subject: '.$sl_blog_subject.'<br />'; 
		//print 'Post: '.$sl_blog_summary.'<br />'; 
		//print 'For user ID '.$USER->id.'<br />'; 
	}
?>
