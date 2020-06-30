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

    //multi explode function
    function multiexplode ($delimiters,$string) {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);
        return  $launch;
    }

    function statistiks(){
        
    }
?>