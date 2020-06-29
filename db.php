<?php 
// Databases for plugin
    
    function create_db(){
        // Creating a start database for plugin, where registrations go
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        //* Create the teams table
        $table_name = $wpdb->prefix . 'htx_tilmelding';
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


        // Creating a start database for colums information
        //* Create the teams table
        $table_name = $wpdb->prefix . 'htx_column';
        $sql = "CREATE TABLE $table_name (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        active INT NOT NULL DEFAULT 1,
        columnNameFront TEXT,
        columnNameBack TEXT,
        format TEXT,
        special TEXT,
        specialName TEXT,
        sorting TEXT,
        dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
        dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta( $sql );


        // Creating a start database for plugin, where settings go
        //* Create the teams table
        $table_name = $wpdb->prefix . 'htx_settings';
        $sql = "CREATE TABLE $table_name (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        active INT NOT NULL DEFAULT 1,
        settingName TEXT,
        settingOne TEXT,
        settingTwo TEXT,
        settingThree TEXT,
        settingFour TEXT,
        settingFive TEXT,
        dateCreate DATETIME DEFAULT CURRENT_TIMESTAMP,
        dateUpdate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta( $sql );
    }

?>