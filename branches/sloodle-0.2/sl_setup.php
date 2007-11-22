<?php

	require_once('config.php');
	require_once('locallib.php');

	print_header(get_string('sloodlesetup', 'sloodle'), '', '', '', false, '', '', false, '');
	print_heading(get_string('sloodlesetup', 'sloodle'), 'center', 1);
	print_heading(get_string('sloodleversion', 'sloodle').': '.(string)SLOODLE_VERSION, 'center', 4);

	require_login();
	if (isadmin() || SLOODLE_ALLOW_NORMAL_USER_ACCESS_TO_ADMIN_FUNCTIONS_FOR_TESTING) {
    
        // Make sure Sloodle is properly installed
        if (sloodle_is_installed()) {
            // Seems to be installed OK...
        
		// Get the HTTP parameters
    		$sloodle_auth_method = optional_param('sloodle_auth_method',null,PARAM_RAW);
    		$sloodle_pwd = optional_param('sloodle_pwd',null,PARAM_RAW);
    		$show_sloodle_pwd = optional_param('showpwd',null,PARAM_RAW);

		// Perform error-checks on the new prim password
		if (empty($sloodle_pwd) == false || $sloodle_pwd == "0") {
			$pwd_error_msg = "";
			// Is is an appropriate length?
			$len = strlen($sloodle_pwd);
			if ($len < 5) {
				$pwd_error_msg .= get_string('primpass:tooshort', 'sloodle') . "<br/>";
			} else if ($len > 9) {
				$pwd_error_msg .= get_string('primpass:toolong', 'sloodle') . "<br/>";
			}
			// Is it numeric only?
			if (!ctype_digit($sloodle_pwd)) {
				$pwd_error_msg .= get_string('primpass:numonly', 'sloodle') . "<br/>";
			}
			// Does it have a leading zero?
			if ($len >= 1 && substr($sloodle_pwd, 0, 1) == "0") {
				$pwd_error_msg .= get_string('primpass:leadingzero', 'sloodle') . "<br/>";
			}

			// Were there any error messages?
			if (!empty($pwd_error_msg)) {
				// Reset the password variable
				$sloodle_pwd = '';
				// Display the message(s)
				print_simple_box("<b>".get_string('primpass:error','sloodle')."</b><br/>".$pwd_error_msg);
			}
		}

		// Has a new prim password been specified?
    		if (empty($sloodle_pwd)) {
			// No - do we already have one in the database?
			$sloodle_pwd = sloodle_get_config('SLOODLE_PRIM_PASSWORD');
			if (empty($sloodle_pwd)) {
				// Pick one at random
				$sloodle_pwd = (string)mt_rand(100000000, 999999999);
				sloodle_set_config('SLOODLE_PRIM_PASSWORD', $sloodle_pwd);
				print_simple_box(get_string('primpass:random','sloodle'));
			}
		} else {
    			// Store the new password
    			$result = sloodle_set_config('SLOODLE_PRIM_PASSWORD',$sloodle_pwd);
			if ((int)$result > 0) {
				print_simple_box(get_string('primpass:updated','sloodle'));
			}
    		}

		// Has a new auth method been specified?
    		if (empty($sloodle_auth_method)) {
			// No - do we already have on in the database?
			$sloodle_auth_method = sloodle_get_config('SLOODLE_AUTH_METHOD');
			if (empty($sloodle_auth_method)) {
				// No - use a default
				$sloodle_auth_method = 'web';
				sloodle_set_config('SLOODLE_AUTH_METHOD', $sloodle_auth_method);
				print_simple_box(get_string('authmethod:default','sloodle'));
			}
		} else {
			// Store the new auth method
    			$result = sloodle_set_config('SLOODLE_AUTH_METHOD',$sloodle_auth_method);
			if ((int)$result > 0) {
				print_simple_box(get_string('authmethod:updated','sloodle'));
			}
    		}

    		$str = '
    				<h3>' . get_string("setsetup:header", "sloodle") . '</h3>
    				<p>' . get_string("setsetup:body1", "sloodle") . '</p>
                   <p>' . get_string("setsetup:body2", "sloodle") . ': <a href="sl_setup_notecard.php">' . get_string("createnotecard", "sloodle") . '</a></p>
                   <p>' . get_string("setsetup:body3", "sloodle") . '</p>
    			';

    		if ( (sloodle_prim_password() == null) || (sloodle_prim_password() == '') ) {
    			$str .= '
    				<h3>' . get_string("primpass:set", "sloodle") . '</h3>
    				<p>' . get_string("primpass:setdesc", "sloodle") . '</p>
    				<form action="'.$_SERVER["PHP_SELF"].'" method="post"><input size="40" maxlength="40" type="text" name="sloodle_pwd" value=""/><input type="submit" value="' . get_string("primpass:save", "sloodle") . '" /></form>
    			';
    		} else {
    			if ($show_sloodle_pwd != null) {
    				$str .= '
    					<h3>' . get_string("primpass:change", "sloodle") . '</h3>
    					<p>' . get_string("primpass:changedesc", "sloodle") . '</p>
    					<form action="'.$_SERVER["PHP_SELF"].'" method="post"><input size="40" maxlength="40" type="text" name="sloodle_pwd" value="'.sloodle_prim_password().'"/><input type="submit" value="' . get_string("primpass:save", "sloodle") . '" /></form>

    				';
    			} else {
    				$str .= '
    					<h3>' . get_string("primpass:isset", "sloodle") . '</h3>
    					<p>' . get_string("primpass:issetdesc", "sloodle") . '
                       <a href="'.$_SERVER["PHP_SELF"].'?showpwd=1">' . get_string("clickchangeit", "sloodle") . '</a>.</p>
    				';
    			}

    			$webCheckedFlag = ' checked';
    			$authCheckedFlag = '';
    			if ($sloodle_auth_method == 'autoregister') {
    				$webCheckedFlag = '';
    				$authCheckedFlag = ' checked';
    			}
    			$str = $str.'
    			<h3>' . get_string("userauth:header", "sloodle") . '</h3>

    			<p>' . get_string("userauth:desc", "sloodle") . '<br />
    			<form action="'.$_SERVER["PHP_SELF"].'" method="post">
    			<table border="0">
    				<tr>
    					<td>
    						<input type="radio" name="sloodle_auth_method"'.$webCheckedFlag.' value="web"/>
    					</td>
    					<td>
    						' . get_string("userauth:sendtopage", "sloodle") . '
    					</td>
    				<tr>
    				<tr>
    					<td>
    						<input type="radio" name="sloodle_auth_method"'.$authCheckedFlag.' value="autoregister"/>
    					</td>
    					<td>
    						' . get_string("userauth:autoreg", "sloodle") . '
    					</td>
    				</tr>
    				<tr>
    					<td>
    						&nbsp;
    					</td>
    					<td>
                       	<i>' . get_string("userauth:autoregnote", "sloodle") . '</i><br />
    					</td>
    				<tr>


    				<tr>
    					<td>
    						&nbsp;
    					</td>
    					<td>
                       	<br/>
    						<input type="submit" value="' . get_string("userauth:save", "sloodle") . '" />
    					</td>
    				<tr>




    			</table>
    			</form>
';
    		}
    		print_simple_box($str, "center");
        
        } else {
            // Sloodle does not appear to be installed
            print_simple_box(get_string("sloodlenotinstalled", "sloodle") . " <a href=\"" . $CFG->wwwroot . "/admin\">" . get_string("moodleadminindex", "sloodle") . "</a>");
        }
        
	} else {
		print_simple_box(get_string("needadmin", "sloodle"), "center");
	}

	if (!isadmin() && SLOODLE_ALLOW_NORMAL_USER_ACCESS_TO_ADMIN_FUNCTIONS_FOR_TESTING) {
		print_simple_box(get_string("wouldneedadmin", "sloodle"), "center");
	}

	print_footer();

	exit;


?>
