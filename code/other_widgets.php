<?php
    // Scripts and style
    HTX_load_standard_backend();
    wp_enqueue_script( 'form_creator_script', "/wp-content/plugins/wp-htxlan/code/JS/otherWidgets.js");

    // Getting start information for database connection
    global $wpdb;
    // Connecting to database, with custom variable
    $link = database_connection();

    // Header
    echo "<h1>HTX Lan widgets</h1>";

    echo "<h3>Deltager antal widgets</h3>";

    echo "<p>Shortcode: <span id='widget_shortcode_participantCount'><i>Indlæser...</i></span></p>";

    echo "<p>Eksempel: <span id='widget_example_participantCount'><i>Indlæser...</i></span></p>";

    // Choose table
    // Getting data about forms
    $table_name = $wpdb->prefix . 'htx_form_tables';
    $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 ORDER BY favorit DESC, tableName ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        echo "Ingen formularer - Opret nogen 🙂";
        $stmt->close();
        $Error = true;
        die;
    } else {
        while($row = $result->fetch_assoc()) {
            $tableIds[] = $row['id'];
            $tableNames[] = $row['tableName'];
        }
        $stmt->close();

        // Getting table id
        if (isset($_GET['formular'])) {
            if (in_array(intval($_GET['formular']), $tableIds)) $tableId = intval($_GET['formular']); else $tableId = $tableIds[0];

            // Check cookie
            if(!isset($_COOKIE['tableId'])) {
                // Set cookie because it does not exist
                setCustomCookie('tableId',$tableId);
            } else {
                // Cookie exist
                if (intval($_COOKIE['tableId']) != $tableId) 
                    setCustomCookie('tableId',$tableId); // Cookie does not match formular - Updatet cookie
            }
        } else {
            // Check cookie
            if(!isset($_COOKIE['tableId'])) {
                // Set cookie because it does not exist
                $tableId = $tableIds[0]; //Use first table
                setCustomCookie('tableId',$tableId);
            } else {
                // Cookie exist
                if (in_array(intval($_COOKIE['tableId']), $tableIds)) 
                    // Cookie is a valid table - Set as new table
                    $tableId = intval($_COOKIE['tableId']); 
                else {
                    // Cookie is not a valid cookie, set standard
                    $tableId = $tableIds[0]; //Use first table
                    setCustomCookie('tableId',$tableId);
                }
            }
        }

        // Post handling
        participantList_post($tableId);

        // Dropdown menu
        // Starting dropdown menu
        echo "<p><label for='form_choose'>Formular: </label>";
        echo "<select id='form_choose_participantCount' name='formular' class='dropdown' oninput='participantCount()'>";
        // writing every option
        for ($i=0; $i < count($tableIds); $i++) {
            // Seeing if value is the choosen one
            if ($tableIds[$i] == $tableId) $isSelected = "selected"; else $isSelected = "";

            // Writing value
            echo "<option value='$tableIds[$i]' $isSelected>$tableNames[$i]</option>";
        }

        // Ending dropdown
        echo "</select></p>";
    }

    echo "<p>Live opdatering: <input type='checkbox' value='1' name='liveUpdate_participantCount' id='liveUpdate_participantCount' oninput='participantCount()'></p>";
    
    echo "<p>Tæl ned fra maks deltager antal: <input type='checkbox' value='1' name='countdown_participantCount' id='coundown_participantCount' oninput='participantCount()'></p>";

    echo "<p>Maks deltager antal: <input type='number' name='max_participantCount' id='max_participantCount' placeholder='50' min='0' step='1' oninput='participantCount()'></p>";

    echo "<p>Eksempel deltager antal: <input type='number' name='example_participantCount' id='example_participantCount' placeholder='50' min='0' step='1' oninput='participantCount()'></p>";

    echo "<script>setTimeout(() => {participantCount();}, 100);</script>";

?>