<?php
    // Backend php page

    // Admin page creation
    add_action('admin_menu', 'setup_admin_menu');

    //Handle post requests
    add_action('wp_ajax_htx_parse_dangerzone_request', 'htx_parse_dangerzone_request');
    add_action('wp_ajax_htx_delete_form', 'htx_delete_form');

    // Creating setup for pages
    function setup_admin_menu(){
        //https://wordpress.stackexchange.com/questions/270783/how-to-make-multiple-admin-pages-for-one-plugin/301806
        //add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
        add_menu_page( 'HTX Lan tilmelding admin', 'HTX lan', 'manage_options', 'HTXLan', 'main_admin_page' );

        //add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
        add_submenu_page('HTXLan', 'HTX LAN tildmelder liste', 'Tilmelder liste', 'manage_options', 'HTX_lan_participants_list', 'HTX_lan_participants_list_function');
        add_submenu_page('HTXLan', 'HTX LAN form oprettor', 'Form creator', 'manage_options', 'HTX_lan_create_form', 'HTX_lan_create_function');
        // add_submenu_page('HTXLan', 'HTX LAN økonomi', 'Økonomi', 'manage_options', 'HTX_lan_economic', 'HTX_lan_economic_function');
    }


    // admin page content
    function main_admin_page(){
        // Widgets and style
        HTX_load_standard_backend();

        // Header
        echo "<h1>HTX Lan tilmeldings admin</h1>";

        // Writing on page

        // List of forms


        // Statistics
        echo "<h3>Statestik</h3>";
        echo "<p>Antal tilmeldte: participentCount</p>";
        echo "<p>Antal input felter: inputCount</p>";


        // Danger zone - Reset tables - (Skal laves om til at køre direkte load på siden (reload med post), istedet for via jquery)
        echo "<h3>Farlig zone</h3>";
        HTX_danger_zone();
        echo "<button class='btn deleteBtn' style='margin-bottom: 0.5rem;' onclick='HTXJS_DeleteParticipants()'>Slet alle tilmeldinger</button><br>";
        echo "<button class='btn deleteBtn' style='margin-bottom: 0.5rem;' onclick='HTXJS_resetDatabases()'>Nulstil databaser</button><br>";
        echo "<button class='btn updateBtn' style='margin-bottom: 0.5rem;' onclick='HTXJS_downloadData()'>Download data</button>";
    }


    // admin submenu
    // admin submenu page content - HTX LAN tildmelder liste
    function HTX_lan_participants_list_function(){
        require "ParticipantList.php";
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
