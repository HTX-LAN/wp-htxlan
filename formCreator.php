<?php
    //Prevent direct file access
    if(!defined('ABSPATH')) {
        header("Location: ../../../");
        die();
    }

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




        echo "<br><br><button type='submit' class='btn' name='submit' value='newForm' onclick='HTXJS_createForm()'>Tilføj ny formular</button><br>";

    echo "</div></div>";

    // Check if form exist
    if (!$noTable AND isset($_GET['form']) AND in_array($_GET['form'],$tableIds)) {
        $tableId = $_GET['form'];

        // Content edit menu
        echo "<div class='formCreator_edit rtl' id='formCreator_edit'><div class='ltr'>";

        // Possible input types in array
        $possibleInput = array("inputbox", "dropdown", "user dropdown", "text area", "radio", "checkbox", "price"); #Missing: checkboxes with text input (for ex team names per game basis), range

        // Possible formats types in array
        $possibleFormat = array("text", "number", "email", 'url', 'color', 'date', 'time', 'week', 'month', 'tel');

        // Possible prices types in array
        $possiblePrice = array("", "DKK", ",-", "kr.", 'danske kroner', '$', 'NOK', 'SEK', 'dollars', 'euro');

        // Possible functions
        $possibleFunctions = array('price_intrance', 'price_extra','teams');
        $possibleFunctionsName = array('Indgangs pris', 'Ekstra pris','Hold valg');
        $possibleFunctionsNonInput = array('price_intrance', 'price_extra');
        $possibleFunctionsNonInputName = array('Indgangs pris', 'Ekstra pris');
        $possibleUniceFunctions = array('price_intrance', 'price_extra');
        $possibleUniceFunction = array("onchange='HTXJS_unCheckFunctionCheckbox(\"1\")'","onchange='HTXJS_unCheckFunctionCheckbox(\"0\")'");
        $possibleFunctionsAll = array('teams');
        $possibleFunctionsAllName = array('Hold valg');

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
                        if($result2->num_rows === 0) {echo "Ingen mulige valg, venligst tilføj nogen"; break;} else {
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
                        if($result2->num_rows === 0) {echo "Ingen mulige valg, venligst tilføj nogen"; break;} else {
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
                    case "user dropdown":
                        // Getting settings category
                        $table_name2 = $wpdb->prefix . 'htx_settings_cat';
                        $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? AND  id = ? AND active = 1 LIMIT 1");
                        $stmt2->bind_param("ii", $tableId,  $settingCat);
                        $stmt2->execute();
                        $result2 = $stmt2->get_result();
                        if($result2->num_rows === 0) {echo "Ingen mulige valg, venligst tilføj nogen"; break;} else {
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
                        if($result2->num_rows === 0) {echo "Ingen mulige valg, venligst tilføj nogen"; break;} else {
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
                        if($result2->num_rows === 0) {echo "Ingen mulige valg, venligst tilføj nogen"; break;} else {
                            while($row2 = $result2->fetch_assoc()) {
                                $setting_cat_settingId = $row2['id'];
                            }
                            // Disabled handling
                            if ($disabled == 1) $disabledClass = "disabled"; else $disabledClass = "";

                            // Getting radio content
                            $table_name3 = $wpdb->prefix . 'htx_settings';
                            $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE settingId = ? AND active = 1 ORDER by sorting ASC, value ASC");
                            $stmt3->bind_param("i", $setting_cat_settingId);
                            $stmt3->execute();
                            $result3 = $stmt3->get_result();
                            if($result3->num_rows === 0) {echo "Ingen mulige valg, venligst tilføj nogen"; break;} else {
                                while($row3 = $result3->fetch_assoc()) {
                                    // Getting data
                                    $setting_settingName = $row3['settingName'];
                                    $setting_id = $row3['id'];

                                    // Write data
                                    echo "<input type='radio' id='$columnNameBack-$setting_id' name='$columnNameBack' value='$setting_id' class='radio $disabledClass' disabled>
                                    <label for='$columnNameBack-$setting_id' class='radio $disabledClass'>$setting_settingName</label><br>";

                                }
                            }
                            $stmt3->close();
                        }
                        $stmt2->close();
                    break;
                    case "checkbox":
                        // Getting settings category
                        $table_name2 = $wpdb->prefix . 'htx_settings_cat';
                        $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? AND  id = ? LIMIT 1");
                        $stmt2->bind_param("ii", $tableId,  $settingCat);
                        $stmt2->execute();
                        $result2 = $stmt2->get_result();
                        if($result2->num_rows === 0) {echo "Ingen mulige valg, venligst tilføj nogen"; break;} else {
                            while($row2 = $result2->fetch_assoc()) {
                                $setting_cat_settingId = $row2['id'];
                            }
                            // Disabled handling
                            if ($disabled == 1) $disabledClass = "disabled"; else $disabledClass = "";

                            // Getting radio content
                            $table_name3 = $wpdb->prefix . 'htx_settings';
                            $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE settingId = ? AND active = 1 ORDER by sorting ASC, value ASC");
                            $stmt3->bind_param("i", $setting_cat_settingId);
                            $stmt3->execute();
                            $result3 = $stmt3->get_result();
                            if($result3->num_rows === 0) {echo "Ingen mulige valg, venligst tilføj nogen"; break;} else {
                                echo "<div class='formCreator_flexRow'>";
                                while($row3 = $result3->fetch_assoc()) {
                                    // Getting data
                                    $setting_settingName = $row3['settingName'];
                                    $setting_id = $row3['id'];

                                    // Write data
                                    echo "<div class='checkboxDiv'><input type='checkbox' id='$columnNameBack-$setting_id' name='".$columnNameBack."[]' value='$setting_id' disabled>
                                        <label for='$columnNameBack-$setting_id'>$setting_settingName</label></div>";

                                }
                                echo "</div>";
                            }
                            $stmt3->close();
                        }
                        $stmt2->close();


                    break;
                    case "text area":
                        echo "<p>$placeholderText</p>";
                    break;
                    case "price":
                        if ($format == 'text') $format = 'DKK';
                        echo "<p>$placeholderText PRICE HERE $format</p>";
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
        echo "<h3 style='border-top: grey solid 1px; padding-top: 0.75rem;'>Tilføj ny række</h3>";
        // Drop down with possible types of input field
        echo "<label>Input type: </label><br><select id='inputType'>";
        for ($i=0; $i < count($possibleInput); $i++) {
            echo "<option value='$possibleInput[$i]'>".ucfirst($possibleInput[$i])."</option>";
        }
        echo "</select><br><br>";
        echo "<button type='submit' name='submit' value='newColumn' class='btn updateBtn' style='margin-top: 0.25rem' onclick='HTXJS_addColumn(" . $tableId . ")'>Tilføj række</button>";

        // End div
        echo "</div>";

        echo "</div></div>";

        // Settings menu
        echo "<div class='formCreator_settings' id='formCreator_settings'>";
        if (isset($_GET['setting']) AND in_array($_GET['setting'],$settingIds)) {
            $setting = $_GET['setting'];

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
                        echo "<button type='submit' name='submit' value='updateSorting' class='btn updateBtn' style='margin-right: 0.5rem;' onclick='HTXJS_updateSorting(" . $settingId . ")'>Opdater</button>";

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
                                    if (in_array($possibleFunctions[$i],$possibleUniceFunctions)) $unice = $possibleUniceFunction[$i]; else $unice = "";
                                    echo "<div style='width: unset'><input class='special' type='checkbox' name='specialName[]' id='function-$i' value='$possibleFunctions[$i]' $unice $selected>
                                    <label for='function-$i'>$possibleFunctionsName[$i]</label></div>";
                                }
                            echo "</div></div>";
                            // Sorting
                            echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            // Required
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' onchange='HTXJS_settingDisabledCheckbox(\"disable\")'  type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                            // Disabled
                            echo "<input type='hidden' name='disabled' value='0'>";
                            echo "<div><label for='settingDisabled'>Deaktiveret </label><input id='settingDisabled' onchange='HTXJS_settingDisabledCheckbox(\"enable\")' type='checkbox' class='inputCheckbox' name='disabled' value='1'";
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

                                            echo "<div><label for='extraSettingName-$i'>Navn</label> <input type='text' id='extraSettingName-$i' class='inputBox settingName' name='settingName-".$row3['id']."' value='".$row3['settingName']."'></div>";
                                            echo "<div><label for='extraSettingValue-$i'>Værdi</label> <input type='text' id='extraSettingValue-$i' class='inputBox settingValue' name='settingValue-".$row3['id']."' value='".$row3['value']."''></div>";
                                            if (!in_array($possibleFunctionsNonInput[1], $specialName)) $expenceDisabled = 'hidden'; else $expenceDisabled = '';
                                            echo "<div class='$expenceDisabled'><label for='extraSettingExpence-$i'>Udgift</label> <input type='text' id='extraSettingExpence-$i' class='inputBox settingExpence' name='settingExpence-".$row3['id']."' value='".$row3['expence']."''></div>";
                                            echo "<div><label for='extraSettingSorting-$i'>Sortering</label> <input type='number' step='1' min='0' id='extraSettingSorting-$i' class='inputBox settingSorting' name='settingSorting-".$row3['id']."' value='".$row3['sorting']."'></div>";
                                            echo "<div><label for='extraSettingDisabled-$i'>Deaktiveret </label><input id='extraSettingDisabled-$i' type='checkbox' class='inputCheckbox settingActive' name='settingActive-".$row3['id']."' value='0'";
                                            if ($row3['active'] == 0) echo "checked";
                                            echo "></div>";
                                            echo "<input class='inputBox hidden settingId' name='settingId-$i' value='".$row3['id']."'>";
                                            echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                            echo "<div style='width: 100%;margin-bottom:1.75rem;'><button type='submit' name='deleteSetting' value='".$row3['id']."' class='btn deleteBtn' onclick='HTXJS_deleteSetting(" . $row3['id'] . ")'>Slet</button></div>";

                                            $i++;
                                        }

                                    }
                                    $stmt3->close();
                                    echo "<input class='inputBox hidden' id='settingsTrue' value='1'>";
                                    echo "<input class='inputBox hidden' id='settingsAmount' value='$i'>";
                                    echo "<input class='inputBox hidden' id='settingsId' value='". $row2['id']."'>";
                                    echo "<input class='inputBox hidden' id='columnType' value='$columnType'>";
                                    echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                    echo "<div style='width: 100%;'><button type='submit' name='submit' value='addSetting' class='btn updateBtn' onclick='HTXJS_addSetting(" . $row2['id'] . ", \"dropdown\")'>Tilføj</button></div>";
                                }
                            }
                            $stmt2->close();
                            echo "</div>";
                        break;
                        case "user dropdown":
                            echo "<div class='formCreator_edit_container formCreator_flexColumn'>";
                            // Name
                            echo "<div><label for='settingName'>Navn </label> <input type='text' id='settingName' class='inputBox' name='columnNameFront' value='$columnNameFront'></div>";
                            // Column type
                            echo "<div style='margin-bottom:0.5rem'><label>Input type <br><i>$columnType</i></label></div>";
                            // Special name
                            echo "<div style='margin-bottom:0.5rem'><label>Funktioner</label><div class='formCreator_flexRow'>";
                                for ($i=0; $i < count($possibleFunctionsAll); $i++) {
                                    if (in_array($possibleFunctionsAll[$i], $specialName)) $selected = "checked"; else $selected = "";
                                    if (in_array($possibleFunctionsAll[$i],$possibleUniceFunctions)) $unice = $possibleUniceFunction[$i]; else $unice = "";
                                    echo "<div style='width: unset'><input class='special' type='checkbox' name='specialName[]' id='function-$i' value='$possibleFunctionsAll[$i]' $unice $selected>
                                    <label for='function-$i'>$possibleFunctionsAllName[$i]</label></div>";
                                }
                            echo "</div></div>";
                            // Sorting
                            echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            // Required
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' onchange='HTXJS_settingDisabledCheckbox(\"disable\")'  type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                            // Disabled
                            echo "<input type='hidden' name='disabled' value='0'>";
                            echo "<div><label for='settingDisabled'>Deaktiveret </label><input id='settingDisabled' onchange='HTXJS_settingDisabledCheckbox(\"enable\")' type='checkbox' class='inputCheckbox' name='disabled' value='1'";
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
                                        echo "<div>OBS! der vil blive tilføjet flere muligheder af brugere på hjemmesiden</div>";
                                        while($row3 = $result3->fetch_assoc()) {
                                            $row3['id'];
                                            $row3['settingName'];
                                            $rowSettingId = $row3['value'];

                                            echo "<div><label for='extraSettingName-$i'>Navn</label> <input type='text' id='extraSettingName-$i' class='inputBox settingName' name='settingName-".$row3['id']."' value='".$row3['settingName']."'></div>";
                                            echo "<div><label for='extraSettingValue-$i'>Værdi</label> <input type='text' id='extraSettingValue-$i' class='inputBox settingValue' name='settingValue-".$row3['id']."' value='".$row3['value']."''></div>";
                                            if (!in_array($possibleFunctionsNonInput[1], $specialName)) $expenceDisabled = 'hidden'; else $expenceDisabled = '';
                                            echo "<div class='$expenceDisabled'><label for='extraSettingExpence-$i'>Udgift</label> <input type='text' id='extraSettingExpence-$i' class='inputBox settingExpence' name='settingExpence-".$row3['id']."' value='".$row3['expence']."''></div>";
                                            echo "<div><label for='extraSettingSorting-$i'>Sortering</label> <input type='number' step='1' min='0' id='extraSettingSorting-$i' class='inputBox settingSorting' name='settingSorting-".$row3['id']."' value='".$row3['sorting']."'></div>";
                                            echo "<div><label for='extraSettingDisabled-$i'>Deaktiveret </label><input id='extraSettingDisabled-$i' type='checkbox' class='inputCheckbox settingActive' name='settingActive-".$row3['id']."' value='0'";
                                            if ($row3['active'] == 0) echo "checked";
                                            echo "></div>";
                                            echo "<input class='inputBox hidden settingId' name='settingId-$i' value='".$row3['id']."'>";
                                            echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                            echo "<div style='width: 100%;margin-bottom:1.75rem;'><button type='submit' name='deleteSetting' value='".$row3['id']."' class='btn deleteBtn' onclick='HTXJS_deleteSetting(" . $row3['id'] . ")'>Slet</button></div>";

                                            $i++;
                                        }

                                    }
                                    $stmt3->close();
                                    echo "<input class='inputBox hidden' id='settingsTrue' value='1'>";
                                    echo "<input class='inputBox hidden' id='settingsAmount' value='$i'>";
                                    echo "<input class='inputBox hidden' id='settingsId' value='". $row2['id']."'>";
                                    echo "<input class='inputBox hidden' id='columnType' value='$columnType'>";
                                    echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                    echo "<div style='width: 100%;'><button type='submit' name='submit' value='addSetting' class='btn updateBtn' onclick='HTXJS_addSetting(" . $row2['id'] . ", \"dropdown\")'>Tilføj</button></div>";
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
                                    if (in_array($possibleFunctions[$i],$possibleUniceFunctions)) $unice = $possibleUniceFunction[$i]; else $unice = "";
                                    echo "<div style='width: unset'><input class='special' type='checkbox' name='specialName[]' id='function-$i' value='$possibleFunctions[$i]' $unice $selected>
                                    <label for='function-$i'>$possibleFunctionsName[$i]</label></div>";
                                }
                            echo "</div></div>";
                            // Sorting
                            echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            // Required
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' onchange='HTXJS_settingDisabledCheckbox(\"disable\")'  type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                            // Disabled
                            echo "<input type='hidden' name='disabled' value='0'>";
                            echo "<div><label for='settingDisabled'>Deaktiveret </label><input id='settingDisabled' onchange='HTXJS_settingDisabledCheckbox(\"enable\")' type='checkbox' class='inputCheckbox' name='disabled' value='1'";
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

                                            echo "<div><label for='extraSettingName-$i'>Navn</label> <input type='text' id='extraSettingName-$i' class='inputBox settingName' name='settingName-".$row3['id']."' value='".$row3['settingName']."'></div>";
                                            echo "<div><label for='extraSettingValue-$i'>Værdi</label> <input type='text' id='extraSettingValue-$i' class='inputBox settingValue' name='settingValue-".$row3['id']."' value='".$row3['value']."''></div>";
                                            if (in_array($possibleFunctionsNonInput[1], $specialName)) {
                                                echo "<div><label for='extraSettingExpence-$i'>Udgift</label> <input type='text' id='extraSettingExpence-$i' class='inputBox settingExpence' name='settingExpence-".$row3['id']."' value='".$row3['expence']."''></div>";
                                            }
                                            echo "<div><label for='extraSettingSorting-$i'>Sortering</label> <input type='number' step='1' min='0' id='extraSettingSorting-$i' class='inputBox settingSorting' name='settingSorting-".$row3['id']."' value='".$row3['sorting']."'></div>";
                                            echo "<div><label for='extraSettingDisabled-$i'>Deaktiveret </label><input id='extraSettingDisabled-$i' type='checkbox' class='inputCheckbox settingActive' name='settingActive-".$row3['id']."' value='0'";
                                            if ($row3['active'] == 0) echo "checked";
                                            echo "></div>";
                                            echo "<input class='inputBox hidden settingId' name='settingId-$i' value='".$row3['id']."'>";
                                            echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                            echo "<div style='width: 100%;margin-bottom:1.75rem;'><button type='submit' name='deleteSetting' value='".$row3['id']."' class='btn deleteBtn' onclick='HTXJS_deleteSetting(" . $row3['id'] . ")'>Slet</button></div>";

                                            $i++;
                                        }

                                    }
                                    $stmt3->close();
                                    echo "<input class='inputBox hidden' id='settingsTrue' value='1'>";
                                    echo "<input class='inputBox hidden' id='settingsAmount' value='$i'>";
                                    echo "<input class='inputBox hidden' id='settingsId' value='". $row2['id']."'>";
                                    echo "<input class='inputBox hidden' id='columnType' value='$columnType'>";
                                    echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                    echo "<div style='width: 100%;'><button type='submit' name='submit' value='addSetting' class='btn updateBtn' onclick='HTXJS_addSetting(" . $row2['id'] . ", \"radio\")'>Tilføj</button></div>";
                                }
                            }
                            $stmt2->close();
                            echo "</div>";
                        break;
                        case "checkbox":
                            echo "<div class='formCreator_edit_container formCreator_flexRow'>";
                            // Name
                            echo "<div><label for='settingName'>Navn </label> <input type='text' id='settingName' class='inputBox' name='columnNameFront' value='$columnNameFront'></div>";
                            // Column type
                            echo "<div style='margin-bottom:0.5rem'><label>Input type <br><i>$columnType</i></label></div>";
                            // Special name
                            echo "<div style='margin-bottom:0.5rem'><label>Funktioner</label><div class='formCreator_flexRow'>";
                                for ($i=0; $i < count($possibleFunctions); $i++) {
                                    if (in_array($possibleFunctions[$i], $specialName)) $selected = "checked"; else $selected = "";
                                    if (in_array($possibleFunctions[$i],$possibleUniceFunctions)) $unice = $possibleUniceFunction[$i]; else $unice = "";
                                    echo "<div style='width: unset'><input class='special' type='checkbox' name='specialName[]' id='function-$i' value='$possibleFunctions[$i]' $unice $selected>
                                    <label for='function-$i'>$possibleFunctionsName[$i]</label></div>";
                                }
                            echo "</div></div>";
                            // Sorting
                            echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            // Required
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' onchange='HTXJS_settingDisabledCheckbox(\"disable\")'  type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                            // Disabled
                            echo "<input type='hidden' name='disabled' value='0'>";
                            echo "<div><label for='settingDisabled'>Deaktiveret </label><input id='settingDisabled' onchange='HTXJS_settingDisabledCheckbox(\"enable\")' type='checkbox' class='inputCheckbox' name='disabled' value='1'";
                            if ($disabled == 1) echo "checked";
                            echo "></div>";
                            // Dropdown options
                            echo "<div><h4>Checkbox muligheder</h4></div>";

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

                                            echo "<div><label for='extraSettingName-$i'>Navn</label> <input type='text' id='extraSettingName-$i' class='inputBox settingName' name='settingName-".$row3['id']."' value='".$row3['settingName']."'></div>";
                                            echo "<div><label for='extraSettingValue-$i'>Værdi</label> <input type='text' id='extraSettingValue-$i' class='inputBox settingValue' name='settingValue-".$row3['id']."' value='".$row3['value']."''></div>";
                                            if (in_array($possibleFunctionsNonInput[1], $specialName)) {
                                                echo "<div><label for='extraSettingExpence-$i'>Udgift</label> <input type='text' id='extraSettingExpence-$i' class='inputBox settingExpence' name='settingExpence-".$row3['id']."' value='".$row3['expence']."''></div>";
                                            }
                                            echo "<div><label for='extraSettingSorting-$i'>Sortering</label> <input type='number' step='1' min='0' id='extraSettingSorting-$i' class='inputBox settingSorting' name='settingSorting-".$row3['id']."' value='".$row3['sorting']."'></div>";
                                            echo "<div><label for='extraSettingDisabled-$i'>Deaktiveret </label><input id='extraSettingDisabled-$i' type='checkbox' class='inputCheckbox settingActive' name='settingActive-".$row3['id']."' value='0'";
                                            if ($row3['active'] == 0) echo "checked";
                                            echo "></div>";
                                            echo "<input class='inputBox hidden settingId' name='settingId-$i' value='".$row3['id']."'>";
                                            echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                            echo "<div style='width: 100%;margin-bottom:1.75rem;'><button type='submit' name='deleteSetting' value='".$row3['id']."' class='btn deleteBtn' onclick='HTXJS_deleteSetting(" . $row3['id'] . ")'>Slet</button></div>";

                                            $i++;
                                        }

                                    }
                                    $stmt3->close();
                                    echo "<input class='inputBox hidden' id='settingsTrue' value='1'>";
                                    echo "<input class='inputBox hidden' id='settingsAmount' value='$i'>";
                                    echo "<input class='inputBox hidden' id='settingsId' value='". $row2['id']."'>";
                                    echo "<input class='inputBox hidden' id='columnType' value='$columnType'>";
                                    echo "<button type='submit' name='submit' value='updateSetting' class='hidden'>Opdater</button>";
                                    echo "<div style='width: 100%;'><button type='submit' name='submit' value='addSetting' class='btn updateBtn' onclick='HTXJS_addSetting(" . $row2['id'] . ", \"checkbox\")'>Tilføj</button></div>";
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
                                for ($i=0; $i < count($possibleFunctionsAll); $i++) {
                                    if (in_array($possibleFunctionsAll[$i], $specialName)) $selected = "checked"; else $selected = "";
                                    echo "<div style='width: unset'><input class='special' type='checkbox' name='specialName[]' id='function-$i' value='$possibleFunctionsAll[$i]' $selected>
                                    <label for='function-$i'>$possibleFunctionsAllName[$i]</label></div>";
                                }
                            echo "</div></div>";
                            // Placeholder text
                            echo "<div><label for='settingPlaceholder'>Placeholder tekst </label><input type='$format' id='settingPlaceholder' class='inputBox' name='placeholderText' value='$placeholderText'></div>";
                            // Sorting
                            echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            // Required
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' onchange='HTXJS_settingDisabledCheckbox(\"disable\")'  type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                            // Disabled
                            echo "<input type='hidden' name='disabled' value='0'>";
                            echo "<div><label for='settingDisabled'>Deaktiveret </label><input id='settingDisabled' onchange='HTXJS_settingDisabledCheckbox(\"enable\")' type='checkbox' class='inputCheckbox' name='disabled' value='1'";
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
                            echo "<div class='hidden'><label for='settingSpecial'>Funktion navn </label> <input id='settingSpecial' class='inputBox' class='special' name='specialName' value=''></div>";
                            // Format (hidden)
                            echo "<div class='hidden'><label for='settingFormat'>Format </label> <input id='settingFormat' class='inputBox' name='format' value='text'></div>";
                            // Required (hidden)
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div class='hidden'><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                        break;
                        case "price":
                            echo "<div class='formCreator_edit_container formCreator_flexRow'>";
                            // Name
                            echo "<div><label for='settingName'>Overskrift </label> <input type='text' id='settingName' class='inputBox' name='columnNameFront' value='$columnNameFront'></div>";
                            // Column type
                            echo "<div style='margin-bottom:0.5rem'><label>Input type <br><i>$columnType</i></label></div>";
                            // price
                            echo "<div><label for='settingFormat'>Text efter pris </label> <br><select id='settingFormat' class='inputBox' name='format'>";
                            for ($i=0; $i < count($possiblePrice); $i++) {
                                if ($possiblePrice[$i] == $format) $selected = "selected"; else $selected = "";
                                echo "<option value='$possiblePrice[$i]' $selected>$possiblePrice[$i]</option>";
                            }
                            echo "</select></div>";
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
                            echo "<div class='hidden'><label for='settingSpecial'>Funktion navn </label> <input id='settingSpecial' class='inputBox' class='special' name='specialName' value=''></div>";
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
                                for ($i=0; $i < count($possibleFunctionsAll); $i++) {
                                    if (in_array($possibleFunctionsAll[$i], $specialName)) $selected = "checked"; else $selected = "";
                                    echo "<div style='width: unset'><input class='special' type='checkbox' name='specialName[]' id='function-$i' value='$possibleFunctionsAll[$i]' $selected>
                                    <label for='function-$i'>$possibleFunctionsAllName[$i]</label></div>";
                                }
                            echo "</div></div>";
                            // Sorting
                            echo "<div><label for='settingSorting'>Sortering </label> <input type='number' id='settingSorting' class='inputBox' name='sorting' value='$sorting'></div>";
                            // Required
                            echo "<input type='hidden' name='required' value='0'>";
                            echo "<div><label for='settingRequired'>Skal udfyldes </label><input id='settingRequired' onchange='HTXJS_settingDisabledCheckbox(\"disable\")'  type='checkbox' class='inputCheckbox' name='required' value='1'";
                            if ($required == 1) echo "checked";
                            echo "></div>";
                            // Disabled
                            echo "<input type='hidden' name='disabled' value='0'>";
                            echo "<div><label for='settingDisabled'>Deaktiveret </label><input id='settingDisabled' onchange='HTXJS_settingDisabledCheckbox(\"enable\")' type='checkbox' class='inputCheckbox' name='disabled' value='1'";
                            if ($disabled == 1) echo "checked";
                            echo "></div>";
                        break;
                    }

                    echo "</div>";
                    // make submit button
                    echo "<button type='submit' name='submit' value='updateSetting' class='btn updateBtn' style='margin-right: 0.5rem;' onclick='HTXJS_updateColumn(" . $settingId . ")'>Opdater</button>";
                    echo "<button type='delete' name='submit' value='deleteColumn' class='btn deleteBtn' onclick='HTXJS_deleteColumn(" . $settingId . ")'>Slet</button>";
                }
            }
            $stmt->close();

            // End div
            echo "</div>";
        } else if (isset($_GET['setting']) AND $_GET['setting'] == 0) {
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
                    echo "<button type='submit' value='updateForm' class='btn updateBtn' style='margin-right: 0.5rem;' onclick='HTXJS_updateForm(" . $tableId .")'>Opdater</button>";
                    echo "<button type='delete' class='btn deleteBtn' onclick='HTXJS_deleteForm(" . $tableId . ")'>Slet</button>";

                }
            }
        } else if (isset($_GET['setting'])) echo "Den valgte input blev ikke fundet";

        echo "</div>";

    } else if (isset($_GET['form'])) echo "Den valgte formular blev ikke fundet";


    // Ending main area
    echo "</div>";
?>
