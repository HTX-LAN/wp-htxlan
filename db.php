<?php 
// Databases for plugin
    
    function create_db(){
        // Getting start information to create databases
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        // Creating a start database for plugin, where registrations go
        $table_name = $wpdb->prefix . 'htx_form_1';
        $sql = "CREATE TABLE $table_name (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        active INT NOT NULL DEFAULT 1,
        firstName TEXT,
        lastName TEXT,
        email TEXT,
        phone TEXT,
        ticketType TEXT,
        class TEXT,
        school TEXT,
        discordTag TEXT,
        gametagOne TEXT,
        payed INT DEFAULT 0,
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
        dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
        dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta( $sql );
        // Insert standard values



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
        special TEXT,
        specialName TEXT,
        sorting TEXT,
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
        settingName TEXT,
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
        dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
        dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta( $sql );
    }

    // function for dropping all tables for plugin
    function drop_db(){
        // Getting start information
        global $wpdb;
        // require_once( ABSPATH . 'wp-config.php' );
        try {
            $link = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        } catch(Exception $e) { 
            error_log($e->getMessage());
            return('Error connecting to database. Error message:'.$e); //Should be a message a typical user could understand
        }

        // Dropping first form table (Denne skal laves om, så den dropper alle form tables, disse tables står i htx_form_tables)
        $table_name = $wpdb->prefix . 'htx_form_1';
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

?>