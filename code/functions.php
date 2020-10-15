<?php
    // Functions and scripts written in php

    // frontend post handling
    function HTX_frontend_post($tableId) {
        // Post handling
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Database connection
            $link = database_connection();
            global $wpdb;

            // Check tableId
            if (!is_numeric($tableId)) return "Sql injection attempt";
            $tableId = intval($tableId);

            switch  ($_POST['submit']) {
                // New submission
                case 'new':
                    $possibleInput = array("inputbox", "dropdown", "user dropdown", "text area", "radio", "checkbox", "price");
                    $columnsWithSettings = array("dropdown", "user dropdown", "radio", "checkbox");
                    $columnsWithOther = array("dropdown", "radio");
                    $nonUserInput = array("text area", "price");
                    
                    // Predefined error text
                    $errorRegistration = "<div class='form_warning'>
                    <div class='form_warning_icon'>
                        <span class='material-icons form_warning_icon_span'>error_outline</span>
                    </div>
                    <div class='form_warning_text'>
                        <span>
                            Tilmeldingen blev ikke tilføjet - Der er noget galt med tilmeldingen - Venligst kontakt support
                        </span>
                    </div></div>";
                    $error = "<div class='form_warning'>
                    <div class='form_warning_icon'>
                        <span class='material-icons form_warning_icon_span'>error_outline</span>
                    </div>
                    <div class='form_warning_text'>
                        <span>
                            Formularen blev ikke indsend - Der er noget galt med indholdet af formularen - Venligst kontakt support
                        </span>
                    </div></div>";

                    $errorSettings = "<div class='form_warning'>
                        <div class='form_warning_icon'>
                            <span class='material-icons form_warning_icon_span'>error_outline</span>
                        </div>
                        <div class='form_warning_text'>
                            <span>
                            Der var en fejl ved en valgmulighed du valgte.<br>
                            den pågældene valgmulighed er ikke længere tilgængelig.<br>
                            Venligt prøv igen.<br>
                            Fejlen blev fundet ved: ";
                    $errorSettingsSmall = "Det valgte input var ikke muligt.";

                    $endSpan = "</span></div></div>";

                    $redBorder1 = "<script>setTimeout(() => {
                        inputId = '";
                    $redBorder2 = "';
                        elementInput = document.getElementById(inputId+'-input');
                        elementInput.setAttribute('style', 'border-color:red;color: red;');
                        text = '";
                    $redBorder3 = "';
                        elementText = document.getElementById(inputId+'-text');
                        elementText.innerHTML = text;
                        }, 300);</script>";
                    $redBorder2_extra = "';
                        elementInput = document.getElementById(inputId);
                        elementInput.setAttribute('style', 'border-color:red;color: red;');
                        text = '";

                    $errorRequired = "<div class='form_warning'>
                        <div class='form_warning_icon'>
                            <span class='material-icons form_warning_icon_span'>error_outline</span>
                        </div>
                        <div class='form_warning_text'>
                            <span>
                            Venligst udfyld alle felter med *.
                            </span>";
                    $errorRequiredSmall = "Dette felt skal udfyldes.";

                    $errorUnique = "<div class='form_warning'>
                        <div class='form_warning_icon'>
                            <span class='material-icons form_warning_icon_span'>error_outline</span>
                        </div>
                        <div class='form_warning_text'>
                            <span>Værdien i det røde felt skal være unik for hver person,<br>
                            venligst indtast en unik værdi i det røde felt.<br>
                            Hvis du mener at dette er en fejl, eller du skal lave om i din tilmelding, er du velkommen til at tage kontakt til os.<br><br>
                            Felt med fejl: ";
                    $errorUniqueSmall = "Dette felt skal være unikt for hver tilmelding.";

                    $errorEmail = "<div class='form_warning'>
                        <div class='form_warning_icon'>
                            <span class='material-icons form_warning_icon_span'>error_outline</span>
                        </div>
                        <div class='form_warning_text'>
                            <span>
                                Emailen findes allerede,
                                venligst kontakt en administrator,
                                hvis du vil lave om i din tilmelding
                            </span>
                        </div></div>";
                    $errorEmailSmall = "Denne email findes allerede.";

                    $errorInvalidEmail = "<div class='form_warning'>
                        <div class='form_warning_icon'>
                            <span class='material-icons form_warning_icon_span'>error_outline</span>
                        </div>
                        <div class='form_warning_text'>
                            <span>
                                Den indtastede email er ikke gyldig.
                            </span>
                        </div></div>";
                    $errorInvalidEmailSmall = "Denne email er ikke gyldig.";

                    $errorInvalidLengthMin = "<div class='form_warning'>
                    <div class='form_warning_icon'>
                        <span class='material-icons form_warning_icon_span'>error_outline</span>
                    </div>
                    <div class='form_warning_text'>
                        <span>
                            Det indtastet svar var for kort.
                        </span>
                    </div></div>";
                    $errorInvalidLengthMinSmall = "Venligst ændre svaret til minimum at have det angivet antal tegn.";

                    $errorInvalidLengthMax = "<div class='form_warning'>
                    <div class='form_warning_icon'>
                        <span class='material-icons form_warning_icon_span'>error_outline</span>
                    </div>
                    <div class='form_warning_text'>
                        <span>
                            Det indtastet svar var for langt.
                        </span>
                    </div></div>";
                    $errorInvalidLengthMaxSmall = "Venligst ændre svaret til maksimalt at have det angivet antal tegn.";

                    try {
                        // Check that the form trying to submit to, is the right one
                        $table_name = $wpdb->prefix . 'htx_form_tables';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE id = ?");
                        $stmt->bind_param("i", $tableId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows === 0) return "Formularen findes ikke";
                        while($row = $result->fetch_assoc()) {
                            if ($row['registration'] == 1) 
                                $registration = 1;
                            else 
                                $registration = 0;
                            if ($row['emailEnable'] == 1) 
                                $emailEnable = 1;
                            else 
                                $emailEnable = 0;
                            $emailSender = $row['emaiSender'];
                            $emailText = html_entity_decode($row['emailText']);
                            $emailSubject = $row['emailSubject'];
                        }
                        $stmt->close();
                        // Starting price
                        $price = 0;
                        $priceExtra = 0;
                        $possiblePriceFunctions = array("price_intrance", "price_extra");

                        // Checking values
                        // Sanatize email
                        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                        // Check if mail is valid
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $link->rollback(); //remove all queries from queue if error (undo)
                            return $errorInvalidEmail.$redBorder1."3".$redBorder2.$errorInvalidEmailSmall.$redBorder3;
                        }

                        // Check if mail exist, and only return error if table is not a registration form
                        if ($registration == 1) {
                            $table_name = $wpdb->prefix . 'htx_form_users';
                            $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE email = ? AND tableId = ?");
                            $stmt->bind_param("si", $email, $tableId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if($result->num_rows === 0) {} else return $errorEmail;
                        } 
                        
                        // Convert values to the right format
                        // Getting column info
                        $table_name = $wpdb->prefix . 'htx_column';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableId = ? and active = 1");
                        $stmt->bind_param("i", $tableId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows === 0) return "Ingen kollonner"; else {
                            while($row = $result->fetch_assoc()) {
                                $columnId[] = $row['id'];
                                $columnNameFront[] = $row['columnNameFront'];
                                $columnNameBack[] = $row['columnNameBack'];
                                $format[] = $row['format'];
                                $columnType[] = $row['columnType'];
                                $special[] = $row['special'];
                                $specialName[] = explode(",", $row['specialName']);
                                $placeholderText[] = $row['placeholderText'];
                                $minChar[] = $row['minChar'];
                                $maxChar[] = $row['maxChar'];
                                $sorting[] = $row['sorting'];
                                $required[] = $row['required'];
                                if (in_array($row['columnType'],$columnsWithSettings)) {
                                    // Get settings for column
                                    $table_name2 = $wpdb->prefix . 'htx_settings_cat';
                                    $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE settingNameBack = ? and tableId = ? and active = 1");
                                    $stmt2->bind_param("si", $row['columnNameBack'], $tableId);
                                    $stmt2->execute();
                                    $result2 = $stmt2->get_result();
                                    if($result2->num_rows === 0) {
                                        // No cat for it
                                    } else {
                                        // Get settings
                                        while($row2 = $result2->fetch_assoc()) {
                                            $table_name3 = $wpdb->prefix . 'htx_settings';
                                            $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE settingId = ? and active = 1");
                                            $stmt3->bind_param("i", $row2['id']);
                                            $stmt3->execute();
                                            $result3 = $stmt3->get_result();
                                            if($result3->num_rows === 0) {
                                                // No settings
                                            } else {
                                                if (in_array('noneInput',explode(",", $row['specialName']))) {
                                                    $columnSettingsId[$row['id']][] = "0";
                                                    $columnSettingsValue[$row['id']][] = 0;
                                                }
                                                
                                                while($row3 = $result3->fetch_assoc()) {
                                                    $columnSettingsId[$row['id']][] = $row3['id'];
                                                    $columnSettingsValue[$row['id']][$row3['id']] = $row3['value'];
                                                }
                                            }
                                            $stmt3->close();
                                        }
                                    }
                                    $stmt2->close();
                                }
                            }
                        }
                        $stmt->close();

                        // Inserting every input into row

                        $link->autocommit(FALSE); //turn on transactions

                        // Inserting user and getting id
                        $table_name = $wpdb->prefix . 'htx_form_users';
                        $stmt = $link->prepare("INSERT INTO `$table_name` (tableId, email) VALUES (?, ?)");
                        $stmt->bind_param("is", $tableId, htmlspecialchars(trim($email)));
                        $stmt->execute();
                        $formUserId = $link->insert_id;
                        $stmt->close();

                        // Inserting rest of rows
                        $inputName = $inputValue = "";
                        $table_name = $wpdb->prefix . 'htx_form';
                        $stmt = $link->prepare("INSERT INTO `$table_name` (name, value, userId, tableId) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("ssii", $inputName, $inputValue, $formUserId, $tableId);
                        for ($i=0; $i < count($columnNameBack); $i++) {
                            $specialPostArrayStart = array();
                            $inputName = $columnNameBack[$i];

                            // Check if input for column should be unique
                            if (in_array('unique',$specialName[$i])) {
                                $table_name2 = $wpdb->prefix . 'htx_form';
                                $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? AND name = ? and value = ? and active = 1 LIMIT 1");
                                $stmt2->bind_param("iss", $tableId, $columnNameBack[$i], htmlspecialchars(strval(trim($_POST[$columnNameBack[$i]]))));
                                $stmt2->execute();
                                $result2 = $stmt2->get_result();
                                if($result2->num_rows === 0) {} else {
                                    if (htmlspecialchars(strval(trim($_POST[$columnNameBack[$i]]))) != ""){
                                        $link->rollback(); //remove all queries from queue if error (undo)
                                        return $errorUnique.$columnNameFront[$i].$redBorder1.$columnId[$i].$redBorder2.$errorUniqueSmall.$redBorder3.$endSpan;
                                    }
                                }
                                $stmt2->close();
                            }

                            // Does a speciel implode a data, when it is a checkbox
                            if ($columnType[$i] == 'checkbox') {
                                if(!empty($_POST[$columnNameBack[$i]])) {
                                    foreach($_POST[$columnNameBack[$i]] as $specials) {
                                        if (!in_array($specials,$columnSettingsId[$columnId[$i]])) {
                                            $link->rollback(); //remove all queries from queue if error (undo)
                                            return $errorSettings.$columnNameFront[$i].$redBorder1.$columnId[$i].$redBorder2.$errorSettingsSmall.$redBorder3.$endSpan;
                                        }
                                        $specialPostArrayStart[] = $specials;
                                        if (in_array('price_intrance', $specialName[$i]) && $columnSettingsValue[$columnId[$i]][$specials] != "") {
                                            $price = $price + floatval($columnSettingsValue[$columnId[$i]][$specials]);
                                        } else if (in_array('price_extra', $specialName[$i]) && $columnSettingsValue[$columnId[$i]][$specials] != ""){
                                            $priceExtra = $priceExtra + floatval($columnSettingsValue[$columnId[$i]][$specials]);
                                        }

                                    }
                                    $inputValue = implode(",", $specialPostArrayStart);
                                } else $inputValue = "";
                            } else if($columnType[$i] == 'user dropdown') {
                                // Check if new user dropdown setting is made
                                if (isset($_POST[$columnNameBack[$i].'-extra']) AND $_POST[$columnNameBack[$i].'-extra'] != "" AND $_POST[$columnNameBack[$i].'-extra'] != null AND $_POST[$columnNameBack[$i].'-extra'] != NULL) {
                                    $userDropdown = 1;
                                    
                                    $inputValue = strtolower(htmlspecialchars(strval(trim($_POST[$columnNameBack[$i].'-extra']))));
                                    
                                    // Getting setting cat id
                                    $table_name2 = $wpdb->prefix . 'htx_settings_cat';
                                    $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? and settingNameBack = ?");
                                    if(!$stmt2)
                                        throw new Exception($link->error);
                                    $stmt2->bind_param("is", $tableId, $columnNameBack[$i]);
                                    $stmt2->execute();
                                    $result = $stmt2->get_result();
                                    if($result->num_rows === 0) throw new Exception('No setting cat'); else {
                                        while($row = $result->fetch_assoc()) {
                                            $settingCatId = $row['id'];
                                        }
                                    }
                                    $stmt2->close();

                                    // Getting already existing settings
                                    $table_name2 = $wpdb->prefix . 'htx_settings';
                                    $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE settingId = ? ORDER BY sorting DESC");
                                    if(!$stmt2)
                                        throw new Exception($link->error);
                                    $stmt2->bind_param("i", $settingCatId);
                                    $stmt2->execute();
                                    $result = $stmt2->get_result();
                                    if($result->num_rows === 0) $userDropdown = '1'; else {
                                        while($row = $result->fetch_assoc()) {
                                            // Check if setting exist
                                            if (strtolower($row['settingName']) == $inputValue) {
                                                $inputValue = $row['id'];
                                                $userDropdown = 0;
                                            }
                                            $defaultInputValue = $row['id'];
                                        }
                                    }
                                    $stmt2->close();

                                    if ($userDropdown == 1) {
                                        // New user setting does not exist -> create it
                                        $table_name2 = $wpdb->prefix . 'htx_settings';
                                        $stmt2 = $link->prepare("INSERT INTO `$table_name2` (settingName, value, settingId, active, sorting, type) VALUES (?, ?, ?, 1, 10, 'user dropdown')");
                                        if(!$stmt2)
                                            throw new Exception($link->error);
                                        // Cehck if input is inside length
                                        if (in_array('maxChar', $specialName[$i])){
                                            if (strlen(trim($_POST[$columnNameBack[$i].'-extra'])) > $maxChar[$i]){
                                                $link->rollback(); //remove all queries from queue if error (undo)
                                                return $errorInvalidLengthMax.$redBorder1."extraUserSetting-$i".$redBorder2_extra.$errorInvalidLengthMaxSmall.$redBorder3;
                                            }
                                        }
                                        $stmt2->bind_param("ssi", htmlspecialchars(strval(trim($_POST[$columnNameBack[$i].'-extra']))), htmlspecialchars(strval(trim($_POST[$columnNameBack[$i].'-extra']))), $settingCatId);
                                        $stmt2->execute();
                                        $inputValue = intval($link->insert_id);
                                        $stmt2->close();
                                    }
                                } else {
                                    if (!in_array($_POST[$columnNameBack[$i]], $columnSettingsId[$columnId[$i]]) AND $_POST[$columnNameBack[$i]] != "") {
                                        $link->rollback(); //remove all queries from queue if error (undo)
                                        return $errorSettings.$columnNameFront[$i]."test her".$redBorder1.$columnId[$i].$redBorder2.$errorSettingsSmall.$redBorder3.$endSpan;
                                    }
                                    $inputValue = htmlspecialchars(strval(trim($_POST[$columnNameBack[$i]])));
                                }
                            } else {
                                // Check if column has settings
                                if (in_array($columnType[$i],$columnsWithSettings)) {
                                    if (in_array($columnType[$i],$columnsWithOther) AND $_POST[$columnNameBack[$i]."Other"]!="" AND $_POST[$columnNameBack[$i]."Other"]!=NULL AND isset($_POST[$columnNameBack[$i]."Other"])) {
                                        $inputValue = htmlspecialchars(trim($_POST[$columnNameBack[$i]."Other"]));
                                    } else if (!in_array($_POST[$columnNameBack[$i]], $columnSettingsId[$columnId[$i]]) AND $_POST[$columnNameBack[$i]] != "") {
                                        $link->rollback(); //remove all queries from queue if error (undo)
                                        return $errorSettings.$columnNameFront[$i].$redBorder1.$columnId[$i].$redBorder2.$errorSettingsSmall.$redBorder3.$endSpan;
                                    } else {
                                        $inputValue = htmlspecialchars(intval(trim($_POST[$columnNameBack[$i]])));
                                        if (in_array('price_intrance', $specialName[$i]) && $columnSettingsValue[$columnId[$i]][$_POST[$columnNameBack[$i]]] != "") {
                                            $price = $price + floatval($columnSettingsValue[$columnId[$i]][$_POST[$columnNameBack[$i]]]);
                                        } else if (in_array('price_extra', $specialName[$i]) && $columnSettingsValue[$columnId[$i]][$_POST[$columnNameBack[$i]]] != ""){
                                            $priceExtra = $priceExtra + floatval($columnSettingsValue[$columnId[$i]][$_POST[$columnNameBack[$i]]]);
                                        }
                                    }
                                } else {
                                    // Setting does not have settings, and are an user input
                                    // Check if column format is email
                                    if ($format[$i] == 'email') {
                                        // Sanatize email
                                        $email = filter_var($_POST[$columnNameBack[$i]], FILTER_SANITIZE_EMAIL);
                                        // Check if mail is valid
                                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                            $link->rollback(); //remove all queries from queue if error (undo)
                                            return $errorInvalidEmail.$redBorder1.$columnId[$i].$redBorder2.$errorInvalidEmailSmall.$redBorder3;
                                        } else 
                                            $inputValue = $email;
                                    } else if ($format[$i] == 'number') {
                                        $inputValue = floatval(trim($_POST[$columnNameBack[$i]]));
                                    } else {
                                        $inputValue = htmlspecialchars(strval(trim($_POST[$columnNameBack[$i]])));
                                    }
                                    // Cehck if input is inside length
                                    if (in_array('minChar', $specialName[$i])){
                                        if (strlen(trim($_POST[$columnNameBack[$i]])) < $minChar[$i]){
                                            $link->rollback(); //remove all queries from queue if error (undo)
                                            return $errorInvalidLengthMin.$redBorder1.$columnId[$i].$redBorder2.$errorInvalidLengthMinSmall.$redBorder3;
                                        }
                                    }
                                    if (in_array('maxChar', $specialName[$i])){
                                        if (strlen(trim($_POST[$columnNameBack[$i]])) > $maxChar[$i]){
                                            $link->rollback(); //remove all queries from queue if error (undo)
                                            return $errorInvalidLengthMax.$redBorder1.$columnId[$i].$redBorder2.$errorInvalidLengthMaxSmall.$redBorder3;
                                        }
                                    }
                                }
                            }
                            if ($required[$i] == 1 AND $inputValue == "") {
                                $link->rollback(); //remove all queries from queue if error (undo)
                                return $errorRequired.$redBorder1.$columnId[$i].$redBorder2.$errorRequiredSmall.$redBorder3.$endSpan;
                            }

                            $stmt->execute();
                        }
                        $priceTotal = $price+$priceExtra;
                        $stmt->close();
                        $link->autocommit(TRUE); //turn off transactions + commit queued queries
                    } catch(Exception $e) {
                        $link->rollback(); //remove all queries from queue if error (undo)
                        // throw $e;
                        if ($registration == 1) 
                            $return = $errorRegistration;
                        else 
                            $return = $error;
                        return $return;
                        
                    }

                    // Error handling (Needs to be more specifik)

                    // Success handling
                    // Clearing post
                    $_POST = array();
                    // Writing success for user to see
                    if ($registration == 0) {
                        $succes = "<div class='form_success'>
                        <div class='form_success_icon'>
                            <span class='material-icons form_success_icon_span'>done_outline</span>
                        </div>
                        <div class='form_success_text'>
                            <span>
                                Formularen blev indsendt
                            </span>
                        </div></div>";
                    } else {
                        $succes = "<div class='form_success'>
                        <div class='form_success_icon'>
                            <span class='material-icons form_success_icon_span'>done_outline</span>
                        </div>
                        <div class='form_success_text'>
                            <span>
                                Tilmeldingen blev tilføjet
                            </span>
                        </div></div>";
                    }
                    // Email notification
                    if ($emailEnable == 1) {
                        $headers = array();
                        // Prepping email
                        $message = str_replace("%submissionNumber%", "$formUserId", $emailText);
                        $message = str_replace("%email%", "$email", $message);
                        $message = str_replace("%ticketPriceTotal%", "$priceTotal", $message);
                        $message = str_replace("%ticketPriceIntrance%", "$price", $message);
                        $message = str_replace("%ticketPriceExtra%", "$priceExtra", $message);
                        $headers[] = "From: $emailSender";
                        $headers[] = "MIME-Version: 1.0" . "\r\n";
                        $headers[] = "Content-type:text/html;charset=UTF-8" . "\r\n";
                        $subject = $emailSubject;
                        // Sending email
                        wp_mail($email, $subject, $message, $headers);
                    }
                    return $succes;
                break;
                default: return "Noget gik galt🤔";
            }
        } else {
        }
    }

    function HTX_participant_edit_post($tableId) {
        // Post handling
        if ($_SERVER["REQUEST_METHOD"] == "POST" and isset($_POST['userId'])) {
            // Database connection
            $link = database_connection();
            global $wpdb;

            // Check tableId
            if (!is_numeric($tableId)) return "Sql injection attempt";
            $tableId = intval($tableId);

            switch  ($_POST['submit']) {
                // New submission
                case 'update':
                    $possibleInput = array("inputbox", "dropdown", "user dropdown", "text area", "radio", "checkbox", "price");
                    $columnsWithSettings = array("dropdown", "user dropdown", "radio", "checkbox");
                    $columnsWithOther = array("dropdown", "radio");
                    $nonUserInput = array("text area", "price");
                    
                    // Predefined error text
                    $errorRegistration = "<div class='form_warning'>
                    <div class='form_warning_icon'>
                        <span class='material-icons form_warning_icon_span'>error_outline</span>
                    </div>
                    <div class='form_warning_text'>
                        <span>
                            Tilmeldingen blev ikke tilføjet - Der er noget galt med tilmeldingen - Venligst kontakt support
                        </span>
                    </div></div>";
                    $error = "<div class='form_warning'>
                    <div class='form_warning_icon'>
                        <span class='material-icons form_warning_icon_span'>error_outline</span>
                    </div>
                    <div class='form_warning_text'>
                        <span>
                            Formularen blev ikke indsend - Der er noget galt med indholdet af formularen - Venligst kontakt support
                        </span>
                    </div></div>";

                    $errorSettings = "<div class='form_warning'>
                        <div class='form_warning_icon'>
                            <span class='material-icons form_warning_icon_span'>error_outline</span>
                        </div>
                        <div class='form_warning_text'>
                            <span>
                            Der var en fejl ved en valgmulighed du valgte.<br>
                            den pågældene valgmulighed er ikke længere tilgængelig.<br>
                            Venligt prøv igen.<br>
                            Fejlen blev fundet ved: ";
                    $errorSettingsSmall = "Det valgte input var ikke muligt.";

                    $endSpan = "</span></div></div>";

                    $redBorder1 = "<script>setTimeout(() => {
                        inputId = '";
                    $redBorder2 = "';
                        elementInput = document.getElementById(inputId+'-input');
                        elementInput.setAttribute('style', 'border-color:red;color: red;');
                        text = '";
                    $redBorder2_extra = "';
                        elementInput = document.getElementById(inputId);
                        elementInput.setAttribute('style', 'border-color:red;color: red;');
                        text = '";
                    $redBorder3 = "';
                        elementText = document.getElementById(inputId+'-text');
                        elementText.innerHTML = text;
                        }, 300);</script>";

                    $errorRequired = "<div class='form_warning'>
                        <div class='form_warning_icon'>
                            <span class='material-icons form_warning_icon_span'>error_outline</span>
                        </div>
                        <div class='form_warning_text'>
                            <span>
                            Venligst udfyld alle felter med *.
                            </span>";
                    $errorRequiredSmall = "Dette felt skal udfyldes.";

                    $errorUnique = "<div class='form_warning'>
                        <div class='form_warning_icon'>
                            <span class='material-icons form_warning_icon_span'>error_outline</span>
                        </div>
                        <div class='form_warning_text'>
                            <span>Værdien i det røde felt skal være unik for hver person,<br>
                            venligst indtast en unik værdi i det røde felt.<br>
                            Hvis du mener at dette er en fejl, eller du skal lave om i din tilmelding, er du velkommen til at tage kontakt til os.<br><br>
                            Felt med fejl: ";
                    $errorUniqueSmall = "Dette felt skal være unikt for hver tilmelding.";

                    $errorEmail = "<div class='form_warning'>
                        <div class='form_warning_icon'>
                            <span class='material-icons form_warning_icon_span'>error_outline</span>
                        </div>
                        <div class='form_warning_text'>
                            <span>
                                Emailen findes allerede,
                                venligst kontakt en administrator,
                                hvis du vil lave om i din tilmelding
                            </span>
                        </div></div>";
                    $errorEmailSmall = "Denne email findes allerede.";

                    $errorInvalidEmail = "<div class='form_warning'>
                        <div class='form_warning_icon'>
                            <span class='material-icons form_warning_icon_span'>error_outline</span>
                        </div>
                        <div class='form_warning_text'>
                            <span>
                                Den indtastede email er ikke gyldig.
                            </span>
                        </div></div>";
                    $errorInvalidEmailSmall = "Denne email er ikke gyldig.";

                    $errorInvalidLengthMin = "<div class='form_warning'>
                    <div class='form_warning_icon'>
                        <span class='material-icons form_warning_icon_span'>error_outline</span>
                    </div>
                    <div class='form_warning_text'>
                        <span>
                            Det indtastet svar var for kort.
                        </span>
                    </div></div>";
                    $errorInvalidLengthMinSmall = "Venligst ændre svaret til minimum at have det angivet antal tegn.";

                    $errorInvalidLengthMax = "<div class='form_warning'>
                    <div class='form_warning_icon'>
                        <span class='material-icons form_warning_icon_span'>error_outline</span>
                    </div>
                    <div class='form_warning_text'>
                        <span>
                            Det indtastet svar var for langt.
                        </span>
                    </div></div>";
                    $errorInvalidLengthMaxSmall = "Venligst ændre svaret til maksimalt at have det angivet antal tegn.";

                    try {
                        // Check that the form trying to submit to, is the right one
                        $table_name = $wpdb->prefix . 'htx_form_tables';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE id = ?");
                        $stmt->bind_param("i", $tableId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows === 0) return "Formularen findes ikke";
                        while($row = $result->fetch_assoc()) {
                            if ($row['registration'] == 1) 
                                $registration = 1;
                            else 
                                $registration = 0;
                        }
                        $stmt->close();

                        // Check that the user trying to submit to, is the right one
                        $formUserId = intval($_POST['userId']);
                        $table_name = $wpdb->prefix . 'htx_form_users';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE id = ?");
                        $stmt->bind_param("i", $formUserId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows != 1) return $formUserId.$error;
                        $stmt->close();
                        
                        // Convert values to the right format
                        // Getting column info
                        $table_name = $wpdb->prefix . 'htx_column';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableId = ? and active = 1");
                        $stmt->bind_param("i", $tableId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows === 0) return "Ingen kollonner"; else {
                            while($row = $result->fetch_assoc()) {
                                $columnId[] = $row['id'];
                                $columnNameFront[] = $row['columnNameFront'];
                                $columnNameBack[] = $row['columnNameBack'];
                                $format[] = $row['format'];
                                $columnType[] = $row['columnType'];
                                $special[] = $row['special'];
                                $specialName[] = explode(",", $row['specialName']);
                                $minChar[] = $row['minChar'];
                                $maxChar[] = $row['maxChar'];
                                $placeholderText[] = $row['placeholderText'];
                                $sorting[] = $row['sorting'];
                                $required[] = $row['required'];
                                if (in_array($row['columnType'],$columnsWithSettings)) {
                                    // Get settings for column
                                    $table_name2 = $wpdb->prefix . 'htx_settings_cat';
                                    $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE settingNameBack = ? and tableId = ? and active = 1");
                                    $stmt2->bind_param("si", $row['columnNameBack'], $tableId);
                                    $stmt2->execute();
                                    $result2 = $stmt2->get_result();
                                    if($result2->num_rows === 0) {
                                        // No cat for it
                                    } else {
                                        // Get settings
                                        while($row2 = $result2->fetch_assoc()) {
                                            $table_name3 = $wpdb->prefix . 'htx_settings';
                                            $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE settingId = ? and active = 1");
                                            $stmt3->bind_param("i", $row2['id']);
                                            $stmt3->execute();
                                            $result3 = $stmt3->get_result();
                                            if($result3->num_rows === 0) {
                                                // No settings
                                            } else {
                                                if (in_array('noneInput',explode(",", $row['specialName']))) {
                                                    $columnSettingsId[$row['id']][] = "0";
                                                }
                                                
                                                while($row3 = $result3->fetch_assoc()) {
                                                    $columnSettingsId[$row['id']][] = $row3['id'];
                                                }
                                            }
                                            $stmt3->close();
                                        }
                                    }
                                    $stmt2->close();
                                }
                            }
                        }
                        $stmt->close();

                        // Inserting every input into row

                        $link->autocommit(FALSE); //turn off transactions

                        // Inserting rest of rows
                        $inputName = $inputValue = "";
                        $table_name = $wpdb->prefix . 'htx_form';
                        $stmt = $link->prepare("UPDATE `$table_name` SET value = ? WHERE name = ? and userId = ? and tableId = ?");
                        $stmt->bind_param("ssii", $inputValue, $inputName, $formUserId, $tableId);
                        for ($i=0; $i < count($columnNameBack); $i++) {
                            $specialPostArrayStart = array();
                            $inputName = $columnNameBack[$i];

                            // Skip email editing
                            if ($columnNameBack[$i] == 'email') continue;

                            // Check if database record exist
                            $stmt2 = $link->prepare("SELECT * FROM `$table_name` WHERE name = ? and userId = ? and tableId = ?");
                            $stmt2->bind_param("sii", $inputName, $formUserId, $tableId);
                            $stmt2->execute();
                            $result2 = $stmt2->get_result();
                            if($result2->num_rows === 0) {
                                $stmt3 = $link->prepare("INSERT INTO `$table_name` (name, userId, tableId) VALUES (?, ?, ?)");
                                $stmt3->bind_param("sii", $inputName, $formUserId, $tableId);
                                $stmt3->execute();
                                $stmt3->close();
                            }
                            $stmt2->close();

                            // Check if input for column should be unique
                            if (in_array('unique',$specialName[$i])) {
                                $table_name2 = $wpdb->prefix . 'htx_form';
                                $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? AND name = ? and value = ? and active = 1 LIMIT 1");
                                $stmt2->bind_param("iss", $tableId, $columnNameBack[$i], htmlspecialchars(strval(trim($_POST[$columnNameBack[$i]]))));
                                $stmt2->execute();
                                $result2 = $stmt2->get_result();
                                if($result2->num_rows === 0) {} else {
                                    while($row2 = $result2->fetch_assoc()) {
                                        if ($row2['userId'] != $formUserId) {
                                            if (htmlspecialchars(strval(trim($_POST[$columnNameBack[$i]]))) != ""){
                                                $link->rollback(); //remove all queries from queue if error (undo)
                                                return $errorUnique.$columnNameFront[$i].$redBorder1.$columnId[$i].$redBorder2.$errorUniqueSmall.$redBorder3.$endSpan;
                                            }
                                        }
                                    }
                                    
                                }
                                $stmt2->close();
                            }

                            // Does a speciel implode a data, when it is a checkbox
                            if ($columnType[$i] == 'checkbox') {
                                if(!empty($_POST[$columnNameBack[$i]])) {
                                    foreach($_POST[$columnNameBack[$i]] as $specials) {
                                        if (!in_array($specials,$columnSettingsId[$columnId[$i]])) {
                                            $link->rollback(); //remove all queries from queue if error (undo)
                                            return $errorSettings.$columnNameFront[$i].$redBorder1.$columnId[$i].$redBorder2.$errorSettingsSmall.$redBorder3.$endSpan;
                                        }
                                        $specialPostArrayStart[] = $specials;
                                    }
                                    $inputValue = implode(",", $specialPostArrayStart);
                                } else $inputValue = "";
                            } else if($columnType[$i] == 'user dropdown') {
                                // Check if new user dropdown setting is made
                                if (isset($_POST[$columnNameBack[$i].'-extra']) AND $_POST[$columnNameBack[$i].'-extra'] != "" AND $_POST[$columnNameBack[$i].'-extra'] != null AND $_POST[$columnNameBack[$i].'-extra'] != NULL) {
                                    $userDropdown = 1;
                                    
                                    $inputValue = strtolower(htmlspecialchars(strval(trim($_POST[$columnNameBack[$i].'-extra']))));
                                    
                                    // Getting setting cat id
                                    $table_name2 = $wpdb->prefix . 'htx_settings_cat';
                                    $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? and settingNameBack = ?");
                                    if(!$stmt2)
                                        throw new Exception($link->error);
                                    $stmt2->bind_param("is", $tableId, $columnNameBack[$i]);
                                    $stmt2->execute();
                                    $result = $stmt2->get_result();
                                    if($result->num_rows === 0) throw new Exception('No setting cat'); else {
                                        while($row = $result->fetch_assoc()) {
                                            $settingCatId = $row['id'];
                                        }
                                    }
                                    $stmt2->close();

                                    // Getting already existing settings
                                    $table_name2 = $wpdb->prefix . 'htx_settings';
                                    $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE settingId = ? ORDER BY sorting DESC");
                                    if(!$stmt2)
                                        throw new Exception($link->error);
                                    $stmt2->bind_param("i", $settingCatId);
                                    $stmt2->execute();
                                    $result = $stmt2->get_result();
                                    if($result->num_rows === 0) $userDropdown = '1'; else {
                                        while($row = $result->fetch_assoc()) {
                                            // Check if setting exist
                                            if (strtolower($row['settingName']) == $inputValue) {
                                                $inputValue = $row['id'];
                                                $userDropdown = 0;
                                            }
                                            $defaultInputValue = $row['id'];
                                        }
                                    }
                                    $stmt2->close();

                                    if ($userDropdown == 1) {
                                        // New user setting does not exist -> create it
                                        $table_name2 = $wpdb->prefix . 'htx_settings';
                                        $stmt2 = $link->prepare("INSERT INTO `$table_name2` (settingName, value, settingId, active, sorting, type) VALUES (?, ?, ?, 1, 10, 'user dropdown')");
                                        if(!$stmt2)
                                            throw new Exception($link->error);
                                        // Cehck if input is inside length
                                        if (in_array('maxChar', $specialName[$i])){
                                            if (strlen(trim($_POST[$columnNameBack[$i].'-extra'])) > $maxChar[$i]){
                                                $link->rollback(); //remove all queries from queue if error (undo)
                                                return $errorInvalidLengthMax.$redBorder1."extraUserSetting-$i".$redBorder2_extra.$errorInvalidLengthMaxSmall.$redBorder3;
                                            }
                                        }
                                        $stmt2->bind_param("ssi", htmlspecialchars(strval(trim($_POST[$columnNameBack[$i].'-extra']))), htmlspecialchars(strval(trim($_POST[$columnNameBack[$i].'-extra']))), $settingCatId);
                                        $stmt2->execute();
                                        $inputValue = intval($link->insert_id);
                                        $stmt2->close();
                                    }
                                } else {
                                    if (!in_array($_POST[$columnNameBack[$i]], $columnSettingsId[$columnId[$i]]) AND $_POST[$columnNameBack[$i]] != "") {
                                        $link->rollback(); //remove all queries from queue if error (undo)
                                        return $errorSettings.$columnNameFront[$i]."test her".$redBorder1.$columnId[$i].$redBorder2.$errorSettingsSmall.$redBorder3.$endSpan;
                                    }
                                    $inputValue = htmlspecialchars(strval(trim($_POST[$columnNameBack[$i]])));
                                }
                            } else {
                                // Check if column has settings
                                if (in_array($columnType[$i],$columnsWithSettings)) {
                                    if (in_array($columnType[$i],$columnsWithOther) AND $_POST[$columnNameBack[$i]."Other"]!="" AND $_POST[$columnNameBack[$i]."Other"]!=NULL AND isset($_POST[$columnNameBack[$i]."Other"])) {
                                        $inputValue = htmlspecialchars(trim($_POST[$columnNameBack[$i]."Other"]));
                                    } else if (!in_array($_POST[$columnNameBack[$i]], $columnSettingsId[$columnId[$i]]) AND $_POST[$columnNameBack[$i]] != "") {
                                        $link->rollback(); //remove all queries from queue if error (undo)
                                        return $errorSettings.$columnNameFront[$i].$redBorder1.$columnId[$i].$redBorder2.$errorSettingsSmall.$redBorder3.$endSpan;
                                    } else 
                                        $inputValue = htmlspecialchars(intval(trim($_POST[$columnNameBack[$i]])));
                                } else {
                                    // Setting does not have settings, and are an user input
                                    // Check if column format is email
                                    if ($format[$i] == 'email') {
                                        // Sanatize email
                                        $email = filter_var($_POST[$columnNameBack[$i]], FILTER_SANITIZE_EMAIL);
                                        // Check if mail is valid
                                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                            $link->rollback(); //remove all queries from queue if error (undo)
                                            return $errorInvalidEmail.$redBorder1.$columnId[$i].$redBorder2.$errorInvalidEmailSmall.$redBorder3;
                                        } else 
                                            $inputValue = $email;
                                    } else if ($format[$i] == 'number') {
                                        $inputValue = floatval(trim($_POST[$columnNameBack[$i]]));
                                    } else {
                                        $inputValue = htmlspecialchars(strval(trim($_POST[$columnNameBack[$i]])));
                                    }
                                    // Cehck if input is inside length
                                    if (in_array('minChar', $specialName[$i])){
                                        if (strlen(trim($_POST[$columnNameBack[$i]])) < $minChar[$i]){
                                            $link->rollback(); //remove all queries from queue if error (undo)
                                            return $errorInvalidLengthMin.$redBorder1.$columnId[$i].$redBorder2.$errorInvalidLengthMinSmall.$redBorder3;
                                        }
                                    }
                                    if (in_array('maxChar', $specialName[$i])){
                                        if (strlen(trim($_POST[$columnNameBack[$i]])) > $maxChar[$i]){
                                            $link->rollback(); //remove all queries from queue if error (undo)
                                            return $errorInvalidLengthMax.$redBorder1.$columnId[$i].$redBorder2.$errorInvalidLengthMaxSmall.$redBorder3;
                                        }
                                    }
                                }
                            }
                            if ($required[$i] == 1 AND $inputValue == "" AND $columnNameBack[$i] != 'email') {
                                $link->rollback(); //remove all queries from queue if error (undo)
                                return $errorRequired.$redBorder1.$columnId[$i].$redBorder2.$errorRequiredSmall.$redBorder3.$endSpan;
                            }

                            $stmt->execute();
                        }
                        $stmt->close();
                        $link->autocommit(TRUE); //turn off transactions + commit queued queries
                    } catch(Exception $e) {
                        $link->rollback(); //remove all queries from queue if error (undo)
                        throw $e;
                        if ($registration == 1) 
                            $return = $errorRegistration;
                        else 
                            $return = $error;
                        return $return;
                        
                    }

                    // Error handling (Needs to be more specifik)

                    // Success handling
                    // Clearing post
                    $_POST = array();
                    // Writing success for user to see
                    if ($registration == 0) {
                        $succes = "<div class='form_success'>
                        <div class='form_success_icon'>
                            <span class='material-icons form_success_icon_span'>done_outline</span>
                        </div>
                        <div class='form_success_text'>
                            <span>
                                Formularen blev opdateret
                            </span>
                        </div></div>";
                    } else {
                        $succes = "<div class='form_success'>
                        <div class='form_success_icon'>
                            <span class='material-icons form_success_icon_span'>done_outline</span>
                        </div>
                        <div class='form_success_text'>
                            <span>
                                Tilmeldingen blev opdateret
                            </span>
                        </div></div>";
                    }
                    return $succes;
                break;
                default: return "Noget gik galt🤔";
            }
        } else if ($_SERVER["REQUEST_METHOD"] == "POST" and isset($_POST['delete'])) {
            if ($_POST['delete'] == "deleteSubmission") {
                $link = database_connection();
                global $wpdb;
                // Deleting submission
                try {
                    $link->autocommit(FALSE); //turn on transactions
                    $userId = intval($_POST['userid']);
                    // Delete user id
                    $table_name = $wpdb->prefix . 'htx_form_users';
                    $stmt = $link->prepare("DELETE FROM `$table_name` WHERE tableID = ? and id = ?");
                    $stmt->bind_param("ii", $tableId, $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0) echo "Ingen bruger med det id";
                    $stmt->close();

                    // Delete form elements user submittet
                    $table_name = $wpdb->prefix . 'htx_form';
                    $stmt = $link->prepare("DELETE FROM `$table_name` WHERE tableID = ? and userId = ?");
                    $stmt->bind_param("ii", $tableId, $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0) echo "Ingen submission elementer med det id";
                    $stmt->close();

                    $link->autocommit(TRUE); //turn off transactions + commit queued queries
                    echo "<script>setTimeout(() => {informationwindowInsert(1,'Linjen blev slettet')}, 300);</script>"; //User feedback
                } catch(Exception $e) {
                    $link->rollback(); //remove all queries from queue if error (undo)
                    throw $e;
                }
            }
        } else if ($_SERVER["REQUEST_METHOD"] == "POST" and isset($_POST['massAction'])) {
            // Database connection
            $link = database_connection();
            global $wpdb;

            // Check tableId
            if (!is_numeric($tableId)) return "Sql injection attempt";
            $tableId = intval($tableId);

            switch  ($_POST['massAction']) {
                case "massAction_email";
                    // Error messages
                    $errorUser = "<div class='form_warning'>
                    <div class='form_warning_icon'>
                        <span class='material-icons form_warning_icon_span'>error_outline</span>
                    </div>
                    <div class='form_warning_text'>
                        <span>
                            Der er nogle brugere, som ikke længere eksistere
                        </span>
                    </div></div>";
                    $errorText = "<div class='form_warning'>
                    <div class='form_warning_icon'>
                        <span class='material-icons form_warning_icon_span'>error_outline</span>
                    </div>
                    <div class='form_warning_text'>
                        <span>
                            Der blev ikke angivet nogen email tekst.
                        </span>
                    </div></div>";

                    try {
                        $link->autocommit(FALSE); //turn off transactions
                        // Check that the form trying to submit to, is the right one
                        $table_name = $wpdb->prefix . 'htx_form_tables';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE id = ?");
                        $stmt->bind_param("i", $tableId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows != 1) {
                            $link->rollback(); //remove all queries from queue if error (undo)
                            return "Formularen findes ikke";
                        }
                        while($row = $result->fetch_assoc()) {
                            $tableName = $row['tableName'];
                            $fromEmail = $row['emailSender'];
                        }
                        $stmt->close();

                        // Check every user id
                        $users = explode(",",$_POST['massAction_users']);
                        // Check that the user trying to submit to, is the right one
                        for ($i=0; $i < count($users); $i++) { 
                            $table_name = $wpdb->prefix . 'htx_form_users';
                            $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE id = ?");
                            $stmt->bind_param("i", $users[$i]);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if($result->num_rows != 1) {
                                $link->rollback(); //remove all queries from queue if error (undo)
                                return $errorUser;
                            } else {
                                while($row = $result->fetch_assoc()) {
                                    $emails[] = $row['email'];
                                }
                            }
                            $stmt->close();
                        }

                        // Check email sender
                        if (isset($_POST['emailSender'])) {
                            $emailSender = trim($_POST['emailSender']);
                            if (strlen($emailSender) < 1 and strlen($fromEmail) < 1) {
                                // set no from, and let wp_mail handle it
                                $emailSender = "";
                            } else if (strlen($emailSender) < 1) {
                                $emailSender = $fromEmail;
                            }
                        } else {
                            if (strlen($fromEmail) < 1) {
                                // set no from, and let wp_mail handle it
                                $emailSender = "";
                            } else {
                                $emailSender = $fromEmail;
                            }
                        }

                        // Check email recipitant
                        if (isset($_POST['emailReciever'])) {
                            $emailReciever = trim($_POST['emailReciever']);
                            if (strlen($emailReciever) < 1 and strlen($fromEmail) < 1) {
                                // set no from, and let wp_mail handle it
                                $emailReciever = "Undisclosed Recipients <no-reply@".remove_http(site_url()).">";
                            } else if (strlen($emailReciever) < 1) {
                                $emailReciever = $fromEmail;
                            } else {
                                $emailReciever = "Undisclosed Recipients <$emailReciever>";
                            }
                        } else {
                            if (strlen($fromEmail) < 1) {
                                $emailReciever = "Undisclosed Recipients <no-reply@".remove_http(site_url()).">";
                            } else {
                                $emailReciever = $fromEmail;
                            }
                        }

                        // Check subject is set
                        if (isset($_POST['emailsubject'])) {
                            $emailSubject = trim($_POST['emailsubject']);
                            if (strlen($emailSubject) < 1) {
                                // Email subject is not set - Setting standard
                                $emailSubject = "Email for $tableName";
                            }
                        } else {
                            // Email subject is not set - Setting standard
                            $emailSubject = "Email for $tableName";
                        }

                        // Check email text is set
                        if (isset($_POST['emailtext']) and strlen($_POST['emailtext']) > 0) {
                            $emailText = $_POST['emailtext'];
                        } else {
                            $link->rollback(); //remove all queries from queue if error (undo)
                            return $errorText;
                        }

                        // Headers
                        $headers = array();
                        $headers[] = "From: $emailSender";
                        $headers[] = "Reply-To: $emailSender";
                        $headers[] = "MIME-Version: 1.0" . "\r\n";
                        $headers[] = "Content-type:text/html;charset=UTF-8" . "\r\n";

                        foreach($emails as $email){
                            $headers[] = 'Bcc: '.$email;
                        }

                        // Sending email
                        wp_mail($emailReciever, $emailSubject, $emailText, $headers);

                        $link->autocommit(TRUE); //turn off transactions + commit queued queries
                        return "<div class='form_success'>
                        <div class='form_success_icon'>
                            <span class='material-icons form_success_icon_span'>done_outline</span>
                        </div>
                        <div class='form_success_text'>
                            <span>
                                Emails er blevet afsendt!
                            </span>
                        </div></div>";
                    } catch(Exception $e) {
                        $link->rollback(); //remove all queries from queue if error (undo)
                        throw $e;                        
                    }
                    
                break;
                default:
                    return "<div class='form_warning'>
                    <div class='form_warning_icon'>
                        <span class='material-icons form_warning_icon_span'>error_outline</span>
                    </div>
                    <div class='form_warning_text'>
                        <span>
                            Der var ikke angivet nogen masse ændring funktion
                        </span>
                    </div></div>";
                break;
            }
        }
    }

    function HTX_frontend_switch($ColumnInfo, $tableId, $possiblePriceFunctions, $i, $priceSet, $possiblePrice) {
        // Custom connection to database
        $link = database_connection();
        global $wpdb;
        HTX_load_standard_frontend();
        $html = "";
        // Get all elements
        $columnId[$i] = $ColumnInfo['columnId'];
        $columnNameFront[$i] = $ColumnInfo['columnNameFront'];
        $columnNameBack[$i] = $ColumnInfo['columnNameBack'];
        $format[$i] = $ColumnInfo['format'];
        $columnType[$i] = $ColumnInfo['columnType'];
        $special[$i] = $ColumnInfo['special'];
        $specialName[$i] = $ColumnInfo['specialName'];
        $specialNameExtra[$i] = $ColumnInfo['specialNameExtra'];
        $specialNameExtra2[$i] = $ColumnInfo['specialNameExtra2'];
        $specialNameExtra3[$i] = $ColumnInfo['specialNameExtra3'];
        $minChar[$i] = $ColumnInfo['minChar'];
        $maxChar[$i] = $ColumnInfo['maxChar'];
        $placeholderText[$i] = $ColumnInfo['placeholderText'];
        $formatExtra[$i] = $ColumnInfo['formatExtra'];
        $sorting[$i] = $ColumnInfo['sorting'];
        $disabled[$i] = $ColumnInfo['disabled'];
        $required[$i] = $ColumnInfo['required'];
        $settingCat[$i] = $ColumnInfo['settingCat'];
        $POST = $ColumnInfo['POST'];
        $POSTextra = $ColumnInfo['POSTextra'];
        // Setup for required label
        if ($required[$i] == 1) {$isRequired = "required"; $requiredStar = "<i style='color: red'>*</i>";} else {$isRequired = ""; $requiredStar = "";}
        if (in_array('unique',$specialName[$i])) $requiredStar .= " <i title='Dette input skal være unikt for hver tilmelding' style='cursor: help'>(unikt)</i>"; else $requiredStar .= "";
        // Setup for disabled
        if ($disabled[$i] == 1) $disabledClass = "hidden"; else $disabledClass = "";
        // Main writing of input
        $html .= "\n<div id='$columnId[$i]-div'>";
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
                    $html .= "\n<select id='$columnId[$i]-input' name='$columnNameBack[$i]' oninput='HTX_frontend_js()' class='dropdown $disabledClass $priceClass' $priceFunction $isRequired>";
                    
                    // None input option
                    if (in_array('noneInput',$specialName[$i])) {
                        if($POST == 0) $postSelected = 'selected'; else $postSelected = '';
                        $html .= "\n<option value='0' $postSelected></option>";
                    }

                    // Writing dropdown options
                    while($row = $result->fetch_assoc()) {
                        // Getting data
                        $setting_settingName = $row['settingName'];
                        $setting_id = $row['id'];

                        // Set as selected from post
                        if($POST == $setting_id) $postSelected = 'selected'; else $postSelected = '';

                        // Write data
                        $html .= "\n<option value='$setting_id' $postSelected>".$setting_settingName."</option>";

                        if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0)
                            $html .= "\n<script>price['$setting_id']='".$row['value']."';</script>";
                    }

                    // Finishing dropdown
                    $html .= "\n</select>";

                    // Other input option
                    if (in_array('otherInput',$specialName[$i])) {
                        $html .= "\n<small><i><label>Andet: </label>";
                        $html .= "\n<input name='$columnNameBack[$i]Other' type='text' placeholder='Andet' id='$columnId[$i]-input-other' style='max-width: 250px; margin-top: 10px' value='".$_POST[$columnNameBack[$i]."Other"]."'>";
                        $html .= "\n</i></small>";
                    }
                }
                $stmt->close();
                $html .= "\n<small id='$columnId[$i]-text' class='form_warning_smalltext'></small>";
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
                    $html .= "\n<select id='$columnId[$i]-input' name='$columnNameBack[$i]' id='extraUserSettingDropdown-$i' oninput='HTX_frontend_js()' class='dropdown $disabledClass' $isRequired>";

                    // None input option
                    if (in_array('noneInput',$specialName[$i])) {
                        if($POST == 0) $postSelected = 'selected'; else $postSelected = '';
                        $html .= "\n<option value='0' $postSelected></option>";
                    }

                    // Writing dropdown options
                    while($row = $result->fetch_assoc()) {
                        // Getting data
                        $setting_settingName = $row['settingName'];
                        $setting_id = $row['id'];

                        // Set as selected from post
                        if($POST == $setting_id) $postSelected = 'selected'; else $postSelected = '';

                        // Write data
                        $html .= "\n<option value='$setting_id' $postSelected>".$setting_settingName."</option>";
                    }

                    // Finishing dropdown
                    $html .= "\n</select>";

                    // Possible to add a new input
                    $html .= "\n<small><i><label>Andet: </label>";
                    $html .= "\n<input name='$columnNameBack[$i]-extra' type='$format[$i]' id='extraUserSetting-$i' ";
                    if (in_array('maxChar', $specialName[$i])) $html .= "oninput='HTX_charAmount($i, \"extraUserSetting-$i\");' maxlength='".$maxChar[$i]."'";
                    $html .= " class='inputBox  $disabledClass' style='width: unset; margin-top: 5px;' value='".htmlspecialchars($POSTextra)."'></i> <span id='extraUserSetting-$i-text' class='form_warning_smalltext'></span> <span id='charAmount-$i' class='charAmount'></span></small>";
                    if (in_array('maxChar', $specialName[$i])) 
                        $html .= "\n<input type='hidden' id='char-$i' value='max'>
                        <input type='hidden' id='maxChar-$i' value='$maxChar[$i]'>
                        <script>setTimeout(() => {HTX_charAmount($i, \"extraUserSetting-$i\");}, 300);</script>";
                }
                $stmt->close();
                $html .= "\n<small id='$columnId[$i]-text' class='form_warning_smalltext'></small>";
                $html .= "\n</p>";
            break;
            case "radio":
                $html .= "\n<p class='$disabledClass'><label id='$columnId[$i]-input'>$columnNameFront[$i]$requiredStar</label><br>";
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
                        // Price function
                        if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0) $priceClass = 'priceFunctionRadio'; else $priceClass = '';
                        if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0) $priceFunction = "onchange='HTXJS_price_update()'"; else $priceFunction = '';

                        // None input option
                        if (in_array('noneInput',$specialName[$i])) {
                            if($POST == 0) $postSelected = 'checked="checked"'; else $postSelected = '';
                            $html .= "\n<input type='radio' id='$columnNameBack[$i]-0' name='$columnNameBack[$i]' oninput='HTX_frontend_js()' value='0' class='inputBox $columnId[$i]-radio $disabledClass $priceClass' $priceFunction $postSelected>
                            <label for='$columnNameBack[$i]-0'><i>Intet</i></label><br>";

                            // Price for javascript
                            if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0)
                                $html .= "\n<script>price[0]='0';</script>";
                        }
                        while($row3 = $result3->fetch_assoc()) {
                            // Getting data
                            $setting_settingName = $row3['settingName'];
                            $setting_id = $row3['id'];

                            // Set as selected from post
                            if($POST == $setting_id) $postSelected = 'checked="checked"'; else $postSelected = '';

                            // Write data
                            $html .= "\n<input type='radio' id='$columnNameBack[$i]-$setting_id' name='$columnNameBack[$i]' oninput='HTX_frontend_js()' value='$setting_id' class='inputBox $columnId[$i]-radio $disabledClass $priceClass' $priceFunction $postSelected>
                            <label for='$columnNameBack[$i]-$setting_id'>$setting_settingName</label><br>";

                            // Price for javascript
                            if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0)
                                $html .= "\n<script>price['$setting_id']='".$row3['value']."';</script>";

                        }
                        // Other input option
                        if (in_array('otherInput',$specialName[$i])) {
                            $html .= "\n<small><i><label>Andet: </label>";
                            $html .= "\n<input name='$columnNameBack[$i]Other' type='text' placeholder='Andet' id='$columnId[$i]-input-other' style='max-width: 250px; margin-top: 10px'>";
                            $html .= "\n</i></small>";
                        }
                    }
                    $stmt3->close();
                }
                $stmt->close();
                $html .= "\n<small id='$columnId[$i]-text' class='form_warning_smalltext'></small>";
                $html .= "\n</p>";
            break;
            case "checkbox":
                $html .= "\n<p class='$disabledClass'><label id='$columnId[$i]-input'>$columnNameFront[$i]$requiredStar</label><br>";
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
                            if (isset($POST)) {
                                if(in_array($setting_id, $POST)) $postSelected = 'checked="checked"'; else $postSelected = '';
                            }

                            // Price function
                            if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0) $priceClass = 'priceFunctionCheckbox'; else $priceClass = '';
                            if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0) $priceFunction = "onchange='HTXJS_price_update()'"; else $priceFunction = '';

                            // Write data
                            $html .= "\n<div class='checkboxDiv'><input type='checkbox' id='$columnNameBack[$i]-$setting_id' oninput='HTX_frontend_js()' class='$priceClass $columnId[$i]-checkbox' name='".$columnNameBack[$i]."[]' $priceFunction value='$setting_id' $postSelected>
                            <label for='$columnNameBack[$i]-$setting_id'>$setting_settingName</label></div>";

                            // Price for javascript
                            if (count(array_intersect($specialName[$i],$possiblePriceFunctions)) > 0)
                                $html .= "\n<script>price['$setting_id']='".$row3['value']."';</script>";
                        }
                        $html .= "\n</div>";
                    }
                    $stmt3->close();
                }
                $stmt->close();
                $html .= "\n<small id='$columnId[$i]-text' class='form_warning_smalltext'></small>";
                $html .= "\n</p>";
            break;
            case "text area":
                $html .= "\n<h5 id='$columnId[$i]-input'>$columnNameFront[$i]</h5>";
                $html .= "\n<p>$placeholderText[$i]</p>";
            break;
            case "spacing":
                $html .= "\n<div style='width: 100%; height: ".$placeholderText[$i]."rem; margin: 0px; padding: 0px;'></div>";
            break;
            case "price":
                if ($priceSet == false) {
                    if (!in_array($format[$i], $possiblePrice)) $format[$i] = "";
                    $html .= "\n<h5 id='$columnId[$i]-input'>$columnNameFront[$i]</h5>";
                    $html .= "\n<p>$placeholderText[$i] <span id='priceLine' onload=\"HTXJS_price_update()\">0</span> $format[$i]</p><script>setTimeout(() => {HTXJS_price_update()}, 500);</script>";
                    $priceSet = true;
                }
            break;
            default:
                if ($format[$i] == 'textarea') $inputMethod = 'textarea'; else $inputMethod = 'input';
                $html .= "\n<p class='$disabledClass'><label>$columnNameFront[$i]$requiredStar</label>";
                $html .= "\n<$inputMethod id='$columnId[$i]-input' name='$columnNameBack[$i]' type='$format[$i]' placeholder='$placeholderText[$i]' oninput='HTX_frontend_js();";
                if ($format[$i] == 'range') $html .= "document.getElementById(\"$columnId[$i]-rangeValue\").innerHTML = document.getElementById(\"$columnId[$i]-input\").value;' min='$formatExtra[$i]' max='$specialNameExtra3[$i]' style='padding: 0px;' ";
                else {
                    if (in_array('minChar', $specialName[$i]) OR in_array('maxChar', $specialName[$i])) $html .= "HTX_charAmount($i, \"$columnId[$i]-input\");' ";
                    else $html .= "'";
                    if (in_array('minChar', $specialName[$i])) $html .= "minlength='".$minChar[$i]."' ";
                    if (in_array('maxChar', $specialName[$i])) $html .= "maxlength='".$maxChar[$i]."' ";
                }
                if ($format[$i] == 'tel') $html .= "pattern='$formatExtra[$i]' ";
                $html .= "class='inputBox  $disabledClass' value='".$POST."' $isRequired>";
                if ($format[$i] == 'textarea') $html .= "\n".$POST."\n</textarea>";
                if ($format[$i] == 'tel') $html .= "\n<small>Format: $placeholderText[$i]</small>";
                else if ($format[$i] == 'range') $html .= "\n<small>værdi: <span id='$columnId[$i]-rangeValue'>$placeholderText[$i]</span></small>";
                else {
                    if (in_array('minChar', $specialName[$i]) AND in_array('maxChar', $specialName[$i])) 
                        $html .= "\n<input type='hidden' id='char-$i' value='both'>
                        <small id='charAmount-$i' class='charAmount charAmountWarning'>venligst indtast et svar længere end eller lig med $minChar[$i] tegn.</small>
                        <script>setTimeout(() => {HTX_charAmount($i, \"$columnId[$i]-input\");}, 300);</script>
                        <input type='hidden' id='minChar-$i' value='$minChar[$i]'>
                        <input type='hidden' id='maxChar-$i' value='$maxChar[$i]'>";
                    else if (in_array('minChar', $specialName[$i])) $html .= "\n<input type='hidden' id='char-$i' value='min'><small id='charAmount-$i' class='charAmount'>venligst indtast et svar længere end eller lig med $minChar[$i] tegn.</small>
                    <input type='hidden' id='minChar-$i' value='$minChar[$i]'>
                    <script>setTimeout(() => {HTX_charAmount($i, \"$columnId[$i]-input\");}, 300);</script>";
                    else if (in_array('maxChar', $specialName[$i])) $html .= "\n<input type='hidden' id='char-$i' value='max'><small id='charAmount-$i' class='charAmount'></small>
                    <input type='hidden' id='maxChar-$i' value='$maxChar[$i]'>
                    <script>setTimeout(() => {HTX_charAmount($i, \"$columnId[$i]-input\");}, 300);</script>";
                }
                $html .= "\n<small id='$columnId[$i]-text' class='form_warning_smalltext'></small>";
                $html .= "\n</p>";
        }
        return $html;
    }

    function database_connection() {
        // Connecting to database, with custom variable
        try {
            $link = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        } catch(Exception $e) {
            error_log($e->getMessage());
            return('Error connecting to database.'); //Should be a message a typical user could understand
        }
        return $link;
    }

    //multi explode function
    function multiexplode ($delimiters,$string) {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);
        return  $launch;
    }

    // Count number of times a string is in an array
    function count_array_values($my_array, $match) {
        $count = 0;
        foreach ($my_array as $key => $value)
        {
            if ($value == $match)
            {
                $count++;
            }
        }

        return $count;
    }

    // Setting cookie
    function setCustomCookie($cookieName, $cookieValue) {
        wp_enqueue_script( 'cookie', "/wp-content/plugins/wp-htxlan/code/JS/cookie.js");
        echo "<script>
        function setCookieTime(cname,cvalue,exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays*24*60*60*1000));
            var expires = \"expires=\" + d.toGMTString();
            document.cookie = cname + \"=\" + cvalue + \";\" + expires + \";path=/\";
        }
        </script>";
        echo "<script>setCookieTime('$cookieName','$cookieValue',30)</script>";
    }

    // script for text when sql is not working as it should (error message) - frontend
    function HTX_frontend_sql_notworking() {
        $html = "<p>Noget er galt her...</p>";
        $html .= "<p>Har du haft gjort noget? 🤔</p>";
        $html .= "<p>Hmmm, det kan også være noget fra vores side af, det er jo os der har sat det op..</p>";
        $html .= "<p>Venligst kom tilbage igen senere, og se om det virker. <br>Hvis det ikke virker der, så venligst kontakt os.❤</p>";
        return $html;
    }

    // Script to get url for backend
    function getUrl() {
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
         $url = "https://";
        else
            $url = "http://";
        // Append the host(domain name, ip) to the URL.
        $url.= $_SERVER['HTTP_HOST'];

        // Append the requested resource location to the URL
        $url.= $_SERVER['REQUEST_URI'];

        return $url;
    }

    // Script to get domain
    function remove_http($url) {
        $disallowed = array('http://', 'https://', 'http://www.', 'https://www.');
        foreach($disallowed as $d) {
           if(strpos($url, $d) === 0) {
              return str_replace($d, '', $url);
           }
        }
        return $url;
     }

?>
