<?php

//Prevent direct file access
if(!defined('ABSPATH')) {
    header("Location: ../../../");
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
            $table_name = $wpdb->prefix . "htx_form_tables";
            $stmt = $link->prepare('DELETE FROM `' . $table_name . '` WHERE `id`=?');
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param('i', $_POST['formid']);
            $stmt->execute();
            $stmt->close();
            $table_name = $wpdb->prefix . "htx_form";
            $stmt = $link->prepare('DELETE FROM `' . $table_name . '` WHERE `tableId`=?');
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param('i', $_POST['formid']);
            $stmt->execute();
            $stmt->close();
            $link->close();
            $response->success = true;
        } catch(Exception $e) {
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
            $stmt = $link->prepare("UPDATE $table_name SET tableName = ?, tableDescription = ? WHERE id = ?");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("ssi", $_POST['tableName'], $_POST['tableDescription'], $_POST['formid']);
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
        $possibleInput = array("inputbox", "dropdown", "text area", "radio", "checkbox");
        $response = new stdClass();
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();
            $link->autocommit(FALSE); //turn on transactions
            // User input
            $userInputType = $_POST['inputType'];
            $tableId = $_POST['tableId'];
            //Default values
            $format = 'text';
            // Break if the user input is not known
            if (!in_array($userInputType, $possibleInput)) throw new Exception('Invalid input type');
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
            if ($userInputType == 'dropdown') {
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
                $stmt = $link->prepare("INSERT INTO $table_name (settingId, settingName, value, special, specialName, type) VALUES (?, ?, ?, ?, ?, ?)");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("ississ", $settingCat, $settingName, $value, $special, $specialName, $settingType);
                $settingName = "new setting"; $value="new setting"; $settingType="radio";
                $stmt->execute();
                $stmt->close();
            }
            if ($userInputType == 'checkbox') {
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
                $stmt = $link->prepare("INSERT INTO $table_name (settingId, settingName, value, special, specialName, type) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ississ", $settingCat, $settingName, $value, $special, $specialName, $settingType);
                $settingName = "new setting"; $value="new setting"; $settingType="checkbox";
                $stmt->execute();
                $stmt->close();
            }

            $table_name = $wpdb->prefix . 'htx_settings_cat';
            $stmt = $link->prepare("UPDATE $table_name SET settingNameBack = ? WHERE id = ?");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param("ii", $lastId, $settingCat);
            $stmt->execute();
            $stmt->close();

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
            $possibleFormat = array("text", "number", "email", 'url', 'color', 'date', 'time', 'week', 'month');
            $setting = $_POST['setting'];

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
                        if(!$stmt1)
                            throw new Exception($link->error);
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

            if (!isset($_POST['placeholder'])) $placeholderText = ""; else $placeholderText = trim($_POST['placeholder']);
            if ($_POST['disabled'] == 1) $required = 0; else $required = $_POST['required']; #Disabeling the option for both required and hidden input
            if (in_array(trim($_POST['format']), $possibleFormat)) $formatPost = htmlspecialchars(trim($_POST['format'])); else $formatPost = $possibleFormat[0];
            if (trim($_POST['name']) == "") throw new Exception("No name given.");
            $link->autocommit(FALSE); //turn on transactions
            $table_name = $wpdb->prefix . 'htx_column';
            $stmt1 = $link->prepare("UPDATE `$table_name` SET columnNameFront = ?, format = ?, special = ?, specialName = ?, sorting = ?, required = ?, disabled = ?, placeholderText = ? WHERE id = ?");
            if(!$stmt1)
                throw new Exception($link->error);
            if(!empty($_POST['specialName'])) {
                $speciealPost = 1;
                foreach($_POST['specialName'] as $specials) {
                    $specialPostArrayStart[] = $specials;
                }
                $specialPostArray = implode(",", $specialPostArrayStart);
            } else {
                $speciealPost = 0;
                $specialPostArray = "";
            }
            $stmt1->bind_param("ssisiiisi", htmlspecialchars(trim($_POST['name'])), $formatPost, $speciealPost, $specialPostArray, intval($_POST['sorting']), $required, $_POST['disabled'], $placeholderText, $setting);
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
        $possibleInput = array("inputbox", "dropdown", "text area", "radio", "checkbox");
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();

            $table_name = $wpdb->prefix . 'htx_settings';
            $link->autocommit(FALSE); //turn on transactions
            $stmt = $link->prepare("INSERT INTO $table_name (settingId, settingName, value, special, specialName, type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ississ", $_POST['setting'], $settingName, $value, $special, $specialName, $settingType);
            $settingName = "new setting"; $value="new setting"; $special=0; $specialName="";
            if (in_array($_POST['columnType'], $possibleInput)) $settingType=htmlspecialchars($_POST['columnType']); else $settingType="dropdown";
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

?>
