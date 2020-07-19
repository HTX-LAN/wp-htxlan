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
    if($result->num_rows === 0) {echo "Der er ingen turneringer";$stmt->close();$Error = true;} else {
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
                $teamsTournament[] = $row['placeholderText'];
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
    if (!$Error) {
        for ($i=0; $i < count($tournamentColumnIds); $i++) { 
            echo "<h2>$tournamentColumnName[$i]</h2>";
            for ($index=0; $index < count($tournamentNames[$i]); $index++) { 
                echo "
                <table class='InfoTable' style='width: unset'>
                    <thead>
                        <tr>
                            <th colspan='7'><h2 style='margin: 0px;'>".$tournamentNames[$i][$index]." - $index</h2></th>
                        </tr>
                    </thead>
                    <tbody>";
                    echo "<tr>
                        <th>Holdnavn</th>
                        <th>Spiller navn</th>
                        <th>Email</th>
                        <th>Skole</th>
                        <th>Klasse</th>
                        <th>Discord</th>
                        <th>Ingame navn</th>
                    </tr>";
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
                                    <th colspan='7'>".$teamsNames[$index][$team]."</th>
                                </tr>
                                <tr>
                                    <td colspan='7'>Ingen tilmeldte endnu</td>
                                </tr>";
                            } else {
                                echo "
                                <tr>
                                    <th colspan='7'>".$teamsNames[$index][$team]."</th>
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
                  
                

                echo "
                    </tbody>
                    <tr colspan='9' style='background-color: unset; height: 2rem;'></tr>
                </table>";
            }
        }
    }
    

?>