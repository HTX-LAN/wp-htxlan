<?php
    // Widgets and style
    HTX_load_standard_backend();

    // Header
    echo "<h1>HTX Lan økonomi</h1>";

    // Getting start information for database connection
    global $wpdb;
    // Connecting to database, with custom variable
    $link = database_connection();

    // Getting data about forms
    $table_name = $wpdb->prefix . 'htx_form_tables';
    $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 ORDER BY favorit DESC, tableName ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0) echo "Ingen formularer"; else {
        while($row = $result->fetch_assoc()) {
            $tableIds[] = $row['id'];
            $tableNames[] = $row['tableName'];
        }

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
        if($result2->num_rows === 0) echo "Ingen registreringer"; else {
            while($row = $result2->fetch_assoc()) {
                $usersId[] = $row['id'];
                $usersPayed[] = $row['payed'];
                $usersArrived[] = $row['arrived'];
                $usersCrew[] = $row['crew'];
                $usersPrice[] = $row['price'];
            }

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
                        <th>Element navn</th>
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
                <tbody>
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
                    </tr>
                    <tr style='background-color: unset; height: 2rem;'>
                        <td colspan='9' style='background-color: unset;'></td>
                    </tr>
                    <tr>
                        <th colspan='9'><h2 style='margin: 0px;'>Ekstra</h2></th>
                    </tr>
                    <tr>
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
                    </tr>
                    <tr style='background-color: unset; height: 2rem;'>
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
        $stmt2->close();
    }
    $stmt->close();

?>