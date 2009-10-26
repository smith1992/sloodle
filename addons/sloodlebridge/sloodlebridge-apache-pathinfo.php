<?php
// SLOODLE Bridge for Apache, using PathInfo.
// This script should help bypass SSL (https) certificate problems which can prevent SLOODLE from interacting with Moodle.

// It is only designed to work on servers running Apache, which have AcceptPathInfo enabled.
// An alternative approach is to use Apache's mod_rewrite instead of PATH_INFO.

// This script is released under the GNU General Public Licence v3.
// Author: Peter R. Bloomfield



///// CONFIGURATION /////

// Define the web address of the Moodle installation.
// The is the address you would normally provide to SLOODLE in-world.
// Include the "https://" at the start.
$sloodleserverroot = "";

// Set this to FALSE if you want to skip the compatibility check. (Default: TRUE)
// This is only useful if you KNOW the script will work, but the compatibility check reports a false negative.
$checkcompatibility = TRUE;

///// END CONFIGURATION /////

// We only want to bridge connections coming from scripts trying to access data.
// Users browsing should be redirected to the proper address.
// This is not really a security concern, since it is easy to change the user agent reported by a browser.
// Rather, it is to allow users to be forwarded to specific Moodle pages by SLOODLE.
// It is not perfect, but will hopefully be sufficient.

// NOTE: even redirecting browsers will not work if PathInfo is not available

// Define the user-agents (source of HTTP requests) for which we will provide a bridge.
// Each entry can be just a prefix (e.g. everything up to a /, which would typically omit version info)
$bridgeuseragents = array();
$bridgeuseragents[] = "Second Life LSL";

// Should we provide a bridge?
$bridgeconnection = false;
global $bridgeconnection;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    foreach ($bridgeuseragents as $agent) {
        if (strpos($_SERVER['HTTP_USER_AGENT'], $agent) === 0) {
            $bridgeconnection = true;
        }
    }
}

// Make sure the Moodle address has been specified.
if (empty($sloodleserverroot)) error("This SLOODLE Bridge script has not yet been configured. Please edit the PHP script to specify the Moodle address in the \"sloodleserverroot\" variable.");


// This function will be used to report an error message and exit.
// The format of the error message depends the user agent.
function error($msg)
{
    global $bridgeconnection;
    // Is it a script requesting the data?
    if ($bridgeconnection) {
        if (headers_sent()) header("Content-Type:text/plain; Charset=UTF-8");
        exit("-1|SLOODLEBRIDGE\n".$msg);
    } else {
        exit("SLOODLE Bridge Error: ".$msg);
    }
}

// Compatibility check
$iscompatible = 0;
if ($checkcompatibility) {
    // Make sure we're running Apache
    if (apache_get_version() == FALSE) error("Your server does appear to be running Apache, so the SLOODLE Bridge for Apache will not work.");

    // Check the AcceptPathInfo setting
    if (!isset($_SERVER['PATH_INFO'])) error("The PATH_INFO is not available. Either enabled the AcceptPathInfo setting in your Apache configuration, or use the 'mod_rewrite' version of the SLOODLE Bridge. NOTE: if you are testing this script in your browser, then make sure you add '/_test_' to the end of the URL, after the '.php'.");

    // Check to see if the cURL extension is not yet loaded
    if (!extension_loaded('curl')) {
        error("The cURL extension for PHP is not loaded. The SLOODLE Bridge for Apache cannot work without this extension. Contact your server administrator about enabling / installing this extension.");
    }

    $iscompatible = 1;
} else {
    $iscompatible = -1; // unknown compatibility
}

// Take all the extra path info as our relative path within the Moodle installation.
// (Possible security step: only allow access to the SLOODLE installation?)
$path = '/';
if (isset($_SERVER['PATH_INFO'])) $path = $_SERVER['PATH_INFO'];
// Check if this was just a test.
if ($path == '/_test_') {
    header("Content-Type:text/plain; Charset=UTF-8");
    echo "\n\n*** SLOODLE Bridge for Apache (using PathInfo) ***\n";
    if ($iscompatible == 1) echo "Compatibility test passed.";
    else if ($iscompatible == 0) echo "Compatibility test failed.";
    else echo "Compatibility unknown.";
    exit();
}


// Construct our full URL
$url = $sloodleserverroot.$path;

// Take any GET data and append it to the url
if (isset($_GET) && count($_GET) > 0) {
    $url .= '?';
    $isfirst = true;
    foreach ($_GET as $getKEY => $getVALUE) {
        if ($isfirst) $isfirst = false;
        else $url .= '&';
        $url .= $getKEY.'='.$getVALUE;
    }
}

//echo "Fetching URL: {$url}\n\n";

// Redirect the connection if we are not bridging it
if (!$bridgeconnection) {
    if (!headers_sent()) header("Location: {$url}");
    exit("Please follow this link to continue: <a href=\"{$url}\">{$url}</a>");
}


// Setup cURL to fetch the resource
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

// Add any POST data which has been provided
if (isset($_POST) && count($_POST) > 0) {
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
}

// Execute the request
$response = curl_exec($ch);
// Check the HTTP status code of the response
$httpstatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($httpstatus > 200) {
    header("HTTP/1.0 {$httpstatus}");
}
curl_close($ch);

// If the execution of the request failed, then report an error
if (!is_string($response)) error("-1|SLOODLEBRIDGE\nThe SLOODLE Bridge script failed to access the requested resource.");

echo $response;

?>