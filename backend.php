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
        // Echo to show -> How to do Wordpress on admin pages

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
        // Echo to show

        // Widgets and style
        HTX_load_standard_backend();

        // Getting start information for database connection
        global $wpdb;
        // Connecting to database, with custom variable
        $link = database_connection();

        // Header
        echo "<h1>HTX Lan tilmeldinger</h1>";

        // Getting data about forms
        $table_name = $wpdb->prefix . 'htx_form_tables';
        $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 ORDER BY favorit DESC, tableName ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) echo "Ingen registreringer"; else {
            while($row = $result->fetch_assoc()) {
                $tableIds[] = $row['id'];
                $tableNames[] = $row['tableName'];
            }

            // Tabel med alle tilmeldinger som kan ses - Evt en knap som kan trykkes, hvor så at felterne kan blive redigerbare - Man kan vælge imellem forskellige forms
            if(!isset($_COOKIE["submissionTableCookie"])) echo "cookie not set";
            // Dropdown menu, with every form
            // Getting cookie value
            $cookie_name = "submissionTableCookie";
            if(!isset($_COOKIE[$cookie_name])) {
                // Cookie does not exist
                // Setting new cookie
                setCustomCookie($cookie_name, $tableIds[0]);
            } else {
                // Cookie exist, but needs checking
                $tableCookie = $_COOKIE[$cookie_name];
                if (in_array($tableCookie, $tableIds)) {
                    // Cookie is valid
                } else {
                    // Cookie value is not correct anymore - Updating cookie and saving for 30 days
                    setCustomCookie($cookie_name, $tableIds[0]);
                    $tableCookie = $tableIds[0];
                }
            }

            // Getting post value, if there has been a post
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                if ($_POST['posttype'] == 'tableUpdate') {
                    $table = $_POST['formular'];
                    if ($table != $tableCookie) {
                        // Checking if id is in database
                        if (in_array($table, $tableIds)) {
                            // Post is not the same as cookie - Updating cookie and saving for 30 days
                            setCustomCookie($cookie_name, $table);
                            $tableCookie = $table;
                        } else {
                            $tableCookie = $tableIds[0];
                            setCustomCookie($cookie_name, $tableIds[0]);
                        }
                    }
                }
            }
            // Setting tableId for the rest of the page
            $tableId = $tableCookie;
        
            // Starting dropdown menu
            echo "<p><h3>Formular:</h3> ";
            echo "<form method=\"post\"><select name='formular' class='dropdown' onchange='form.submit()'>";
            // writing every option
            for ($i=0; $i < count($tableIds); $i++) { 
                // Seeing if value is the choosen one
                if ($tableIds[$i] == $tableId) $isSelected = "selected"; else $isSelected = "";

                // Writing value
                echo "<option value='$tableIds[$i]' $isSelected>$tableNames[$i]</option>";
            }
            
            // Ending dropdown
            echo "</select><input name='posttype' value='tableUpdate' class='hidden'></form><br></p>";
            
            // Start of table with head
            echo "<div class='formGroup formGroup_scroll_left'><div class='formGroup_container'><table class='InfoTable'><thead><tr>";

            // Getting information from database
            // Users
            $table_name = $wpdb->prefix . 'htx_form_users';
            $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableid = ? AND active = 1");
            $stmt->bind_param("i", $tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) echo "Ingen registreringer"; else {
                // Getting every column
                $table_name3 = $wpdb->prefix . 'htx_column';
                $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE tableid = ? AND adminOnly = 0");
                $stmt3->bind_param("i", $tableId);
                $stmt3->execute();
                $result3 = $stmt3->get_result();
                if($result3->num_rows === 0) {return HTX_frontend_sql_notworking();} else {
                    while($row3 = $result3->fetch_assoc()) {
                        $columnNameFront[] = $row3['columnNameFront'];
                        $columnNameBack[] = $row3['columnNameBack'];
                        $format[] = $row3['format'];
                        $columnType[] = $row3['columnType'];
                        $special[] = $row3['special'];
                        $specialName[] = $row3['specialName'];
                        $placeholderText[] = $row3['placeholderText'];
                        $sorting[] = $row3['sorting'];
                        $adminOnly[] = $row3['adminOnly'];
                        $required[] = $row3['required'];
                    }
                }
                $stmt3->close();

                // Writing every column and insert into table head
                for ($i=0; $i < count($columnNameBack); $i++) { 
                    echo "<th>$columnNameFront[$i]</th>";
                }
                // Ending head
                echo "</tr></head>";

                // User information
                // Stating table body
                echo "<tbody>";

                // Getting every user ids
                while($row = $result->fetch_assoc()) {
                    $userid[] = $row['id'];
                }
                // Getting and writing every user information
                for ($i=0; $i < count($userid); $i++) { 
                    echo "<tr>";
                    // For every column
                    for ($index=0; $index < count($columnNameBack); $index++) { 
                        echo "<td class=''>";
                        // Getting data for specefied column
                        $table_name2 = $wpdb->prefix . 'htx_form';
                        $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableid = ? AND userId = ? AND name = ?");
                        $stmt2->bind_param("iis", $tableId, $userid[$i], $columnNameBack[$index]);
                        $stmt2->execute();
                        $result2 = $stmt2->get_result();
                        if($result2->num_rows === 0) echo "Ingen oplysninger"; else {
                            while($row2 = $result2->fetch_assoc()) {
                                // Writing data from table
                                echo $row2['value'];
                            } 
                        }
                        $stmt2->close();
                        echo "</td>";
                    }
                }
                
            }
            $stmt->close();

            // Ending table
            echo "</thead></table></div>";



        }

        
    }

    // admin submenu page content - HTX LAN tildmeldings side laver
    function HTX_lan_create_function(){
        // Echo to show

        // Header
        echo "<h1>HTX Lan tilmeldings skabelon</h1>";

        // Liste over ting som kan ændres, som fx navne på felter og lignende - Her skal man også kunne vælge imellem forms
    }

    // admin submenu page content - HTX LAN tildmeldings side laver
    function HTX_lan_economic_function(){
        // Echo to show

        // Header
        echo "<h1>HTX Lan økonomi</h1>";

        // Økonomi side, som har alting med økonomi at gøre
    }

?>