<?php 
// Databases for plugin
    
    function create_db(){
        // Getting start information to create databases
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        // Connecting to database, with custom variable
        $link = database_connection();
        
        // Creating a start database for plugin, where registrations go
        $table_name = $wpdb->prefix . 'htx_form';
        $sql = "CREATE TABLE $table_name (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        active INT NOT NULL DEFAULT 1,
        dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
        dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        payed INT DEFAULT 0,
        firstName TEXT,
        lastName TEXT,
        email TEXT,
        phone TEXT,
        ticketType TEXT,
        class TEXT,
        school TEXT,
        discordTag TEXT,
        gametagOne TEXT
        ) $charset_collate;";
        dbDelta( $sql );

        // Creating table where forms tables users goes
        $table_name = $wpdb->prefix . 'htx_form_users';
        $sql = "CREATE TABLE $table_name (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        tableId INT,
        email TEXT,
        dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
        dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta( $sql );

        // Creating table where forms tables names goes, and if they are active or not
        $table_name = $wpdb->prefix . 'htx_form_tables';
        $sql = "CREATE TABLE $table_name (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        active INT NOT NULL DEFAULT 1,
        tableId INT,
        shortcode TEXT,
        tableName TEXT,
        dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
        dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta( $sql );
        // Insert standard values
        $one = 1; $two = "HTX_Tilmeldningsblanket"; $three = 'HTX Tilmelding 1';
        $stmt = $link->prepare("INSERT INTO $table_name (tableId, shortcode, tableName) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $one, $two, $three);
        $stmt->execute();
        $stmt->close();


        // Creating a table for colums information
        $table_name = $wpdb->prefix . 'htx_column';
        $sql = "CREATE TABLE $table_name (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        tableId INT DEFAULT 1,
        active INT NOT NULL DEFAULT 1,
        columnNameFront TEXT,
        columnNameBack TEXT,
        format TEXT,
        columnType TEXT,
        special INT,
        specialName TEXT,
        placeholderText TEXT,
        sorting INT,
        adminOnly INT,
        required INT,
        dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
        dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta( $sql );
        // Insert standard values


        // Creating a start database for plugin, where settings categories goes
        $table_name = $wpdb->prefix . 'htx_settings_cat';
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

        // Creating a start database for plugin, where settings goes
        $table_name = $wpdb->prefix . 'htx_settings';
        $sql = "CREATE TABLE $table_name (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        settingId INT,
        active INT NOT NULL DEFAULT 1,
        settingName TEXT,
        value TEXT,
        special TEXT,
        specialName TEXT,
        type text,
        sorting INT,
        dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
        dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta( $sql );
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
    function insert_data() {
        // Connecting to database, with custom variable
        $link = database_connection();
        // Inserting standard column in standard form
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'htx_column';
            $link->autocommit(FALSE); //turn on transactions
            $stmt = $link->prepare("INSERT INTO $table_name (tableId, columnNameFront, columnNameBack, format, columnType, special, specialName, sorting, placeholderText, adminOnly, required) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssissi", $tableId, $columnNameFront, $columnNameBack, $format, $columnType, $special, $specialName, $sorting, $placeholderText, $adminOnly, $required);
            $tableId = 1; 
            $columnNameFront = "Fornavn"; $columnNameBack='firstName'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 1; $placeholderText = "John"; $adminOnly = 0; $required = 1;
            $stmt->execute();  
            $columnNameFront = "Efternavn"; $columnNameBack='lastName'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 2; $placeholderText = "Smith"; $adminOnly = 0; $required = 1;
            $stmt->execute();
            $columnNameFront = "E-mail"; $columnNameBack='email'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 3; $placeholderText = "john@htx-lan.dk"; $adminOnly = 0; $required = 1;
            $stmt->execute();
            $columnNameFront = "Mobil nummer"; $columnNameBack='phone'; $format="number"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 4; $placeholderText = "12345678"; $adminOnly = 0; $required = 0;
            $stmt->execute();
            $columnNameFront = "Billet type"; $columnNameBack='ticketType'; $format="text"; $columnType="dropdown"; $special=1; $specialName="price"; $sorting = 5; $placeholderText = ""; $adminOnly = 0; $required = 1;
            $stmt->execute();
            $columnNameFront = "Skole"; $columnNameBack='school'; $format="text"; $columnType="dropdown"; $special=0; $specialName=""; $sorting = 6; $placeholderText = ""; $adminOnly = 0; $required = 1; $required = 1;
            $stmt->execute();
            $columnNameFront = "Klasse"; $columnNameBack='class'; $format="text"; $columnType="dropdown"; $special=0; $specialName=""; $sorting = 7; $placeholderText = ""; $adminOnly = 0; $required = 1; $required = 1;
            $stmt->execute();
            $columnNameFront = "Discord navn"; $columnNameBack='discordTag'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 8; $placeholderText = "John#1234"; $adminOnly = 0; $required = 1;
            $stmt->execute();
            $columnNameFront = "Gametag one"; $columnNameBack='gametagOne'; $format="text"; $columnType="inputbox"; $special=0; $specialName=""; $sorting = 9; $placeholderText = "John"; $adminOnly = 0; $required = 0;
            $stmt->execute();
            $columnNameFront = "Betalt"; $columnNameBack='payed'; $format="number"; $columnType="inputbox"; $special=1; $specialName="payed"; $sorting = 10; $placeholderText = "0"; $adminOnly = 1; $required = 0;
            $stmt->execute();
            $stmt->close();
            $link->autocommit(TRUE); //turn off transactions + commit queued queries
        } catch(Exception $e) {
            $link->rollback(); //remove all queries from queue if error (undo)
            throw $e;
        }
        // Inserting settings category for standard form
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'htx_settings_cat';
            $link->autocommit(FALSE); //turn on transactions
            $stmt = $link->prepare("INSERT INTO $table_name (tableId, settingName, settingNameBack, special, specialName, settingType) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $tableId, $settingName, $settingNameBack, $special, $specialName, $settingType);
            $tableId = 1; 
            $settingName = "Billeter"; $settingNameBack='ticketType'; $special=1; $specialName="price"; $settingType="dropdown";
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
        // Inserting settings for standard form
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'htx_settings';
            $link->autocommit(FALSE); //turn on transactions
            $stmt = $link->prepare("INSERT INTO $table_name (settingId, settingName, value, special, specialName, type, sorting) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssi", $settingId, $settingName, $value, $special, $specialName, $settingType, $sorting);
            $settingId = 1; 
            $settingName = "Billet type 1"; $value=10; $special=1; $specialName="price"; $settingType="dropdown"; $sorting = 1;
            $stmt->execute(); 
            $settingName = "Billet type 2"; $value=20; $special=1; $specialName="price"; $settingType="dropdown"; $sorting = 2;
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
?>