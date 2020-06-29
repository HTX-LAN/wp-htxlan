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

    // Information alert code - Backend
    function HTX_information_alert_backend() {
        // HTML for information alert
        echo '<div id="informationwindow"></div>';
        // Files for JS and CSS
        wp_enqueue_script( 'informationAlertJs', "/wp-content/plugins/WPPlugin-HTXLan/JS/informationAlert.JS");
        wp_enqueue_style( 'informationAlertStyle', "/wp-content/plugins/WPPlugin-HTXLan/CSS/informationAlert.CSS");

    }

    // Scripts for dangerzone panel
    function HTX_danger_zone() {
        // Script for deleting all participants - JS part
        echo "
        <script>
            function HTXJS_DeleteParticipants() {
                confirm('Vil du virkelig slette alle tilmeldinger?');
            }
        </script>
        
        
        ";
    }
        