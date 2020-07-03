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
        add_submenu_page('HTXLan', 'HTX LAN form oprettor', 'Form creator', 'manage_options', 'HTX_lan_create_form', 'HTX_lan_create_function');
        add_submenu_page('HTXLan', 'HTX LAN økonomi', 'Økonomi', 'manage_options', 'HTX_lan_economic', 'HTX_lan_economic_function');
    }
    

    // admin page content
    function main_admin_page(){
        // Widgets and style
        HTX_load_standard_backend();

        // Post handling
        HTX_backend_post();

        // Header
        echo "<h1>HTX Lan tilmeldings admin</h1>";

        // Writing on page

        // List of forms


        // Statistics
        echo "<h3>Statestik</h3>";
        echo "<p>Antal tilmeldte: participentCount</p>";
        echo "<p>Antal input felter: inputCount</p>";


        // Danger zone - Create, delete and reset tables - (Skal laves om til at køre direkte load på siden (reload med post), istedet for via jquery)
        echo "<h3>Farlig zone</h3>";
        HTX_danger_zone();
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        echo "<button class='button button-link-delete' onclick='HTXJS_DeleteParticipants()'>Slet alle tilmeldinger</button><br>";
        echo "<button class='button button-link-delete' onclick='HTXJS_dropDatabases(\"$actual_link\")'>Slet databaser</button><br>";
        echo "<button class='button' onclick='HTXJS_createDatabases(\"$actual_link\")'>Opret databaser</button><br>";
        echo "<button class='button' type='submit'>Download data</button>";
    }
    
    
    // admin submenu
    // admin submenu page content - HTX LAN tildmelder liste
    function HTX_lan_participants_list_function(){
        require "PaarticipantList.php";
    }

    // admin submenu page content - HTX LAN tildmeldings side laver
    function HTX_lan_create_function(){
        require "formCreator.php";
    }

    // admin submenu page content - HTX LAN tildmeldings side laver
    function HTX_lan_economic_function(){
        // Økonomi side, som har alting med økonomi at gøre
        // Header
        echo "<h1>HTX Lan økonomi</h1>";
    }

?>