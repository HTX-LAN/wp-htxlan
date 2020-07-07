<?php
    /**
    * Plugin Name: HTX-Lan
    * Plugin URI: https://the0mikkel.github.io/WPPlugin-HTXLan/
    * Description: This plugin, is a custom plugin for HTX-lan's website
    * Version: 0.1.5
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

?>