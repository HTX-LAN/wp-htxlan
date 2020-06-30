<?php
    // Functions and scripts written in php

    // Backend post handling
    function HTX_backend_post() {


        // Danger zone
        // Script for deleting all participants - PHP part

        // Script for dropping databases

        // Script for creating databases
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            switch  ($_POST['postType']) {
                case 'createDatabases':
                    create_db();
                    insert_data();
                break;
                case 'dropDatabases':
                    drop_db();
                break;
            }
        }
    }

    // frontend post handling
    function HTX_frontend_post($tableId) {
        // Database connection
        $link = database_connection();
        global $wpdb;

        // Check tableId
        if (!ctype_alnum($tableId)) return "Sql injection attempt";

        // Post handling
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            switch  ($_POST['submit']) {
                // New submission
                case 'new':
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
                    $stmt->bind_param("si", trim($_POST['email']), $tableId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0) {} else return "Email already exist";
                    $stmt->close();
                    

                    // Convert values to the right format
                    // Getting column info
                    $table_name = $wpdb->prefix . 'htx_column';
                    $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableid = ? AND adminOnly = 0");
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
                            $adminOnly[] = $row['adminOnly'];
                            $required[] = $row['required'];
                        }
                    }
                    $stmt->close();
                    
                    // Inserting every input into row
                    try {
                        $link->autocommit(FALSE); //turn on transactions

                        // Inserting user and getting id
                        $table_name = $wpdb->prefix . 'htx_form_users';
                        $stmt = $link->prepare("INSERT INTO `$table_name` (tableId, email) VALUES (?, ?)");
                        $stmt->bind_param("is", $tableId, trim($_POST['email']));
                        $stmt->execute();
                        $formUserId = $link->insert_id;
                        $stmt->close();

                        // Inserting rest of rows
                        $table_name = $wpdb->prefix . 'htx_form';
                        $stmt = $link->prepare("INSERT INTO `$table_name` (name, value, userId, tableId) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("ssii", $inputName, $inputValue, intval($formUserId), intval($tableId));
                        for ($i=0; $i < count($columnNameBack); $i++) { 
                            $inputName = $columnNameBack[$i];
                            $inputValue = strval(trim($_POST[$columnNameBack[$i]])); 

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
            return('Error connecting to database. Error message:'.$e); //Should be a message a typical user could understand
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
?>