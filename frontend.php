<?php
    // Frontend php site

    // Shortcode for blancket
    add_shortcode('HTX_Tilmeldningsblanket','HTX_lan_tilmdeldingsblanket_function');
    //perform the shortcode output
    function HTX_lan_tilmdeldingsblanket_function($atts, $content = '', $tag){
        // add to $html, to return it at the end
        $html = '';
        $html .= '<p>Hello World</p>';
        return $html;
    }
?>