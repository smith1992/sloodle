<?php
    /**
    * Defines a function to display information about a particular Sloodle controller module.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This file expects that the core Sloodle/Moodle functionality has already been included.
    
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    /**
    * Displays information about the given Sloodle module.
    * Note: the user should already be logged-in, with information in the $USER global.
    *
    * @param object $cm A coursemodule object for the module being displayed
    * @param object $sloodle A database record object for a Sloodle instance
    * @param bool $showprotected True if protected data (such as prim password) should be made available
    * @return bool True if successful, or false otherwise (e.g. wrong type of module, or user not logged-in)
    */
    function sloodle_view_slideshow($cm, $sloodle, $editmode = false)
    {
        global $CFG;
    
        // Check that there is valid Sloodle data
        if (empty($cm) || empty($sloodle) || $sloodle->type != SLOODLE_TYPE_SLIDESHOW) return false;
        
        // Construct a dummy session and a Slideshow object
        $session = new SloodleSession(false);
        $slideshow = new SloodleModuleSlideshow($session);
        if (!$slideshow->load($cm->id)) return false;
        
        // The name, type and description of the module should already be displayed by the main "view.php" script.
        // In edit mode, we will display a form to let the user add/delete URLs.
        
        // Display a box containing our stuff
        echo "<div style=\"text-align:center;\">\n";
        
        
        
        // Is edit mode active?
        if ($editmode) {
        
            // We need to delete each image that's been requested for deletion.
            // Go through each request parameter that starts with 'sloodledeleteimage'
            foreach ($_REQUEST as $reqname => $reqvalue) {
                $pos = strpos($reqname, 'sloodledeleteimage');
                if ($pos === 0) {
                    echo "Deleting ".substr($reqname, 18)."<hr>";
                    $slideshow->delete_image((int)substr($reqname, 18));
                }
            }
            
            // Has an image been added?
            if (isset($_REQUEST['sloodleaddimage'])) {
                $slideshow->add_image($_REQUEST['sloodleimageurl']);
            }
            
            
            // Get an updated list of image URLs
            $images = $slideshow->get_image_urls();
        
            // Start a box containing the form
            print_box_start('generalbox boxaligncenter boxwidthwide');
            echo '<h3>View and delete image links</h3>'; // TODO: use language pack!
            // Any images to display?
            if ($images === false || count($images) == 0) {
                echo '<h4 style="color:#ff0000;">No images found</h4>'; // TODO: use language pack!
            } else {
                echo '<form action="" method="get"><fieldset>';
                echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
                echo '<ol>';
                // Go through each image we have, and display the link along with a delete button
                foreach ($images as $imageid => $imageurl) {
                    echo "<li><a href=\"$imageurl\">$imageurl</a> <input type=\"submit\" value=\"Delete\" name=\"sloodledeleteimage{$imageid}\" /></li>\n";
                }
                echo '</ol><br/><br/></fieldset></form>';
            }
            
            // Display a form for adding an image
            echo '<h3>Add image link</h3>'; // TODO: use language pack!
            echo '<form action="" method="post">';
            echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
            echo '<input type="text" name="sloodleimageurl" value="" /> <input type="submit" value="Add" name="sloodleaddimage" />';
            echo '</fieldset></form>';
            
            print_box_end();
        } else {
            echo '<h3>In-browser slidehshow...</h3>';
            
        }
        
        echo "</div>\n";
        
        return true;
    }
    
    
?>
