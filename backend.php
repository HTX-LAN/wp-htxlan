<?php
    // Backend php page

    // Admin page creation
    add_action('admin_menu', 'setup_admin_menu');
    
    // Creating setup for pages
    function setup_admin_menu(){
        //https://wordpress.stackexchange.com/questions/270783/how-to-make-multiple-admin-pages-for-one-plugin/301806
        //add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
        add_menu_page( 'HTX Lan tilmelding admin', 'HTX lan', 'manage_options', 'HTXLan', 'main_admin_page' );

        //add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
        add_submenu_page('HTXLan', 'HTX LAN tildmelder liste', 'Tilmelder liste', 'manage_options', 'HTX_lan_participants_list', 'HTX_lan_participants_list_function');
    }
    

    // admin page content
    function main_admin_page(){
        // Echo to show -> How to do Wordpress on admin pages

        // Widgets and style
        HTX_load_standard_backend();

        // Header
        echo "<h1>HTX Lan tilmeldings admin</h1>";

        // Statesikker
        // Loading statestik

        // Writing on page
        echo "<h3>Statestik</h3>";
        echo "<p>Antal tilmeldte: participentCount</p>";
        echo "<p>Antal input felter: inputCount</p>";

        // Danger zone - Create, delete and reset tables
        echo "<p>Antal input felter: inputCount</p>";
        
    }
    
    
    // admin submenu
    
    // admin submenu page content - HTX LAN tildmelder liste
    function HTX_lan_participants_list_function(){
        // Echo to show

        // Header
        echo "<h1>HTX Lan tilmeldinger</h1>";

        // Tabel med alle tilmeldinger som kan ses - Evt en knap som kan trykkes, hvor så at felterne kan blive redigerbare
    }

    // admin submenu page content - HTX LAN tildmeldings side laver
    function HTX_lan_create_function(){
        // Echo to show

        // Header
        echo "<h1>HTX Lan tilmeldings skabelon</h1>";

        // Liste over ting som kan ændres, som fx navne på felter og lignende
    }

?>