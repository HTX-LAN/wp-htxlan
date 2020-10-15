<?php

//Prevent direct file access
if(!defined('ABSPATH')) {
    header("Location: ../../../../");
    die();
}

function htx_parse_dangerzone_request() {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['postType'])) {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        header('Content-type: application/json');
        switch($_POST['postType']) {
            case 'resetDB':
                try {
                    drop_db();
                    create_db();
                    $response->success = true;
                } catch(Exception $e) {
                    $response->success = false;
                    $response->error = $e->getMessage();
                }
                break;
            case 'downloadParticipants':
                try {
                    $csv = to_csv("htx_form_users");
                    $response->success = true;
                    $response->csv = $csv;
                    $response->filename = "htx_data_" . date("dmY-His") . ".csv";
                } catch(Exception $e) {
                    $response->success = false;
                    $response->error = $e->getMessage();
                }
                break;
        }
        echo json_encode($response);
        wp_die();
    }
}

function htx_delete_form() {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['formid'])) {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();
            $tableId = intval($_POST['formid']);
            $link->autocommit(FALSE); //turn on transactions

            // Check if form exist
            $table_name = $wpdb->prefix . 'htx_form_tables';
            $stmt = $link->prepare("SELECT * FROM $table_name WHERE id = ?");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("i", $tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) throw new Exception("Form does not exist");
            $stmt->close();

            // Delete table id
            $table_name = $wpdb->prefix . "htx_form_tables";
            $stmt = $link->prepare('DELETE FROM `' . $table_name . '` WHERE `id`=?');
            if(!$stmt)
                throw new Exception("error at ".$table_name);
            $stmt->bind_param('i', $tableId);
            $stmt->execute();
            $stmt->close();

            // Delete submission to form
            $table_name = $wpdb->prefix . "htx_form";
            $stmt = $link->prepare('DELETE FROM `' . $table_name . '` WHERE `tableId`=?');
            if(!$stmt)
                throw new Exception("error at ".$table_name);
            $stmt->bind_param('i', $tableId);
            $stmt->execute();
            $stmt->close();

            // Delete users for form
            $table_name = $wpdb->prefix . "htx_form_users";
            $stmt = $link->prepare('DELETE FROM `' . $table_name . '` WHERE `tableId`=?');
            if(!$stmt)
                throw new Exception("error at ".$table_name);
            $stmt->bind_param('i', $tableId);
            $stmt->execute();
            $stmt->close();

            // Delete settings categories to form
            $table_name = $wpdb->prefix . "htx_settings_cat";
            $stmt = $link->prepare('DELETE FROM  '.$table_name.' WHERE `tableId`=?');
            if(!$stmt)
                throw new Exception("error at ".$table_name."\n".$link->error);
            $stmt->bind_param('i', $tableId);
            $stmt->execute();
            $stmt->close();

            // Delete settings for form
            $table_name = $wpdb->prefix . "htx_settings";
            $stmt = $link->prepare('DELETE FROM `' . $table_name . '` WHERE `tableId`=?');
            if(!$stmt)
                throw new Exception("error at ".$table_name);
            $stmt->bind_param('i', $tableId);
            $stmt->execute();
            $stmt->close();

            // Delete columns/form element for form
            $table_name = $wpdb->prefix . "htx_column";
            $stmt = $link->prepare('DELETE FROM `' . $table_name . '` WHERE `tableId`=?');
            if(!$stmt)
                throw new Exception("error at ".$table_name);
            $stmt->bind_param('i', $tableId);
            $stmt->execute();
            $stmt->close();
            
            $link->autocommit(TRUE); //turn off transactions + commit queued queries
            $link->close();
            $response->success = true;
        } catch(Exception $e) {
            $link->rollback(); //remove all queries from queue if error (undo)
            $response->success = false;
            $response->error = $e->getMessage();
        }
        echo json_encode($response);
        wp_die();
    }
}

function htx_update_form() {
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['formid']) && isset($_POST['tableName']) && isset($_POST['tableDescription'])) {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();
            $link->autocommit(FALSE); //turn on transactions
            $table_name = $wpdb->prefix . 'htx_form_tables';
            $stmt = $link->prepare("UPDATE $table_name SET tableName = ?, tableDescription = ?, arrived = ?, crew = ?, pizza = ?, registration = ?, arrivedAtDoor = ?, closeFormActive = ?, openForm = ?, closeForm = ?, emailEnable = ?, emailSender = ?, emailText = ?, emailSubject = ? WHERE id = ?");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("ssiiiiiississsi", $_POST['tableName'], $_POST['tableDescription'],$arrived,$crew,$pizza,$registration,$arrivedAtDoor, $closeFormActive, $openDate, $closeDate, $emailEnable, $emailSender, $emailText, $emailSubject, $_POST['formid']);
            $arrived = intval($_POST['arrived']);
            $crew = intval($_POST['crew']);
            $pizza = intval($_POST['pizza']);
            $registration = intval($_POST['registration']);
            $arrivedAtDoor = intval($_POST['arrivedAtDoor']);
            $closeFormActive = intval($_POST['closeFormActive']);
            if (strtotime($_POST['tableOpenDate']) == false)
                throw new Exception('Date format not supported');
            else 
                $openDate = date('Y-m-d H:i:s', strtotime($_POST['tableOpenDate']));
            if (strtotime($_POST['tableCloseDate']) == false && $closeFormActive == 1)
                throw new Exception('Date format not supported');
            else if (strtotime($_POST['tableCloseDate']) == false)
                $closeDate = NULL;
            else
                $closeDate = date('Y-m-d H:i:s', strtotime($_POST['tableCloseDate']));
            if (strtotime($openDate) > strtotime($closeDate)  && $closeFormActive == 1)
                throw new Exception('Close date, shall not be before start date');
            $emailEnable = intval($_POST['emailEnable']);
            $emailSender = $_POST['emailSender'];
            $emailText = htmlentities($_POST['emailText']);
            $emailSubject = $_POST['emailSubject'];
            $stmt->execute();
            $stmt->close();
            $link->autocommit(TRUE); //turn off transactions + commit queued queries
            $link->close();
            $response->success = true;
        } catch(Exception $e) {
            $response->success = false;
            $response->error = $e->getMessage();
            $link->rollback(); //remove all queries from queue if error (undo)
        }
        echo json_encode($response);
        wp_die();
    }
}

function htx_create_form() {
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();
            $link->autocommit(FALSE); //turn on transactions

            // Creating new form in form tables
            $table_name = $wpdb->prefix . 'htx_form_tables';
            $shortcode = "HTX_Tilmeldningsblanket"; $Name = 'Ny formular';
            $stmt = $link->prepare("INSERT INTO $table_name (shortcode, tableName) VALUES (?, ?)");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("ss", $shortcode, $Name);
            $stmt->execute();
            $newTableId = intval($link->insert_id);
            if ($newTableId == 0) throw new Exception('Error creating new form.');
            $stmt->close();

            // Creating standard inputs (First- & lastname, email & phone)
            $table_name = $wpdb->prefix . 'htx_column';
            $link->autocommit(FALSE); //turn on transactions
            $stmt = $link->prepare("INSERT INTO $table_name (tableId, columnNameFront, columnNameBack, format, columnType, special, specialName, sorting, placeholderText, required, settingCat) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("issssssisii", $tableId, $columnNameFront, $columnNameBack, $format, $columnType, $special, $specialName, $sorting, $placeholderText, $required, $settingCat);
            $tableId = $newTableId;
            $columnNameFront = "Fornavn"; $columnNameBack='firstName'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 1; $placeholderText = "John"; $adminOnly = 0; $required = 1; $settingCat = 0;
            $stmt->execute();
            $columnNameFront = "Efternavn"; $columnNameBack='lastName'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 2; $placeholderText = "Smith"; $adminOnly = 0; $required = 1; $settingCat = 0;
            $stmt->execute();
            $columnNameFront = "E-mail"; $columnNameBack='email'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 3; $placeholderText = "john@htx-lan.dk"; $adminOnly = 0; $required = 1; $settingCat = 0;
            $stmt->execute();
            $columnNameFront = "Mobil nummer"; $columnNameBack='phone'; $format="number"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 4; $placeholderText = "12345678"; $adminOnly = 0; $required = 0; $settingCat = 0;
            $stmt->execute();
            $stmt->close();

            $link->autocommit(TRUE); //turn off transactions + commit queued queries
            $link->close();
            $response->success = true;
            $response->id = $tableId;
            $response->name = $Name;
        } catch(Exception $e) {
            $response->success = false;
            $response->error = $e->getMessage();
            $link->rollback(); //remove all queries from queue if error (undo)
        }
        echo json_encode($response);
        wp_die();
    }
}

function htx_new_column() {
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['inputType']) && isset($_POST['tableId'])) {
        if(!current_user_can("manage_options"))
            return;
        $possibleInput = array("inputbox", "dropdown", "user dropdown", "text area", "radio", "checkbox", "price", 'spacing');
        $possibleInputWithSettingCat = array("dropdown", "user dropdown", "radio", "checkbox");
        $possibleFormat = array("text", "number", "email", 'url', 'color', 'date', 'time', 'week', 'month', 'tel', 'range','textarea');
        $possiblePrice = array("", "DKK", ",-", "kr.", 'danske kroner', '$', 'NOK', 'SEK', 'dollars', 'euro');
        $response = new stdClass();
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();
            $link->autocommit(FALSE); //turn off transactions
            // User input
            $userInputType = $_POST['inputType'];

            // Break if the user input is not known
            if (!in_array($userInputType, $possibleInput)) throw new Exception('Invalid input type');

            // Check if table exist
            $tableId = intval($_POST['tableId']);

            $table_name = $wpdb->prefix . 'htx_form_tables';
            $stmt = $link->prepare("SELECT * FROM $table_name WHERE id = ? AND active = 1");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("i", $tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) throw new Exception("Form does not exist");
            $stmt->close();

            // Get last sorting
            $table_name = $wpdb->prefix . 'htx_column';
            $stmt = $link->prepare("SELECT sorting FROM $table_name WHERE tableId = ? AND active = 1 ORDER BY sorting DESC LIMIT 1");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("i", $tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {} else {
                while($row = $result->fetch_assoc()) {
                    $sorting = $row['sorting'];
                  }    
            }
            $stmt->close();

            // Define values for new element
            $columnNameFront = "New element"; $format=$possibleFormat[0]; $columnType=$userInputType; $special=0; $specialName="";
            $placeholderText = ""; $required = 0; $settingCat = 0; $sorting = $sorting+1;

            $table_name = $wpdb->prefix . 'htx_column';
            $stmt = $link->prepare("INSERT INTO $table_name (tableId, columnNameFront, format, columnType, special, specialName, sorting, placeholderText, required, settingCat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("isssssisii", $tableId, $columnNameFront, $format, $columnType, $special, $specialName, $sorting, $placeholderText, $required, $settingCat);
            $stmt->execute();
            $lastId = intval($link->insert_id);
            $stmt->close();
            if ($lastId < 0) throw new Exception('Invalid ID');
            $stmt = $link->prepare("UPDATE $table_name SET columnNameBack = ? WHERE id = ?");
            $stmt->bind_param("ii", $lastId, $lastId);
            $stmt->execute();
            $stmt->close();

            $columnNameBack = $lastId;
            if (in_array($userInputType, $possibleInputWithSettingCat)){
                // If dropdown, then make setting category first
                $table_name = $wpdb->prefix . 'htx_settings_cat';
                $stmt = $link->prepare("INSERT INTO $table_name (tableId, settingNameBack, settingType, special, specialName) VALUES (?, ?, ?, ?, ?)");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("issis", $tableId, $columnNameBack, $columnType, $special, $specialName);
                $stmt->execute();
                $settingCat = intval($link->insert_id);
                if ($settingCat < 0) throw new Exception('Invalid settings category');
                $stmt->close();

                // Insert standard first setting
                $table_name = $wpdb->prefix . 'htx_settings';
                $link->autocommit(FALSE); //turn on transactions
                $stmt = $link->prepare("INSERT INTO $table_name (tableId, settingId, settingName, value, special, specialName, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iississ",$tableId, $settingCat, $settingName, $value, $special, $specialName, $settingType);
                $settingName = "new setting"; $value="new setting"; 
                $settingType=$userInputType;
                $stmt->execute();
                $stmt->close();

                $table_name = $wpdb->prefix . 'htx_column';                
                $stmt = $link->prepare("UPDATE $table_name SET settingCat = ? WHERE id = ?");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("ii", $settingCat, $lastId);
                $stmt->execute();
                $stmt->close();
            }
            // Check if price already exist
            if ($userInputType == 'price'){
                $text = 'price';
                $table_name = $wpdb->prefix . 'htx_column';
                $stmt = $link->prepare("SELECT * FROM $table_name WHERE tableId = ? and columnType = ?");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("is", $tableId, $text);
                $stmt->execute();
                $result = $stmt->get_result();
                if((($result->num_rows)-1) === 0) {} else
                    throw new Exception('Price element already exist');
                $stmt->close();
            }

            $link->autocommit(TRUE); //turn off transactions + commit queued queries
            $link->close();
            $response->success = true;
            $response->id = $lastId;
        } catch(Exception $e) {
            $response->success = false;
            $response->error = $e->getMessage();
            $link->rollback(); //remove all queries from queue if error (undo)
        }
        echo json_encode($response);
        wp_die();
    }
}

function htx_update_sorting() {
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sorting']) && isset($_POST['setting'])) {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();

            $setting = $_POST['setting'];
            $sorting = intval($_POST['sorting']);

            $link->autocommit(FALSE); //turn on transactions
            $table_name = $wpdb->prefix . 'htx_column';
            $stmt1 = $link->prepare("UPDATE `$table_name` SET sorting = ? WHERE id = ?");
            if(!$stmt1)
                throw new Exception($link->error);
            $stmt1->bind_param("ii", $sorting, $setting);
            $stmt1->execute();
            $stmt1->close();

            $link->autocommit(TRUE); //turn off transactions + commit queued queries
            $link->close();
            $response->success = true;
        } catch(Exception $e) {
            $response->success = false;
            $response->error = $e->getMessage();
            $link->rollback(); //remove all queries from queue if error (undo)
        }
        echo json_encode($response);
        wp_die();
    }
}

function htx_update_column() {
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sorting']) && isset($_POST['setting'])) {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();
            $link->autocommit(FALSE); //turn on transactions

            $possibleFormat = array("text", "number", "email", 'url', 'color', 'date', 'time', 'week', 'month', 'tel', 'range','textarea');
            $possiblePrice = array("", "DKK", ",-", "kr.", 'danske kroner', '$', 'NOK', 'SEK', 'dollars', 'euro');
            $setting = intval($_POST['setting']);

            // Check if table exist
            $tableId = intval($_POST['formid']);

            $table_name = $wpdb->prefix . 'htx_form_tables';
            $stmt = $link->prepare("SELECT * FROM $table_name WHERE id = ? AND active = 1");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("i", $tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) throw new Exception("Form does not exist");
            $stmt->close();

            // Update column settings
            if (isset($_POST['settingsTrue']) AND $_POST['settingsTrue'] == "1") {
                // There are settings
                // Getting number of settings - Checking settingsAmount is a number
                if (isset($_POST['settingsAmount']) AND intval($_POST['settingsAmount']) > 0) {
                    $settingAmount = intval($_POST['settingsAmount']);
                    $table_name = $wpdb->prefix . 'htx_settings';
                    $stmt1 = $link->prepare("UPDATE `$table_name` SET settingName = ?, value = ?, sorting = ?, active = ?, expence = ? WHERE id = ?");
                    if(!$stmt1)
                        throw new Exception($link->error);
                    for ($i=0; $i < $settingAmount; $i++) {
                        // Update every setting
                        // Id for line
                        $lineId = intval($_POST['settingId-'.$i]);
                        if (intval($_POST['settingActive-'.$lineId]) != 0) $active = 1; else $active = 0;
                        $settingNameParam = htmlspecialchars(trim($_POST['settingName-'.$lineId]));
                        $settingValueParam = htmlspecialchars(trim($_POST['settingValue-'.$lineId]));
                        $settingSortingParam = intval($_POST['settingSorting-'.$lineId]);
                        $settingExpenceParam = floatval($_POST['settingExpence-'.$lineId]);
                        $stmt1->bind_param("ssiisi", $settingNameParam, $settingValueParam, $settingSortingParam, $active, $settingExpenceParam, $lineId);
                        $stmt1->execute();
                    }

                    $stmt1->close();
                }
            }

            if (!isset($_POST['placeholder'])) $placeholderText = ""; else $placeholderText = htmlspecialchars(trim($_POST['placeholder']));
            if ($_POST['disabled'] == 1) $required = 0; else $required = $_POST['required']; #Disabeling the option for both required and hidden input
            if (in_array(htmlspecialchars(trim($_POST['format'])), $possibleFormat) OR in_array(trim($_POST['format']), $possiblePrice)) $formatPost = htmlspecialchars(trim($_POST['format'])); else $formatPost = $possibleFormat[0];
            if (htmlspecialchars(trim($_POST['name'])) == "") throw new Exception("No name given.");
            $table_name = $wpdb->prefix . 'htx_column';
            $stmt1 = $link->prepare("UPDATE `$table_name` SET columnNameFront = ?, format = ?, special = ?, specialName = ?, sorting = ?, required = ?, disabled = ?, placeholderText = ?, teams = ?, formatExtra = ?, specialNameExtra = ?, specialnameExtra2 = ?, specialnameExtra3 = ?, minChar = ?, maxChar = ? WHERE id = ?");
            if(!$stmt1)
                throw new Exception($link->error);

            // Special name
            if(!empty($_POST['specialName'])) {
                $speciealPost = 1;
                foreach($_POST['specialName'] as $specials) {
                    $specialPostArrayStart[] = htmlspecialchars(trim($specials));
                }
                $specialPostArray = implode(",", $specialPostArrayStart);
            } else {
                $speciealPost = 0;
                $specialPostArray = "";
            }

            // special name extra 3
            if (isset($_POST['specialNameExtra3'])) {
                $specialNameExtra3 = floatval(trim($_POST['specialNameExtra3']));
            } else {
                $specialNameExtra3 = "";
            }

            // format extra
            if ($formatPost == 'tel') {
                $formatExtra = htmlspecialchars(trim($_POST['formatExtra']));
            } else if ($formatPost == 'range') {
                $formatExtra = floatval(trim($_POST['formatExtra']));
                $placeholderText = floatval($placeholderText);
                if ($specialNameExtra3 < $formatExtra) $specialNameExtra3 = floatval($formatExtra)+floatval(10);
                if ($placeholderText < $formatExtra) $placeholderText = $formatExtra;
                else if ($placeholderText > $specialNameExtra3) $placeholderText = $specialNameExtra3;
            } else {
                $formatExtra = '';
            }

            // Text in text area
            $table_name2 = $wpdb->prefix . 'htx_column';
            $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE id = ?");
            $stmt2->bind_param("i", $setting);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if($result2->num_rows === 0) {} else {
                while($row2 = $result2->fetch_assoc()) {
                    if ($row2['columnType'] == 'text area') 
                        $placeholderText = htmlspecialchars($_POST['placeholder'],ENT_NOQUOTES);
                }
            } 
            $stmt2->close();

            // Special name extra
            if (in_array('show', explode(",", $specialPostArray))) {
                $table_name2 = $wpdb->prefix . 'htx_column';
                $stmt2 = $link->prepare("SELECT id FROM `$table_name2` WHERE tableid = ?");
                $stmt2->bind_param("i", $tableId);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if($result2->num_rows === 0) {} else {
                    while($row2 = $result2->fetch_assoc()) {
                        $columnIds[] = $row2['id'];
                    }
                }
                $stmt2->close();
                $specialNameExtra = htmlspecialchars(trim($_POST['specialNameExtra']));
                if (!in_array($specialNameExtra, $columnIds)) $specialNameExtra = "";
            } else {
                $specialNameExtra = "";
            }

            // Special name extra 2
            if ($_POST['settingShowValueKind'] == 1) {
                // input box
                $specialnameExtra2 = htmlspecialchars(trim($_POST['settingShowValue']));
            } else if ($_POST['settingShowValueKind'] == 2) {
                // Multiple
                if(!empty($_POST['settingShowValue'])) {
                    foreach($_POST['settingShowValue'] as $specials) {
                        $specialNamePostArrayStart[] = htmlspecialchars(trim($specials));
                    }
                    $specialnameExtra2 = implode(",", $specialNamePostArrayStart);
                } else $specialnameExtra2 = "";
            } else {
                // None
                $specialnameExtra2 = "";
            }

            // Character count
            if (in_array('minChar', explode(",", $specialPostArray))) {
                $minChar = intval($_POST['minChar']);
                if ($minChar < 0) $minChar = 0;
            } else $minChar = 0;
            if (in_array('maxChar', explode(",", $specialPostArray))) {
                $maxChar = intval($_POST['maxChar']);
                if ($maxChar < 0) $maxChar = 0;
            } else $maxChar = 250;
            

            $stmt1->bind_param("ssisiiissssssiii", $nameParam, $formatPost, $speciealPost, $specialPostArray, $sortingParam, $required, $disabledParam, $placeholderText, $teamsParam, $formatExtra, $specialNameExtra,$specialnameExtra2,$specialNameExtra3,$minChar,$maxChar,$setting);

            $nameParam = htmlspecialchars(trim($_POST['name']));
            $sortingParam = intval($_POST['sorting']);
            $disabledParam = intval($_POST['disabled']);
            $teamsParam = htmlspecialchars(trim($_POST['teams']));
            


            // Updating special, and inserting as array

            $stmt1->execute();
            $stmt1->close();

            $link->autocommit(TRUE); //turn off transactions + commit queued queries
            $link->close();
            $response->success = true;
        } catch(Exception $e) {
            $response->success = false;
            $response->error = $e->getMessage();
            $link->rollback(); //remove all queries from queue if error (undo)
        }
        echo json_encode($response);
        wp_die();
    }
}

function htx_delete_column() {
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['setting'])) {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();

            $setting = $_POST['setting'];
            $settingsId = intval($_POST['setting']);

            $link->autocommit(FALSE); //turn on transactions

            // Delete cat
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
            $link->close();
            $response->success = true;
        } catch(Exception $e) {
            $response->success = false;
            $response->error = $e->getMessage();
            $link->rollback(); //remove all queries from queue if error (undo)
        }
        echo json_encode($response);
        wp_die();
    }
}

function htx_delete_setting() {
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['setting'])) {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();

            $settingId = $_POST['setting'];
            $table_name = $wpdb->prefix . 'htx_settings';
            $link->autocommit(FALSE); //turn on transactions
            $stmt = $link->prepare("DELETE FROM $table_name WHERE id = ?");
            $stmt->bind_param("i", $settingId);
            $stmt->execute();
            $stmt->close();
            $link->autocommit(TRUE); //turn off transactions + commit queued queries
            $link->close();
            $response->success = true;
        } catch(Exception $e) {
            $response->success = false;
            $response->error = $e->getMessage();
            $link->rollback(); //remove all queries from queue if error (undo)
        }
        echo json_encode($response);
        wp_die();
    }
}

function htx_add_setting() {
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['setting'])) {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        $possibleInput = array("inputbox", "dropdown", "user dropdown", "text area", "radio", "checkbox", "price");
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();

            // Check if table exist
            $tableId = intval($_POST['tableId']);

            $table_name = $wpdb->prefix . 'htx_form_tables';
            $stmt = $link->prepare("SELECT * FROM $table_name WHERE id = ? AND active = 1");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("i", $tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) throw new Exception("Form does not exist");
            $stmt->close();

            $table_name = $wpdb->prefix . 'htx_settings';
            $link->autocommit(FALSE); //turn on transactions
            $stmt = $link->prepare("INSERT INTO $table_name (tableId, settingId, settingName, value, special, specialName, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iississ", $tableId, $_POST['setting'], $settingName, $value, $special, $specialName, $settingType);
            $settingName = "new setting"; $value="new setting"; $special=0; $specialName="";
            if (in_array($_POST['columnType'], $possibleInput)) $settingType = htmlspecialchars($_POST['columnType']); else $settingType="dropdown";
            $stmt->execute();
            $stmt->close();
            $link->autocommit(TRUE); //turn off transactions + commit queued queries
            $link->close();
            $response->success = true;
        } catch(Exception $e) {
            $response->success = false;
            $response->error = $e->getMessage();
            $link->rollback(); //remove all queries from queue if error (undo)
        }
        echo json_encode($response);
        wp_die();
    }
}

function htx_dublicate_form() {
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['formid'])) {
        $response = new stdClass();
        header('Content-type: application/json');
        if(!current_user_can("manage_options"))
            {return;$response->error = "Missing permission";}

        try {
            global $wpdb;
            $link = database_connection();
            $link->autocommit(FALSE); //turn on transactions

            $tableId = intval($_POST['formid']);

            // getting curent form name and data
            $table_name = $wpdb->prefix . 'htx_form_tables';
            $stmt = $link->prepare("SELECT * FROM `$table_name` where id = ? LIMIT 1");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param('i',$tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                throw new Exception('The form is no longer available');
                $stmt->close();
            } else {
                while($row = $result->fetch_assoc()) {
                    $tableName = $row['tableName']." (copy)";
                    $tableActive = $row['active'];
                    $tableDescription = $row['tableDescription'];
                    $tableArrived = $row['arrived'];
                    $tableCrew = $row['crew'];
                    $tablePiza = $row['pizza'];
                    $tableArrivedAtDoor = $row['arrivedAtDoor'];
                    $tableRegistration = $row['registration'];
                    $tableOpenForm = $row['openForm'];
                    $tableCloseForm = $row['closeForm'];
                    $tableCloseFormActive = intval($row['closeFormActive']);
                    $tableEmailEnable = $row['emailEnable'];
                    $tableEmailSender = $row['emailSender'];
                    $tableEmailSubject = $row['emailSubject'];
                    $tableEmailText = $row['emailText'];
                }
                $stmt->close();

                // Make new table
                $stmt = $link->prepare("INSERT INTO `$table_name` (active, favorit, shortcode, tableName, tableDescription, arrived, crew, pizza, arrivedAtDoor, registration, openForm, closeForm, closeFormActive, emailEnable, emailSender, emailSubject, emailText) VALUES (?,0,'HTX_Tilmeldningsblanket',?,?,?,?,?,?,?,?,?,?,?,?,?,?);");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("issiiiiisssssss", $tableActive, $tableName, $tableDescription,$tableArrived,$tableCrew,$tablePiza,$tableArrivedAtDoor,$tableRegistration, $tableOpenForm, $tableCloseForm, $tableCloseFormActive,$tableEmailEnable,$tableEmailSender,$tableEmailSubject,$tableEmailText);
                if(!$stmt)
                    throw new Exception($link->error);
                if ($stmt->execute()) { 
                    // it worked
                } else {
                    // it didn't
                    throw new Exception("New table was not added.\n Error code: ".$stmt->error);
                }
                $tableNewId = $link->insert_id;
                $stmt->close();
            }
            
            // Get current settings category
            $table_name = $wpdb->prefix . 'htx_settings_cat';
            $stmt = $link->prepare("SELECT * FROM `$table_name` where tableId = ?");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param('i',$tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                $stmt->close();
            } else {
                while($row = $result->fetch_assoc()) {
                    $settingCatId[] = $row['id'];
                    $settingCatActive[] = $row['active'];
                    $settingCatNameBack[] = $row['settingNameBack'];
                    $settingCatType[] = $row['settingType'];
                }
                $stmt->close();

                // Make new settings category
                $stmt = $link->prepare("INSERT INTO `$table_name` (active, settingNameBack, settingType, tableId) VALUES (?,?,?,?)");
                for ($i=0; $i < count($settingCatId); $i++) { 
                    $stmt->bind_param("issi", $settingCatActive[$i], $settingCatNameBack[$i], $settingCatType[$i],$tableNewId);
                    $stmt->execute();
                    $settignCatNewId[$settingCatId[$i]] = $link->insert_id;
                }
                $stmt->close();
            }

            // Get current settings
            $table_name = $wpdb->prefix . 'htx_settings';
            $stmt = $link->prepare("SELECT * FROM `$table_name` where tableId = ?");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param('i',$tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                $stmt->close();
            } else {
                while($row = $result->fetch_assoc()) {
                    $settingId[] = $row['id'];
                    $settingSettingId[] = $row['settingId'];
                    $settingActive[] = $row['active'];
                    $settingName[] = $row['settingName'];
                    $settingValue[] = $row['value'];
                    $settingExpence[] = $row['expence'];
                    $settingType[] = $row['type'];
                    $settingSorting[] = $row['sorting'];
                }
                $stmt->close();

                // Make new settings
                $stmt = $link->prepare("INSERT INTO `$table_name` (settingId, active, settingName, value, expence, type, sorting, tableId) VALUES (?,?,?,?,?,?,?,?)");
                for ($i=0; $i < count($settingId); $i++) { 
                    $stmt->bind_param("issssssi", $settignCatNewId[$settingSettingId[$i]], $settingActive[$i], $settingName[$i], $settingValue[$i], $settingExpence[$i], $settingType[$i], $settingSorting[$i], $tableNewId);
                    $stmt->execute();
                    $settignNewId[$settingId[$i]] = $link->insert_id;
                }
                $stmt->close();
            }

            // Get current users
            $table_name = $wpdb->prefix . 'htx_form_users';
            $stmt = $link->prepare("SELECT * FROM `$table_name` where tableId = ?");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param('i',$tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                $stmt->close();
            } else {
                while($row = $result->fetch_assoc()) {
                    $usersId[] = $row['id'];
                    $usersActive[] = $row['active'];
                    $usersPayed[] = $row['payed'];
                    $usersArrived[] = $row['arrived'];
                    $usersCrew[] = $row['crew'];
                    $usersPizza[] = $row['pizza'];
                    $usersEmail[] = $row['email'];
                }
                $stmt->close();

                // Make new users
                $stmt = $link->prepare("INSERT INTO `$table_name` (active, payed, arrived, crew, pizza, email, tableId) VALUES (?,?,?,?,?,?,?)");
                if(!$stmt)
                throw new Exception($link->error);
                for ($i=0; $i < count($usersId); $i++) { 
                    $stmt->bind_param("isiissi", $usersActive[$i], $usersPayed[$i], $usersArrived[$i], $usersCrew[$i], $usersPizza[$i], $usersEmail[$i], $tableNewId);
                    $stmt->execute();
                    $usersNewId[$usersId[$i]] = $link->insert_id;
                }
                $stmt->close();
            }

            // Get current columns
            $table_name = $wpdb->prefix . 'htx_column';
            $stmt = $link->prepare("SELECT * FROM `$table_name` where tableId = ?");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param('i',$tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                $stmt->close();
            } else {
                while($row = $result->fetch_assoc()) {
                    $columnId[] = $row['id'];
                    $columnActive[] = $row['active'];
                    $columnNameFront[] = $row['columnNameFront'];
                    $columnNameBack[] = $row['columnNameBack'];
                    $columnSettingCat[] = $row['settingCat'];
                    $columnFormat[] = $row['format'];
                    $columnType[] = $row['columnType'];
                    $columnSpecial[] = $row['special'];
                    $columnSpecialName[] = $row['specialName'];
                    $columnPlaceholderText[] = $row['placeholderText'];
                    $columnTeams[] = $row['teams'];
                    $columnFormatExtra[] = $row['formatExtra'];
                    $columnSpecialNameExtra[] = $row['specialNameExtra'];
                    $columnSpecialNameExtra2[] = $row['specialNameExtra2'];
                    $columnSpecialNameExtra3[] = $row['specialNameExtra3'];
                    $columnSpecialNameExtra4[] = $row['specialNameExtra4'];
                    $columnMinChar[] = $row['minChar'];
                    $columnMaxChar[] = $row['maxChar'];
                    $columnSorting[] = $row['sorting'];
                    $columnDisabled[] = $row['disabled'];
                    $columnRequired[] = $row['required'];
                }
                $columnSettingCat['zero'] = 0;
                $stmt->close();

                // Make new columns
                $stmt = $link->prepare("INSERT INTO `$table_name` (active, columnNameFront, columnNameBack, settingCat, format, columnType, special, specialName, placeholderText, teams, formatExtra, specialNameExtra, specialNameExtra2, specialNameExtra3, specialNameExtra4, minChar, maxChar, sorting, disabled, required, tableId) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                if(!$stmt)
                    throw new Exception($link->error);
                
                for ($i=0; $i < count($columnId); $i++) { 
                    if ($columnSettingCat[$i] == 0 or $columnSettingCat[$i] == null or $columnSettingCat[$i] == NULL) $columnSettingCatNew[$i] = 0; 
                        else $columnSettingCatNew[$i] = $settignCatNewId[$columnSettingCat[$i]];
                    if ($columnTeams[$i] == "" or $columnTeams[$i] == null) $columnTeamsNew[$i] = "";
                        else $columnTeamsNew[$i] = $settignNewId[$columnTeams[$i]];
                    if ($columnSpecialNameExtra2[$i] == "" or $columnSpecialNameExtra2[$i] == null or $columnSpecialNameExtra2[$i] == NULL) $columnSpecialNameExtra2New[$i] = ""; 
                    else $columnSpecialNameExtra2New[$i] = $settignNewId[$columnSpecialNameExtra2[$i]];

                    $stmt->bind_param("sssssssssssssssiisssi", $columnActive[$i], $columnNameFront[$i], $columnNameBack[$i], $columnSettingCatNew[$i], $columnFormat[$i],$columnType[$i], $columnSpecial[$i], $columnSpecialName[$i], $columnPlaceholderText[$i], $columnTeamsNew[$i], $columnFormatExtra[$i], $columnSpecialNameExtra[$i],$columnSpecialNameExtra2New[$i], $columnSpecialNameExtra3[$i], $columnSpecialNameExtra4[$i], $columnMinChar[$i], $columnMaxChar[$i], $columnSorting[$i],$columnDisabled[$i],$columnRequired[$i], $tableNewId);
                    $stmt->execute();
                    $columnNewId[$columnId[$i]] = $link->insert_id;

                    if($columnSpecialNameExtra[$i] != "" or $columnSpecialNameExtra[$i] != null OR $columnSpecialNameExtra[$i] != NULL){
                        $updateSpecialNameExtra[] = $link->insert_id;
                        $updateSpecialNameExtraI[] = $i;
                    }
                }

                // Update table to get the right specialNameExtra values
                $stmt->close();
                $stmt = $link->prepare("UPDATE `$table_name` SET specialNameExtra = ? WHERE id = ?");
                if(!$stmt)
                    throw new Exception($link->error);
                if (isset($updateSpecialNameExtra)) {
                    for ($i=0; $i < count($updateSpecialNameExtra); $i++) { 
                        $stmt->bind_param("ii", $columnNewId[$columnSpecialNameExtra[$updateSpecialNameExtraI[$i]]] ,$updateSpecialNameExtra[$i]);
                        $stmt->execute();
                        $response->newSpecialNameExtra[] = $columnNewId[$columnSpecialNameExtra[$updateSpecialNameExtraI[$i]]];
                    }
                }
                $stmt->close();
            }

            // Get current submissions
            $table_name = $wpdb->prefix . 'htx_form';
            $stmt = $link->prepare("SELECT * FROM `$table_name` where tableId = ?");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param('i',$tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                $stmt->close();
            } else {
                while($row = $result->fetch_assoc()) {
                    $form[] = $row['id'];
                    $formActive[] = $row['active'];
                    $formUserId[] = $row['userId'];
                    $formName[] = $row['name'];
                    $formValue[] = $row['value'];
                }   
                $stmt->close();

                // Make new submissions
                $stmt = $link->prepare("INSERT INTO `$table_name` (active, userId, name, value, tableId) VALUES (?,?,?,?,?)");
                if(!$stmt)
                    throw new Exception($link->error);
                for ($i=0; $i < count($form); $i++) {
                    if (in_array($formName[$i],$settingCatNameBack)) {
                        if (in_array($formValue[$i],$settingId))
                            $formValue[$i] = $settignNewId[$formValue[$i]];
                    }
                    $stmt->bind_param("ssssi", $formActive[$i], $usersNewId[$formUserId[$i]], $formName[$i], $formValue[$i], $tableNewId);
                    $stmt->execute();
                    $formNewId[$formUserId[$i]] = $link->insert_id;
                }
                $stmt->close();
            }

            $link->autocommit(TRUE); //turn off transactions + commit queued queries
            $link->close();

            $response->success = true;
            $response->id = $tableId;
            $response->newName = $tableName;
            $response->tableId = $tableNewId;
        } catch(Exception $e) {
            $response->success = false;
            $response->error = $e->getMessage();
            $link->rollback(); //remove all queries from queue if error (undo)
        }

        echo json_encode($response);
        wp_die();
    }
}

function htx_participant_update() {
    // Post handling
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['formid'])) {
        $response = new stdClass();
        header('Content-type: application/json');
        // Post handling
        // Database connection
        $link = database_connection();
        global $wpdb;

        if(!current_user_can("manage_options")){
            $response->success = 'permission denied';
            echo json_encode($response);
            wp_die();
        }
        if ($_POST['postType'] == 'update') {
            try {
                $tableId = intval($_POST['formid']);
                $userId = intval($_POST['userId']);
                $link->autocommit(FALSE); //turn on transactions

                // Check if form exist
                $table_name = $wpdb->prefix . 'htx_form_tables';
                $stmt = $link->prepare("SELECT * FROM $table_name WHERE id = ?");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("i", $tableId);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows === 0) throw new Exception("Form does not exist");
                $stmt->close();

                // Checking user id
                $table_name = $wpdb->prefix . 'htx_form_users';
                $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableID = ? and active = 1 and id = ?");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("ii", $tableId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows === 0) {
                        throw new Exception('User does not exist');
                }
                $stmt->close();

                // Getting and checking values
                $paymentMethodsId = array("0", "0-f", "1-f");
                if (!isset($_POST['paymentOption']) AND !in_array($_POST['paymentOption'], $paymentMethodsId))
                    throw new Exception('payment error');
                else 
                    $payment = $_POST['paymentOption'];

                if (isset($_POST['arrived']) AND $_POST['arrived'] != "0" AND $_POST['arrived'] != "1")
                    throw new Exception('arrived error');
                else 
                    $arrived = intval($_POST['arrived']);

                if (isset($_POST['arrivedAtDoor']) AND $_POST['arrivedAtDoor'] != "0" AND $_POST['arrivedAtDoor'] != "1")
                    throw new Exception('arrivedAtDoor error');
                else 
                    $arrivedAtDoor = intval($_POST['arrivedAtDoor']);

                if (isset($_POST['crew']) AND $_POST['crew'] != "0" AND $_POST['crew'] != "1")
                    throw new Exception('crew error');
                else 
                    $crew = intval($_POST['crew']);

                if (isset($_POST['pizza']) AND $_POST['pizza'] != "0" AND $_POST['pizza'] != "1")
                    throw new Exception('pizza error');
                else 
                    $pizza = intval($_POST['pizza']);

                // Sending new payment id to server
                $table_name = $wpdb->prefix . 'htx_form_users';
                $stmt = $link->prepare("UPDATE $table_name SET crew = ?, payed = ?, arrived = ?, arrivedAtDoor = ?, pizza = ? WHERE id = ?");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("isiiii", $crew,$payment,$arrived,$arrivedAtDoor,$pizza, $userId);
                $stmt->execute();
                $stmt->close();

                $link->autocommit(TRUE); //turn off transactions + commit queued queries
                $link->close();
                $response->success = true;
            } catch(Exception $e) {
                $link->rollback(); //remove all queries from queue if error (undo)
                $response->success = false;
                $response->error = $e->getMessage();
            }
            echo json_encode($response);
            wp_die();
        } else if ($_POST['postType'] == "deleteSubmission") {
            try {
                $tableId = intval($_POST['formid']);
                $userId = intval($_POST['userId']);
                $link->autocommit(FALSE); //turn on transactions

                // Check if form exist
                $table_name = $wpdb->prefix . 'htx_form_tables';
                $stmt = $link->prepare("SELECT * FROM $table_name WHERE id = ?");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("i", $tableId);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows === 0) throw new Exception("Form does not exist");
                $stmt->close();

                // Checking user id
                $table_name = $wpdb->prefix . 'htx_form_users';
                $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableID = ? and active = 1 and id = ?");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("ii", $tableId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows === 0) {
                        throw new Exception('User does not exist');
                }
                $stmt->close();

                // Delete user id
                $table_name = $wpdb->prefix . 'htx_form_users';
                $stmt = $link->prepare("DELETE FROM `$table_name` WHERE tableID = ? and id = ?");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("ii", $tableId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows === 0) throw new Exception('No user with this id');
                $stmt->close();

                // Delete form elements user submittet
                $table_name = $wpdb->prefix . 'htx_form';
                $stmt = $link->prepare("DELETE FROM `$table_name` WHERE tableID = ? and userId = ?");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("ii", $tableId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                $link->autocommit(TRUE); //turn off transactions + commit queued queries
                $link->close();
                $response->success = true;
            } catch(Exception $e) {
                $link->rollback(); //remove all queries from queue if error (undo)
                $response->success = false;
                $response->error = $e->getMessage();
            }
            echo json_encode($response);
            wp_die();
        } else {
            $response->success = 'postType not defined';
        }
    }

    echo json_encode($response);
    wp_die();
}

// Widgets
function htx_live_participant_count() {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['formid'])) {
        $response = new stdClass();
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();
            $tableId = intval($_POST['formid']);
            $link->autocommit(FALSE); //turn on transactions

            // Check if form exist
            $table_name = $wpdb->prefix . 'htx_form_tables';
            $stmt = $link->prepare("SELECT id FROM $table_name WHERE id = ?");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("i", $tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) throw new Exception("Form does not exist");
            $stmt->close();

            // Get participant count
            $table_name = $wpdb->prefix . 'htx_form_users';
            $stmt = $link->prepare("SELECT tableId FROM `$table_name` WHERE tableId = ?");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("i", $tableId);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) $number = 0;
            else if ($result->num_rows < 0) throw new Exception("Form does not exist");
            else $number = $result->num_rows;
            $stmt->close();

            $response->success = 'true';
            $response->number = $number;
            $link->autocommit(TRUE); //turn off transactions + commit queued queries
        } catch(Exception $e) {
            $response->success = 'false';
            $response->number = '0';
            $response->error = $e->getMessage();
            $link->rollback(); //remove all queries from queue if error (undo)
        }

        echo json_encode($response);
        wp_die();
    }
} 

?>
