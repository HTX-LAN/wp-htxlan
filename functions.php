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

    function statistiks(){
        
    }
?>