<?php
    //Prevent direct file access
    if(!defined('ABSPATH')) {
        header("Location: ../../../");
        die();
    }

    // Frontend php site

    // Shortcode for blancket
    frontend_update();
    function frontend_update(){
        add_shortcode('HTX_Tilmeldningsblanket','HTX_lan_tilmdeldingsblanket_function');
    }

    //perform the shortcode output
    function HTX_lan_tilmdeldingsblanket_function($atts = array()){
        // Custom connection to database
        $link = database_connection();
        global $wpdb;

        // add to $html, to return it at the end -> It is how to do shortcodes in Wordpress
        $html = "";

        // Check and get form from shortcode
        if (!isset($atts['form'])) $tableId = 0; else $tableId = $atts['form'];

        // Standard load
        $html .= HTX_load_standard_frontend();

        // Getting and writing form name
        $table_name = $wpdb->prefix . 'htx_form_tables';
        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE id = ?");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) {return "<p>Dette er et specielt lavet plugin, som ikke er opsat rigtigt. Godt gået 😊</p>";} else {
            while($row = $result->fetch_assoc()) {
                $formName = $row['tableName'];
            }
        }
        $stmt->close();
        $html .= "\n<h2>$formName</h2>";

        // Price handling array
        $possiblePriceFunctions = array("price_intrance", "price_extra");
        $priceSet = false;

        // Post handling
        $postError = HTX_frontend_post($tableId);

        // Getting and writing content to form
        // Getting column info
        $table_name = $wpdb->prefix . 'htx_column';
        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableid = ? ORDER BY sorting ASC, columnNameFront ASC");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) {return HTX_frontend_sql_notworking();} else {
            while($row = $result->fetch_assoc()) {
                $columnNameFront[] = $row['columnNameFront'];
                $columnNameBack[] = $row['columnNameBack'];
                $format[] = $row['format'];
                $columnType[] = $row['columnType'];
                $special[] = $row['special'];
                $specialName[] = explode(",", $row['specialName']);
                $placeholderText[] = $row['placeholderText'];
                $sorting[] = $row['sorting'];
                $disabled[] = $row['disabled'];
                $required[] = $row['required'];
                $settingCat[] = $row['settingCat'];
            }
        }
        $stmt->close();
        // Setting up form
        $html .= "\n<form method=\"post\">";
        $html .= "\n<script>var price = {};</script>";

        // Writing for every column entry
        for ($i=0; $i < count($columnNameFront); $i++) {
            // Setup for required label
            if ($required[$i] == 1) {$isRequired = "required"; $requiredStar = "<i style='color: red'>*</i>";} else {$isRequired = ""; $requiredStar = "";}
            // Setup for disabled
            if ($disabled[$i] == 1) $disabledClass = "hidden"; else $disabledClass = "";
            // Main writing of input
            switch ($columnType[$i]) {
                case "dropdown":
                    $html .= "\n<p class='$disabledClass'><label>$columnNameFront[$i]$requiredStar</label>";
                    // Getting settings category
                    $table_name = $wpdb->prefix . 'htx_settings_cat';
                    $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableId = ? AND  id = ? LIMIT 1");
                    $stmt->bind_param("ii", $tableId,  $settingCat[$i]);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0)  {return HTX_frontend_sql_notworking();} else {
                        while($row = $result->fetch_assoc()) {
                            $setting_cat_settingId = $row['id'];
                        }
                    }
                    $stmt->close();

                    // Getting dropdown content
                    $table_name = $wpdb->prefix . 'htx_settings';
                    $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE settingId = ? ORDER BY sorting");
                    $stmt->bind_param("i", $setting_cat_settingId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0)  {return $html .= "\nDer er på nuværende tidspunkt ingen mulige valg her<input type='hidden' name='name='$columnNameBack[$i]' value=''>";} else {
                        // Price function
                        if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0) $priceClass = 'priceFunction'; else $priceClass = '';
                        if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0) $priceFunction = "onchange='HTXJS_price_update()'"; else $priceFunction = '';
                        
                        // Writing first part of dropdown
                        $html .= "\n<select name='$columnNameBack[$i]' class='dropdown $disabledClass $priceClass' $priceFunction $isRequired>";

                        // Writing dropdown options
                        while($row = $result->fetch_assoc()) {
                            // Getting data
                            $setting_settingName = $row['settingName'];
                            $setting_id = $row['id'];

                            // Set as selected from post
                            if($_POST[$columnNameBack[$i]] == $setting_id) $postSelected = 'selected'; else $postSelected = '';

                            // Write data
                            $html .= "\n<option value='$setting_id' $postSelected>".$setting_settingName."</option>";

                            if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0)
                                $html .= "\n<script>price['$setting_id']=".$row['value'].";</script>";
                        }

                        // Finishing dropdown
                        $html .= "\n</select>";
                    }
                    $stmt->close();
                    $html .= "\n</p>";
                break;
                case "user dropdown":
                    $html .= "\n<p class='$disabledClass'><label>$columnNameFront[$i]$requiredStar</label>";
                    // Getting settings category
                    $table_name = $wpdb->prefix . 'htx_settings_cat';
                    $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableId = ? AND  id = ? LIMIT 1");
                    $stmt->bind_param("ii", $tableId,  $settingCat[$i]);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0)  {return HTX_frontend_sql_notworking();} else {
                        while($row = $result->fetch_assoc()) {
                            $setting_cat_settingId = $row['id'];
                        }
                    }
                    $stmt->close();

                    // Getting dropdown content
                    $table_name = $wpdb->prefix . 'htx_settings';
                    $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE settingId = ? ORDER BY sorting AND settingName");
                    $stmt->bind_param("i", $setting_cat_settingId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0)  {return $html .= "\nDer er på nuværende tidspunkt ingen mulige valg her <input type='hidden' name='name='$columnNameBack[$i]' value=''>";} else {
                        
                        // Writing first part of dropdown
                        $html .= "\n<select name='$columnNameBack[$i]' id='extraUserSettingDropdown-$i' class='dropdown $disabledClass' $isRequired>";

                        // Writing dropdown options
                        while($row = $result->fetch_assoc()) {
                            // Getting data
                            $setting_settingName = $row['settingName'];
                            $setting_id = $row['id'];

                            // Set as selected from post
                            if($_POST[$columnNameBack[$i]] == $setting_id) $postSelected = 'selected'; else $postSelected = '';

                            // Write data
                            $html .= "\n<option value='$setting_id' $postSelected>".$setting_settingName."</option>";
                        }

                        // Finishing dropdown
                        $html .= "\n</select>";

                        // Possible to add a new input
                        $html .= "\n<small><i><label>Andet: </label>";
                        $html .= "\n<input name='$columnNameBack[$i]-extra' type='$format[$i]' id='extraUserSetting-$i' 
                        class='inputBox  $disabledClass' style='width: unset; margin-top: 5px;' value='".htmlspecialchars($_POST[$columnNameBack[$i].'-extra'])."'></small>";
                    }
                    $stmt->close();
                    $html .= "\n</p>";
                break;
                case "radio":
                    $html .= "\n<p class='$disabledClass'><label>$columnNameFront[$i]$requiredStar</label><br>";
                    // Getting settings category
                    $table_name = $wpdb->prefix . 'htx_settings_cat';
                    $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableId = ? AND  id = ? AND active = 1 LIMIT 1");
                    $stmt->bind_param("ii", $tableId,  $settingCat[$i]);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0)  {return HTX_frontend_sql_notworking();} else {
                        while($row2 = $result->fetch_assoc()) {
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
                        if($result3->num_rows === 0) $html .= "\nDer er på nuværende tidspunkt ingen mulige valg her<input type='hidden' name='name='$columnNameBack[$i]' value='' disabled>"; else {
                            while($row3 = $result3->fetch_assoc()) {
                                // Getting data
                                $setting_settingName = $row3['settingName'];
                                $setting_id = $row3['id'];

                                // Set as selected from post
                                if($_POST[$columnNameBack[$i]] == $setting_id) $postSelected = 'checked="checked"'; else $postSelected = '';

                                // Price function
                                if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0) $priceClass = 'priceFunctionRadio'; else $priceClass = '';
                                if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0) $priceFunction = "onchange='HTXJS_price_update()'"; else $priceFunction = '';

                                // Write data
                                $html .= "\n<input type='radio' id='$columnNameBack[$i]-$setting_id' name='$columnNameBack[$i]' value='$setting_id' class='inputBox  $disabledClass $priceClass' $priceFunction $postSelected>
                                <label for='$columnNameBack[$i]-$setting_id'>$setting_settingName</label><br>";

                                // Price for javascript
                                if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0)
                                    $html .= "\n<script>price['$setting_id']=".$row3['value'].";</script>";

                            }
                        }
                        $stmt3->close();
                    }
                    $stmt->close();
                    $html .= "\n</p>";
                break;
                case "checkbox":
                    $html .= "\n<p class='$disabledClass'><label>$columnNameFront[$i]$requiredStar</label><br>";
                    // Getting settings category
                    $table_name = $wpdb->prefix . 'htx_settings_cat';
                    $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableId = ? AND  id = ? AND active = 1 LIMIT 1");
                    $stmt->bind_param("ii", $tableId,  $settingCat[$i]);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0)  {return HTX_frontend_sql_notworking();} else {
                        while($row2 = $result->fetch_assoc()) {
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
                        if($result3->num_rows === 0) $html .= "\nDer er på nuværende tidspunkt ingen mulige valg her<input type='hidden' name='name='$columnNameBack[$i]' value='' disabled>"; else {
                            $html .= "\n<div class='formCreator_flexRow'>";
                            while($row3 = $result3->fetch_assoc()) {
                                // Getting data
                                $setting_settingName = $row3['settingName'];
                                $setting_id = $row3['id'];

                                // Set as selected from post
                                if($_POST[$columnNameBack[$i]] == $setting_id) $postSelected = 'checked="checked"'; else $postSelected = '';

                                // Price function
                                if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0) $priceClass = 'priceFunctionCheckbox'; else $priceClass = '';
                                if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0) $priceFunction = "onchange='HTXJS_price_update()'"; else $priceFunction = '';

                                // Write data
                                $html .= "\n<div class='checkboxDiv'><input type='checkbox' id='$columnNameBack[$i]-$setting_id' class='$priceClass' name='".$columnNameBack[$i]."[]' $priceFunction value='$setting_id' $postSelected>
                                <label for='$columnNameBack[$i]-$setting_id'>$setting_settingName</label></div>";

                                // Price for javascript
                                if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0)
                                    $html .= "\n<script>price['$setting_id']=".$row3['value'].";</script>";
                            }
                            $html .= "\n</div>";
                        }
                        $stmt3->close();
                    }
                    $stmt->close();
                    $html .= "\n</p>";
                break;
                case "text area":
                    $html .= "\n<h5>$columnNameFront[$i]</h5>";
                    $html .= "\n<p>$placeholderText[$i]</p>";
                break;
                case "price":
                    if ($priceSet == false) {
                        $html .= "\n<h5>$columnNameFront[$i]</h5>";
                        $html .= "\n<p>$placeholderText[$i] <span id='priceLine' onload=\"HTXJS_price_update()\">0</span> $format[$i]</p><script>setTimeout(() => {HTXJS_price_update()}, 500);</script>";
                        $priceSet = true;
                    }
                break;
                default:
                    $html .= "\n<p class='$disabledClass'><label>$columnNameFront[$i]$requiredStar</label>";
                    $html .= "\n<input name='$columnNameBack[$i]' type='$format[$i]' placeholder='$placeholderText[$i]'";
                    if ($format[$i] == 'tel') $html .= "\npattern='[0-9]{8}'";
                    $html .= "\n class='inputBox  $disabledClass' value='".$_POST[$columnNameBack[$i]]."' $isRequired>";
                    if ($format[$i] == 'tel') $html .= "\n<small>Format: 12345678</small>";
                    $html .= "\n</p>";
            }

        }
        $html .= "\n<input name='tableId' value='$tableId' style='display: none'></p>";

        // Error handling block !Needs to be made to popup window
        // if (isset($postError))
        $html .= "\n<p>$postError</p>";

        // Ending form with submit and reset buttons
        $html .= "\n<p><button type='submit' name='submit' value='new'>Tilmeld</button> <button type='reset' name='reset'>Nulstil</button></p></form>";

        // Success handling - Give information via popup window, that the regristration have been saved

        // Returning html code
        return $html;
    }
?>
