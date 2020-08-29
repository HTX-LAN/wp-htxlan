<?php
    //Prevent direct file access
    if(!defined('ABSPATH')) {
        header("Location: ../../../../");
        die();
    }

    // Scripts with all sorts of code, that is written in either JS og CSS

    // Loading parameters - backend
    function HTX_load_standard_backend() {
        // Ajax and icons
        echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>';
        echo '<link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet">';

        // Style
        wp_enqueue_style( 'standard_stylesheet', "/wp-content/plugins/wp-htxlan/code/CSS/style.css");

        // Script
        wp_enqueue_script( 'table_script', "/wp-content/plugins/wp-htxlan/code/JS/table.js");
        wp_enqueue_script( 'cookie', "/wp-content/plugins/wp-htxlan/code/JS/cookie.js");

        // Alert window
        HTX_information_alert_backend();
    }
    // Loading parameters - frontend
    function HTX_load_standard_frontend() {
        // Style
        wp_enqueue_style( 'frontendForm', "/wp-content/plugins/wp-htxlan/code/CSS/form.css");

        // Script
        wp_enqueue_script( 'frontend_script', "/wp-content/plugins/wp-htxlan/code/JS/frontend.js");

        // Ajax and icons
        $html = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>';
        $html .= '<link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet">';
        return $html;
    }

    // Information alert code - Backend
    function HTX_information_alert_backend() {
        // HTML for information alert
        echo '<div id="informationwindow"></div>';
        // Files for JS and CSS
        wp_enqueue_script( 'informationAlertJs', "/wp-content/plugins/wp-htxlan/code/JS/informationAlert.js");
        wp_enqueue_style( 'informationAlertStyle', "/wp-content/plugins/wp-htxlan/code/CSS/informationAlert.css");

    }

    // Scripts for dangerzone panel
    function HTX_danger_zone() {
        // JS files
        wp_enqueue_script( 'DangerZoneJS', "/wp-content/plugins/wp-htxlan/code/JS/DangerZone.js");
    }
