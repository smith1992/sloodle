<?php 
 /**
* Freemail v1.1 with SL patch
*
* @package freemail
* @copyright Copyright (c) 2008 Serafim Panov
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
* @author Serafim Panov
* 
*
*/
$string['modulename'] = 'FreeMail';

$string['freemail_001'] = 'Please change CHMOD to 777 (writable) on \"freemail\" folder in your moodle module directory!';

$string['freemail_002'] = 'Don\'t forget to set cron job to \"check_mail.php\" file. Full path is ';

$string['freemail_003'] = 'or Moodle main cron file:';

$string['freemail_004'] = 'Don\'t forget to setup smtp settings in the admin area.';

$string['freemail_005'] = 'If you want to test script, please go here:';

$string['freemail_006'] = 'Mailbox user login';

$string['freemail_007'] = 'Mailbox user password';

$string['freemail_008'] = 'Mailbox settings';

$string['freemail_010'] = 'Mail header';

$string['freemail_011'] = 'Mail footer';

$string['freemail_012'] = 'Mail subject';

$string['freemail_013'] = 'Upload profile image';

$string['freemail_014'] = 'Incorrect email';

$string['freemail_015'] = 'Incorrect password (image)';

$string['freemail_016'] = 'No commands';

$string['freemail_017'] = 'No image';

$string['freemail_018'] = 'Wrong image size';

$string['freemail_019'] = 'Help';

$string['freemail_020'] = 'Blog add';

$string['freemail_021'] = 'Incorrect password (blog)';

$string['freemail_022'] = 'No file (gallery)';

$string['freemail_023'] = 'Item is added';

$string['freemail_024'] = 'Parse mail begin, total number: ';

$string['freemail_025'] = 'send help';

$string['freemail_026'] = 'change profile image';

$string['freemail_027'] = 'add blog';

$string['freemail_028'] = 'gallery albom';

$string['freemail_029'] = 'Parse mail is ended';

$string['freemail_030'] = 'upload attached files';

$string['freemail_031'] = 'Email adress (FreeMail)';
$string['freemail:cronnotice'] = '<b>Welcome to the Freemail module Modified for Second Life</b>';
$string['freemail:cronnotice'] .= "<br>For more information about this module, please visit: <a href='http://slisweb.sjsu.edu/sl/index.php/Sloodle_Postcard_Blogger_(Freemail)'>The Sloodle Wiki Link for the Postcard Blogger</a>";
$string['freemail:cronnotice'] .="<br>If you are running this manually, we suggest that you set up a <a href='http://slisweb.sjsu.edu/sl/index.php/Cron'>cron job on your server</a>";    
$string['freemail:cronnotice'] .='<br>This will enable you to run a mail check automatically.  ';
$string['freemail:cronnotice'] .='<br><br>In addition, if you are getting no-write errors, you probably forgot to make http://yoursite.com/moodle/mod/freemail/log.php chmod to 777';
$string['freemail:cronnotice'] .="<br>Good luck! And please join our <a href='http://slisweb.sjsu.edu/sl/index.php/Cron'>";
$string['freemail:cronnotice'] .='Discussion forums on http://sloodle.org</a>';



?>