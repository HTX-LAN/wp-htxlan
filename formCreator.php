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
                    <a onclick='submitForm(\"form-tableOfContent-$tableIds[$i]\")' ";
                    if (isset($_GET['form']) AND $_GET['form'] == $tableIds[$i]) {echo "class='highlighted'"; $tableName = $tableNames[$i];}
                    echo "'>$tableNames[$i]
                    </a><br>
                </form>";
                        
            } 
        }
        
        
            

        echo "<br><br>
        <form method='POST'>
            <button type='submit' class='btn' name='submit' value='newForm'>Tilføj ny formular</button>
        </form><br>";
        // Post handling from form
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if  ($_POST['submit'] == "newForm") {
                new_HTX_form();
            }
        }
        
    echo "</div></div>";

    // Check if form exist
    if (!$noTable AND isset($_GET['form']) AND in_array($_GET['form'],$tableIds)) {
        $tableId = $_GET['form'];

        // Content edit menu
        echo "<div class='formCreator_edit rtl' id='formCreator_edit'><div class='ltr'>";

        // Possible input types in array
        $possibleInput = array("inputbox", "dropdown", "text area", "radio"); #Missing: checkboxes, checkboxes with text input (for ex team names per game basis), range

        // Possible formats types in array
        $possibleFormat = array("text", "number", "email", 'url', 'color', 'date', 'time', 'week', 'month'); #Missing "tel", but needs more backend work - needs pattern attribute to work

        // Possible functions
        $possibleFunctions = array('price_intrance', 'price_extra');
        $possibleFunctionsName = array('Indgangs pris', 'Ekstra pris');

        // Make div
        echo "<div id='edit-form-$tableId' class='formCreator_edit_container'>";

        // Write header
        echo "<form method=\"get\" id='formSettings'>
            <input name='page' value='".$_GET['page']."' class='hidden'>
            <input name='form' value='$tableId' class='hidden'>
            <input class='hidden' name='setting' value='0'>
            <h2 onclick='document.getElementById(\"formSettings\").submit()' style='margin-top: 0px;'><a>$tableName</a></h2>
        </form>";

        // Column info
        $table_name = $wpdb->prefix . 'htx_column';
        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableid = ? ORDER by sorting ASC, columnNameFront ASC");
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
                $required = $row['required'];
                $settingCat = $row['settingCat'];
                $disabled = $row['disabled'];
                
                // Write
                echo "<div id='settingEdit-$settingTableId-$settingId' class='formCreator_edit_block ";
                if (isset($_GET['setting']) AND $_GET['setting'] == $settingId) echo "highlighted";
                echo "'><h4>$columnNameFront";
                if ($required == 1) echo "<span style='color: red'>*</span>";
                echo "</h4>";

                // Edit button
                echo "<form id='form-content-$settingTableId-$settingId' action='admin.php' method=\"get\">
                    <button type='submit' class='material-icons settingIcon'>settings</button>
                    <input name='page' value='".$_GET['page']."' class='hidden'>
                    <input name='form' value='$settingTableId' class='hidden'>
                    <input class='hidden' name='setting' value='$settingId'>
                </form>";

                // Show based on column type
                switch ($columnType) {
                    case "dropdown":
                        // Getting settings category
                        $table_name2 = $wpdb->prefix . 'htx_settings_cat';
                        $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? AND  id = ? AND active = 1 LIMIT 1");
                        $stmt2->bind_param("ii", $tableId,  $settingCat);
                        $stmt2->execute();
                        $result2 = $stmt2->get_result();
                        if($result2->num_rows === 0) echo "Ingen mulige valg, venligst tilføj nogen"; else {
                            while($row2 = $result2->fetch_assoc()) {
                                $setting_cat_settingId = $row2['id'];
                            }
                        }
                        $stmt2->close();

                        // Disabled handling
                        if ($disabled == 1) $disabledClass = "disabled"; else $disabledClass = "";

                        // Writing first part of dropdown
                        echo "<select name='$columnNameBack' class='dropdown $disabledClass' disabled>";
                        
                        // Getting dropdown content
                        $table_name2 = $wpdb->prefix . 'htx_settings';
                        $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE settingId = ? AND active = 1 ORDER by sorting ASC, value ASC");
                        $stmt2->bind_param("i", $setting_cat_settingId);
                        $stmt2->execute();
                        $result2 = $stmt2->get_result();
                        if($result2->num_rows === 0) echo "Ingen mulige valg, venligst tilføj nogen"; else {
                            while($row2 = $result2->fetch_assoc()) {
                                // Getting data
                                $setting_settingName = $row2['settingName'];
                                $setting_id = $row2['id'];
    
                                // Write data
                                echo "<option>".$setting_settingName."</option>";
                            }
                        }
                        $stmt2->close();
    
                        // Finishing dropdown
                        echo "</select>";
                    break;
                    case "radio":
                        // Getting settings category
                        $table_name2 = $wpdb->prefix . 'htx_settings_cat';
                        $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? AND  id = ? LIMIT 1");
                        $stmt2->bind_param("ii", $tableId,  $settingCat);
                        $stmt2->execute();
                        $result2 = $stmt2->get_result();
                        if($result2->num_rows === 0) echo "Ingen mulige valg, venligst tilføj nogen"; else {
                            while($row2 = $result2->fetch_assoc()) {
                                $setting_cat_settingId = $row2['id'];
                            }
                        }
                        $stmt2->close();

                        // Disabled handling
                        if ($disabled == 1) $disabledClass = "disabled"; else $disabledClass = "";
                        
                        // Getting radio content
                        $table_name2 = $wpdb->prefix . 'htx_settings';
                        $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE settingId = ? AND active = 1 ORDER by sorting ASC, value ASC");
                        $stmt2->bind_param("i", $setting_cat_settingId);
                        $stmt2->execute();
                        $result2 = $stmt2->get_result();
                        if($result2->num_rows === 0) echo "Ingen mulige valg, venligst tilføj nogen"; else {
                            while($row2 = $result2->fetch_assoc()) {
                                // Getting data
                                $setting_settingName = $row2['settingName'];
                                $setting_id = $row2['id'];
    
                                // Write data
                                echo "<input type='radio' id='$columnNameBack-$setting_id' name='$columnNameBack' value='$setting_id' class='radio $disabledClass' disabled>
                                <label for='$columnNameBack-$setting_id' class='radio $disabledClass'>$setting_settingName</label><br>";

                            }
                        }
                        $stmt2->close();
    
                        // Finishing dropdown
                        echo "</select>";
                    break;
                    case "text area":
                        echo "<p>$placeholderText</p>";
                    break;
                    default:
                        // Input preview
                        echo "<input type='$format' value='$placeholderText' class='inputBox' disabled>";
                    break;
                }
                // End write
                echo "</div>";
            }
        }
        $stmt->close();

        // Create new row
        echo "<form method=\"post\" class='addColumn'>";
        echo "<h4>Tilføj ny række</h4>";
        // Drop down with possible types of input field
        echo "<label>Input type: </label><br><select name='inputType'>";
        for ($i=0; $i < count($possibleInput); $i++) { 
            echo "<option value='$possibleInput[$i]'>".ucfirst($possibleInput[$i])."</option>";
        }
        echo "</select><br><br>";
        echo "<button type='submit' name='submit' value='newColumn' class='btn updateBtn' style='margin-top: 0.25rem'>Tilføj række</button>";
        echo "</form>";

        // Post handling from form
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            switch  ($_POST['submit']) {
                // New submission
                case 'newColumn':
                    try {
                        $link->autocommit(FALSE); //turn on transactions
                        // User input
                        $userInputType = $_POST['inputType'];
                        // Break if the user input is not known
                        if (!in_array($userInputType, $possibleInput)) break;
                        // Define values for new element
                        $columnNameFront = "New element"; $format=$possibleFormat[0]; $columnType=$userInputType; $special=0; $specialName=""; 
                        $placeholderText = ""; $required = 0; $settingCat = 0; $sorting = $sorting+1;
                        if ($userInputType == 'dropdown') {
                            // If dropdown, then make setting category first
                            $table_name = $wpdb->prefix . 'htx_settings_cat';
                            $stmt = $link->prepare("INSERT INTO $table_name (tableId, settingNameBack, settingType, special, specialName) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("iss", $tableId, $columnNameBack, $columnType, $special, $specialName);
                            $stmt->execute();
                            $settingCat = intval($link->insert_id);
                            if ($settingCat < 0) throw new Exception('Setting cat is bad');
                            $stmt->close();

                            // Insert standard first setting
                            $table_name = $wpdb->prefix . 'htx_settings';
                            $link->autocommit(FALSE); //turn on transactions
                            $stmt = $link->prepare("INSERT INTO $table_name (settingId, settingName, value, special, specialName, type) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("ississ", $settingCat, $settingName, $value, $special, $specialName, $settingType);
                            $settingName = "new setting"; $value="new setting"; $settingType="dropdown";
                            $stmt->execute(); 
                            $stmt->close();
                        }
                        if ($userInputType == 'radio') {
                            // If dropdown, then make setting category first
                            $table_name = $wpdb->prefix . 'htx_settings_cat';
                            $stmt = $link->prepare("INSERT INTO $table_name (tableId, settingNameBack, settingType, special, specialName) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("iss", $tableId, $columnNameBack, $columnType, $special, $specialName);
                            $stmt->execute();
                            $settingCat = intval($link->insert_id);
                            if ($settingCat < 0) throw new Exception('Setting cat is bad');
                            $stmt->close();

                            // Insert standard first setting
                            $table_name = $wpdb->prefix . 'htx_settings';
                            $link->autocommit(FALSE); //turn on transactions
                            $stmt = $link->prepare("INSERT INTO $table_name (settingId, settingName, value, special, specialName, type) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("ississ", $settingCat, $settingName, $value, $special, $specialName, $settingType);
                            $settingName = "new setting"; $value="new setting"; $settingType="radio";
                            $stmt->execute(); 
                            $stmt->close();
                        }
                        
                        // Create new column, with standard values, and user input
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'htx_column';
                        $stmt = $link->prepare("INSERT INTO $table_name (tableId, columnNameFront, format, columnType, special, specialName, sorting, placeholderText, required, settingCat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("isssssisii", $tableId, $columnNameFront, $format, $columnType, $special, $specialName, $sorting, $placeholderText, $required, $settingCat);
                        $stmt->execute();
                        $lastId = intval($link->insert_id);
                        if ($lastId < 0) throw new Exception('Id is bad');
                        $stmt->close();
                        $stmt = $link->prepare("UPDATE $table_name SET columnNameBack = ? WHERE id = ?");
                        $stmt->bind_param("ii", $lastId, $lastId);
                        $stmt->execute();
                        $stmt->close();
                        if ($userInputType == 'dropdown' OR $userInputType == 'radio') {
                            // If dropdown, then update settingNameBack with id from column
                            $table_name = $wpdb->prefix . 'htx_settings_cat';
                            $stmt = $link->prepare("UPDATE $table_name SET settingNameBack = ? WHERE id = ?");
                            $stmt->bind_param("ii", $lastId, $settingCat);
                            $stmt->execute();
                            $stmt->close();
                        }

                        $link->autocommit(TRUE); //turn off transactions + commit queued queries

                        // Alert user through prompt
                        echo "<script>location.reload();</script>"; #Because this post is after the form, there may be problems with not loading in
                    } catch(Exception $e) {
                        $mysqli->rollback(); //remove all queries from queue if error (undo)
                        throw $e;
                    }
                break;
            }
        }

        // End div
        echo "</div>";

        echo "</div></div>";

        // Settings menu
        echo "<div class='formCreator_settings' id='formCreator_settings'><form method=\"post\">";
        if (isset($_GET['setting']) AND in_array($_GET['setting'],$settingIds)) {
            $setting = $_GET['setting'];

            // Post handling
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                switch  ($_POST['submit']) {
                    // New submission
                    case 'updateSetting':
                        try {
                            // Update column settings
                            if (isset($_POST['settingsTrue']) AND $_POST['settingsTrue'] == "1") {
                                // There are settings
                                // Getting number of settings - Checking settingsAmount is a number
                                if (isset($_POST['settingsAmount']) AND intval($_POST['settingsAmount']) > 0) {
                                    $settingAmount = intval($_POST['settingsAmount']);
                                    try {
                                        $link->autocommit(FALSE); //turn on transactions
                                        $table_name = $wpdb->prefix . 'htx_settings';
                                        $stmt1 = $link->prepare("UPDATE `$table_name` SET settingName = ?, value = ?, sorting = ?, active = ? WHERE id = ?");
                                        
                                        for ($i=0; $i < $settingAmount; $i++) { 
                                            // Update every setting
                                            // Id for line
                                            $lineId = intval($_POST['settingId-'.$i]);
                                            if (intval($_POST['settingActive-'.$lineId]) != 0 AND intval($_POST['settingActive-'.$lineId]) != 1) $active = 1; else $active = trim($_POST['settingActive-'.$lineId]);
                                            $stmt1->bind_param("ssiii", htmlspecialchars(trim($_POST['settingName-'.$lineId])), htmlspecialchars(trim($_POST['settingValue-'.$lineId])), intval($_POST['settingSorting-'.$lineId]), $active, $lineId);
                                            $stmt1->execute();
                                        }
                                        
                                        $stmt1->close();
                                        $link->autocommit(TRUE); //turn off transactions + commit queued queries
                                    } catch(Exception $e) {
                                        $link->rollback(); //remove all queries from queue if error (undo)
                                        throw $e;
                                    }
                                    
                                }
                            }

                            // Update normal settings
                        
                            if (!isset($_POST['placeholderText'])) $placeholderText = ""; else $placeholderText = trim($_POST['placeholderText']);
                            if (intval($_POST['disabled']) == 1) $required = 0; else $required = intval($_POST['required']); #Disabeling the option for both required and hidden input
                            if (in_array(trim($_POST['format']), $possibleFormat)) $formatPost = htmlspecialchars(trim($_POST['format'])); else $formatPost = $possibleFormat[0];
                            if (trim($_POST['columnNameFront']) == "") break;
                            $link->autocommit(FALSE); //turn on transactions
                            $table_name = $wpdb->prefix . 'htx_column';
                            $stmt1 = $link->prepare("UPDATE `$table_name` SET columnNameFront = ?, format = ?, special = ?, specialName = ?, sorting = ?, required = ?, disabled = ?, placeholderText = ? WHERE id = ?");
                            $stmt1->bind_param("ssisiiisi", htmlspecialchars(trim($_POST['columnNameFront'])), $formatPost, $speciealPost, $specialPostArray, intval($_POST['sorting']), $required, intval($_POST['disabled']), $placeholderText, $setting);
                            if ($_POST['specialName'] == "") $speciealPost = 0; else $speciealPost = 1;
                            // Updating special, and inserting as array
                            if(!empty($_POST['specialName'])) {
                                foreach($_POST['specialName'] as $specials) {
                                    $specialPostArrayStart[] = $specials;
                                }
                                $specialPostArray = implode(",", $specialPostArrayStart);
                            } else $specialPostArray = "";

                            $stmt1->execute();
                            $stmt1->close();

                            $link->autocommit(TRUE); //turn off transactions + commit queued queries
                            // echo "<script>location.reload();</script>";
                        } catch(Exception $e) {
                            $link->rollback(); //remove all queries from queue if error (undo)
                            throw $e;
                        }
                    break;
                    case 'updateSorting':
                        try {
                            // Update sorting
                            $link->autocommit(FALSE); //turn on transactions
                            $table_name = $wpdb->prefix . 'htx_column';
                            $stmt1 = $link->prepare("UPDATE `$table_name` SET sorting = ? WHERE id = ?");
                            $stmt1->bind_param("ii", intval($_POST['sorting']), $setting);
                            $stmt1->execute();
                            $stmt1->close();

                            $link->autocommit(TRUE); //turn off transactions + commit queued queries
                            echo "<script>location.reload();</script>";
                        } catch(Exception $e) {
                            $link->rollback(); //remove all queries from queue if error (undo)
                            throw $e;
                        }
                    break;
                    case 'deleteColumn':
                        // Delete form element, with setting cat / settings if exist
                        try {
                            $link->autocommit(FALSE); //turn on transactions

                            // Delete cat
                            if (isset($_POST['settingsId']) AND intval($_POST['settingsId']) > 0) {
                                $settingsId = intval($_POST['settingsId']);
                                $table_name = $wpdb->prefix . 'htx_settings_cat';
                                $stmt = $link->prepare("DELETE FROM $table_name WHERE id = ?");
                                $stmt->bind_param("i", $settingsId);
                                $stmt->execute(); 
                                $stmt->close();

                                // Delete settings
                                if (isset($_POST['settingsTrue']) AND $_POST['settingsTrue'] == "1") {
                                    // There are settings
                                    // Getting number of settings - Checking settingsAmount is a number
                                    if (isset($_POST['settingsAmount']) AND intval($_POST['settingsAmount']) > 0) {
                                        $settingAmount = intval($_POST['settingsAmount']);
                                        $link->autocommit(FALSE); //turn on transactions
                                        $table_name = $wpdb->prefix . 'htx_settings';
                                        $stmt1 = $link->prepare("DELETE FROM $table_name WHERE id = ?");
                                        
                                        for ($i=0; $i < $settingAmount; $i++) { 
                                            // Update every setting
                                            // Id for line
                                            $lineId = intval($_POST['settingId-'.$i]);
                                            $stmt1->bind_param("i", $lineId);
                                            $stmt1->execute();
                                        }
                                        
                                        $stmt1->close();
                                    }
                                }
                            }
                            // Delete column
                            $table_name = $wpdb->prefix . 'htx_column';
                            $stmt1 = $link->prepare("DELETE FROM $table_name WHERE id = ?");
                            $stmt1->bind_param("i", $setting);
                            $stmt1->execute();
                            $stmt1->close();

                            // Delete form inputs from users
                            $table_name = $wpdb->prefix . 'htx_form';
                            $stmt1 = $link->prepare("DELETE FROM $table_name WHERE name = ?");
                            $stmt1->bind_param("i", $setting);
                            $stmt1->execute();
                            $stmt1->close();
                            
                            $link->autocommit(TRUE); //turn off transactions + commit queued queries
                            echo "<script>location.reload();</script>";
                        } catch(Exception $e) {
                            $link->rollback(); //remove all queries from queue if error (undo)
                            throw $e;
                        }
                        
                    break;
                    case 'addSetting':
                        // Add new setting element
                        if (isset($_POST['settingsId']) AND intval($_POST['settingsId']) > 0) {
                            $idNewSetting = intval($_POST['settingsId']);
                            try {
                                $table_name = $wpdb->prefix . 'htx_settings';
                                $link->autocommit(FALSE); //turn on transactions
                                $stmt = $link->prepare("INSERT INTO $table_name (settingId, settingName, value, special, specialName, type) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("ississ", $idNewSetting, $settingName, $value, $special, $specialName, $settingType);
                                $settingName = "new setting"; $value="new setting"; $special=0; $specialName="";
                                if (in_array($_POST['columnType'], $possibleInput)) $settingType=htmlspecialchars($_POST['columnType']); else $settingType="dropdown";
                                $stmt->execute(); 
                                $stmt->close();
                                $link->autocommit(TRUE); //turn off transactions + commit queued queries
                            } catch(Exception $e) {
                                $link->rollback(); //remove all queries from queue if error (undo)
                                throw $e;
                            }
                        }
                    break;
                }
                if (isset($_POST['deleteSetting']) AND intval($_POST['deleteSetting']) > 0) {
                    $settingId = intval($_POST['deleteSetting']);
                    try {
                        $table_name = $wpdb->prefix . 'htx_settings';
                        $link->autocommit(FALSE); //turn on transactions
                        $stmt = $link->prepare("DELETE FROM $table_name WHERE id = ?");
                        $stmt->bind_param("i", $settingId);
                        $stmt->execute(); 
                        $stmt->close();
                        $link->autocommit(TRUE); //turn off transactions + commit queued queries
                    } catch(Exception $e) {
                        $link->rollback(); //remove all queries from queue if error (undo)
                        throw $e;
                    }
                }
            }

            // Make div
            echo "<div id='setting-form-$tableId'>";
            // Column info
            $table_name = $wpdb->prefix . 'htx_column';
            $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableid = ? AND id = ?");
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
                    $specialName = explode(",", $row['specialName']);
                    $placeholderText = $row['placeholderText'];
                    $sorting = $row['sorting'];
                    $disabled = $row['disabled'];
                    $required = $row['required'];
                    $settingCat = $row['settingCat'];
                    
                    // Write
                    echo "<div id='settingEdit-$settingTableId-$settingId'><h3>$columnNameFront</h3>";

                    if ($columnNameBack == "email") {
                        echo "<p>Dette input kan ikke ændres, fordi dette input er essentielt for pluginet.</p><p>Du kan dog ændre placeringen herunder.</p></div>";
                        // Sorting
                        echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            
                        // make submit button
                        echo "<button type='submit' name='submit' value='updateSorting' class='btn updateBtn' style='margin-right: 0.5rem;'>Opdater</button>";

                        break;
                    }

                    switch ($columnType) {
                        case "dropdown":
                            echo "<div class='formCreator_edit_container formCreator_flexColumn'>";
                            // Name
                            echo "<div><label for='settingName'>Navn </label> <input type='text' id='settingName' class='inputBox' name='columnNameFront' value='$columnNameFront'></div>";
                            // Column type
                            echo "<div style='margin-bottom:0.5rem'><label>Input type <br><i>$columnType</i></label></div>";
                            // Special name
                            echo "<div style='margin-bottom:0.5rem'><label>Funktioner</label><div class='formCreator_flexRow'>";
                                for ($i=0; $i < count($possibleFunctions); $i++) { 
                                    if (in_array($possibleFunctions[$i], $specialName)) $selected = "checked"; else $selected = "";
                                    echo "<div style='width: unset'><input type='checkbox' name='specialName[]' id='function-$i' value='$possibleFunctions[$i]' $selected>
                                    <label for='function-$i'>$possibleFunctionsName[$i]</label></div>";
                                }
                            echo "</div></div>";
                            // Sorting
                            echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            // Required
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                            // Disabled
                            echo "<input type='hidden' name='disabled' value='0'>";
                            echo "<div><label for='settingDisabled'>Deaktiveret </label><input id='settingDisabled' type='checkbox' class='inputCheckbox' name='disabled' value='1'";
                            if ($disabled == 1) echo "checked";
                            echo "></div>";
                            // Dropdown options
                            echo "<h4>Dropdown muligheder</h4>";

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
                                    $i = 0;
                                    if($result3->num_rows === 0) echo "<div><p>Ingen radio muligheder</p></div>"; else {
                                        while($row3 = $result3->fetch_assoc()) {
                                            $row3['id'];
                                            $row3['settingName'];
                                            $rowSettingId = $row3['value'];

                                            echo "<div><label for='extraSettingName-$i'>Navn</label> <input type='text' id='extraSettingName-$i' class='inputBox' name='settingName-".$row3['id']."' value='".$row3['settingName']."'></div>";
                                            echo "<div><label for='extraSettingValue-$i'>Værdi</label> <input type='text' id='extraSettingValue-$i' class='inputBox' name='settingValue-".$row3['id']."' value='".$row3['value']."''></div>";
                                            echo "<div><label for='extraSettingSorting-$i'>Sortering</label> <input type='number' step='1' min='0' id='extraSettingSorting-$i' class='inputBox' name='settingSorting-".$row3['id']."' value='".$row3['sorting']."'></div>";
                                            echo "<input type='hidden' name='settingActive-".$row3['id']."' value='1'>";
                                            echo "<div><label for='extraSettingDisabled-$i'>Deaktiveret </label><input id='extraSettingDisabled-$i' type='checkbox' class='inputCheckbox' name='settingActive-".$row3['id']."' value='0'";
                                            if ($row3['active'] == 0) echo "checked";
                                            echo "></div>";
                                            echo "<input class='inputBox hidden' name='settingId-$i' value='".$row3['id']."'>";
                                            echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                            echo "<div style='width: 100%;margin-bottom:1.75rem;'><button type='submit' name='deleteSetting' value='".$row3['id']."' class='btn deleteBtn'>Slet</button></div>";

                                            $i++;
                                        }
                                        
                                    }
                                    $stmt3->close();
                                    echo "<input class='inputBox hidden' name='settingsTrue' value='1'>";
                                    echo "<input class='inputBox hidden' name='settingsAmount' value='$i'>";
                                    echo "<input class='inputBox hidden' name='settingsId' value='". $row2['id']."'>";
                                    echo "<input class='inputBox hidden' name='columnType' value='$columnType'>";
                                    echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                    echo "<div style='width: 100%;'><button type='submit' name='submit' value='addSetting' class='btn updateBtn'>Tilføj</button></div>";
                                }
                            }
                            $stmt2->close();
                            echo "</div>";
                        break;
                        case "radio":
                            echo "<div class='formCreator_edit_container formCreator_flexRow'>";
                            // Name
                            echo "<div><label for='settingName'>Navn </label> <input type='text' id='settingName' class='inputBox' name='columnNameFront' value='$columnNameFront'></div>";
                            // Column type
                            echo "<div style='margin-bottom:0.5rem'><label>Input type <br><i>$columnType</i></label></div>";
                            // Special name
                            echo "<div style='margin-bottom:0.5rem'><label>Funktioner</label><div class='formCreator_flexRow'>";
                                for ($i=0; $i < count($possibleFunctions); $i++) { 
                                    if (in_array($possibleFunctions[$i], $specialName)) $selected = "checked"; else $selected = "";
                                    echo "<div style='width: unset'><input type='checkbox' name='specialName[]' id='function-$i' value='$possibleFunctions[$i]' $selected>
                                    <label for='function-$i'>$possibleFunctionsName[$i]</label></div>";
                                }
                            echo "</div></div>";
                            // Sorting
                            echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            // Required
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                            // Disabled
                            echo "<input type='hidden' name='disabled' value='0'>";
                            echo "<div><label for='settingDisabled'>Deaktiveret </label><input id='settingDisabled' type='checkbox' class='inputCheckbox' name='disabled' value='1'";
                            if ($disabled == 1) echo "checked";
                            echo "></div>";
                            // Dropdown options
                            echo "<div><h4>Radio muligheder</h4></div>";

                            // Getting radio setting category
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
                                    $i = 0;
                                    if($result3->num_rows === 0) echo "<div><p>Ingen radio muligheder, venligst tilføj en, før formularen kan bruges</p></div>"; else {
                                        while($row3 = $result3->fetch_assoc()) {
                                            $row3['id'];
                                            $row3['settingName'];
                                            $rowSettingId = $row3['value'];

                                            echo "<div><label for='extraSettingName-$i'>Navn</label> <input type='text' id='extraSettingName-$i' class='inputBox' name='settingName-".$row3['id']."' value='".$row3['settingName']."'></div>";
                                            echo "<div><label for='extraSettingValue-$i'>Værdi</label> <input type='text' id='extraSettingValue-$i' class='inputBox' name='settingValue-".$row3['id']."' value='".$row3['value']."''></div>";
                                            echo "<div><label for='extraSettingSorting-$i'>Sortering</label> <input type='number' step='1' min='0' id='extraSettingSorting-$i' class='inputBox' name='settingSorting-".$row3['id']."' value='".$row3['sorting']."'></div>";
                                            echo "<input type='hidden' name='settingActive-".$row3['id']."' value='1'>";
                                            echo "<div><label for='extraSettingDisabled-$i'>Deaktiveret </label><input id='extraSettingDisabled-$i' type='checkbox' class='inputCheckbox' name='settingActive-".$row3['id']."' value='0'";
                                            if ($row3['active'] == 0) echo "checked";
                                            echo "></div>";
                                            echo "<input class='inputBox hidden' name='settingId-$i' value='".$row3['id']."'>";
                                            echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                            echo "<div style='width: 100%;margin-bottom:1.75rem;'><button type='submit' name='deleteSetting' value='".$row3['id']."' class='btn deleteBtn'>Slet</button></div>";

                                            $i++;
                                        }
                                        
                                    }
                                    $stmt3->close();
                                    echo "<input class='inputBox hidden' name='settingsTrue' value='1'>";
                                    echo "<input class='inputBox hidden' name='settingsAmount' value='$i'>";
                                    echo "<input class='inputBox hidden' name='settingsId' value='". $row2['id']."'>";
                                    echo "<input class='inputBox hidden' name='columnType' value='$columnType'>";
                                    echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                    echo "<div style='width: 100%;'><button type='submit' name='submit' value='addSetting' class='btn updateBtn'>Tilføj</button></div>";
                                }
                            }
                            $stmt2->close();
                            echo "</div>";
                        break;
                        case 'inputbox':
                            echo "<div class='formCreator_edit_container formCreator_flexRow'>";
                            // Name
                            echo "<div><label for='settingName'>Navn </label> <input type='text' id='settingName' class='inputBox' name='columnNameFront' value='$columnNameFront'></div>";
                            // Format
                            echo "<div><label for='settingFormat'>Format </label> <br><select id='settingFormat' class='inputBox' name='format'>";
                            for ($i=0; $i < count($possibleFormat); $i++) { 
                                if ($possibleFormat[$i] == $format) $selected = "selected"; else $selected = "";
                                echo "<option value='$possibleFormat[$i]' $selected>$possibleFormat[$i]</option>";
                            }
                            echo "</select></div>";
                            // Column type
                            echo "<div style='margin-bottom:0.5rem'><label>Input type <br><i>$columnType</i></label></div>";
                            // Special name
                            echo "<div style='margin-bottom:0.5rem'><label>Funktioner</label><div class='formCreator_flexRow'>";
                                for ($i=0; $i < count($possibleFunctions); $i++) { 
                                    if (in_array($possibleFunctions[$i], $specialName)) $selected = "checked"; else $selected = "";
                                    echo "<div style='width: unset'><input type='checkbox' name='specialName[]' id='function-$i' value='$possibleFunctions[$i]' $selected>
                                    <label for='function-$i'>$possibleFunctionsName[$i]</label></div>";
                                }
                            echo "</div></div>";
                            // Placeholder text
                            echo "<div><label for='settingPlaceholder'>Placeholder tekst </label><input type='$format' id='settingPlaceholder' class='inputBox' name='placeholderText' value='$placeholderText'></div>";
                            // Sorting
                            echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            // Required
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                            // Disabled
                            echo "<input type='hidden' name='disabled' value='0'>";
                            echo "<div><label for='settingDisabled'>Deaktiveret </label><input id='settingDisabled' type='checkbox' class='inputCheckbox' name='disabled' value='1'";
                            if ($disabled == 1) echo "checked";
                            echo "></div>";
                        break;
                        case "text area":
                            echo "<div class='formCreator_edit_container formCreator_flexRow'>";
                            // Name
                            echo "<div><label for='settingName'>Overskrift </label> <input type='text' id='settingName' class='inputBox' name='columnNameFront' value='$columnNameFront'></div>";
                            // Column type
                            echo "<div style='margin-bottom:0.5rem'><label>Input type <br><i>$columnType</i></label></div>";
                            // Placeholder text
                            echo "<div><label for='settingPlaceholder'>Tekst </label><br><textarea id='settingPlaceholder' class='textArea' name='placeholderText'>$placeholderText</textarea></div>";
                            // Sorting
                            echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            // Disabled
                            echo "<input type='hidden' name='disabled' value='0'>";
                            echo "<div><label for='settingDisabled'>Deaktiveret </label><input id='settingDisabled' type='checkbox' class='inputCheckbox' name='disabled' value='1'";
                            if ($disabled == 1) echo "checked";
                            echo "></div>";

                            // Special name (hidden)
                            echo "<div class='hidden'><label for='settingSpecial'>Funktion navn </label> <input id='settingSpecial' class='inputBox' name='specialName' value='$specialName'></div>";
                            // Format (hidden)
                            echo "<div class='hidden'><label for='settingFormat'>Format </label> <input id='settingFormat' class='inputBox' name='format' value='text'></div>";
                            // Required (hidden)
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div class='hidden'><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                        break;
                        default:
                            echo "<div class='formCreator_edit_container formCreator_flexRow'>";
                            // Name
                            echo "<div><label for='settingName'>Navn </label> <input type='text' id='settingName' class='inputBox' name='columnNameFront' value='$columnNameFront'></div>";
                            // Format
                            echo "<div><label for='settingFormat'>Format </label> <br><select id='settingFormat' class='inputBox' name='format'>";
                            for ($i=0; $i < count($possibleFormat); $i++) { 
                                if ($possibleFormat[$i] == $format) $selected = "selected"; else $selected = "";
                                echo "<option value='$possibleFormat[$i]' $selected>$possibleFormat[$i]</option>";
                            }
                            echo "</select></div>";
                            // Column type
                            echo "<div style='margin-bottom:0.5rem'><label>Input type <br><i>$columnType</i></label></div>";
                            // Special name
                            echo "<div style='margin-bottom:0.5rem'><label>Funktioner</label><div class='formCreator_flexRow'>";
                                for ($i=0; $i < count($possibleFunctions); $i++) { 
                                    if (in_array($possibleFunctions[$i], $specialName)) $selected = "checked"; else $selected = "";
                                    echo "<div style='width: unset'><input type='checkbox' name='specialName[]' id='function-$i' value='$possibleFunctions[$i]' $selected>
                                    <label for='function-$i'>$possibleFunctionsName[$i]</label></div>";
                                }
                            echo "</div></div>";
                            // Sorting
                            echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            // Required
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                            // Disabled
                            echo "<input type='hidden' name='disabled' value='0'>";
                            echo "<div><label for='settingDisabled'>Deaktiveret </label><input id='settingDisabled' type='checkbox' class='inputCheckbox' name='disabled' value='1'";
                            if ($disabled == 1) echo "checked";
                            echo "></div>";
                        break;
                    }
                    
                    echo "</div>";
                    // make submit button
                    echo "<button type='submit' name='submit' value='updateSetting' class='btn updateBtn' style='margin-right: 0.5rem;'>Opdater</button>";
                    echo "<button type='delete' name='submit' value='deleteColumn' class='btn deleteBtn'>Slet</button>";
                }
            }
            $stmt->close();

            // End div
            echo "</div></form>";
        } else if (isset($_GET['setting']) AND $_GET['setting'] == 0) {
            // Post handling
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                switch  ($_POST['submit']) {
                    // New submission
                    case 'updateForm':
                        try {
                            $link->autocommit(FALSE); //turn on transactions
                            $table_name = $wpdb->prefix . 'htx_form_tables';
                            $stmt = $link->prepare("UPDATE $table_name SET tableName = ?, tableDescription = ? WHERE id = ?");
                            $stmt->bind_param("ssi",$_POST['tableName'], $_POST['tableDescription'], $tableId);
                            $stmt->execute();
                            $stmt->close();
                            $link->autocommit(TRUE); //turn off transactions + commit queued queries
                        } catch(Exception $e) {
                            $link->rollback(); //remove all queries from queue if error (undo)
                            throw $e;
                        }
                    break;
                    case 'deleteForm':
                        echo "<script>confirm('Denn feature er endu ikke sat op');</script>";
                    break;
                }
            }


            // Show form settings
            echo "<h3>Formular indstillinger</h3>";
            echo "<div class='formCreator_edit_container formCreator_flexRow'>";
            $table_name = $wpdb->prefix . 'htx_form_tables';
            $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) echo "Noget gik galt"; else {
                while($row = $result->fetch_assoc()) {
                    // Info
                    $tableShortcode = $row['shortcode'];
                    $tableName = $row['tableName'];
                    $tableDescription = $row['tableDescription'];
                    $taleDateCreate = $row['dateCreate'];
                    $tableDateUpdate = $row['dateUpdate'];

                    // Name
                    echo "<div><label for='tableName'>Navn </label> <input type='text' id='tableName' class='inputBox' name='tableName' value='$tableName'></div>";
                    // Description
                    echo "<div><label for='tableDescription'>Beskrivelse </label> <br><textarea id='tableDescription' class='textArea' name='tableDescription'>$tableDescription</textarea></div>";
                    // Shortcode
                    echo "<div><label>Shortcode </label> <br><i>[$tableShortcode form='$tableId']</i></div>";
                    // Shortcode
                    echo "<div><label>Dato oprettet </label> <br><i>$taleDateCreate</i></div>";


                    echo "</div>";
                    // make submit button
                    echo "<button type='submit' name='submit' value='updateForm' class='btn updateBtn' style='margin-right: 0.5rem;'>Opdater</button>";
                    echo "<button type='delete' name='submit' value='deleteForm' class='btn deleteBtn'>Slet</button>";
                
                }
            }
        } else if (isset($_GET['setting'])) echo "Den valgte input blev ikke fundet";

        echo "</div>";

    } else if (isset($_GET['form'])) echo "Den valgte formular blev ikke fundet";


    // Ending main area
    echo "</div>";
?>