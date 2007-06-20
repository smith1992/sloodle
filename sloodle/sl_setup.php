<?php

	require_once('config.php');
	require_once('locallib.php');

	print_header('Sloodle Setup', '', '', '', false, '', '', false, '');
	print_heading('Sloodle Setup');

	require_login();
	if (isadmin() || SLOODLE_ALLOW_NORMAL_USER_ACCESS_TO_ADMIN_FUNCTIONS_FOR_TESTING) {

		$sloodle_auth_method = optional_param('sloodle_auth_method',null,PARAM_RAW);

		$sloodle_pwd = optional_param('sloodle_pwd',null,PARAM_RAW);
		$show_sloodle_pwd = optional_param('showpwd',null,PARAM_RAW);
		if ($sloodle_pwd != null) {
			// set the sloodle password
			$result = sloodle_set_config('SLOODLE_PRIM_PASSWORD',$sloodle_pwd);
			
		}

		if ($sloodle_auth_method != null) {
			$result = sloodle_set_config('SLOODLE_AUTH_METHOD',$sloodle_auth_method);
		}

		$sloodle_auth_method = sloodle_get_config('SLOODLE_AUTH_METHOD');

		$str = '
				<h3>Setup a "Sloodle Set object.</h3>
				<p>Sloodle objects in Second Life need to be configured so that they know which server to talk to, which course to use and how to prove to the server that it has permission to talk to it.</p><p><a href="sl_setup_notecard.php">Click here</a> to create a configuration notecard to put in a "Sloodle Set" object.</p><p>The "Sloodle Set" object can then be used to rez other objects with the same configuration.</p>
			';

		if ( (sloodle_prim_password() == null) || (sloodle_prim_password() == '') ) {
			$str .= '
				<h3>Set Prim Password Number</h3>
				<p>You need to set a password that your Second Life objects will use to talk to Moodle. This should be a 9-digit number.</p>
				<form action="sl_setup.php" method="post"><input size="40" maxlength="40" type="text" name="sloodle_pwd" value=""/><input type="submit" value="Save Prim Password" /></form>
			';
		} else {
			if ($show_sloodle_pwd != null) {
				$str .= '
					<h3>Change Prim Password</h3>
					<p>If you change this password, you will need to update the scripts in all your Second Life objects that use it.</p>
					<form action="sl_setup.php" method="post"><input size="40" maxlength="40" type="text" name="sloodle_pwd" value="'.sloodle_prim_password().'"/><input type="submit" value="Save Prim Password" /></form>

				';
			} else {
				$str .= '
					<h3>Prim Password is set.</h3>
					<p>Your prim password will be automatically included in your LSL scripts. <a href="sl_setup.php?showpwd=1">Click here to change it</a>.</p>
				';
			}

			$webCheckedFlag = ' checked';
			$authCheckedFlag = '';
			if ($sloodle_auth_method == 'autoregister') {
				$webCheckedFlag = '';
				$authCheckedFlag = ' checked';
			}
			$str = $str.'
			<h3>User Authentication</h3>

			<p>What should Sloodle objects do when they meet an avatar they haven\'t seen before?<br />
			<form action="sl_setup.php" method="post">
			<table border="0">
				<tr>
					<td>
						<input type="radio" name="sloodle_auth_method"'.$webCheckedFlag.' value="web"/>
					</td>
					<td>
						Send avatars to a web page and make them login or register there.<br />
					</td>
				<tr>
				<tr>
					<td>
						<input type="radio" name="sloodle_auth_method"'.$authCheckedFlag.' value="autoregister"/>
					</td>
					<td>
						Automatically register them as a new user in Moodle.
					</td>
				</tr>
				<tr>
					<td>
						&nbsp;
					</td>
					<td>
						Allowing automatic registration may conflict with your usual Moodle administration policies, and may not work properly with some authentication methods.<br />
					</td>
				<tr>


				<tr>
					<td>
						&nbsp;
					</td>
					<td>
						<input type="submit" value="Save User Authentication Settings" />
					</td>
				<tr>




			</table>
			</form>
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
