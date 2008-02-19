<?php

    /**
    * Sloodle module core library functionality.
    *
    * This script is required by Moodle to contain certain key functionality for the module.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */

// Process the configuration options for the Sloodle module
// $config is a reference to the submitted configuration settings
// We can perform validation and other processing here as necessary
function sloodle_process_options(&$config)
{
    global $CFG;

    // Determine the page which we should re-direct to if validation fails
    $redirect = $CFG->wwwroot . '/admin/module.php?module=sloodle';

    // This string will contain error codes to supply to the configuration page if validation fails
    $error_codes = "";

    // Make sure the prim password is valid
    $sloodle_pwd = $config->prim_password;
    // Is is an appropriate length?
    $len = strlen($sloodle_pwd);
    if ($len < 5) {
        $error_codes .= "&sloodlepwdshort=yes";
    } else if ($len > 9) {
        $error_codes .= "&sloodlepwdlong=yes";
    }
    // Is it numeric only?
    if (!ctype_digit($sloodle_pwd)) {
        $error_codes .= "&sloodlepwdnonnum=yes";
    }
    // Does it have a leading zero?
    if ($len >= 1 && substr($sloodle_pwd, 0, 1) == "0") {
        $error_codes .= "&sloodlepwdleadzero=yes";
    }
    
    // Is the flag for allowing teachers to edit user data valid?
    if (isset($config->allow_user_edit_by_teachers) == FALSE || ($config->allow_user_edit_by_teachers != 'true' && $config->allow_user_edit_by_teachers != 'false')) {
        // Force it to a default value
        $config->allow_user_edit_by_teachers = 'false';
    }

    // Is the auth method recognised?
    if (!($config->auth_method == "web" || $config->auth_method == "autoregister")) {
        $error_codes .= "&sloodleauthinvalid=yes";
    }

    // Were there any error messages?
    if (!empty($error_codes)) {
        // Append our parameters to the error codes string
        $error_codes .= "&sloodlepwd={$config->prim_password}&sloodleauth={$config->auth_method}";
    
        // Redirect back to the configuration page
        if (!headers_sent()) {
	    header("Location: " . $redirect . $error_codes . "&header_redirect=true");
            exit();
        }
        redirect($redirect . $error_codes . "&header_redirect=false", "There was an error in the configuration. Please try again.");
        exit();
    }
}


?>