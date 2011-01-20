<?php
/**
* Defines a library class for managing SLOODLE Plugins.
* It is constructed and used through a SloodleSession object.
*
* @package sloodle
* @copyright Copyright (c) 2009 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
*
* @contributor Peter R. Bloomfield
*/


// This library expects that the Sloodle config file has already been included
//  (along with the Moodle libraries)

/** Include the general Sloodle functionality. */
require_once(SLOODLE_DIRROOT.'/lib/general.php');


/**
* A class to load SLOODLE plugins, and provide a means to access them.
* @package sloodle
*/
class SloodlePluginManager
{
// DATA //

    /**
    * Internal only - reference to the containing {@link SloodleSession} object.
    * Note: always check that it is not null before use!
    * @var object
    * @access protected
    */
    var $_session = null;

    /**
    * Plugin instance cache.
    * Stores the plugins which have already been created, to prevent new ones being instantiated unnecessarily.
    * Is an associative array of plugin names to plugin objects.
    * @var array
    * @access protected
    */
    var $plugin_cache = array();

    
// FUNCTIONS //

    /**
    * Class constructor.
    * @param object &$_session Reference to the containing {@link SloodleSession} object, if available.
    * @access public
    */
    function SloodlePluginManager(&$_session)
    {
        if (!is_null($_session)) $this->_session = &$_session;
    }

    /**
    * Loads all available plugins from the specified folder.
    * @param string $folder Name of the folder to load plugins from. This is a sub-folder of the 'sloodle/plugin' folder. It will only load files which are directly contained inside it.
    * @return bool True if successful, or false if it fails. (It will only report failure if the folder does not exist, or there is an error accessing it.)
    */
    function load_plugins($folder)
    {
        if (empty($folder)) return false;

        // Get a list of all the files in the specified folder
        $pluginFolder = SLOODLE_DIRROOT.'/plugin/'.$folder;
        $files = sloodle_get_files($pluginFolder, true);
        if (!$files) return false;
        if (count($files) == 0) return true;

        // Start by including the relevant base class files, if they are available
        @include_once(SLOODLE_DIRROOT.'/plugin/_base.php');
        @include_once($pluginFolder.'/_base.php');

        // Go through each filename
        foreach ($files as $file) {
            // Include the specified file
            include_once($pluginFolder.'/'.$file);
        }

        return true;
    }
    
    /**
    * Gets an array of the names of all SLOODLE plugins derived from the specified type.
    * By default, this gets all plugins. Specify a different base class to get others.
    * NOTE: this will search all plugins loaded by all plugin managers in the current PHP script.
    * (There is no way to tell which manager loaded which plugins.)
    * Plugin names correspond to class names, with the 'SloodlePlugin' prefix.
    * @param string $type Name of a plugin base class.
    * @return array Numeric array of plugin names. These names correspond to class names.
    */
    function get_plugin_names($type = 'SloodlePluginBase')
    {
        // We want to create an array of plugin names
        $plugins = array();
		$type = strtolower($type);

        // Go through all declared classes
        $classes = get_declared_classes();
        foreach ($classes as $srcClassName) {
			// Down-case the class name for PHP4 compatibility
			$className = strtolower($srcClassName);
		
            // Make sure this is a SLOODLE plugin by checking that it starts "SloodlePlugin" but not "SloodlePluginbase"
            if (strpos($className, 'sloodleplugin') !== 0 || strpos($className, 'aloodlepluginbase') === 0) continue;
            // Make sure this is not one of the supporting classes
            if ($className == 'sloodlepluginbase' || $className == 'sloodlepluginmanager') continue;

            // Make sure it is in fact a plugin by ensuring it is appropriately derived from the given base plugin class
            $tempPlugin = @new $className();
            if (!is_subclass_of($tempPlugin, $type)) continue;

            // Remove the 'SloodlePlugin' prefix from the class name
            $className = substr($className, 13);
            $plugins[] = strtolower($className);
        }

        return $plugins;
    }

    /**
    * Gets an instance of the specified plugin type.
    * This only works if the plugin has been loaded, and if it is derived from SloodlePluginBase.
    * @param string $name Name of the plugin type to get. If it does not start with "SloodlePlugin", then that is added to the start.
    * @param bool $forcenew If false (default) then a cached instance of the plugin will be returned. Set this to true to force the manager to create a new plugin instance.
    * @return object|bool An object descended from SloodlePluginBase if successful, or false on failure.
    */
    function get_plugin($name, $forcenew = false)
    {
		// Down-case the incoming plugin name for PHP4 compatibility
		$name = strtolower($name);
        // Prepend 'SloodlePlugin' if necessary
        if (strpos($name, 'sloodleplugin') !== 0) $name = 'sloodleplugin'.$name;
        // Make sure the specified class exists
        if (!class_exists($name)) return false;
        // Do we have a cached plugin of this type?
        if ($forcenew == false && !empty($this->plugin_cache[$name]) && is_a($this->plugin_cache[$name], $name)) return $this->plugin_cache[$name];

        // Attempt to construct an instance of the plugin
        $plugin = new $name();
        // Make sure it is a valid plugin
        if (is_subclass_of($plugin, 'sloodlepluginbase')) {
            $this->plugin_cache[$name] = $plugin;
            return $plugin;
        }
        return false;
    }

}

?>
