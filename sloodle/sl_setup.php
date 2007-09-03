<?php

	require_once('config.php');
	require_once('locallib.php');

	print_header(get_string("sloodlesetup", "sloodle"), '', '', '', false, '', '', false, '');
	print_heading(get_string("sloodlesetup", "sloodle"));

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
				<h3>' . get_string("setsetup:header", "sloodle") . '</h3>
				<p>' . get_string("setsetup:body1", "sloodle") . '</p>
               <p>' . get_string("setsetup:body2", "sloodle") . ': <a href="sl_setup_notecard.php">' . get_string("createnotecard", "sloodle") . '</a></p>
               <p>' . get_string("setsetup:body3", "sloodle") . '</p>
			';

		if ( (sloodle_prim_password() == null) || (sloodle_prim_password() == '') ) {
			$str .= '
				<h3>' . get_string("primpass:set", "sloodle") . '</h3>
				<p>' . get_string("primpass:setdesc", "sloodle") . '</p>
				<form action="sl_setup.php" method="post"><input size="40" maxlength="40" type="text" name="sloodle_pwd" value=""/><input type="submit" value="' . get_string("primpass:save", "sloodle") . '" /></form>
			';
		} else {
			if ($show_sloodle_pwd != null) {
				$str .= '
					<h3>' . get_string("primpass:change", "sloodle") . '</h3>
					<p>' . get_string("primpass:changedesc", "sloodle") . '</p>
					<form action="sl_setup.php" method="post"><input size="40" maxlength="40" type="text" name="sloodle_pwd" value="'.sloodle_prim_password().'"/><input type="submit" value="' . get_string("primpass:save", "sloodle") . '" /></form>

				';
			} else {
				$str .= '
					<h3>' . get_string("primpass:isset", "sloodle") . '</h3>
					<p>' . get_string("primpass:issetdesc", "sloodle") . '
                   <a href="sl_setup.php?showpwd=1">' . get_string("clickchangeit", "sloodle") . '</a>.</p>
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
			<form action="sl_setup.php" method="post">
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
		print_simple_box(get_string("needadmin", "sloodle"), "center");
	}

	if (!isadmin() && SLOODLE_ALLOW_NORMAL_USER_ACCESS_TO_ADMIN_FUNCTIONS_FOR_TESTING) {
		print_simple_box(get_string("wouldneedadmin", "sloodle"), "center");
	}

	print_footer();

	exit;


?>
