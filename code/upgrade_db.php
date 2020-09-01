<?php
    // Upgrading of database
    function upgrade_db() {
        global $databaseVersion;
        // Check version number
        try {
            global $wpdb;
            $link = database_connection();
            $link->autocommit(FALSE); //turn on transactions

            $db_error = false;
            $table_name = $wpdb->prefix . 'htx_settings';
            $stmt = $link->prepare("SELECT * FROM $table_name WHERE settingName = 'databaseVersion' and active = 2 and type = 'databaseVersion' limit 1");
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) $db_error = true; else {
                while($row = $result->fetch_assoc()) {
                    $serverDatabaseversion = floatval($row['value']);
                }
            }
            $stmt->close();

            $link->autocommit(TRUE); //turn off transactions + commit queued queries
            $link->close();
        } catch(Exception $e) {
            $db_error = true;
            $link->rollback(); //remove all queries from queue if error (undo)
            echo "<p style='color: red'><br>There was an error with the database, please reset database!<br>Error message from server:".$e."</p>";
        }

        if ($serverDatabaseversion != $databaseVersion and $db_error == false) {
            // Update database
            try {
                echo "<p style='color: orange'>Database update in progress...</p>";
                global $wpdb;
                $link = database_connection();
                $link->autocommit(FALSE); //turn on transactions

                // Insert upgrade forms here
                if ($serverDatabaseversion < 0.1) {
                    // Some action here to update databse to 0.1

                    // Update version number
                    $table_name = $wpdb->prefix . 'htx_settings';
                    $stmt = $link->prepare("UPDATE $table_name SET value = '0.1' WHERE settingName = 'databaseVersion' and active = 2 and type = 'databaseVersion'");
                    if(!$stmt)
                        throw new Exception($link->error);
                    $stmt->execute();
                    $stmt->close();

                    $serverDatabaseversion = 0.1;
                }
                // Insert upgrade forms here
                if ($serverDatabaseversion < 0.2) {
                    // Some action here to update databse to 0.1
                    $table_name = $wpdb->prefix . 'htx_form_tables';
                    $command = "ALTER TABLE $table_name ADD `registration` INT NOT NULL DEFAULT '1' AFTER `arrived`";
                    $stmt = $link->prepare("$command");
                    if(!$stmt)
                        throw new Exception($link->error);
                    $stmt->execute();
                    $stmt->close();

                    // Update version number
                    $table_name = $wpdb->prefix . 'htx_settings';
                    $stmt = $link->prepare("UPDATE $table_name SET value = '0.2' WHERE settingName = 'databaseVersion' and active = 2 and type = 'databaseVersion'");
                    if(!$stmt)
                        throw new Exception($link->error);
                    $stmt->execute();
                    $stmt->close();

                    $serverDatabaseversion = 0.2;
                }

                // Update version number
                $table_name = $wpdb->prefix . 'htx_settings';
                $stmt = $link->prepare("UPDATE $table_name SET value = ? WHERE settingName = 'databaseVersion' and active = 2 and type = 'databaseVersion'");
                if(!$stmt)
                    throw new Exception($link->error);
                $stmt->bind_param("s", $databaseVersion);
                $stmt->execute();
                $stmt->close();

                $link->autocommit(TRUE); //turn off transactions + commit queued queries
                $link->close();
                echo "<p style='color: green'>Database is updated to $databaseVersion</p>";
            } catch(Exception $e) {
                $link->rollback(); //remove all queries from queue if error (undo)
                echo "<br> Update failed. <br>Error:<br>".$e;
            }
        } else if ($db_error == true) {
            echo "<p style='color: red'><br>There was an error with the database, please reset database!</p>";
            
        } else {
            echo "<p style='color: green'>Database is up to date</p>";
        }
    }
?>