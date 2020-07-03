<?php
    // Liste over ting som kan ændres, som fx navne på felter og lignende - Her skal man også kunne vælge imellem forms
    // Widgets and style
    HTX_load_standard_backend();
    wp_enqueue_style( 'form_creator_style', "/wp-content/plugins/WPPlugin-HTXLan/CSS/formCreator.css");
    wp_enqueue_script( 'form_creator_script', "/wp-content/plugins/WPPlugin-HTXLan/JS/formCreator.js");

    // Getting start information for database connection
    global $wpdb;
    // Connecting to database, with custom variable
    $link = database_connection();

    // Header
    echo "<h1>HTX Lan tilmeldings skabelon</h1>";

    // Main area to work in
    echo "<div class='formCreator_main'>";

    // Table of content menu
    // Getting data about forms
    echo "<div class='formCreator_tableOfContent rtl' id='formCreator_tableOfContent'><div class='ltr'>";

        $table_name = $wpdb->prefix . 'htx_form_tables';
        $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 ORDER BY favorit DESC, tableName ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) {echo "Ingen tabeller"; $noTable = true;} else {
            while($row = $result->fetch_assoc()) {
                $tableIds[] = $row['id'];
                $tableNames[] = $row['tableName'];
            }
            $noTable = false;
            for ($i=0; $i < count($tableNames); $i++) { 
                // Ved klik på den form man vil vælge, kommer formularen frem i midten af skærmen
                echo "<form id='form-tableOfContent-$tableIds[$i]' action='admin.php' method=\"get\">
                    <input name='page' value='".$_GET['page']."' class='hidden'>
                    <input name='form' value='$tableIds[$i]' class='hidden'>
                    <a onclick='submitForm(\"form-tableOfContent-$tableIds[$i]\")'>$tableNames[$i]</a><br>
                </form>";
                        
            } 
        }
        
        
            

        echo "<br><br><a onclick=''>Tilføj ny formular</a>";
    echo "</div></div>";

    // Check if form exist
    if (!$noTable AND isset($_GET['form'])) {
        // Content edit menu
        echo "<div class='formCreator_edit rtl' id='formCreator_edit'><div class='ltr'>";
        
        // Check url for form - If form is not existing, then show nothing
        if (in_array($_GET['form'],$tableIds)) {
            $tableId = $_GET['form'];

            // Make div
            echo "<div id='edit-form-$tableId' class='formCreator_edit_container'>";
            // Column info
            $table_name = $wpdb->prefix . 'htx_column';
            $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableid = ? AND adminOnly = 0 ORDER by sorting");
            $stmt->bind_param("i", $tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) echo "Noget gik galt"; else {
                while($row = $result->fetch_assoc()) {
                    // Info
                    $settingIds[] = $row['id'];
                    $settingId = $row['id'];
                    $settingTableId = $row['tableId'];
                    $columnNameFront = $row['columnNameFront'];
                    $columnNameBack = $row['columnNameBack'];
                    $format = $row['format'];
                    $columnType = $row['columnType'];
                    $special = $row['special'];
                    $specialName = $row['specialName'];
                    $placeholderText = $row['placeholderText'];
                    $sorting = $row['sorting'];
                    $adminOnly = $row['adminOnly'];
                    $required = $row['required'];
                    
                    // Write
                    echo "<div id='settingEdit-$settingTableId-$settingId' class='formCreator_edit_block ";
                    if (isset($_GET['setting']) AND $_GET['setting'] == $settingId) echo "highlighted";
                    echo "'><h4>$columnNameFront</h4>";
                    echo "<form id='form-content-$settingTableId-$settingId' action='admin.php' method=\"get\">
                        <button type='submit' class='material-icons settingIcon'>settings</button>
                        <input name='page' value='".$_GET['page']."' class='hidden'>
                        <input name='form' value='$settingTableId' class='hidden'>
                        <input class='hidden' name='setting' value='$settingId'>
                    </form>";
                    echo "<input value='$placeholderText' class='inputBox' disabled>";
                    echo "</div>";
                }
            }
            $stmt->close();

            // Create new row
            echo "<form method=\"post\" class='addColumn'>";
            echo "<h4>Tilføj ny række</h4>";
            // Drop down with possible types of input field
            echo "<label>Input type: </label><br><select name='inputType'><option value='inputbox'>Input box</option><option value='dropdown'>Drop down</option></select><br>";
            echo "<button type='submit' name='submit' value='newColumn' style='margin-top: 0.25rem'>Tilføj række</button>";
            echo "</form>";

            // Post handling from form
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                switch  ($_POST['submit']) {
                    // New submission
                    case 'newColumn':
                        try {
                            $link->autocommit(FALSE); //turn on transactions
                            // Possible input types in array
                            $possibleInput = array("inputBox", "dropdown");
                            // User input
                            $userInputType = $_POST['inputType'];
                            // Break if the user input is not known
                            if (!in_array($userInputType, $possibleInput)) break;
                            // Define values for new element
                            $columnNameFront = "New element"; $format="text"; $columnType=$userInputType; $special=0; $specialName=""; 
                            $placeholderText = ""; $adminOnly = 0; $required = 0; $settingCat = 0; 
                            if ($userInputType == 'dropdown') {
                                // If dropdown, then make setting category first
                                $table_name = $wpdb->prefix . 'htx_settings_cat';
                                $stmt = $link->prepare("INSERT INTO $table_name (tableId, settingNameBack, settingType) VALUES (?, ?, ?)");
                                $stmt->bind_param("iss", $tableId, $columnNameBack, $columnType);
                                $stmt->execute();
                                $settingCat = intval($link->insert_id);
                                if ($settingCat < 0) throw new Exception('Setting cat is bad');
                                $stmt->close();
                            }
                            
                            // Create new column, with standard values, and user input
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'htx_column';
                            $stmt = $link->prepare("INSERT INTO $table_name (tableId, columnNameFront, format, columnType, special, specialName, sorting, placeholderText, adminOnly, required, settingCat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("isssssissii", $tableId, $columnNameFront, $format, $columnType, $special, $specialName, $sorting, $placeholderText, $adminOnly, $required, $settingCat);
                            $stmt->execute();
                            $lastId = intval($link->insert_id);
                            if ($lastId < 0) throw new Exception('Id is bad');
                            $stmt->close();
                            $stmt = $link->prepare("UPDATE $table_name SET columnNameBack = ? WHERE id = ?");
                            $stmt->bind_param("ii", $lastId, $lastId);
                            $stmt->execute();
                            $stmt->close();

                            $link->autocommit(TRUE); //turn off transactions + commit queued queries

                            // Alert user through prompt
                            echo "<script>confirm('Nyt element tilføjet'); location.reload();</script>"; #Because this post is after the form, there may be problems with not loading in
                        } catch(Exception $e) {
                            $mysqli->rollback(); //remove all queries from queue if error (undo)
                            throw $e;
                        }
                    break;
                }
            }

            // End div
            echo "</div>";
        }

        echo "</div></div>";

        // Settings menu
        echo "<div class='formCreator_settings' id='formCreator_settings'>";
        if (isset($_GET['setting']) AND in_array($_GET['setting'],$settingIds)) {
            $setting = $_GET['setting'];

            // Make div
            echo "<div id='setting-form-$tableId'>";
            // Column info
            $table_name = $wpdb->prefix . 'htx_column';
            $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableid = ? AND adminOnly = 0 AND id = ?");
            $stmt->bind_param("ii", $tableId, intval($setting));
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) echo "Noget gik galt"; else {
                while($row = $result->fetch_assoc()) {
                    // Info
                    $settingId = $row['id'];
                    $settingTableId = $row['tableId'];
                    $columnNameFront = $row['columnNameFront'];
                    $columnNameBack = $row['columnNameBack'];
                    $format = $row['format'];
                    $columnType = $row['columnType'];
                    $special = $row['special'];
                    $specialName = $row['specialName'];
                    $placeholderText = $row['placeholderText'];
                    $sorting = $row['sorting'];
                    $adminOnly = $row['adminOnly'];
                    $required = $row['required'];
                    $settingCat = $row['settingCat'];
                    
                    // Write
                    echo "<div id='settingEdit-$settingTableId-$settingId'><h3>$columnNameFront</h3>";
                    switch ($columnType) {
                        case "dropdown":
                            echo "<div class='formCreator_edit_container formCreator_flexRow'>";
                            echo "<div><p>Navn</p><input class='inputBox' value='$columnNameFront'></div>";
                            echo "<div><p>format</p><input class='inputBox' value='$format'></div>";
                            echo "<div><p>columnType</p><input class='inputBox' value='$columnType'></div>";
                            echo "<div><p>special</p><input class='inputBox' value='$special'></div>";
                            echo "<div><p>specialName</p><input class='inputBox' value='$specialName'></div>";
                            echo "<div><p>sorting</p><input class='inputBox' value='$sorting'></div>";
                            echo "<div><p>required</p><input class='inputBox' value='$required'></div>";
                            echo "<h4>Dropdown indstillinger</h4>";
                            // Getting dropdown setting category
                            $table_name2 = $wpdb->prefix . 'htx_settings_cat';
                            $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? AND id = ?");
                            $stmt2->bind_param("is", $tableId, $settingCat);
                            $stmt2->execute();
                            $result2 = $stmt2->get_result();
                            if($result2->num_rows === 0) echo "<p>Ingen indstillinger for dropdown</p>"; else {
                                while($row2 = $result2->fetch_assoc()) {
                                    $row2['id'];
                                    $row2['settingName'];
                                    $row2['special'];
                                    $row2['specialName'];
                                    $row2['settingType'];
                                    // Getting dropdown settings
                                    $table_name3 = $wpdb->prefix . 'htx_settings';
                                    $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE settingId = ?");
                                    $stmt3->bind_param("i", $row2['id']);
                                    $stmt3->execute();
                                    $result3 = $stmt3->get_result();
                                    if($result3->num_rows === 0) echo "<p>Ingen dropdown muligheder</p>"; else {
                                        while($row3 = $result3->fetch_assoc()) {
                                            $row3['id'];
                                            $row3['settingName'];
                                            $row3['value'];

                                            echo "<div><p>Navn</p><input class='inputBox' value='".$row3['settingName']."'></div>";
                                        
                                        }
                                    }
                                    $stmt3->close();
                                }
                            }
                            $stmt2->close();
                            echo "</div>";
                        break;
                        default:
                            echo "<div class='formCreator_edit_container formCreator_flexRow'>";
                            echo "<div><p>Navn</p><input class='inputBox' value='$columnNameFront'></div>";
                            echo "<div><p>format</p><input class='inputBox' value='$format'></div>";
                            echo "<div><p>columnType</p><input class='inputBox' value='$columnType'></div>";
                            echo "<div><p>special</p><input class='inputBox' value='$special'></div>";
                            echo "<div><p>specialName</p><input class='inputBox' value='$specialName'></div>";
                            echo "<div><p>placeholderText</p><input class='inputBox' value='$placeholderText'></div>";
                            echo "<div><p>sorting</p><input class='inputBox' value='$sorting'></div>";
                            echo "<div><p>required</p><input class='inputBox' value='$required'></div>";
                            echo "</div>";
                        break;
                    }
                    
                    echo "</div>";
                }
            }
            $stmt->close();

            // End div
            echo "</div>";
        }

        echo "</div>";

    }


    // Ending main area
    echo "</div>";
?>