<?php
    /**
    * Plugin Name: HTX-Lan
    * Plugin URI: https://themikkel.servehttp.com/HTX-Lan-plugin
    * Description: This plugin, is a custom plugin for HTX-lan's website
    * Version: 0.1
    * Author: Mikkel Albrechtsen & Frej Nielsen
    * Author URI: https://themikkel.servehttp.com
    */

    // Database creation
    require 'db.php';

    // custom code, such as css and javascript, backed needly as php files
    require 'scripts.php';

    // Custom functions
    require 'functions.php';

    // Frontend for users to see
    require 'frontend.php';

    // Backend for admins to see
    require 'backend.php';

?>