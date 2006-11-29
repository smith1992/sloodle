<?php

	require_once('config.php');
	require_once('locallib.php');

	print_header('Sloodle Setup', '', '', '', false, '', '', false, '');
	print_heading('Sloodle Setup');

	require_login();
	if (isadmin() || SLOODLE_ALLOW_NORMAL_USER_ACCESS_TO_ADMIN_FUNCTIONS_FOR_TESTING) {

		$sloodle_pwd = optional_param('sloodle_pwd',null,PARAM_RAW);
		if ($sloodle_pwd != null) {
			// set the sloodle password
			$result = sloodle_set_config('SLOODLE_PRIM_PASSWORD',$sloodle_pwd);
			
		}

		if ( (sloodle_prim_password() == null) || (sloodle_prim_password() == '') ) {
			$str = '
				<h3>Set Prim Password</h3>
				<p>You need to set a password that your Second Life objects will use to talk to Moodle.</p>
				<form action="sl_setup.php" method="post"><input size="40" maxlength="40" type="text" name="sloodle_pwd" /><input type="submit" value="Save Prim Password" /></form>
			';
		} else {

		$str = '
			<h3>Authentication</h3>
			<p>The following pages show LSL scripts ready for you to copy and paste into objects in Second Life. Details that need to be configured, like the URL of your Moodle installation, will be included in the scripts automatically.</p>
			<ul>
				<li><a href="login/sl_loginzone_setup.php">Login Zone</a> - A script to create a prim above your sim to allow users to click a Second Life URL link in Moodle and be automatically recognized in Second Life.</li>
				<li><a href="login/sl_signup_setup.php">Signup</a> - A script to create a prim which gives a user a URL to click taking them to the Moodle login or registration page. Once signed in, they will be automatically recognized in Second Life.</li>
				<li><a href="login/sl_userinfo_setup.php">User Info</a> - A script to create a prim which fetches some basic information from Moodle (name, picture) and display it in Second Life.</li>
			</ul>
		';
		}
		print_simple_box($str, "center");
	} else {
		//print_simple_box('You need admin privileges to access this page.', "center");
		print_simple_box('You would need admin privileges to access this page.', "center");
	}

	if (!isadmin() && SLOODLE_ALLOW_NORMAL_USER_ACCESS_TO_ADMIN_FUNCTIONS_FOR_TESTING) {
		print_simple_box('You would normally need admin privileges to access this page, but I\'ve let you in, since it\'s a demo.', "center");
	}

	print_footer();

	exit;


?>
