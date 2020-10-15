<?php
    /**
    * Plugin Name: HTX-Lan
    * Plugin URI: https://htx-lan.github.io/wp-htxlan/
    * Description: Event plugin designet for lan parties, and used by HTX-lan.dk
    * Version: 0.1.18
    * Author: Mikkel Albrechtsen & Frej Alexander Nielsen
    * Author URI: https://htx-lan.github.io/wp-htxlan/authors
    */

    // Setting newest database  version
    $databaseVersion = 0.7;

    //Prevent direct file access
    if(!defined('ABSPATH')) {
        header("Location: ../../../");
        die();
    }

    // Database creation
    require 'code/db.php';

    // custom code, such as css and javascript
    require 'code/scripts.php';

    // Custom functions
    require 'code/functions.php';

    // Frontend for users to see
    require 'code/frontend.php';

    // Backend for admins to see
    require 'code/backend.php';

    // Post request handlers
    require 'code/post_handlers.php';

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
