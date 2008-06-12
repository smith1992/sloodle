The "sloodleobject" folder should be copied to your Moodle installation's "mod/assignment/type" folder.

You will also need to edit the assignment module's language files for each language you use.
The file can be found at:

lang/<langcode>/assignment.php

(<langcode> should be replaced with the language code, such as "en_utf8").

Append the following line to the language file:

$string['typesloodleobject'] = 'Sloodle Object';


(You can adjust the 'Sloodle Object' text if you would like to name it something else.