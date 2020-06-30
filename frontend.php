<?php
    // Frontend php site

    // Shortcode for blancket
    add_shortcode('HTX_Tilmeldningsblanket','HTX_lan_tilmdeldingsblanket_function');
    //perform the shortcode output
    function HTX_lan_tilmdeldingsblanket_function($atts, $content = '', $tag){
        // Custom connection to database
        $link = database_connection();
        // add to $html, to return it at the end -> It is how to do shortcodes in Wordpress
        
        // Getting and writign form name
        $html = '<h2>Hello World</h2>';
        return $html;
    }
?>