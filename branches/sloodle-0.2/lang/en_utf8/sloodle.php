<?php
// This is the English language file for Sloodle.
// It is included automatically by the Moodle framework.
// Retrieve strings using the Moodle get_string or print_string functions.

$string['authmethod:default'] = 'User authentication method automatically set to default.';
$string['authmethod:invalid'] = 'Invalid user authentication method. Please re-select.';
$string['authmethod:updated'] = 'User authentication method updated';

$string['backtosloodlesetup'] = 'Back to the Sloodle Setup page';

$string['cfgnotecard:header'] = 'Sloodle Configuration Notecard';
$string['cfgnotecard:choosecourse'] = 'Select which course you would like to configure your Sloodle objects to communicate with:';
$string['cfgnotecard:paste'] = 'Paste the following in the sloodle_config notecard in your Sloodle objects.'; //Deprecated
$string['cfgnotecard:generate'] = 'Generate notecard text';
$string['cfgnotecard:instructions'] = 'To configure a Sloodle object, edit or create a notecard called \'config_notecard\' in its inventory, and add the text from the box below.';
$string['cfgnotecard:security'] = 'For security reasons, you should make sure that the \'sloodle_config\' notecard *and* the object itself cannot be modified by the next owner.';
$string['cfgnotecard:inworld'] = '* If you prefer to configure your object in-world, just delete or rename the sloodle_config notecard in the Sloodle Set object. It will ask your avatar for the appropriate settings.'; //Deprecated
$string['cfgnotecard:setnote'] = 'Note: if you configure a Sloodle Set, then it will automatically configure any other objects it creates (although you can still manually configure them if you want to).';

$string['choosecourse'] = 'Choose the course you want to use in Second Life.';
$string['clickchangeit'] = 'Click here to change it';
$string['clickhere'] = 'click here';
$string['clicktovisitsloodle.org'] = 'Click here to visit Sloodle.org';
$string['configerror'] = 'Configuration Error';
$string['createnotecard'] = 'Create notecard';

$string['help:primpassword'] = 'What is the Prim Password for?';
$string['help:userauth'] = 'What are the User Authentication methods?';
$string['help:versionnumbers'] = 'What do these numbers mean?';

$string['objectconfig:header'] = 'Sloodle Object Configuration';
$string['objectconfig:body'] = 'Some Sloodle objects will require a configuration notecard before you can use them with your Moodle installation. Click the following link to get the text for a configuration notecard:';

$string['modulename'] = 'Sloodle Virtual Classroom';
$string['modulenameplural'] = 'Sloodle Virtual Classrooms';

$string['moodleadminindex'] = 'Moodle administration index';

$string['needadmin'] = 'You need admin privileges to access this page.';

$string['primpass:set'] = 'Set Prim Password Number';
$string['primpass:setdesc'] = 'The password should be a number which is between 5 and 9 digits long, and which does not start with a 0. Please note that if you change this password, you will need to update the configuration of all your Second Life objects which use it.';
$string['primpass:save'] = 'Save Prim Password';
$string['primpass:change'] = 'Change Prim Password';
$string['primpass:changedesc'] = 'The password should be a number which is between 5 and 9 digits long, and which does not start with a 0. Please note that if you change this password, you will need to update the scripts in all your Second Life objects that use it.';
$string['primpass:isset'] = 'Prim Password is set.';
$string['primpass:issetdesc'] = 'Your prim password will be automatically included in your LSL scripts.';
$string['primpass:errornotset'] = 'Error: Prim password isn\'t set, and I couldn\'t create one';

$string['primpass:tooshort'] = 'Prim Password should be at least 5 digits long.';
$string['primpass:toolong'] = 'Prim Password should be at most 9 digits long.';
$string['primpass:numonly'] = 'Prim Password should only contain numeric digits (0 to 9).';
$string['primpass:error'] = 'Prim Password Error';
$string['primpass:updated'] = 'Prim Password updated';
$string['primpass:leadingzero'] = 'Prim Password should not start with a 0.';
$string['primpass:random'] = 'A random prim password has been automatically generated for you.';

$string['releasenum'] = 'Module release number';

$string['sloodle'] = 'Sloodle';
$string['sloodlenotinstalled'] = 'Sloodle does not appear to be installed yet. Please use visit the Moodle administration index to finish Sloodle installation:';
$string['sloodlesetup'] = 'Sloodle Setup';
$string['sloodleversion'] = 'Sloodle Version';

$string['sloodleobjectdistributor'] = 'Sloodle Object Distributor';
$string['sloodleobjectdistributor:nochannel'] = 'Distribution channel not available - Object not rezzed in-world?';

$string['submit'] = 'Submit';

$string['userauth:header'] = 'User Authentication';
$string['userauth:desc'] = 'What should Sloodle objects do when they meet an avatar they haven\'t seen before?';
$string['userauth:sendtopage'] = '<b>Web</b>: Send avatars to a web page and make them login or register there.';
$string['userauth:autoreg'] = '<b>Auto</b>: Automatically register them as a new user in Moodle.';
$string['userauth:autoregnote'] = 'Note: Allowing automatic registration may conflict with your usual Moodle administration policies, and may not work properly with some authentication methods.';
$string['userauth:save'] = 'Save User Authentication Settings';

$string['wouldneedadmin'] = 'You would normally need admin privileges to access this page, but I\'ve let you in, since it\'s a demo.';

$string['xmlrpc:unexpectedresponse'] = 'Not getting the expected XMLRPC response. Is Second Life broken again?';
$string['xmlrpc:error'] = 'XMLRPC Error';


?>
