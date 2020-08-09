<?php

//Prevent direct file access
if(!defined('ABSPATH')) {
    header("Location: ../../../");
    die();
}

// Databases for plugin

    function create_db(){
        // Getting start information to create databases
        global $wpdb;
        $db_name = DB_NAME;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        // Connecting to database, with custom variable
        $link = database_connection();

        // Creating a start database for plugin, where registrations go
        $table_name = $wpdb->prefix . 'htx_form';
        //Verify that table does not exist
        $res = $link->query("SELECT * FROM information_schema.tables WHERE TABLE_NAME = \"$table_name\" AND TABLE_SCHEMA = \"$db_name\"");
        if(!$res) {
            //Failed to query database
            throw new Exception($link->error);
        }
        if($res->num_rows == 0) {
            //Table does not exist - create it
            $sql = "CREATE TABLE $table_name (
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            active INT NOT NULL DEFAULT 1,
            dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
            dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            userId INT,
            tableId INT,
            name TEXT,
            value TEXT
            ) $charset_collate;";
            dbDelta( $sql );
        }

        // Creating table where forms tables users goes
        $table_name = $wpdb->prefix . 'htx_form_users';
        //Verify that table does not exist
        $res = $link->query("SELECT * FROM information_schema.tables WHERE TABLE_NAME = \"$table_name\" AND TABLE_SCHEMA = \"$db_name\"");
        if(!$res) {
            //Failed to query database
            throw new Exception($link->error);
        }
        if($res->num_rows == 0) {
            //Table does not exist - create it
            $sql = "CREATE TABLE $table_name (
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            active INT NOT NULL DEFAULT 1,
            tableId INT,
            payed varchar(255) default 0,
            arrived INT default 0,
            crew INT default 0,
            email TEXT,
            lastEditedBy TEXT,
            dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
            dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) $charset_collate;";
            dbDelta( $sql );
        }

        // Creating table where forms tables names goes, and if they are active or not
        $table_name = $wpdb->prefix . 'htx_form_tables';
        //Verify that table does not exist
        $res = $link->query("SELECT * FROM information_schema.tables WHERE TABLE_NAME = \"$table_name\" AND TABLE_SCHEMA = \"$db_name\"");
        if(!$res) {
            //Failed to query database
            throw new Exception($link->error);
        }
        if($res->num_rows == 0) {
            //Table does not exist - create it
            $sql = "CREATE TABLE $table_name (
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            active INT NOT NULL DEFAULT 1,
            favorit INT DEFAULT 0,
            shortcode TEXT,
            tableName TEXT,
            tableDescription TEXT,
            dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
            dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) $charset_collate;";
            dbDelta( $sql );
            // Insert standard values
            $one = 'Standard formular'; $two = "HTX_Tilmeldningsblanket"; $three = 'HTX Tilmelding 1'; $four = 1;
            $stmt = $link->prepare("INSERT INTO $table_name (tableDescription, shortcode, tableName, favorit) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $one, $two, $three, $four);
            $stmt->execute();
            $stmt->close();
        }

        // Creating a table for colums information
        $table_name = $wpdb->prefix . 'htx_column';
        //Verify that table does not exist
        $res = $link->query("SELECT * FROM information_schema.tables WHERE TABLE_NAME = \"$table_name\" AND TABLE_SCHEMA = \"$db_name\"");
        if(!$res) {
            //Failed to query database
            throw new Exception($link->error);
        }
        if($res->num_rows == 0) {
            //Table does not exist - create it
            $sql = "CREATE TABLE $table_name (
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            tableId INT DEFAULT 1,
            active INT NOT NULL DEFAULT 1,
            columnNameFront TEXT,
            columnNameBack TEXT,
            settingCat INT,
            format TEXT,
            columnType TEXT,
            special INT,
            specialName TEXT,
            placeholderText TEXT,
            teams TEXT,
            formatExtra TEXT,
            specialNameExtra TEXT,
            specialNameExtra2 TEXT,
            specialNameExtra3 TEXT,
            specialNameExtra4 TEXT,
            sorting INT,
            disabled INT DEFAULT 0,
            required INT,
            dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
            dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) $charset_collate;";
            dbDelta( $sql );

            //Insert default values
            try {
                $link->autocommit(FALSE); //turn on transactions
                $stmt = $link->prepare("INSERT INTO $table_name (tableId, columnNameFront, columnNameBack, format, columnType, special, specialName, sorting, placeholderText, required, settingCat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssisii", $tableId, $columnNameFront, $columnNameBack, $format, $columnType, $special, $specialName, $sorting, $placeholderText, $required, $settingCat);
                $tableId = 1;
                $columnNameFront = "Fornavn"; $columnNameBack='firstName'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 1; $placeholderText = "John"; $required = 1; $settingCat = 0;
                $stmt->execute();
                $columnNameFront = "Efternavn"; $columnNameBack='lastName'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 2; $placeholderText = "Smith"; $required = 1; $settingCat = 0;
                $stmt->execute();
                $columnNameFront = "E-mail"; $columnNameBack='email'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 3; $placeholderText = "john@htx-lan.dk"; $required = 1; $settingCat = 0;
                $stmt->execute();
                $columnNameFront = "Mobil nummer"; $columnNameBack='phone'; $format="number"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 4; $placeholderText = "12345678"; $required = 0; $settingCat = 0;
                $stmt->execute();
                $columnNameFront = "Billet type"; $columnNameBack='ticketType'; $format="text"; $columnType="dropdown"; $special=1; $specialName="price_intrance"; $sorting = 5; $placeholderText = ""; $required = 1; $settingCat = 1;
                $stmt->execute();
                $columnNameFront = "Skole"; $columnNameBack='school'; $format="text"; $columnType="dropdown"; $special=0; $specialName=""; $sorting = 6; $placeholderText = ""; $required = 1; $required = 1; $settingCat = 2;
                $stmt->execute();
                $columnNameFront = "Klasse"; $columnNameBack='class'; $format="text"; $columnType="dropdown"; $special=0; $specialName=""; $sorting = 7; $placeholderText = ""; $required = 1; $required = 1; $settingCat = 3;
                $stmt->execute();
                $columnNameFront = "Discord navn"; $columnNameBack='discordTag'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 8; $placeholderText = "John#1234"; $required = 1;
                $stmt->execute();
                $columnNameFront = "Gametag one"; $columnNameBack='gametagOne'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 9; $placeholderText = "John"; $required = 0;
                $stmt->execute();
                $stmt->close();
                $link->autocommit(TRUE); //turn off transactions + commit queued queries
            } catch(Exception $e) {
                $link->rollback(); //remove all queries from queue if error (undo)
                throw $e;
            }
        }

        // Creating a start database for plugin, where settings categories goes
        $table_name = $wpdb->prefix . 'htx_settings_cat';
        //Verify that table does not exist
        $res = $link->query("SELECT * FROM information_schema.tables WHERE TABLE_NAME = \"$table_name\" AND TABLE_SCHEMA = \"$db_name\"");
        if(!$res) {
            //Failed to query database
            throw new Exception($link->error);
        }
        if($res->num_rows == 0) {
            //Table does not exist - create it
            $sql = "CREATE TABLE $table_name (
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            active INT NOT NULL DEFAULT 1,
            tableId INT,
            settingName TEXT,
            settingNameBack TEXT,
            special TEXT,
            specialName TEXT,
            settingType text,
            dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
            dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) $charset_collate;";
            dbDelta( $sql );
            //Insert default values
            try {
                $link->autocommit(FALSE); //turn on transactions
                $stmt = $link->prepare("INSERT INTO $table_name (tableId, settingName, settingNameBack, special, specialName, settingType) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $tableId, $settingName, $settingNameBack, $special, $specialName, $settingType);
                $tableId = 1;
                $settingName = "Billeter"; $settingNameBack='ticketType'; $special=1; $specialName="price_intrance"; $settingType="dropdown";
                $stmt->execute();
                $settingName = "Skole"; $settingNameBack='school'; $special=0; $specialName=""; $settingType="dropdown";
                $stmt->execute();
                $settingName = "klasse"; $settingNameBack='class'; $special=0; $specialName=""; $settingType="dropdown";
                $stmt->execute();
                $stmt->close();
                $link->autocommit(TRUE); //turn off transactions + commit queued queries
            } catch(Exception $e) {
                $link->rollback(); //remove all queries from queue if error (undo)
                throw $e;
            }
        }

        // Creating a start database for plugin, where settings goes
        $table_name = $wpdb->prefix . 'htx_settings';
        //Verify that table does not exist
        $res = $link->query("SELECT * FROM information_schema.tables WHERE TABLE_NAME = \"$table_name\" AND TABLE_SCHEMA = \"$db_name\"");
        if(!$res) {
            //Failed to query database
            throw new Exception($link->error);
        }
        if($res->num_rows == 0) {
            //Table does not exist - create it
            $sql = "CREATE TABLE $table_name (
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            settingId INT,
            tableId INT,
            active INT NOT NULL DEFAULT 1,
            settingName TEXT,
            value TEXT,
            expence float DEFAULT 0,
            special TEXT,
            specialName TEXT,
            type text,
            sorting INT,
            dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
            dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) $charset_collate;";
            dbDelta( $sql );

            //Insert default values
            try {
                $link->autocommit(FALSE); //turn on transactions
                $stmt = $link->prepare("INSERT INTO $table_name (settingId, tableId, settingName, value, special, specialName, type, sorting) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssssi", $settingId, $tableId, $settingName, $value, $special, $specialName, $settingType, $sorting);
                $settingId = 1;
                $tableId = 1;
                $settingName = "Billet type 1"; $value=10; $special=1; $specialName="price_intrance"; $settingType="dropdown"; $sorting = 1;
                $stmt->execute();
                $settingName = "Billet type 2"; $value=20; $special=1; $specialName="price_intrance"; $settingType="dropdown"; $sorting = 2;
                $stmt->execute();
                $settingId = 2;
                $settingName = "HTX"; $value="HTX"; $special=0; $specialName=""; $settingType="dropdown"; $sorting = 1;
                $stmt->execute();
                $settingName = "HHX"; $value="HHX"; $special=0; $specialName=""; $settingType="dropdown"; $sorting = 2;
                $stmt->execute();
                $settingName = "EUX/EUC"; $value="EUX/EUC"; $special=0; $specialName=""; $settingType="dropdown"; $sorting = 3;
                $stmt->execute();
                $settingId = 3;
                $settingName = "Klasse 1"; $value="Klasse 1"; $special=0; $specialName=""; $settingType="dropdown"; $sorting = 1;
                $stmt->execute();
                $settingName = "Klasse 2"; $value="Klasse 2"; $special=0; $specialName=""; $settingType="dropdown"; $sorting = 2;
                $stmt->execute();
                $stmt->close();
                $link->autocommit(TRUE); //turn off transactions + commit queued queries
            } catch(Exception $e) {
                $link->rollback(); //remove all queries from queue if error (undo)
                throw $e;
            }
        }

        //Close DB connection
        $link->close();
    }

    // function for dropping all tables for plugin
    function drop_db(){
        // Getting start information
        global $wpdb;
        // Connecting to database, with custom variable
        $link = database_connection();

        // Dropping form
        $table_name = $wpdb->prefix . 'htx_form';
        $sql = "DROP TABLE $table_name;";
        mysqli_query($link, $sql);

        // Dropping form users
        $table_name = $wpdb->prefix . 'htx_form_users';
        $sql = "DROP TABLE $table_name;";
        mysqli_query($link, $sql);


        // Dropping tables list table
        $table_name = $wpdb->prefix . 'htx_form_tables';
        $sql = "DROP TABLE $table_name;";
        mysqli_query($link, $sql);

        // Dropping column table
        $table_name = $wpdb->prefix . 'htx_column';
        $sql = "DROP TABLE $table_name;";
        mysqli_query($link, $sql);

        // Dropping settings category table
        $table_name = $wpdb->prefix . 'htx_settings_cat';
        $sql = "DROP TABLE $table_name;";
        mysqli_query($link, $sql);
        // Dropping settings table
        $table_name = $wpdb->prefix . 'htx_settings';
        $sql = "DROP TABLE $table_name;";
        mysqli_query($link, $sql);
    }

    function to_csv($table_suffix) {
        global $wpdb;
        $link = database_connection();

        $table_name = $wpdb->prefix . $table_suffix;

        $result = $link->query("SELECT * FROM " . $link->real_escape_string($table_name));
        if(!$result) {
            //Failed to query database
            throw new Exception($link->error);
        }


        $csv = "";
        $fields = $result->field_count;

        for($i = 0; $i < $fields; $i++) {
            $csv .= "\"" . $result->fetch_field_direct($i)->name . "\",";
        }
        $csv .= "\n";

        while($row = $result->fetch_assoc()) {
            for($i = 0; $i < $fields; $i++) {
                $csv .= "\"" . $row[$result->fetch_field_direct($i)->name] . "\"";
                if($i + 1 < $fields)
                    $csv .= ",";
            }
            $csv .= "\n";
        }

        $link->close();
        return substr($csv, 0, -2);
    }
?>
