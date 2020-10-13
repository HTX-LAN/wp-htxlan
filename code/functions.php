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
        } else if (isset($_POST['delete'])) {
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
        }
    }

    function HTX_frontend_switch() {
        
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

?>
