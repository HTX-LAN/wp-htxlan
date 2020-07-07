<?php
    /**
    * Plugin Name: HTX-Lan
    * Plugin URI: https://the0mikkel.github.io/WPPlugin-HTXLan/
    * Description: This plugin, is a custom plugin for HTX-lan's website
    * Version: 0.1.4
    * Author: Mikkel Albrechtsen & Frej
    * Author URI: https://the0mikkel.github.io/WPPlugin-HTXLan/
    */

    // Database creation
    require 'db.php';

    // custom code, such as css and javascript
    require 'scripts.php';

    // Custom functions
    require 'functions.php';

    // Frontend for users to see
    require 'frontend.php';

    // Backend for admins to see
    require 'backend.php';

    //Setup to be run when the plugin is initially enabled.
    function HTX_initial_setup() {
        create_db();
    }

    //Will delete all data when the plugin is uninstalled.
    function HTX_uninstall() {
        drop_db();
    }

    register_activation_hook(__FILE__, "HTX_initial_setup");
    register_uninstall_hook(__FILE__, "HTX_uninstall");
?>
