<?php
    // Scripts with all sorts of code, that is written in either JS og CSS

    // Loading parameters - backend
    function HTX_load_standard_backend() {
        // Style

        // Ajax and icons
        echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>';
        echo '<link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet">';

        // Alert window
        HTX_information_alert_backend();
    }
    // Loading parameters - frontend
    function HTX_load_standard_frontend() {
        // Style
        wp_enqueue_style( 'frontendForm', "/wp-content/plugins/WPPlugin-HTXLan/CSS/form.css");

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
        wp_enqueue_script( 'informationAlertJs', "/wp-content/plugins/WPPlugin-HTXLan/JS/informationAlert.js");
        wp_enqueue_style( 'informationAlertStyle', "/wp-content/plugins/WPPlugin-HTXLan/CSS/informationAlert.css");

    }

    // Scripts for dangerzone panel
    function HTX_danger_zone() {
        // JS files
        wp_enqueue_script( 'DangerZoneJS', "/wp-content/plugins/WPPlugin-HTXLan/JS/DangerZone.js");
    }
        