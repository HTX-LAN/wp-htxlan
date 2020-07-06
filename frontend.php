<?php
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
        $html .= "<h2>$formName</h2>";

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
                $specialName[] = $row['specialName'];
                $placeholderText[] = $row['placeholderText'];
                $sorting[] = $row['sorting'];
                $disabled[] = $row['disabled'];
                $required[] = $row['required'];
                $settingCat[] = $row['settingCat'];
            }
        }
        $stmt->close();
        // Setting up form
        $html .= "<form method=\"post\">";

        // Writing for every column entry
        for ($i=0; $i < count($columnNameFront); $i++) { 
            // Setup for required label
            if ($required[$i] == 1) {$isRequired = "required"; $requiredStar = "<i style='color: red'>*</i>";} else {$isRequired = ""; $requiredStar = "";}
            // Setup for disabled
            if ($disabled[$i] == 1) $disabledClass = "hidden"; else $disabledClass = "";
            // Main writing of input
            switch ($columnType[$i]) {
                case "dropdown":
                    $html .= "<p class='$disabledClass'><label>$columnNameFront[$i]$requiredStar</label>";
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
                    // Writing first part of dropdown
                    $html .= "<select name='$columnNameBack[$i]' class='dropdown $disabledClass' $isRequired>";
                    
                    // Getting dropdown content
                    $table_name = $wpdb->prefix . 'htx_settings';
                    $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE settingId = ? ORDER BY sorting");
                    $stmt->bind_param("i", $setting_cat_settingId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0)  {return HTX_frontend_sql_notworking();} else {
                        while($row = $result->fetch_assoc()) {
                            // Getting data
                            $setting_settingName = $row['settingName'];
                            $setting_id = $row['id'];

                            // Set as selected from post
                            if($_POST[$columnNameBack[$i]] == $setting_id) $postSelected = 'selected'; else $postSelected = '';

                            // Write data
                            $html .= "<option value='$setting_id' $postSelected>".$setting_settingName."</option>";
                        }
                    }
                    $stmt->close();

                    // Finishing dropdown
                    $html .= "</select>";
                break;
                case "text area":
                    $html .= "<h5>$columnNameFront[$i]</h5>";
                    $html .= "<p>$placeholderText[$i]</p>";
                break;
                default: 
                    $html .= "<p class='$disabledClass'><label>$columnNameFront[$i]$requiredStar</label>";    
                    $html .= "<input name='$columnNameBack[$i]' type='$format[$i]' placeholder='$placeholderText[$i]' class='inputBox  $disabledClass' value='".$_POST[$columnNameBack[$i]]."' $isRequired></p>";
            }
            
        }
        $html .= "<input name='tableId' value='$tableId' style='display: none'></p>";

        // Error handling block !Needs to be made to popup window
        // if (isset($postError)) 
        $html .= "<p>$postError</p>";

        // Ending form with submit and reset buttons
        $html .= "<p><button type='submit' name='submit' value='new'>Tilmeld</button> <button type='reset' name='reset'>Nulstil</button></p>";

        // Success handling - Give information via popup window, that the regristration have been saved
        
        // Returning html code
        return $html;
    }
?>