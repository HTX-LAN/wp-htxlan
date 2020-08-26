<?php
    //Prevent direct file access
    if(!defined('ABSPATH')) {
        header("Location: ../../../");
        die();
    }

    // Tabel med alle tilmeldinger som kan ses - Evt en knap som kan trykkes, hvor så at felterne kan blive redigerbare - Man kan vælge imellem forskellige forms
    // Widgets and style
    HTX_load_standard_backend();
    wp_enqueue_script( 'participantList_script', "/wp-content/plugins/WPPlugin-HTXLan/JS/participant.js");

    // Getting start information for database connection
    global $wpdb;
    // Connecting to database, with custom variable
    $link = database_connection();

    // Non user editable inputs saved
    $nonUserInput = array('text area', 'price','spacing');

    // Header
    echo "<h1>HTX Lan tilmeldinger</h1>";

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
                $columns[] = $row['id'];
            }
        }

        // Getting user preferrence
        $table_name = $wpdb->prefix . 'htx_settings';
        $stmt = $link->prepare("SELECT * FROM `$table_name` where settingName = ? AND NOT value = '' AND active = 1 AND type = 'participantUserPreference' AND tableId = ? LIMIT 1");
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
                    if ($userSetting == false) {
                        // Make new record in database
                        $table_name = $wpdb->prefix . 'htx_settings';
                        $stmt = $link->prepare("INSERT INTO `$table_name` (settingName, value, type, tableId) VALUES (?, ?, 'participantUserPreference', ?)");
                        $stmt->bind_param("isi", $userId, $headsShownString, $tableId);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        // Update record
                        $table_name = $wpdb->prefix . 'htx_settings';
                        $stmt = $link->prepare("UPDATE `$table_name` SET value = ? WHERE settingName = ? and tableId = ?");
                        $stmt->bind_param("sii", $headsShownString, $userId, $tableId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }

                break;
            }
        }

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
                if (in_array($row['id'], $headsShown)) $selected = 'checked="checked"'; else $selected = '';
                if ($userSetting == false) $selected = 'checked="checked"';
                echo "<input type='checkbox' id='checkBox-".$row['id']."' name='shownColumns[]' value='".$row['id']."' $selected><label for='checkBox-".$row['id']."'>".$row['columnNameFront']."</label><br>";
            }
        }
        echo "<button type='submit' class='btn updateBtn' name='post' value='updateUserPreference'>Opdater</button>";
        echo "</form>";
        echo "</div>";


        // Start of table with head
        echo "<div class='formGroup_container'><div class='formGroup formGroup_scroll_left'>
            <table class='InfoTable' id='participantListTable'><thead><tr>";

        // Getting information from database
        // Users
        $table_name = $wpdb->prefix . 'htx_form_users';
        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableId = ? AND active = 1");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) echo "Ingen registreringer"; else {
            // Getting every column
            $table_name3 = $wpdb->prefix . 'htx_column';
            $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE tableid = ?");
            $stmt3->bind_param("i", $tableId);
            $stmt3->execute();
            $result3 = $stmt3->get_result();
            if($result3->num_rows === 0) {return HTX_frontend_sql_notworking();} else {
                while($row3 = $result3->fetch_assoc()) {
                    $columnId[] = $row3['id'];
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

            // Pre main column
            echo "<th></th>";
            echo "<th onClick='sortTable(1,1,\"participantListTable\",true,\"participantListTable\")' title='Sorter efter denne kolonne' style='cursor: pointer' class='table_header'>
            <span>Id</span>
            <span class='material-icons arrowInline sortingCell_participantListTable' id='sortingSymbol_participantListTable_1'></span></th>";

            // Writing every column and insert into table head
            $columnNumber = 2;
            for ($i=0; $i < count($columnNameBack); $i++) {
                // Check if input should not be shown
                if (!in_array($columnType[$i], $nonUserInput)) {
                    if (!in_array($columnId[$i], $headsShown) AND $userSetting == true) $shown[$i] = "hidden"; else $shown[$i] = "";
                    echo "<th onClick='sortTable(1,$columnNumber,\"participantListTable\",true,\"participantListTable\")' class='table_header $shown[$i]' title='Sorter efter denne kolonne' style='cursor: pointer'>
                        <span class='table_header_text'>$columnNameFront[$i]</span>&nbsp;
                        <span class='material-icons arrowInline sortingCell_participantListTable' id='sortingSymbol_participantListTable_$columnNumber'></span>
                    </th>";
                    $columnNumber++;
                }
            }
            // Writing extra lines
            echo "<th>Betaling</th>";
            echo "<th><span class='material-icons' title='Ankommet' style='cursor: help'>flight_land</span></th>";
            echo "<th><span class='material-icons' title='Person er en del af crew' style='cursor: help'>people_alt</span></th>";
            echo "<th>Pris</th>";
            echo "<th></th>";

            // Ending head
            echo "</tr></head>";

            // User information
            // Stating table body
            echo "<tbody>";

            // Getting every user ids
            while($row = $result->fetch_assoc()) {
                $userid[] = $row['id'];
                $payed[] = $row['payed'];
                $arrived[] = $row['arrived'];
                $crew[] = $row['crew'];
                $prices[] = $row['price'];
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
                $settingName[0] = "";
                $settingValue[0] = 0;
                while($row3 = $result3->fetch_assoc()) {
                    $settingName[$row3['id']] = $row3['settingName'];
                    $settingValue[$row3['id']] = $row3['value'];
                }
            }

            // download data array
            $dataForDownload = array();

            // Getting and writing every user information
            for ($i=0; $i < count($userid); $i++) {
                // Data for line
                $lineData = array();
                // Starting price
                $price = 0;
                $priceExtra = 0;

                echo "<tr class='InfoTableRow'>";
                echo "<td><span class='material-icons' style='cursor: pointer'>edit</span></td>";
                echo "<td>$userid[$i]</td>";

                // data
                $lineData['id'] = intval($userid[$i]);

                // For every column
                for ($index=0; $index < count($columnNameBack); $index++) {
                    // Getting data for specefied column
                    $table_name2 = $wpdb->prefix . 'htx_form';
                    $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableid = ? AND userId = ? AND name = ?");
                    $stmt2->bind_param("iis", $tableId, $userid[$i], $columnNameBack[$index]);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if($result2->num_rows === 0) {
                        if (!in_array($columnType[$index], $nonUserInput)) {
                            echo "<td class='$shown[$index]'>";
                            echo "<i style='color: red'>-</i>";
                            echo "</td>";

                            // Data for download
                            $lineData[$columnNameFront[$index]] = '-';
                        }
                    } else {
                        if (!in_array($columnType[$index], $nonUserInput)) {
                            echo "<td class='$shown[$index]'>";
                            while($row2 = $result2->fetch_assoc()) {
                                // Checks if dropdown or other where value is an id
                                if (in_array($row2['name'], $settingNameBacks)) {
                                    // Writing data from id, if dropdown or radio
                                    if ($columnType[$index] == "checkbox") {
                                        $valueArray = explode(",", $row2['value']);
                                        if (count($valueArray) > 0) {
                                            // Data
                                            $lineData[$columnNameFront[$index]] = '';
                                            for ($j=0; $j < count($valueArray); $j++) {
                                                // Writing price
                                                if (in_array('price_intrance', $specialName[$index])) {
                                                    if ($settingValue[$valueArray[$j]] != "") {
                                                        $price = $price + floatval($settingValue[$valueArray[$j]]);
                                                        echo htmlspecialchars($settingName[$valueArray[$j]]);

                                                        // Data
                                                        $lineData[$columnNameFront[$index]] .= htmlspecialchars($settingName[$valueArray[$j]]);
                                                    } else {
                                                        echo htmlspecialchars($settingValue[$valueArray[$j]]);

                                                        // Data
                                                        $lineData[$columnNameFront[$index]] .= htmlspecialchars($settingValue[$valueArray[$j]]);
                                                    }

                                                } else if (in_array('price_extra', $specialName[$index])) {
                                                    // Writing extra price
                                                    if ($settingValue[$valueArray[$j]] != "") {
                                                        $priceExtra = $priceExtra + floatval($settingValue[$valueArray[$j]]);
                                                        echo htmlspecialchars($settingName[$valueArray[$j]]);

                                                        // Data
                                                        $lineData[$columnNameFront[$index]] .= htmlspecialchars($settingName[$valueArray[$j]]);
                                                    } else {
                                                        echo htmlspecialchars($settingValue[$valueArray[$j]]);

                                                        // Data
                                                        $lineData[$columnNameFront[$index]] .= htmlspecialchars($settingValue[$valueArray[$j]]);
                                                    }
                                                } else {
                                                    echo htmlspecialchars($settingValue[$valueArray[$j]]);

                                                    // Data
                                                    $lineData[$columnNameFront[$index]] .= htmlspecialchars($settingValue[$valueArray[$j]]);
                                                }
                                                // Insert comma, if the value is not the last
                                                if (count($valueArray) != ($j + 1)) {
                                                    echo ", ";
                                                    // Data
                                                    $lineData[$columnNameFront[$index]] .= ", ";
                                                }
                                            }
                                        }else echo "test";
                                    } else {
                                        if (in_array('price_intrance', $specialName[$index])) {
                                            // Writing price
                                            if ($settingValue[$row2['value']] != "") {
                                                $price = $price + floatval($settingValue[$row2['value']]);
                                                echo htmlspecialchars($settingName[$row2['value']]);

                                                // Data
                                                $lineData[$columnNameFront[$index]] .= htmlspecialchars($settingName[$row2['value']]);
                                            } else {
                                                echo htmlspecialchars($settingValue[$row2['value']]);

                                                // Data
                                                $lineData[$columnNameFront[$index]] .= htmlspecialchars($settingValue[$row2['value']]);
                                            }

                                        } else if (in_array('price_extra', $specialName[$index])) {
                                            // Writing extra price
                                            if ($settingValue[$row2['value']] != "") {
                                                $priceExtra = $priceExtra + floatval($settingValue[$row2['value']]);
                                                echo htmlspecialchars($settingName[$row2['value']]);

                                                // Data
                                                $lineData[$columnNameFront[$index]] .= htmlspecialchars($settingName[$row2['value']]);
                                            } else {
                                                echo htmlspecialchars($settingValue[$row2['value']]);

                                                // Data
                                                $lineData[$columnNameFront[$index]] .= htmlspecialchars($settingValue[$row2['value']]);
                                            }
                                        } else {
                                            echo htmlspecialchars($settingValue[$row2['value']]);

                                            // Data
                                            $lineData[$columnNameFront[$index]] .= htmlspecialchars($settingValue[$row2['value']]);
                                        }
                                    }

                                } else {
                                    // Checks column type
                                    if (!in_array($columnType[$index], $nonUserInput)) {
                                        // Writing data from table
                                        echo htmlspecialchars($row2['value']);
                                        // Writing price
                                        if (in_array('price_intrance', $specialName[$index])) {
                                            $price = $price + floatval($row2['value']);
                                        }
                                        if (in_array('price_intrance', $specialName[$index])) {
                                            $priceExtra = $priceExtra + floatval($row2['value']);
                                        }

                                        // Data
                                        $lineData[$columnNameFront[$index]] .= htmlspecialchars($row2['value']);
                                    }
                                }
                            }
                            echo "</td>";
                        }
                    }
                    $stmt2->close();
                }

                // Adding payed, and arrived at the end of inputs
                // Payed

                // Getting different payed option - These are pre determined, such as cash, mobilepay
                $paymentMethods = array("Kontant", "Mobilepay");
                $paymentMethodsId = array("0", "0-f", "1-f");

                echo "<td ";
                if ($payed[$i] == "0") echo "class='unpayed'";
                else if ($payed[$i] == "0-i" OR $payed[$i] == "1-i") echo "class='crewpayed'";
                else if (in_array($payed[$i], $paymentMethodsId)) echo "class='payed'";
                echo ">
                    <form id='$i-pay' method='POST'>
                    <input type='hidden' name='userId' value='$userid[$i]'>
                    <input type='hidden' name='post' value='paymentUpdate'>
                    <select name='paymentOption'  onchange='document.getElementById(\"$i-pay\").submit()'>
                        <option value='0'";
                    if ($payed[$i] == 0) echo "selected";
                        echo">Ingen</option>";
                for ($j=0; $j < count($paymentMethods); $j++) {
                    echo "<option value='$j-f'";
                    if ($payed[$i] == "$j-f") echo "selected";
                    echo">$paymentMethods[$j]</option>";
                }
                echo "</select>
                    </form>
                </td>";

                // data
                if ($payed[$i] == 0) {
                    $lineData['Betaling'] = 'Ingen';
                } else if ($payed[$i] == '0-f') {
                    $lineData['Betaling'] = 'Kontant';
                } else if ($payed[$i] == '0-f') {
                    $lineData['Betaling'] = 'Mobilepay';
                } else {
                    $lineData['Betaling'] = '';
                }

                // Arrived
                echo "<td style='text-align: center'>";
                echo "<form id='$i-arrived' method='POST'>";
                echo "<input type='hidden' name='post' value='arrivedtUpdate'><input type='hidden' name='userId' value='$userid[$i]'>";
                echo "<input type='hidden' name='arrived' value='0'>";
                echo "<input id='arrived-$i' type='checkbox' class='inputCheckbox' name='arrived' value='1' onchange='document.getElementById(\"$i-arrived\").submit()'";
                if ($arrived[$i] == 1) echo "checked";
                echo ">";
                echo "</form>";
                echo "</td>";

                // data
                if ($arrived[$i] == 1)
                    $lineData['Ankommet'] = 'Ja';
                else 
                    $lineData['Ankommet'] = 'Nej';

                // Crew
                echo "<td style='text-align: center'>";
                echo "<form id='$i-crew' method='POST'>";
                echo "<input type='hidden' name='post' value='crewUpdate'><input type='hidden' name='userId' value='$userid[$i]'>";
                echo "<input type='hidden' name='crew' value='0'>";
                echo "<input id='crew-$i' type='checkbox' class='inputCheckbox' name='crew' value='1' onchange='document.getElementById(\"$i-crew\").submit()'";
                if ($crew[$i] == 1) {echo "checked"; $price = 0;}
                echo ">";
                echo "</form>";
                echo "</td>";

                // data
                if ($arrived[$i] == 1)
                    $lineData['Crew'] = 'Ja';
                else 
                    $lineData['Crew'] = 'Nej';

                // Price
                $price = floatval($price) + floatval($priceExtra);
                echo "<td>$price,-</td>";

                // data
                $lineData['Pris'] = floatval($price);

                // Delete
                echo "<td>
                <a>
                    <form name='deleteForm-$i' id='deleteForm-$i' method='POST'>
                        <input type='hidden' name='userid' value='$userid[$i]'>
                        <input type='hidden' name='delete' value='deleteSubmission'>
                        <span class='material-icons' style='cursor: pointer' onclick='confirmDelete(\"deleteForm-$i\")'>delete_forever</span>
                    </form>
                </a>
                </td>";

                array_push($dataForDownload, $lineData);
            }

        }
        $stmt->close();

        // Ending table
        echo "</thead></table></div></div>";

        // Download data as CSV
        echo "<script>tableCSVContent = ".json_encode($dataForDownload).";</script>\n";
        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.2.0/papaparse.min.js" integrity="sha512-rKFvwjvE4liWPlFnvH4ZhRDfNZ9FOpdkD/BU5gAIA3VS3vOQrQ5BjKgbO3kxebKhHdHcNUHLqxQYSoxee9UwgA==" crossorigin="anonymous"></script>'."\n";
        echo "<button class='btn updateBtn' onclick='downloadData();'>Download data</button>";
    }

?>
