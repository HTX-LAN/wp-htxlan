<?php
    // Functions and scripts written in php

    // frontend post handling
    function HTX_frontend_post($tableId) {
        // Database connection
        $link = database_connection();
        global $wpdb;

        // Check tableId
        if (!ctype_alnum($tableId)) return "Sql injection attempt";
        $tableId = intval($tableId);

        // Post handling
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            switch  ($_POST['submit']) {
                // New submission
                case 'new':
                    try {
                        // Check that the form trying to submit to, is the right one
                        $table_name = $wpdb->prefix . 'htx_form_tables';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE id = ?");
                        $stmt->bind_param("i", $tableId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows === 0) return "Table does not match";
                        $stmt->close();

                        // Checking values
                        // Check if mail exist
                        $table_name = $wpdb->prefix . 'htx_form_users';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE email = ? AND tableId = ?");
                        $stmt->bind_param("si", htmlspecialchars(trim($_POST['email'])), $tableId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows === 0) {} else return "Email already exist";
                        $stmt->close();


                        // Convert values to the right format
                        // Getting column info
                        $table_name = $wpdb->prefix . 'htx_column';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableid = ?");
                        $stmt->bind_param("i", $tableId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows === 0) return "Ingen kollonner"; else {
                            while($row = $result->fetch_assoc()) {
                                $columnNameFront[] = $row['columnNameFront'];
                                $columnNameBack[] = $row['columnNameBack'];
                                $format[] = $row['format'];
                                $columnType[] = $row['columnType'];
                                $special[] = $row['special'];
                                $specialName[] = $row['specialName'];
                                $placeholderText[] = $row['placeholderText'];
                                $sorting[] = $row['sorting'];
                                $required[] = $row['required'];
                            }
                        }
                        $stmt->close();

                        // Inserting every input into row
                    
                        $link->autocommit(FALSE); //turn on transactions

                        // Inserting user and getting id
                        $table_name = $wpdb->prefix . 'htx_form_users';
                        $stmt = $link->prepare("INSERT INTO `$table_name` (tableId, email) VALUES (?, ?)");
                        $stmt->bind_param("is", $tableId, htmlspecialchars(trim($_POST['email'])));
                        $stmt->execute();
                        $formUserId = $link->insert_id;
                        $stmt->close();

                        // Inserting rest of rows
                        $table_name = $wpdb->prefix . 'htx_form';
                        $stmt = $link->prepare("INSERT INTO `$table_name` (name, value, userId, tableId) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("ssii", $inputName, $inputValue, intval($formUserId), intval($tableId));
                        for ($i=0; $i < count($columnNameBack); $i++) {
                            $inputName = $columnNameBack[$i];
                            
                            // Does a speciel implode a data, when it is a checkbox
                            if ($columnType[$i] == 'checkbox') {
                                if(!empty($_POST[$columnNameBack[$i]])) {
                                    foreach($_POST[$columnNameBack[$i]] as $specials) {
                                        $specialPostArrayStart[] = $specials;
                                    }
                                    $inputValue = implode(",", $specialPostArrayStart);
                                } else $inputValue = "";
                            } else {
                                $inputValue = htmlspecialchars(strval(trim($_POST[$columnNameBack[$i]])));
                            }

                            // Missing validation of phone number and mail adress

                            // Missing validation of dropdown menu

                            $stmt->execute();
                        }
                        $stmt->close();
                        $link->autocommit(TRUE); //turn off transactions + commit queued queries
                    } catch(Exception $e) {
                        $link->rollback(); //remove all queries from queue if error (undo)
                        // throw $e;
                        return "<span style='color: red'>Tilmeldingen blev ikke tilføjet</span>";
                    }

                    // Error handling (Needs to be more specifik)

                    // Success handling
                    return "<span style='color: green'>Tilmeldingen blev tilføjet</span>";
                break;
                default: return "Noget gik galt🤔";
            }
        }
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

    // Setting cookie
    function setCustomCookie($cookieName, $cookieValue) {
        wp_enqueue_script( 'cookie', "/wp-content/plugins/WPPlugin-HTXLan/JS/cookie.js");
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

    // Script to create new form
    function new_HTX_form() {
        try {
            $link = database_connection();
            global $wpdb;
            $link->autocommit(FALSE); //turn on transactions

            // Creating new form in form tables
            echo "create!";
            $table_name = $wpdb->prefix . 'htx_form_tables';
            $shortcode = "HTX_Tilmeldningsblanket"; $Name = 'Ny formular';
            $stmt = $link->prepare("INSERT INTO $table_name (shortcode, tableName) VALUES (?, ?)");
            $stmt->bind_param("ss", $shortcode, $Name);
            $stmt->execute();
            $newTableId = intval($link->insert_id);
            $stmt->close();

            // Creating standard inputs (First- & lastname, email & phone)
            $table_name = $wpdb->prefix . 'htx_column';
            $link->autocommit(FALSE); //turn on transactions
            $stmt = $link->prepare("INSERT INTO $table_name (tableId, columnNameFront, columnNameBack, format, columnType, special, specialName, sorting, placeholderText, required, settingCat) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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

            // Reload page to new form
            echo "<form id='installNewForm' method='GET'>
                <input type='hidden' name='page' value='HTX_lan_create_form'>
                <input type='hidden' name='form' value='$tableId'>
            </form>
            <script>document.getElementById('installNewForm').submit()</script>";


            $link->autocommit(TRUE); //turn off transactions + commit queued queries
        } catch(Exception $e) {
            $link->rollback(); //remove all queries from queue if error (undo)
            // throw $e;
            return "<span style='color: red'>Tilmeldingen blev ikke tilføjet</span>";
        }

    }

    // Participant list post handling from backend
    function participantList_post($tableId){
        // Post handling
        // Database connection
        $link = database_connection();
        global $wpdb;



        // Post handling
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            switch  ($_POST['post']) {
                case 'paymentUpdate':
                    try {
                        $link->autocommit(FALSE); //turn on transactions
                        // Get all user ids
                        $table_name = $wpdb->prefix . 'htx_form_users';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableID = ? and active = 1");
                        $stmt->bind_param("i", $tableId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows === 0) echo "Noget gik galt"; else {
                            while($row = $result->fetch_assoc()) {
                                $userIds[] = $row['id'];
                            }
                        }
                        $stmt->close();
                        // Getting and checking user id
                        if (!isset($_POST['userId']) AND !in_array(intval($_POST['userId']), $userIds)) break;

                        // Getting and checking new payment id
                        // Payment type
                        $paymentMethods = array("Kontant", "Mobilepay");
                        $paymentMethodsId = array("0", "0-f", "1-f");
                        if (!isset($_POST['paymentOption']) AND !in_array($_POST['paymentOption'], $paymentMethodsId)) break;


                        // Sending new payment id to server
                        $table_name = $wpdb->prefix . 'htx_form_users';
                        $stmt = $link->prepare("UPDATE $table_name SET payed = ? WHERE id = ?");
                        $stmt->bind_param("si", $_POST['paymentOption'], $_POST['userId']);
                        $stmt->execute();
                        $stmt->close();

                        $link->autocommit(TRUE); //turn off transactions + commit queued queries
                        echo "<script>setTimeout(() => {informationwindowInsert(1,'Linjen blev opdateret')}, 500);</script>"; //User feedback
                    } catch(Exception $e) {
                        $link->rollback(); //remove all queries from queue if error (undo)
                        throw $e;
                    }
                break;
                case "arrivedtUpdate":
                    try {
                        $link->autocommit(FALSE); //turn on transactions
                        // Get all user ids
                        $table_name = $wpdb->prefix . 'htx_form_users';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableID = ? and active = 1");
                        $stmt->bind_param("i", $tableId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows === 0) echo "Noget gik galt"; else {
                            while($row = $result->fetch_assoc()) {
                                $userIds[] = $row['id'];
                            }
                        }
                        $stmt->close();
                        // Getting and checking user id
                        if (!isset($_POST['userId']) AND !in_array(intval($_POST['userId']), $userIds)) break;

                        // Getting and checking new payment id
                        // Payment type
                        if ($_POST['arrived'] != "0" AND $_POST['arrived'] != "1") break;


                        // Sending new payment id to server
                        $table_name = $wpdb->prefix . 'htx_form_users';
                        $stmt = $link->prepare("UPDATE $table_name SET arrived = ? WHERE id = ?");
                        $stmt->bind_param("ii", $_POST['arrived'], $_POST['userId']);
                        $stmt->execute();
                        $stmt->close();

                        $link->autocommit(TRUE); //turn off transactions + commit queued queries
                        echo "<script>setTimeout(() => {informationwindowInsert(1,'Linjen blev opdateret')}, 500);</script>"; //User feedback
                    } catch(Exception $e) {
                        $link->rollback(); //remove all queries from queue if error (undo)
                        throw $e;
                    }
                break;
                case "crewUpdate":
                    try {
                        $link->autocommit(FALSE); //turn on transactions
                        // Get all user ids
                        $table_name = $wpdb->prefix . 'htx_form_users';
                        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableID = ? and active = 1");
                        $stmt->bind_param("i", $tableId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows === 0) echo "Noget gik galt"; else {
                            while($row = $result->fetch_assoc()) {
                                $userIds[] = $row['id'];
                            }
                        }
                        $stmt->close();
                        // Getting and checking user id
                        if (!isset($_POST['userId']) AND !in_array(intval($_POST['userId']), $userIds)) break;

                        // Getting and checking new payment id
                        // Payment type
                        if ($_POST['crew'] != "0" AND $_POST['crew'] != "1") break;


                        // Sending new payment id to server
                        $table_name = $wpdb->prefix . 'htx_form_users';
                        $stmt = $link->prepare("UPDATE $table_name SET crew = ? WHERE id = ?");
                        $stmt->bind_param("ii", $_POST['crew'], $_POST['userId']);
                        $stmt->execute();
                        $stmt->close();

                        $link->autocommit(TRUE); //turn off transactions + commit queued queries
                        echo "<script>setTimeout(() => {informationwindowInsert(1,'Linjen blev opdateret')}, 500);</script>"; //User feedback
                    } catch(Exception $e) {
                        $link->rollback(); //remove all queries from queue if error (undo)
                        throw $e;
                    }
                break;
            }
        }
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if ($_POST['delete'] == "deleteSubmission") {
                // Deleting submission
                try {
                    $link->autocommit(FALSE); //turn on transactions
                    // Delete user id
                    $table_name = $wpdb->prefix . 'htx_form_users';
                    $stmt = $link->prepare("DELETE FROM `$table_name` WHERE tableID = ? and id = ?");
                    $stmt->bind_param("ii", $tableId, intval($_POST['userid']));
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0) echo "Ingen bruger med det id";
                    $stmt->close();

                    // Delete form elements user submittet
                    $table_name = $wpdb->prefix . 'htx_form';
                    $stmt = $link->prepare("DELETE FROM `$table_name` WHERE tableID = ? and userId = ?");
                    $stmt->bind_param("ii", $tableId, intval($_POST['userid']));
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0) echo "Ingen submission elementer med det id";
                    $stmt->close();

                    $link->autocommit(TRUE); //turn off transactions + commit queued queries
                    echo "<script>setTimeout(() => {informationwindowInsert(1,'Linjen blev slettet')}, 100);</script>"; //User feedback
                } catch(Exception $e) {
                    $link->rollback(); //remove all queries from queue if error (undo)
                    throw $e;
                }
            }
        }
    }
?>
