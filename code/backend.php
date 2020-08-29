<?php
    //Prevent direct file access
    if(!defined('ABSPATH')) {
        header("Location: ../../../../");
        die();
    }

    // Backend php page

    // Admin page creation
    add_action('admin_menu', 'setup_admin_menu');

    //Handle post requests
    add_action('wp_ajax_htx_parse_dangerzone_request', 'htx_parse_dangerzone_request');
    add_action('wp_ajax_htx_delete_form', 'htx_delete_form');
    add_action('wp_ajax_htx_update_form', 'htx_update_form');
    add_action('wp_ajax_htx_create_form', 'htx_create_form');
    add_action('wp_ajax_htx_new_column', 'htx_new_column');
    add_action('wp_ajax_htx_update_sorting', 'htx_update_sorting');
    add_action('wp_ajax_htx_update_column', 'htx_update_column');
    add_action('wp_ajax_htx_delete_column', 'htx_delete_column');
    add_action('wp_ajax_htx_delete_setting', 'htx_delete_setting');
    add_action('wp_ajax_htx_add_setting', 'htx_add_setting');
    add_action('wp_ajax_htx_dublicate_form', 'htx_dublicate_form');

    // Creating setup for pages
    function setup_admin_menu(){
        //https://wordpress.stackexchange.com/questions/270783/how-to-make-multiple-admin-pages-for-one-plugin/301806
        //add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
        add_menu_page( 'HTX Lan tilmelding admin', 'HTX lan', 'manage_options', 'HTXLan', 'main_admin_page' );

        //add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
        add_submenu_page('HTXLan', 'HTX LAN tildmelder liste', 'Tilmelder liste', 'manage_options', 'HTX_lan_participants_list', 'HTX_lan_participants_list_function');
        add_submenu_page('HTXLan', 'HTX LAN form oprettor', 'Form creator', 'manage_options', 'HTX_lan_create_form', 'HTX_lan_create_function');

        // Only show pages, when an element has specific special name in it in it
        // Getting start information for database connection
        global $wpdb;
        // Connecting to database, with custom variable
        $link = database_connection();
        $table_name = $wpdb->prefix . 'htx_column';
        $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 and specialName != '' ");
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) {} else {
            $torunament = false;
            $economic = false;
            while($row = $result->fetch_assoc()) {
                // Tournament
                $tempArray = explode(",", $row['specialName']);

                if (in_array('tournament', $tempArray) AND $torunament == false) {
                    add_submenu_page('HTXLan', 'HTX LAN turnerings hold', 'Turnerings hold', 'manage_options', 'HTX_lan_teams', 'HTX_lan_teams_function');
                    $torunament = true;
                }
                if (in_array('price_extra', $tempArray) OR in_array('price_intrance', $tempArray)) {
                    if ($economic == false) {
                        add_submenu_page('HTXLan', 'HTX LAN økonomi', 'Økonomi', 'manage_options', 'HTX_lan_economic', 'HTX_lan_economic_function');
                        $economic = true;
                    }
                }
                if ($economic == true and $torunament == true) break;
            }
        }
        $stmt->close();
    }


    // admin page content
    function main_admin_page(){
        // Widgets and style
        HTX_load_standard_backend();

        // Header
        echo "<h1>HTX Lan tilmeldinger</h1>";

        // Writing on page

        // List of forms
        echo "<h2>Formularer</h2>";

        // Getting start information for database connection
        global $wpdb;
        // Connecting to database, with custom variable
        $link = database_connection();

        $table_name = $wpdb->prefix . 'htx_form_tables';
        $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 ORDER BY favorit DESC, tableName ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) {
            echo "Ingen formularer";

            // Possible to add form, when none exist
            wp_enqueue_script( 'form_creator_script', "/wp-content/plugins/wp-htxlan/code/JS/formCreator.js");
            echo "<br><br><button type='submit'  class='btn updateBtn' name='submit' value='newForm' onclick='HTXJS_createForm()'>Tilføj ny formular</button><br>";
            // Spacer
            echo "<div style='height: 5rem;width: 100%;'></div>";
        }else {
            while($row = $result->fetch_assoc()) {
                $tableIds[] = $row['id'];
                $tableNames[] = $row['tableName'];
                $tableDescription[] = $row['tableDescription'];

                $table_name2 = $wpdb->prefix . 'htx_form_users';
                $stmt2 = $link->prepare("SELECT count(*) FROM `$table_name2` where active = 1 and tableId = ?");
                $stmt2->bind_param("i", $row['id']);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                while($row2 = $result2->fetch_assoc()) {
                    $tableUserCount[] = $row2['count(*)'];
                }
                $stmt2->close();
            }

            // Writes windows for forms
            echo "<div class='main-backend-area'>";
            for ($i=0; $i < count($tableIds); $i++) { 
                echo "<div class='Quickselect-card' onclick='document.getElementById(\"gotoFormCreatorTable-$tableIds[$i]\").submit();// Form submission'>
                    <form id='gotoFormCreatorTable-$tableIds[$i]' method='GET'>
                    <input type='hidden' name='page' value='HTX_lan_create_form'>
                    <input type='hidden' name='form' value='$tableIds[$i]'>";
                echo "<h3>$tableNames[$i]</h3>";
                if ($tableDescription[$i] != "")
                    echo "<p><b><i>Beskrivelse:</b></i><br>$tableDescription[$i]</p>";
                if ($tableUserCount[$i] > 0)
                    echo "<p><b><i>Tilmeldte:</b></i><br>$tableUserCount[$i]</p>";
                else 
                    echo "<p><b><i>Tilmeldte:</b></i><br>Ingen tilmeldte endnu</p>";
                
                echo "</form></div>";
            }
            echo "</div>";
        }
        $stmt->close();


        // Statistics
        // echo "<h3>Statestik</h3>";
        // echo "<p>Antal tilmeldte: participentCount</p>";
        // echo "<p>Antal input felter: inputCount</p>";


        // Danger zone - Reset tables - (Skal laves om til at køre direkte load på siden (reload med post), istedet for via jquery)
        echo "<h2>Farlig zone</h2>";
        HTX_danger_zone();
        echo "<button class='btn deleteBtn' style='margin-bottom: 0.5rem;' onclick='HTXJS_resetDatabases()'>Nulstil databaser</button><br>";
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
        require "economic.php";
    }

    function HTX_lan_teams_function(){
        require "teams.php";
    }

?>
