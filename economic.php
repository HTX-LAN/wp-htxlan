<?php
    // Widgets and style
    HTX_load_standard_backend();

    // Header
    echo "<h1>HTX Lan økonomi</h1>";

    // Getting start information for database connection
    global $wpdb;
    // Connecting to database, with custom variable
    $link = database_connection();

    $EconomicError = false;

    // Getting data about forms
    $table_name = $wpdb->prefix . 'htx_form_tables';
    $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 ORDER BY favorit DESC, tableName ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0) {echo "Ingen formularer";$stmt->close();$EconomicError = true;} else {
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
        echo "<form method=\"get\"><input type='hidden' name='page' value='HTX_lan_participants_list'><select name='formular' class='dropdown' onchange='form.submit()'>";
        // writing every option
        for ($i=0; $i < count($tableIds); $i++) {
            // Seeing if value is the choosen one
            if ($tableIds[$i] == $tableId) $isSelected = "selected"; else $isSelected = "";

            // Writing value
            echo "<option value='$tableIds[$i]' $isSelected>$tableNames[$i]</option>";
        }

        // Ending dropdown
        echo "</select></form><br></p>";

        // Getting different payed option - These are pre determined, such as cash, mobilepay
        $paymentMethods = array("Kontant", "Mobilepay");
        $paymentMethodsId = array("0", "0-f", "1-f");

        // Getting user information
        $table_name2 = $wpdb->prefix . 'htx_form_users';
        $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? AND active = 1");
        $stmt2->bind_param("i", $tableId);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if($result2->num_rows === 0) {
            echo "Something NEEDS TO BE CHANGED";
            $stmt2->close();
            $EconomicError = true;
        } else {
            // Fetching and storing user values in arrays
            while($row = $result2->fetch_assoc()) {
                $usersId[] = $row['id'];
                $usersPayed[] = $row['payed'];
                switch ($row['payed']) {
                    case "0":
                        $usersPayedFalse[$row['id']] = $row['price'];
                        $usersPayedFalseIds[] = $row['id'];
                    break;
                    case "0-f":
                        $usersPayedCash[$row['id']] = $row['price'];
                        $usersPayedCashIds[] = $row['id'];
                    break;
                    case "1-f":
                        $usersPayedMobile[$row['id']] = $row['price'];
                        $usersPayedMobileIds[] = $row['id'];
                    break;
                }
                
                $usersArrived[$row['id']] = $row['arrived'];
                $usersCrew[$row['id']] = $row['crew'];
                if ($row['crew'] == "1") {
                    $usersCrewOnly[] = $row['id'];
                }
                $usersPrice[$row['id']] = $row['price'];
            }
            $stmt2->close();
            
            // Getting every cat setting that has function as price intrance
            $table_name2 = $wpdb->prefix . 'htx_settings_cat';
            $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? AND active = 1 AND specialName = 'price_intrance'");
            $stmt2->bind_param("i", $tableId);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if($result2->num_rows === 0) {
                echo "Something NEEDS TO BE CHANGED";
                $stmt2->close();
                $EconomicError = true;
            } else if($result2->num_rows > 1) {
                echo "Too many intrance settings";
                $stmt2->close();
                $EconomicError = true;
            } else {
                // Fetching and storing values in arrays
                while($row = $result2->fetch_assoc()) {
                    $settingCatIntranceName = $row['settingName'];
                    $settingCatIntranceNameBack = $row['settingNameBack'];
                    $settingCatIntranceId = $row['id'];
                }
                $stmt2->close();
                // Getting settings for category
                $table_name2 = $wpdb->prefix . 'htx_settings';
                $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE settingId = ? AND active = 1 AND specialName = 'price_intrance' ORDER BY sorting");
                $stmt2->bind_param("i", $settingCatIntranceId);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if($result2->num_rows === 0) {
                    echo "Something NEEDS TO BE CHANGED";
                    $stmt2->close();
                    $EconomicError = true;
                } else {
                    // Fetching and storing values in arrays
                    while($row = $result2->fetch_assoc()) {
                        $settingIntranceIds[] = $row['id'];
                        $settingIntranceName[$row['id']] = $row['settingName'];
                        $settingIntranceValue[$row['id']] = $row['value'];
                    }
                    $stmt2->close();
                }

                // Getting users submittet values for intrance
                $table_name2 = $wpdb->prefix . 'htx_form';
                $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableId = ? AND active = 1 AND name = ?");
                $stmt2->bind_param("is", $tableId, $settingCatIntranceNameBack);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if($result2->num_rows === 0) {
                    echo "Ingen tilmeldinger med pris elementet besvaret";
                    $stmt2->close();
                    $EconomicError = true;
                } else {
                    // Fetching and storing values in arrays
                    while($row = $result2->fetch_assoc()) {
                        $userSubmittetIntranceIds[] = $row['userId'];
                        $userSubmittetIntranceValue[$row['userId']] = $row['value'];
                        for ($i=0; $i < count($settingIntranceIds); $i++) { 
                            if (!in_array($row['userId'],$usersCrewOnly)) {
                                if ($row['value'] == $settingIntranceIds[$i]) {
                                    $userSubmittetIntrance[$settingIntranceIds[$i]][] = $row['userId'];
                                }
                            } else {
                                $userSubmittetIntrance[$settingIntranceIds[$i]][] = Null;
                            }
                        }
                    }
                    $stmt2->close();
                }
            }
        }
    }

    if (!$EconomicError) {
        // Table that shows overall income from different groups
        echo "
        <table class='InfoTable' style='width: unset'>
            <thead>
                <tr>
                    <th colspan='9'><h2 style='margin: 0px;'>Indkomst</h2></th>
                </tr>
                <tr>
                    <th><h3 style='margin: 0px;'>Indgang</h3></th>
                    <th colspan='4'>Beløb</th>
                    <th colspan='4'>Antal</th>
                </tr>
                <tr>
                    <th>$settingCatIntranceName</th>
                    <th>Ikke betalt</th>
                    <th>Kontant</th>
                    <th>Mobilepay</th>
                    <th>I alt</th>
                    <th>Ikke betalt</th>
                    <th>Kontant</th>
                    <th>Mobilepay</th>
                    <th>I alt</th>
                </tr>
            </thead>
            <tbody>";
                for ($i=0; $i < count($settingIntranceIds); $i++) { 
                    // Calculating amounts and value for every setting in element
                    $intranceNonPayedAmount[$i] = count(array_intersect($userSubmittetIntrance[$settingIntranceIds[$i]],$usersPayedFalseIds));
                    $intranceNonPayedSum[$i] = $intranceNonPayedAmount[$i]*$settingIntranceValue[$settingIntranceIds[$i]];
                    $intranceCashAmount[$i] = count(array_intersect($userSubmittetIntrance[$settingIntranceIds[$i]],$usersPayedCashIds));
                    $intranceCashSum[$i] = $intranceCashAmount[$i]*$settingIntranceValue[$settingIntranceIds[$i]];
                    $intranceMobileAmount[$i] = count(array_intersect($userSubmittetIntrance[$settingIntranceIds[$i]],$usersPayedMobileIds));
                    $intranceMobileSum[$i] = $intranceMobileAmount[$i]*$settingIntranceValue[$settingIntranceIds[$i]];

                    // Getting line total
                    $intranceSum[$i] = $intranceNonPayedSum[$i]+$intranceCashSum[$i]+$intranceMobileSum[$i];
                    $intranceAmount[$i] = $intranceNonPayedAmount[$i]+$intranceCashAmount[$i]+$intranceMobileAmount[$i];

                    echo "<tr class='InfoTableRow'>
                        <td>".$settingIntranceName[$settingIntranceIds[$i]]."</td>
                        <td>$intranceNonPayedSum[$i]</td>
                        <td>$intranceCashSum[$i]</td>
                        <td>$intranceMobileSum[$i]</td>
                        <td>$intranceSum[$i]</td>
                        <td>$intranceNonPayedAmount[$i]</td>
                        <td>$intranceCashAmount[$i]</td>
                        <td>$intranceMobileAmount[$i]</td>
                        <td>$intranceAmount[$i]</td>
                    </tr>";
                }
                // Crew row
                // Calculating amounts and value for every setting in element
                $intranceNonPayedAmount[$i+1] = count(array_intersect($usersCrewOnly,$usersPayedFalseIds));
                $intranceCashAmount[$i+1] = count(array_intersect($usersCrewOnly,$usersPayedCashIds));
                $intranceMobileAmount[$i+1] = count(array_intersect($usersCrewOnly,$usersPayedMobileIds));

                // Getting line total
                $intranceAmount[$i+1] = $intranceNonPayedAmount[$i+1]+$intranceCashAmount[$i+1]+$intranceMobileAmount[$i+1];

                echo "<tr class='InfoTableRow'>
                    <td>Crew</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>".$intranceNonPayedAmount[$i+1]."</td>
                    <td>".$intranceCashAmount[$i+1]."</td>
                    <td>".$intranceMobileAmount[$i+1]."</td>
                    <td>".$intranceAmount[$i+1]."</td>
                </tr>";
                // Getting sums for sum row
                $IntranceNonPayedTotalSum = array_sum($intranceNonPayedSum);
                $IntranceNonPayedTotalAmount = array_sum($intranceNonPayedAmount);
                $IntranceCashTotalSum = array_sum($intranceCashSum);
                $IntranceCashTotalAmount = array_sum($intranceCashAmount);
                $IntranceMobileTotalSum = array_sum($intranceMobileSum);
                $IntranceMobileTotalAmount = array_sum($intranceMobileAmount);
                $IntranceTotalSum = array_sum($intranceSum);
                $IntranceTotalAmount = array_sum($intranceAmount);

                echo "<tr class='InfoTableRow'>
                    <td><b>I alt</b></td>
                    <td>$IntranceNonPayedTotalSum</td>
                    <td>$IntranceCashTotalSum</td>
                    <td>$IntranceMobileTotalSum</td>
                    <td>$IntranceTotalSum</td>
                    <td>$IntranceNonPayedTotalAmount</td>
                    <td>$IntranceCashTotalAmount</td>
                    <td>$IntranceMobileTotalAmount</td>
                    <td>$IntranceTotalAmount</td>
                </tr>

                <tr style='background-color: unset; height: 2rem;'>
                    <td colspan='9' style='background-color: unset;'></td>
                </tr>

                <tr>
                    <th colspan='9'><h2 style='margin: 0px;'>Ekstra</h2></th>
                </tr>";

                echo "<tr>
                    <th colspan='9'>Element navn</th>
                </tr>
                <tr class='InfoTableRow'>
                    <td>Element</td>
                    <td>100</td>
                    <td>200</td>
                    <td>300</td>
                    <td>600</td>
                    <td>10</td>
                    <td>20</td>
                    <td>30</td>
                    <td>60</td>
                </tr>
                <tr class='InfoTableRow'>
                    <td>Element</td>
                    <td>100</td>
                    <td>200</td>
                    <td>300</td>
                    <td>600</td>
                    <td>10</td>
                    <td>20</td>
                    <td>30</td>
                    <td>60</td>
                </tr>
                <tr class='InfoTableRow'>
                    <td>Element</td>
                    <td>100</td>
                    <td>200</td>
                    <td>300</td>
                    <td>600</td>
                    <td>10</td>
                    <td>20</td>
                    <td>30</td>
                    <td>60</td>
                </tr>
                <tr class='InfoTableRow'>
                    <td>Element</td>
                    <td>100</td>
                    <td>200</td>
                    <td>300</td>
                    <td>600</td>
                    <td>10</td>
                    <td>20</td>
                    <td>30</td>
                    <td>60</td>
                </tr>
                <tr class='InfoTableRow'>
                    <td><b>I alt</b></td>
                    <td>400</td>
                    <td>800</td>
                    <td>1200</td>
                    <td>2400</td>
                    <td>60</td>
                    <td>80</td>
                    <td>120</td>
                    <td>240</td>
                </tr>";

                echo "<tr style='background-color: unset; height: 2rem;'>
                    <td colspan='9' style='background-color: unset;'></td>
                </tr>
                <tr>
                    <th colspan='9'><h2 style='margin: 0px;'>Ekstra indkomst</h2></th>
                </tr>
                <tr>
                    <th colspan='9'>Element navn</th>
                </tr>
                <tr class='InfoTableRow'>
                    <td>Andet</td>
                    <td>100</td>
                    <td>200</td>
                    <td>300</td>
                    <td>600</td>
                    <td colspan='4'></td>
                </tr>
                <tr style='background-color: unset; height: 2rem;'>
                    <td colspan='9' style='background-color: unset;'></td>
                </tr>
                <tr class='InfoTableRow'>
                    <td><b>I alt</b></td>
                    <td>100</td>
                    <td>200</td>
                    <td>300</td>
                    <td>600</td>
                    <td colspan='4'></td>
                </tr>
                <tr style='background-color: unset; height: 2rem;'>
                    <td colspan='9' style='background-color: unset;'></td>
                </tr>
                <tr>
                    <th colspan='5'><h2 style='margin: 0px;'>Udgifter</h2></th>
                </tr>
                <tr>
                    <th><h3 style='margin: 0px;'>Ekstra elementer</h3></th>
                    <th colspan='4'></th>
                </tr>
                <tr>
                    <th>Element navn</th>
                    <th>Ikke betalt</th>
                    <th>Kontant</th>
                    <th>Mobilepay</th>
                    <th>I alt</th>
                </tr>
                <tr class='InfoTableRow'>
                    <td>Præmier</td>
                    <td>100</td>
                    <td>200</td>
                    <td>300</td>
                    <td>600</td>
                </tr>
                <tr class='InfoTableRow'>
                    <td>Andet</td>
                    <td>100</td>
                    <td>200</td>
                    <td>300</td>
                    <td>600</td>
                </tr>
                <tr class='InfoTableRow'>
                    <td><b>I alt</b></td>
                    <td>100</td>
                    <td>200</td>
                    <td>300</td>
                    <td>600</td>
                    <td colspan='4'></td>
                </tr>
                <tr style='background-color: unset; height: 2rem;'>
                    <td colspan='9' style='background-color: unset;'></td>
                </tr>
                <tr>
                    <th colspan='2'><h2 style='margin: 0px;'>Samlet</h2></th>
                </tr>
                <tr class='InfoTableRow'>
                    <td>i alt</td>
                    <td>100</td>
                </tr>
            </tbody>
        </table><br>";

        echo "<br><table class='InfoTable' style='width: unset'><thead>
            <tr>
                <th colspan='2'>Statestik</th>
                <th>Antal</th>
                <th>Pris</th>
            </tr>
            </thead><tbody>
            <tr>
                <td colspan='2'>Ikke betalt</td>
                <td>10</td>
                <td>100</td>
            </tr>
            <tr>
                <td colspan='2'>Betalt</td>
                <td>60</td>
                <td>6000</td>
            </tr>
            <tr>
                <td colspan='2'>Ankommet</td>
                <td>60</td>
                <td>6000</td>
            </tr>
            <tr>
                <td colspan='2'>Ikke ankommet</td>
                <td>60</td>
                <td>6000</td>
            </tr>
            <tr>
                <td colspan='2'>Ikke ankommet som har betalt</td>
                <td>60</td>
                <td>6000</td>
            </tr>
            <tr>
                <td colspan='2'>Crew</td>
                <td>60</td>
                <td>6000</td>
            </tr>
            <tr>
                <td colspan='2'>Crew som ikke har betalt</td>
                <td>60</td>
                <td>6000</td>
            </tr>
            <tr>
                <td colspan='2'>Ikke crew</td>
                <td>60</td>
                <td>6000</td>
            </tr>
            <tr style='background-color: unset; height: 2rem;'>
                <td colspan='9' style='background-color: unset;'></td>
            </tr>
        </tbody></table>";    
    }

?>