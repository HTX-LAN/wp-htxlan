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

    // Header
    echo "<h1>HTX Lan turneringer og hold</h1>";

    // Choose table
    // Getting data about forms
    $table_name = $wpdb->prefix . 'htx_form_tables';
    $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 ORDER BY favorit DESC, tableName ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0) {echo "Ingen formularer - Opret nogen 🙂";$stmt->close();$Error = true;die;} else {
        while($row = $result->fetch_assoc()) {
            $tableIds[] = $row['id'];
            $tableNames[] = $row['tableName'];
        }
        $stmt->close();

        // Getting table id
        if (in_array(intval($_GET['formular']), $tableIds)) $tableId = intval($_GET['formular']); else $tableId = $tableIds[0];

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
    echo "<button class='btn normalBtn' style='margin-bottom: 2rem;'>Ændre viste felter</button><br>";

    // Get torunaments
    // Get columns, whith tournament function and team function and store in array for later use 
    $table_name = $wpdb->prefix . 'htx_column';
    $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 and tableId = ? and specialName != '' ");
    $stmt->bind_param("i", $tableId);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        echo "Der er ingen turneringer";
        $stmt->close();
        $Error = true;
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
                $teamsColumnName[] = $row['columnNameFront'];
                $teamsColumnbackend[] = $row['columnNameBack'];
                $teamsColumnSettingCat[] = $row['settingCat'];
                $teamsTournament[] = $row['teams'];
            }
            
        }
        $stmt->close();

        // Tournaments
        for ($i=0; $i < count($tournamentColumnIds); $i++) { 
            $table_name = $wpdb->prefix . 'htx_settings_cat';
            $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 and tableId = ? and settingNameBack = ?");
            $stmt->bind_param("ii", $tableId, $tournamentColumnIds[$i]);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                echo "Der er ingen turneringer";
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
                echo "Der er ingen turneringer";
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
        
        // Get teams
        for ($i=0; $i < count($teamsColumnIds); $i++) { 
            $table_name = $wpdb->prefix . 'htx_settings_cat';
            $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 and tableId = ? and settingNameBack = ?");
            $stmt->bind_param("ii", $tableId, $teamsColumnIds[$i]);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                echo "Der er ingen turneringer";
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
                echo "Der er ingen turneringer";
                $stmt->close();
                $Error = true;
                die; #Ending page, becuase of error
            } else {
                while($row = $result->fetch_assoc()) {
                    $teamsNames[$i][] = $row['settingName'];
                    $teamsIds[$i][] = $row['id'];
                    $teamsNamesWId[$i][$row['id']] = $row['settingName'];
                }
                $stmt->close();
            }
        }

        // Get people on team

        // Write page
    }

    // Getting every dropdown and radio categories
    $table_name3 = $wpdb->prefix . 'htx_settings_cat';
    $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE tableId = ? AND active = 1");
    $stmt3->bind_param("i", $tableId);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    if($result3->num_rows === 0) echo ""; else {
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
    if($result3->num_rows === 0) echo ""; else {
        while($row3 = $result3->fetch_assoc()) {
            $settingName[$row3['id']] = $row3['settingName'];
            $settingValue[$row3['id']] = $row3['value'];
        }
    }

    $headsShown = array('firstName', 'lastName', 'email', 'discordTag'); #This controls the shown elements on screen

    if (!isset($headsShown) OR count($headsShown) < 0) $headsShown = array('firstName', 'lastName', 'email', 'discordTag');

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

    // Non user editable inputs saved
    $nonUserInput = array('text area', 'price');

    // Amount of columns
    $TopColumnAmount = count($columnNameBack)+1;

    if (!$Error) {
        for ($i=0; $i < count($tournamentColumnIds); $i++) { 
            echo "<h2>$tournamentColumnName[$i]</h2>";
            for ($index=0; $index < count($tournamentNames[$i]); $index++) { 
                echo "
                <table class='InfoTable' style='width: unset'>
                    <thead>
                        <tr>
                            <th colspan='$TopColumnAmount'><h2 style='margin: 0px;'>".$tournamentNames[$i][$index]."</h2></th>
                        </tr>
                    </thead>
                    <tbody>";
                if (in_array($teamsTournament[$index], $tournamentIds[$i])){  
                    echo "<tr>
                    <th>Holdnavn</th>";
                    // Writing every column and insert into table head
                    for ($iHeaders=0; $iHeaders < count($columnNameBack); $iHeaders++) {
                        // Check if input should not be shown
                        if (!in_array($columnType[$iHeaders], $nonUserInput)) {
                            echo "<th>$columnNameFront[$iHeaders]</th>";
                        }
                    }
                    echo "</tr>";  
                    echo "<tr colspan='9' style='background-color: unset; height: 0.5rem;'></tr>";            
                    for ($team=0; $team < count($teamsIds[$index]); $team++) { 
                        for ($j=0; $j < count($teamsColumnIds); $j++) { 
                            if ($teamsTournament[$j] == $tournamentIds[$i][$index]) {
                                $table_name = $wpdb->prefix . 'htx_form';
                                $stmt = $link->prepare("SELECT DISTINCT userId FROM `$table_name` where active = 1 and tableId = ? and name = ? and value = ? ORDER BY value ASC");
                                $stmt->bind_param("iii", $tableId, $teamsColumnIds[$j], $teamsIds[$index][$team]);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if($result->num_rows === 0) {
                                    $stmt->close();
                                    echo "
                                    <tr>
                                        <th colspan='$TopColumnAmount'>".$teamsNames[$index][$team]."</th>
                                    </tr>
                                    <tr>
                                        <td colspan='$TopColumnAmount'>Ingen tilmeldte endnu</td>
                                    </tr>";
                                } else {
                                    echo "
                                    <tr>
                                        <th colspan='$TopColumnAmount'>".$teamsNames[$index][$team]."</th>
                                    </tr>";
                                    $whileCount = 0;
                                    $noUser = 0;
                                    while($row = $result->fetch_assoc()) {                                
                                        $tmpExplode = array();
            
                                        $table_name2 = $wpdb->prefix . 'htx_form';
                                        $stmt2 = $link->prepare("SELECT * FROM `$table_name2` where active = 1 and tableId = ? and userId = ? and name = ?");
                                        $stmt2->bind_param("iii", $tableId, $row['userId'], $tournamentColumnIds[$i]);
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
                                                echo "<tr>";
            
                                                $table_name2 = $wpdb->prefix . 'htx_form';
                                                $stmt2 = $link->prepare("SELECT * FROM `$table_name2` where active = 1 and tableId = ? and userId = ? and name = ?");
                                                $stmt2->bind_param("iii", $tableId, $row['userId'], $teamsColumnIds[$j]);
                                                $stmt2->execute();
                                                $result2 = $stmt2->get_result();
                                                if($result2->num_rows === 0) {
                                                    $stmt2->close();
                                                } else {
                                                    echo "<td>";
                                                    while($row2 = $result2->fetch_assoc()) {
                                                        if ($teamsNamesWId[$i][$row2['value']] == "") 
                                                            echo $teamsNames[$i][0];
                                                        else
                                                            echo $teamsNamesWId[$i][$row2['value']];
                                                        $newTeam = $teamsNamesWId[$i][$row2['value']];
                                                    }
                                                    echo "</td>";
                                                    $stmt2->close();
                                                }
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
                                            } else
                                                $noUser = $noUser + 1;
                                        }
                                        $whileCount = $whileCount + 1;
                                    }
                                    $stmt->close();
                                    if ($whileCount == $noUser)
                                        echo "<tr>
                                            <td colspan='7'>Ingen tilmeldte endnu</td>
                                        </tr>";
                                        
                                }
                                echo "<tr colspan='9' style='background-color: unset; height: 1rem;'></tr>";
                            }
                        }
                    }
                } else {
                    echo "<tr>";
                    // Writing every column and insert into table head
                    for ($iHeaders=0; $iHeaders < count($columnNameBack); $iHeaders++) {
                        // Check if input should not be shown
                        if (!in_array($columnType[$iHeaders], $nonUserInput)) {
                            echo "<th>$columnNameFront[$iHeaders]</th>";
                        }
                    }
                    echo "</tr>";  
                    // No teams, only players

                    $table_name = $wpdb->prefix . 'htx_form';
                    $stmt = $link->prepare("SELECT DISTINCT userId FROM `$table_name` where active = 1 and tableId = ? and name = ?");
                    $stmt->bind_param("ii", $tableId, $tournamentColumnIds[$i]);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0) {
                        $stmt->close();
                        echo "
                        <tr>
                            <td colspan='$TopColumnAmount'>Ingen tilmeldte endnu</td>
                        </tr>";
                    } else {
                        $whileCount = 0;
                        $noUser = 0;
                        while($row = $result->fetch_assoc()) {                                
                            $tmpExplode = array();

                            $table_name2 = $wpdb->prefix . 'htx_form';
                            $stmt2 = $link->prepare("SELECT * FROM `$table_name2` where active = 1 and tableId = ? and userId = ? and name = ?");
                            $stmt2->bind_param("iii", $tableId, $row['userId'], $tournamentColumnIds[$i]);
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
                                    echo "<tr>";

                                    $table_name2 = $wpdb->prefix . 'htx_form';
                                    $stmt2 = $link->prepare("SELECT * FROM `$table_name2` where active = 1 and tableId = ? and userId = ? and name = ?");
                                    $stmt2->bind_param("iii", $tableId, $row['userId'], $teamsColumnIds[$j]);
                                    $stmt2->execute();
                                    $result2 = $stmt2->get_result();
                                    if($result2->num_rows === 0) {
                                        $stmt2->close();
                                    } else {
                                        echo "<td>";
                                        while($row2 = $result2->fetch_assoc()) {
                                            if ($teamsNamesWId[$i][$row2['value']] == "") 
                                                echo $teamsNames[$i][0];
                                            else
                                                echo $teamsNamesWId[$i][$row2['value']];
                                            $newTeam = $teamsNamesWId[$i][$row2['value']];
                                        }
                                        echo "</td>";
                                        $stmt2->close();
                                    }
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
                                } else
                                    $noUser = $noUser + 1;
                            }
                            $whileCount = $whileCount + 1;
                        }
                        $stmt->close();
                        if ($whileCount == $noUser)
                            echo "<tr>
                                <td colspan='7'>Ingen tilmeldte endnu</td>
                            </tr>";
                            
                    }
                }
                  
                

                echo "
                    <tr colspan='9' style='background-color: unset; height: 2rem;'></tr>
                    </tbody>
                </table>";
            }
        }
    }
    

?>