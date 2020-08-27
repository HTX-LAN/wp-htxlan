<?php
    //Prevent direct file access
    if(!defined('ABSPATH')) {
        header("Location: ../../../");
        die();
    }

    // Widgets and style
    HTX_load_standard_backend();

    // Getting start information for database connection
    global $wpdb;
    // Connecting to database, with custom variable
    $link = database_connection();

    // Non user editable inputs saved
    $nonUserInput = array('text area', 'price', 'spacing');

    // Header
    echo "<h1>HTX Lan turneringer og hold</h1>";

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
        echo "<p><h3>Formular:</h3> ";
        echo "<form method=\"get\"><input type='hidden' name='page' value='HTX_lan_teams'><select name='formular' class='dropdown' onchange='form.submit()'>";
        // writing every option
        for ($i=0; $i < count($tableIds); $i++) {
            // Seeing if value is the choosen one
            if ($tableIds[$i] == $tableId) $isSelected = "selected"; else $isSelected = "";

            // Writing value
            echo "<option value='$tableIds[$i]' $isSelected>$tableNames[$i]</option>";
        }

        // Ending dropdown
        echo "</select></form><br></p>";
    }

    // Possible to edit what to show on this page
    echo "<button class='btn normalBtn' style='margin-bottom: 1rem;' onclick='showTeamColumnSettings()'>Ændre viste felter</button><br>";

    // Get already choosen elements - If no elements are present use default
    $userId = get_current_user_id();
    // $headsShown = array('firstName', 'lastName', 'email', 'discordTag'); #This controls the shown elements on screen - Default elements

    // Gets all columns
    $table_name = $wpdb->prefix . 'htx_column';
    $stmt = $link->prepare("SELECT * FROM `$table_name` where tableId = ?");
    $stmt->bind_param("i", $tableId);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        $stmt->close();
        $columns = array();
    } else {
        while($row = $result->fetch_assoc()) {
            $columns[] = $row['columnNameBack'];
        }
    }

    // Getting user preferrence
    $table_name = $wpdb->prefix . 'htx_settings';
    $stmt = $link->prepare("SELECT * FROM `$table_name` where settingName = ? AND NOT value = '' AND active = 1 AND type = 'teamsUserPreference' AND tableId = ? LIMIT 1");
    $stmt->bind_param("ii", $userId, $tableId);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        $stmt->close();
        $headsShown = array();
        $userSetting = false;
    } else {
        while($row = $result->fetch_assoc()) {
            $headsShown = explode(",", $row['value']);
            if (count(array_intersect($columns, $headsShown)) != count($headsShown)) $headsShown = array();
        }
        $userSetting = true;
    }

    // Post handling
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        switch  ($_POST['post']) {
            case 'updateUserPreference':
                if(!current_user_can("manage_options")){
                    echo "User can not do that!";
                    break;
                }
                $tempArray = array();
                $tempSrting = "";
                for ($i=0; $i < count($_POST['shownColumns']); $i++) { 
                    if (in_array($_POST['shownColumns'][$i], $columns)) {
                        $tempArray[] = $_POST['shownColumns'][$i];
                    }
                }
                if (count(array_intersect($columns, $tempArray)) == count($tempArray)) {
                    $headsShown = $tempArray;
                    $headsShownString = implode(",",$headsShown);

                    // Update database
                    if ($userSetting == false and $headsShownString != "") {
                        // Make new record in database
                        $table_name = $wpdb->prefix . 'htx_settings';
                        $stmt = $link->prepare("INSERT INTO `$table_name` (settingName, value, type, tableId) VALUES (?, ?, 'teamsUserPreference', ?)");
                        $stmt->bind_param("isi", $userId, $headsShownString, $tableId);
                        $stmt->execute();
                        $stmt->close();
                    } else if ($headsShownString != "") {
                        // Update record
                        $table_name = $wpdb->prefix . 'htx_settings';
                        $stmt = $link->prepare("UPDATE `$table_name` SET value = ? WHERE settingName = ? and tableId = ?");
                        $stmt->bind_param("sii", $headsShownString, $userId, $tableId);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        echo "<span style='color: red'>Der gik noget galt, prøv igen</span>";
                    }
                }

            break;
        }
    }
    if (!isset($headsShown) OR count($headsShown) <= 0) $headsShown = array('firstName', 'lastName', 'email');

    // Page for showing possible columns to show
    wp_enqueue_style( 'teams_style', "/wp-content/plugins/WPPlugin-HTXLan/CSS/teams.css");
    wp_enqueue_script( 'teams_script', "/wp-content/plugins/WPPlugin-HTXLan/JS/teams.js");
    echo "<div id='columnShownEditPage' class='columnShownEditPage_closed'>";
    echo "<h2>Viste kolonner</h2>";
    echo "<form method='POST'>";
    // Columns:
    $table_name = $wpdb->prefix . 'htx_column';
    $stmt = $link->prepare("SELECT * FROM `$table_name` where (active = 1 and tableId = ?) AND NOT(columnType = 'text area' OR columnType = 'price' OR columnType = 'spacing')");
    $stmt->bind_param("i", $tableId);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        echo "Der er ingen elementer i form";
        $stmt->close();
        $Error = true;
        die; #Ending page, becuase of error
    } else {
        while($row = $result->fetch_assoc()) {
            if (in_array($row['columnType'], $nonUserInput)) continue;
            if (in_array($row['columnNameBack'], $headsShown)) $selected = 'checked="checked"'; else $selected = '';
            echo "<input type='checkbox' id='checkBox-".$row['id']."' name='shownColumns[]' value='".$row['columnNameBack']."' $selected><label for='checkBox-".$row['id']."'>".$row['columnNameFront']."</label><br>";
        }
    }
    echo "<button type='submit' class='btn updateBtn' name='post' value='updateUserPreference'>Opdater</button>";
    echo "</form>";
    echo "</div>";
    

    // Get torunaments
    $table_name = $wpdb->prefix . 'htx_column';
    $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 and tableId = ? and specialName != '' ");
    $stmt->bind_param("i", $tableId);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        echo "Der er ingen turneringer";
        $stmt->close();
        $Error = true;
        die; #Ending page, becuase of error
    } else {
        while($row = $result->fetch_assoc()) {
            // Tournament
            $tempArray = explode(",", $row['specialName']);
            if (in_array('tournament', $tempArray)) {
                $tournamentColumnIds[] = $row['id'];
                $tournamentColumnName[] = $row['columnNameFront'];
                $tournamentColumnbackend[] = $row['columnNameBack'];
                $tournamentColumnSettingCat[] = $row['settingCat'];
            }
            // Teams
            if (in_array('teams', $tempArray)) {
                $teamsColumnIds[] = $row['id'];
                $teamsColumnName[$row['id']] = $row['columnNameFront'];
                $teamsColumnbackend[$row['id']] = $row['columnNameBack'];
                $teamsColumnSettingCat[$row['id']] = $row['settingCat'];
                $teamsTournament[$row['id']] = $row['teams'];
                $teamsTournamentWname[$row['columnNameBack']] = $row['teams'];
                $teamsColumnType[] = $row['columnType'];
                $teamsColumnTypeWname[$row['columnNameBack']] = $row['columnType'];
            }
        }
        $stmt->close();
    }

    for ($i=0; $i < count($tournamentColumnbackend); $i++) { 
        $table_name = $wpdb->prefix . 'htx_settings_cat';
        $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 and tableId = ? and settingNameBack = ?");
        $stmt->bind_param("ii", $tableId, $tournamentColumnbackend[$i]);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) {
            echo "Der er ingen kategorier for turneringerne $tableId $tournamentColumnbackend[$i]";
            $stmt->close();
            $Error = true;
            die; #Ending page, becuase of error
        } else {
            while($row = $result->fetch_assoc()) {
                $tournamentCatId[$i] = $row['id'];
            }
            $stmt->close();
        }
        $table_name = $wpdb->prefix . 'htx_settings';
        $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 and settingId = ?");
        $stmt->bind_param("i", $tournamentCatId[$i]);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) {
            echo "Der er ingen muligheder for turneringerne";
            $stmt->close();
            $Error = true;
            die; #Ending page, becuase of error
        } else {
            while($row = $result->fetch_assoc()) {
                $tournamentNames[$i][] = $row['settingName'];
                $tournamentIds[$i][] = $row['id'];
                $tournamentNamesWId[$i][$row['id']] = $row['settingName'];
            }
            $stmt->close();
        }
    }

    for ($i=0; $i < count($tournamentColumnIds); $i++) { 
        for ($index=0; $index < count($tournamentIds[$i]); $index++) { 
            // Get teams for turnament
            $table_name = $wpdb->prefix . 'htx_column';
            $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 and tableId = ? and specialName != '' and teams = ?");
            $stmt->bind_param("ii", $tableId, $tournamentIds[$i][$index]);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                $teamColumnId[$i][$index] = false;
                $stmt->close();
            } else {
                while($row = $result->fetch_assoc()) {
                    $teamColumnId[$i][$index][] = $row['columnNameBack'];
                }
                $stmt->close();
            }
        }
    }


    // Get teams
    for ($i=0; $i < count($teamsColumnIds); $i++) { 
        if ($teamsColumnType[$i] != 'inputbox') {
            $table_name = $wpdb->prefix . 'htx_settings_cat';
            $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 and tableId = ? and settingNameBack = ?");
            $stmt->bind_param("ii", $tableId, $teamsColumnbackend[$teamsColumnIds[$i]]);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                echo "Der er ingen indstillings kategorier for '".$teamsColumnName[$teamsColumnIds[$i]]."' $teamsColumnIds[$i]";
                $stmt->close();
                $Error = true;
                die; #Ending page, becuase of error
            } else {
                while($row = $result->fetch_assoc()) {
                    $teamsCatId[$i] = $row['id'];
                }
                $stmt->close();
            }
            $table_name = $wpdb->prefix . 'htx_settings';
            $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 and settingId = ?");
            $stmt->bind_param("i", $teamsCatId[$i]);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                echo "Der er ingen mulige valg for hold for '".$teamsColumnName[$teamsColumnIds[$i]]."'";
                $stmt->close();
                $Error = true;
                die; #Ending page, becuase of error
            } else {
                while($row = $result->fetch_assoc()) {
                    $teamsNames[$i][] = $row['settingName'];
                    $teamsIds[$i][] = $row['id'];
                    $teamsNamesWId[$row['id']] = $row['settingName'];
                    $teamsNamesWcolumnId[$teamsColumnbackend[$teamsColumnIds[$i]]][] = $row['settingName'];
                    $teamsIdsWcolumnId[$teamsColumnbackend[$teamsColumnIds[$i]]][] = $row['id'];
                }
                $stmt->close();
            }
        }
    }

    // Information for user

    // Getting every dropdown and radio categories
    $table_name3 = $wpdb->prefix . 'htx_settings_cat';
    $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE tableId = ? AND active = 1");
    $stmt3->bind_param("i", $tableId);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    if($result3->num_rows === 0) {} else {
        while($row3 = $result3->fetch_assoc()) {
            $settingNameBacks[] = $row3['settingNameBack'];
            $settingType[] = $row3['settingType'];
        }
    }
    // Getting every dropdown and radio names and values
    $table_name3 = $wpdb->prefix . 'htx_settings';
    $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE active = 1");
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    if($result3->num_rows === 0) {} else {
        while($row3 = $result3->fetch_assoc()) {
            $settingName[$row3['id']] = $row3['settingName'];
            $settingId[$row3['id']] = $row3['id'];
            $settingValue[$row3['id']] = $row3['value'];
        }
    }

    for ($i=0; $i < count($headsShown); $i++) { 
        // Gettiing every column
        $table_name3 = $wpdb->prefix . 'htx_column';
        $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE tableid = ? and columnNameBack = ?");
        $stmt3->bind_param("is", $tableId, $headsShown[$i]);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        if($result3->num_rows === 0) {
        } else {
            while($row3 = $result3->fetch_assoc()) {
                $columnNameFront[] = $row3['columnNameFront'];
                $columnNameBack[] = $row3['columnNameBack'];
                $format[] = $row3['format'];
                $columnType[] = $row3['columnType'];
                $special[] = $row3['special'];
                $specialName[] = explode(",", $row3['specialName']);
                $placeholderText[] = $row3['placeholderText'];
                $sorting[] = $row3['sorting'];
                $required[] = $row3['required'];
            }
        }
        $stmt3->close();
    }

    // Amount of columns
    $TopColumnAmount = count($columnNameBack)+1;

    if (!$Error) {
        $sortTable = array();
        for ($i=0; $i < count($tournamentColumnIds); $i++) { 
            echo "<h2>$tournamentColumnName[$i]</h2>";
            for ($index=0; $index < count($tournamentNames[$i]); $index++) { 
                echo "
                <table class='InfoTable sortable' style='width: unset' id='teamsTable-".$tournamentIds[$i][$index]."'>
                    <thead>
                        <tr>
                            <th colspan='$TopColumnAmount'>".$tournamentNames[$i][$index]."</th>
                        </tr>";

                    if ($teamColumnId[$i][$index] == false) {
                        echo "<tr class='InfoTableRow sortingRow' id='sortingRow-".$tournamentIds[$i][$index]."'>";
                        // Writing every column and insert into table head
                        $columnNumber = 0;
                        for ($iHeaders=0; $iHeaders < count($columnNameBack); $iHeaders++) {
                            // Check if input should not be shown
                            if (!in_array($columnType[$iHeaders], $nonUserInput)) {
                                echo "<th onClick='sortTable(1,$columnNumber,\"teamsTable-".$tournamentIds[$i][$index]."\",true,\"".$tournamentIds[$i][$index]."\")' 
                                title='Sorter efter denne kolonne' style='cursor: pointer'>
                                    $columnNameFront[$iHeaders]
                                    <span class='material-icons arrowInline sortingCell_".$tournamentIds[$i][$index]."' id='sortingSymbol_".$tournamentIds[$i][$index]."_$columnNumber'></span>
                                </th>";
                                $columnNumber++;
                            }
                        }
                        echo "</tr>
                        </thead>
                        <tbody>";  
                        // No teams, only players

                        $table_name = $wpdb->prefix . 'htx_form';
                        $stmt = $link->prepare("SELECT DISTINCT userId FROM `$table_name` where active = 1 and tableId = ? and name = ?");
                        $stmt->bind_param("ii", $tableId, $tournamentColumnbackend[$i]);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if($result->num_rows === 0) {
                            $stmt->close();
                            echo "
                            <tr class='InfoTableRow'>
                                <td colspan='$TopColumnAmount'>Ingen tilmeldte endnu</td>
                            </tr>";
                            
                        } else {
                            $whileCount = 0;
                            $noUser = 0;
                            while($row = $result->fetch_assoc()) {                                
                                $tmpExplode = array();

                                $table_name2 = $wpdb->prefix . 'htx_form';
                                $stmt2 = $link->prepare("SELECT * FROM `$table_name2` where active = 1 and tableId = ? and userId = ? and name = ?");
                                $stmt2->bind_param("iii", $tableId, $row['userId'], $tournamentColumnbackend[$i]);
                                $stmt2->execute();
                                $result2 = $stmt2->get_result();
                                if($result2->num_rows === 0) {

                                    $stmt2->close();
                                    $tmpExplode = false;
                                    $noUser = $noUser + 1;
                                } else {
                                    while($row2 = $result2->fetch_assoc()) {                                    
                                        $tmpExplode = array_merge($tmpExplode, array_filter(explode(",", $row2['value'])));
                                    }
                                    $stmt2->close();
                                }

                                if ($tmpExplode != false){
                                    if (in_array($tournamentIds[$i][$index], $tmpExplode)) {
                                        echo "<tr class='InfoTableRow'>";
                                        // Other information
                                        for ($indexWrite=0; $indexWrite < count($columnNameBack); $indexWrite++) {
                                            // Getting data for specefied column
                                            $table_name3 = $wpdb->prefix . 'htx_form';
                                            $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE tableid = ? AND userId = ? AND name = ?");
                                            $stmt3->bind_param("iis", $tableId, $row['userId'], $columnNameBack[$indexWrite]);
                                            $stmt3->execute();
                                            $result3 = $stmt3->get_result();
                                            if($result3->num_rows === 0) {
                                                if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                                    echo "<td>";
                                                    echo "<i style='color: red'>-</i>";
                                                    echo "</td>";
                                                }
                                            } else {
                                                if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                                    echo "<td>";
                                                    while($row3 = $result3->fetch_assoc()) {
                                                        // Checks if dropdown or other where value is an id
                                                        if (in_array($row3['name'], $settingNameBacks)) {
                                                            // Writing data from id, if dropdown or radio
                                                            if ($columnType[$indexWrite] == "checkbox") {
                                                                $valueArray = explode(",", $row3['value']);
                                                                if (count($valueArray) > 0) {
                                                                    for ($jWrite=0; $jWrite < count($valueArray); $jWrite++) {
                                                                        echo htmlspecialchars($settingName[$valueArray[$jWrite]]);
                                                                        // Insert comma, if the value is not the last
                                                                        if (count($valueArray) != ($jWrite + 1)) {
                                                                            echo ", ";
                                                                        }
                                                                    }
                                                                }
                                                            } else if (in_array('otherInput',$specialName[$indexWrite]) and !in_array($row3['value'],$settingId)) {
                                                                if ($row3['value'] == "0") echo ""; else echo htmlspecialchars($row3['value']);
                                                            } else {
                                                                echo htmlspecialchars($settingName[$row3['value']]);
                                                            }

                                                        } else {
                                                            // Checks column type
                                                            if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                                                // Writing data from table
                                                                echo htmlspecialchars($row3['value']);
                                                            }
                                                        }
                                                    }
                                                    echo "</td>";
                                                }
                                            }
                                            $stmt3->close();
                                        }
                                        echo "</tr>";
                                    }
                                }
                            }
                        }
                    } else {
                        echo "<tr class='InfoTableRow sortingRow' id='sortingRow-".$tournamentIds[$i][$index]."'>
                        <th onClick='sortTable(1,0,\"teamsTable-".$tournamentIds[$i][$index]."\",true,\"".$tournamentIds[$i][$index]."\")' title='Sorter efter denne kolonne' style='cursor: pointer'>Holdnavn<span class='material-icons arrowInline sortingCell_".$tournamentIds[$i][$index]."' id='sortingSymbol_".$tournamentIds[$i][$index]."_0'></span></th>";
                        // Writing every column and insert into table head
                        $columnNumber = 1;
                        for ($iHeaders=0; $iHeaders < count($columnNameBack); $iHeaders++) {
                            // Check if input should not be shown
                            if (!in_array($columnType[$iHeaders], $nonUserInput)) {
                                echo "<th onClick='sortTable(1,$columnNumber,\"teamsTable-".$tournamentIds[$i][$index]."\",true,\"".$tournamentIds[$i][$index]."\")' title='Sorter efter denne kolonne' style='cursor: pointer'>
                                    $columnNameFront[$iHeaders]
                                    <span class='material-icons arrowInline sortingCell_".$tournamentIds[$i][$index]."' id='sortingSymbol_".$tournamentIds[$i][$index]."_$columnNumber'></span>
                                </th>";
                                $columnNumber++;
                            }
                        }
                        echo "</tr>
                        </thead>
                        <tbody>"; 
                        for ($j=0; $j < count($teamColumnId[$i][$index]); $j++) {
                            if ($teamsColumnTypeWname[$teamColumnId[$i][$index][$j]] == 'inputbox') {
                                $userIdsGame = array();
                                $table_name = $wpdb->prefix . 'htx_form';
                                $stmt = $link->prepare("SELECT DISTINCT userId, value FROM `$table_name` where active = 1 and tableId = ? and name = ? and value != ''");
                                $stmt->bind_param("ii", $tableId, $tournamentColumnbackend[$i]);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if($result->num_rows === 0) {
                                    $stmt->close();
                                } else {
                                    while($row = $result->fetch_assoc()) {   
                                        if (in_array($tournamentIds[$i][$index], explode(",", $row['value'])))
                                            $userIdsGame[] = $row['userId'];
                                    }
                                    $stmt->close();
                                }
                                

                                for ($y=0; $y < count($userIdsGame); $y++) {     
                                    echo "<tr class='InfoTableRow'>";
                                    $table_name3 = $wpdb->prefix . 'htx_form';
                                    $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE tableid = ? AND userId = ? AND name = ? LIMIT 1");
                                    $stmt3->bind_param("iis", $tableId, $userIdsGame[$y], $teamColumnId[$i][$index][$j]);
                                    $stmt3->execute();
                                    $result3 = $stmt3->get_result();
                                    if($result3->num_rows === 0) {
                                        if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                            echo "<td>";
                                            echo "<i style='color: red'>-</i>";
                                            echo "</td>";
                                        }
                                    } else {
                                        if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                            echo "<td>";
                                            while($row3 = $result3->fetch_assoc()) {
                                                // Checks if dropdown or other where value is an id
                                                if (in_array($row3['name'], $settingNameBacks)) {
                                                    // Writing data from id, if dropdown or radio
                                                    if ($columnType[$indexWrite] == "checkbox") {
                                                        $valueArray = explode(",", $row3['value']);
                                                        if (count($valueArray) > 0) {
                                                            for ($jWrite=0; $jWrite < count($valueArray); $jWrite++) {
                                                                echo htmlspecialchars($settingName[$valueArray[$jWrite]]);
                                                                // Insert comma, if the value is not the last
                                                                if (count($valueArray) != ($jWrite + 1)) {
                                                                    echo ", ";
                                                                }
                                                            }
                                                        }
                                                    } else if (in_array('otherInput',$specialName[$indexWrite]) and !in_array($row3['value'],$settingId)) {
                                                        if ($row3['value'] == "0") echo ""; else echo htmlspecialchars($row3['value']);
                                                    } else {
                                                        echo htmlspecialchars($settingName[$row3['value']]);
                                                    }

                                                } else {
                                                    // Checks column type
                                                    if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                                        // Writing data from table
                                                        echo htmlspecialchars($row3['value']);
                                                    }
                                                }
                                            }
                                            echo "</td>";
                                        }
                                    }
                                    $stmt3->close();

                                    // Other information
                                    for ($indexWrite=0; $indexWrite < count($columnNameBack); $indexWrite++) {
                                        // Getting data for specefied column
                                        $table_name3 = $wpdb->prefix . 'htx_form';
                                        $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE tableid = ? AND userId = ? AND name = ?");
                                        $stmt3->bind_param("iis", $tableId, $userIdsGame[$y], $columnNameBack[$indexWrite]);
                                        $stmt3->execute();
                                        $result3 = $stmt3->get_result();
                                        if($result3->num_rows === 0) {
                                            if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                                echo "<td>";
                                                echo "<i style='color: red'>-</i>";
                                                echo "</td>";
                                            }
                                        } else {
                                            if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                                echo "<td>";
                                                while($row3 = $result3->fetch_assoc()) {
                                                    // Checks if dropdown or other where value is an id
                                                    if (in_array($row3['name'], $settingNameBacks)) {
                                                        // Writing data from id, if dropdown or radio
                                                        if ($columnType[$indexWrite] == "checkbox") {
                                                            $valueArray = explode(",", $row3['value']);
                                                            if (count($valueArray) > 0) {
                                                                for ($jWrite=0; $jWrite < count($valueArray); $jWrite++) {
                                                                    echo htmlspecialchars($settingName[$valueArray[$jWrite]]);
                                                                    // Insert comma, if the value is not the last
                                                                    if (count($valueArray) != ($jWrite + 1)) {
                                                                        echo ", ";
                                                                    }
                                                                }
                                                            }
                                                        } else if (in_array('otherInput',$specialName[$indexWrite]) and !in_array($row3['value'],$settingId)) {
                                                            if ($row3['value'] == "0") echo ""; else echo htmlspecialchars($row3['value']);
                                                        } else {
                                                            echo htmlspecialchars($settingName[$row3['value']]);
                                                        }

                                                    } else {
                                                        // Checks column type
                                                        if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                                            // Writing data from table
                                                            echo htmlspecialchars($row3['value']);
                                                        }
                                                    }
                                                }
                                                echo "</td>";
                                            }
                                        }
                                        $stmt3->close();
                                    }
                                    echo "</tr>";
                                    $sortTable[] = $tournamentIds[$i][$index];
                                }
                            } else {
                                for ($u=0; $u < count($teamsNamesWcolumnId[$teamColumnId[$i][$index][$j]]); $u++) { 
                                    $userIdsGame = array();
                                    $userIdsTeam = array();

                                    echo "<tr class='InfoTableRow'>";

                                    echo "<td colspan='$TopColumnAmount'>".$teamsNamesWcolumnId[$teamColumnId[$i][$index][$j]][$u]."</td>";
                                    
                                    echo "</tr>";

                                    $table_name = $wpdb->prefix . 'htx_form';
                                    $stmt = $link->prepare("SELECT DISTINCT userId, value FROM `$table_name` where active = 1 and tableId = ? and name = ? and value != ''");
                                    $stmt->bind_param("ii", $tableId, $tournamentColumnbackend[$i]);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if($result->num_rows === 0) {
                                        $stmt->close();
                                    } else {
                                        while($row = $result->fetch_assoc()) {   
                                            if (in_array($tournamentIds[$i][$index], explode(",", $row['value'])))
                                                $userIdsGame[] = $row['userId'];
                                        }
                                        $stmt->close();

                                        for ($y=0; $y < count($userIdsGame); $y++) { 
                                            $table_name = $wpdb->prefix . 'htx_form';
                                            $stmt = $link->prepare("SELECT DISTINCT userId, value FROM `$table_name` where active = 1 and tableId = ? and userId = ? and name = ? and value != ''");
                                            $stmt->bind_param("iii", $tableId, $userIdsGame[$y], $teamColumnId[$i][$index][$j]);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            if($result->num_rows === 0) {
                                                $stmt->close();
                                            } else {
                                                while($row = $result->fetch_assoc()) {   
                                                    if ($teamsIdsWcolumnId[$teamColumnId[$i][$index][$j]][$u] == $row['value']) {
                                                        // echo $teamsIdsWcolumnId[$teamColumnId[$i][$index][$j]][$u];
                                                        $userIdsTeam[] = $row['userId'];
                                                    } else {
                                                        if ($u == 0) {
                                                            // If user team have been deleted, then throw into first team on list
                                                            if (!in_array($row['value'],$teamsIdsWcolumnId[$teamColumnId[$i][$index][$j]])) $userIdsTeam[] = $row['userId'];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    

                                    for ($y=0; $y < count($userIdsTeam); $y++) {     
                                        echo "<tr class='InfoTableRow'>";                                
                                        // Team name placeholder
                                        echo "<td>".$teamsNamesWcolumnId[$teamColumnId[$i][$index][$j]][$u]."</td>";

                                        // Other information
                                        for ($indexWrite=0; $indexWrite < count($columnNameBack); $indexWrite++) {
                                            // Getting data for specefied column
                                            $table_name3 = $wpdb->prefix . 'htx_form';
                                            $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE tableid = ? AND userId = ? AND name = ?");
                                            $stmt3->bind_param("iis", $tableId, $userIdsTeam[$y], $columnNameBack[$indexWrite]);
                                            $stmt3->execute();
                                            $result3 = $stmt3->get_result();
                                            if($result3->num_rows === 0) {
                                                if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                                    echo "<td>";
                                                    echo "<i style='color: red'>-</i>";
                                                    echo "</td>";
                                                }
                                            } else {
                                                if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                                    echo "<td>";
                                                    while($row3 = $result3->fetch_assoc()) {
                                                        // Checks if dropdown or other where value is an id
                                                        if (in_array($row3['name'], $settingNameBacks)) {
                                                            // Writing data from id, if dropdown or radio
                                                            if ($columnType[$indexWrite] == "checkbox") {
                                                                $valueArray = explode(",", $row3['value']);
                                                                if (count($valueArray) > 0) {
                                                                    for ($jWrite=0; $jWrite < count($valueArray); $jWrite++) {
                                                                        echo htmlspecialchars($settingName[$valueArray[$jWrite]]);
                                                                        // Insert comma, if the value is not the last
                                                                        if (count($valueArray) != ($jWrite + 1)) {
                                                                            echo ", ";
                                                                        }
                                                                    }
                                                                }
                                                            } else if (in_array('otherInput',$specialName[$indexWrite]) and !in_array($row3['value'],$settingId)) {
                                                                if ($row3['value'] == "0") echo ""; else echo htmlspecialchars($row3['value']);
                                                            } else {
                                                                echo htmlspecialchars($settingName[$row3['value']]);
                                                            }

                                                        } else {
                                                            // Checks column type
                                                            if (!in_array($columnType[$indexWrite], $nonUserInput)) {
                                                                // Writing data from table
                                                                echo htmlspecialchars($row3['value']);
                                                            }
                                                        }
                                                    }
                                                    echo "</td>";
                                                }
                                            }
                                            $stmt3->close();
                                        }
                                        echo "</tr>";
                                    }
                                }
                            }
                        }
                    }
                echo "</tbody></table>";
                echo "<br>";
            }
            if (count($sortTable)>0){
                $sortTable = array_unique($sortTable);
                // Sort table if needed
                for ($i=0; $i < count($sortTable); $i++) { 
                    echo "<script>setTimeout(() => {sortTable(1, 0, \"teamsTable-".$sortTable[$i]."\",false,\"$sortTable[$i]\")}, 300);</script>";
                }
            }
        }
    }
?>