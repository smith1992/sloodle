<?php

    //session_name('linkertest');
    //session_start();
    
    require_once('sl_config.php');

    // If this page has been POSTed then convert data to SESSION values and reload
    if (!empty($_POST['submit']))
    {
        foreach ($_POST as $postName => $postValue)
        {
            $_SESSION['formval_'.$postName] = $postValue;
        }
        header("Location: ".SLOODLE_WWWROOT."/linker-test.php");
        exit();
    }
    
    function get_session_val($name)
    {
        if (isset($_SESSION[$name])) return $_SESSION[$name];
        return '';
    }
    
    // Get our default expected values
    $target = get_session_val('formval_target');
    if (empty($target)) $target = SLOODLE_WWWROOT.'/';
    
    $authToken = get_session_val('formval_auth_token');
    $adminToken = get_session_val('formval_admin_token');
    $objectUUID = get_session_val('formval_object_uuid');
    $objectName = get_session_val('formval_object_name');
    $ownerUUID = get_session_val('formval_owner_uuid');
    $ownerName = get_session_val('formval_owner_name');
    
    $params = array();
    
    for ($i = 0; $i < 15; $i++)
    {
        $name = get_session_val('formval_param_name_'.$i);
        $value = get_session_val('formval_param_value_'.$i);
        
        if (!empty($name))
        {
            $params[] = array($name, $value);
        }
    }
    
    // Has form data been submitted?
    if (!empty($_SESSION['formval_submit']))
    {
        // Yes - make sure we don't reprocess it
        unset($_SESSION['formval_submit']);
        
        // Construct the parameter string
        $paramString = '';
        $isFirstParam = true;
        foreach ($params as $p)
        {
            if (!empty($p))
            {
                // Add the ampersand delimiter between parameters
                if ($isFirstParam) $isFirstParam = false;
                else $paramString .= '&';
                // Add this parameter
                $paramString .= $p[0].'='.$p[1];
            }
        }
        
        // Construct an array of header values
        $headers = array();
        $headers[] = 'X-SecondLife-Object-Key:'.$objectUUID;
        $headers[] = 'X-SecondLife-Object-Name:'.$objectName;
        $headers[] = 'X-SecondLife-Owner-Key:'.$ownerUUID;
        $headers[] = 'X-SecondLife-Owner-Name:'.$ownerName;
        $headers[] = 'X-SLOODLE-Auth-Token:'.$authToken;
        $headers[] = 'X-SLOODLE-Admin-Token:'.$adminToken;
        
        // Create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $target);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $paramString);
        

        // grab URL and pass it to the browser
        $response = curl_exec($ch);
        $responseTimestamp = time();
        
        // Output the result
        echo "<h2>Linker Test Response</h2><em>Response timestamp: {$responseTimestamp}</em><br/><pre style=\"border:solid 1px black;padding:4px;\">";
        if ($response === false)
        {
            echo "An error occurred while requesting the linker script.\n\n";
            echo curl_error($ch);
        } else {
            echo $response;
        }
        echo "</pre>";
        
        // close cURL resource, and free up system resources
        curl_close($ch);
    }
    
?>

<script type="text/javascript">
function selectStandardTarget()
{
    try
    {
        var elemTarget = document.getElementById('target');
        var elemList = document.getElementById('select_target');
        
        if (elemList.value != "")
        {
            elemTarget.value = "<?php echo SLOODLE_WWWROOT; ?>/mod" + elemList.value;
        }
    }
    catch(e)
    {
        alert("Failed to get elements from DOM.");
    }
}
</script>

<h2>Linker Test Input</h2>
<form method="post" action="">
<fieldset>

<label for="target">Target: </label>
<input type="text" name="target" id="target" value="<?php echo $target; ?>" size="100" maxlength="255" />&nbsp;

<select onchange="selectStandardTarget();" id="select_target">
 <option selected="selected" disabled="disabled">Select a standard linker...</option>
 <option value="/chat-1.0/linker.php">chat-1.0</option>
 <option value="/choice-1.0/linker.php">choice-1.0</option>
 <option value="/distributor-1.0/linker.php">distributor-1.0</option>
 <option value="/glossary-1.0/linker.php">glossary-1.0</option>
 <option value="/presenter-2.0/linker.php">presenter-2.0</option>
 <option value="/primdrop-1.0/linker.php">primdrop-1.0</option>
 <option value="/quiz-1.0/linker.php">quiz-1.0</option>
</select>

<br/><br/>

<label for="auth_token">Auth Token: </label>
<input type="text" name="auth_token" id="auth_token" value="<?php echo $authToken; ?>" size="50" maxlength="255" /><br/>
<label for="auth_token">Admin Token: </label>
<input type="text" name="admin_token" id="admin_token" value="<?php echo $adminToken; ?>" size="50" maxlength="255" /><br/><br/>

<label for="auth_token">Object UUID: </label>
<input type="text" name="object_uuid" id="object_uuid" value="<?php echo $objectUUID; ?>" size="36" maxlength="255" /><br/>
<label for="auth_token">Object Name: </label>
<input type="text" name="object_name" id="object_name" value="<?php echo $objectName; ?>" size="36" maxlength="255" /><br/>
<label for="auth_token">Owner UUID: </label>
<input type="text" name="owner_uuid" id="owner_uuid" value="<?php echo $ownerUUID; ?>" size="36" maxlength="255" /><br/>
<label for="auth_token">Owner Name: </label>
<input type="text" name="owner_name" id="owner_name" value="<?php echo $ownerName; ?>" size="36" maxlength="255" /><br/><br/>

<h3>Parameters</h3>
<strong>Name : Value</strong><br/>
<?php
    for ($i = 0; $i < 15; $i++)
    {
        if (!isset($params[$i][0])) $params[$i][0] = '';
        if (!isset($params[$i][1])) $params[$i][1] = '';
        echo "<input type=\"text\" name=\"param_name_{$i}\" id=\"param_name_{$i}\" value=\"{$params[$i][0]}\" size=\"20\" maxlength=\"255\"> : ";
        echo "<input type=\"text\" name=\"param_value_{$i}\" id=\"param_value_{$i}\" value=\"{$params[$i][1]}\" size=\"40\" maxlength=\"255\"><br/>\n";
    }
?>

<br/>
<input type="submit" name="submit" id="submit" value="Submit" /><br/>

</fieldset>
</form>
