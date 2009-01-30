<?php // $Id: mysql.php,v 1.3 2006/08/28 16:41:20 mark-nielsen Exp $
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
function freemail_upgrade($oldversion) {
    global $CFG;

    if ($oldversion < 2006042900) {

       # Do something ...

    }

    return true;
}

?>
