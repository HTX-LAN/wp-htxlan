<?php
    // Frontend php site

    // Shortcode for blancket
    frontend_update();
    function frontend_update(){
        add_shortcode('HTX_Tilmeldningsblanket','HTX_lan_tilmdeldingsblanket_function');
        add_shortcode('HTX_participantCount','HTX_lan_participantCount_function');
    }

    // Ajax
    add_action( 'wp_enqueue_scripts', 'my_scripts' );
    function my_scripts() {
        $plugin_dir = ABSPATH . 'wp-content/plugins/wp-htxlan/';

        // Scripts that needs ajax
        wp_enqueue_script( 'htx_live_participant_count', plugin_dir_url( __FILE__ ) . 'JS/frontend.js', array('jquery'), '1.0.0', true );

        wp_localize_script( 'htx_live_participant_count', 'widgetAjax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'security' => wp_create_nonce( 'ACxxB2EVpJeh3DxBe95F6qfhkwCjX8222CMEA7m3A79rf2N22xy23E4MMQgUsvBsSAtEhNHznckQ9ej4zHGmZnXkhvSHhmxzTYdEBv8BbNQNUaLpbq9mb7Q' )
        ));
    }
    add_action('wp_ajax_htx_live_participant_count', 'htx_live_participant_count');
    add_action('wp_ajax_nopriv_htx_live_participant_count', 'htx_live_participant_count');


    // Perform the shortcode output for form
    function HTX_lan_tilmdeldingsblanket_function($atts = array()){
        
        // Custom connection to database
        $link = database_connection();
        global $wpdb;

        // add to $html, to return it at the end -> It is how to do shortcodes in Wordpress
        $html = "";

        // Check and get form from shortcode
        if (!isset($atts['form'])) $tableId = 0; else $tableId = intval($atts['form']);

        // Standard load
        $html .= HTX_load_standard_frontend();

        // Standard arrays
        $possiblePrice = array("", "DKK", ",-", "kr.", 'danske kroner', '$', 'NOK', 'SEK', 'dollars', 'euro');

        // Getting and writing form name
        $table_name = $wpdb->prefix . 'htx_form_tables';
        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE id = ?");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) return "<p>Denne form virker desværre ikke, fordi den specificeret formular ikke findes.</p><p>Formular: $tableId</p>";
        else {
            while($row = $result->fetch_assoc()) {
                $formName = $row['tableName'];
                $formEmail = $row['emailEnable'];

                // Cehck for open date
                if (strtotime($row['openForm']) > strtotime('now'))
                return "<p>Denne formular er endnu ikke tilgængelig.</p>\n<p>Formularen vil åbne d. ".date('d F Y', strtotime($row['openForm']))." kl: ".date('H:i', strtotime($row['openForm']));
                if ($row['closeFormActive'] == 1 && strtotime($row['closeForm']) < strtotime('now'))
                return "<p>Denne formular er desværre lukket.</p>\n<p>Formularen lukkede d. ".date('d F Y', strtotime($row['closeForm']))." kl: ".date('H:i', strtotime($row['closeForm']));
            }
        }
        $stmt->close();
        $html .= "\n<h2>$formName</h2>";

        // Price handling array
        $possiblePriceFunctions = array("price_intrance", "price_extra");
        $priceSet = false;

        // Post handling
        $postError = HTX_frontend_post($tableId);

        // Getting and writing content to form
        // Getting column info
        $table_name = $wpdb->prefix . 'htx_column';
        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableid = ? ORDER BY sorting ASC, columnNameFront ASC");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) {return HTX_frontend_sql_notworking();} else {
            while($row = $result->fetch_assoc()) {
                $columnId[] = $row['id'];
                $columnNameFront[] = $row['columnNameFront'];
                $columnNameBack[] = $row['columnNameBack'];
                $format[] = $row['format'];
                $formatExtra[] = $row['formatExtra'];
                $columnType[] = $row['columnType'];
                $special[] = $row['special'];
                $specialName[] = explode(",", $row['specialName']);
                $specialNameExtra[] = $row['specialNameExtra'];
                $specialNameExtra2[] = explode(",", $row['specialNameExtra2']);
                $specialNameExtra3[] = $row['specialNameExtra3'];
                $minChar[] = $row['minChar'];
                $maxChar[] = $row['maxChar'];
                $placeholderText[] = $row['placeholderText'];
                $sorting[] = $row['sorting'];
                $disabled[] = $row['disabled'];
                $required[] = $row['required'];
                $settingCat[] = $row['settingCat'];

                $columnNameFrontID[$row['id']] = $row['columnNameFront'];
                $columnNameBackID[$row['id']] = $row['columnNameBack'];
                $formatID[$row['id']] = $row['format'];
                $formatExtraID[$row['id']] = $row['formatExtra'];
                $columnTypeID[$row['id']] = $row['columnType'];
                $specialID[$row['id']] = $row['special'];
                $specialNameID[$row['id']] = explode(",", $row['specialName']);
                $specialNameExtraID[$row['id']] = $row['specialNameExtra'];
                $specialNameExtra2ID[$row['id']] = explode(",", $row['specialNameExtra2']);
                $specialNameExtra3ID[$row['id']] = $row['specialNameExtra3'];
                $minCharID[$row['id']] = $row['minChar'];
                $maxCharID[$row['id']] = $row['maxChar'];
                $placeholderTextID[$row['id']] = $row['placeholderText'];
                $sortingID[$row['id']] = $row['sorting'];
                $disabledID[$row['id']] = $row['disabled'];
                $requiredID[$row['id']] = $row['required'];
                $settingCatID[$row['id']] = $row['settingCat'];
            }
        }
        $stmt->close();
        // Setting up form
        $html .= "\n<form method=\"post\" id='HTX_form_$tableId'>";
        $html .= "\n<p>$postError</p>";
        $html .= "\n<script>var price = {};</script>";
        // Writing for every column entry
        for ($i=0; $i < count($columnNameFront); $i++) {
            if (isset($_POST[$columnNameBack[$i]])) $POST = $_POST[$columnNameBack[$i]]; else if ($columnType[$i] == 'checkbox') $POST = array(); else $POST = "";
            if (isset($_POST[$columnNameBack[$i].'-extra'])) $POSTextra = $_POST[$columnNameBack[$i].'-extra']; else $POSTextra = "";
            $ColumnInfo = array(
                "columnId" => $columnId[$i],
                "columnNameFront" => $columnNameFront[$i],
                "columnNameBack" => $columnNameBack[$i],
                "format" => $format[$i],
                "columnType" => $columnType[$i],
                "special" => $special[$i],
                "specialName" => $specialName[$i],
                "specialNameExtra" => $specialNameExtra[$i],
                "specialNameExtra2" => $specialNameExtra2[$i],
                "specialNameExtra3" => $specialNameExtra3[$i],
                "minChar" => $minChar[$i],
                "maxChar" => $maxChar[$i],
                "placeholderText" => $placeholderText[$i],
                "formatExtra" => $formatExtra[$i],
                "sorting" => $sorting[$i],
                "disabled" => $disabled[$i],
                "required" => $required[$i],
                "settingCat" => $settingCat[$i],
                "POST" => $POST,
                "POSTextra" => $POSTextra
            );
            $html .= HTX_frontend_switch($ColumnInfo, $tableId, $possiblePriceFunctions, $i, $priceSet, $possiblePrice);
        }
        // Writing script for showing elements based on other elements
        $html .= "\n<script>function HTX_frontend_js() {";
        // input field
        $inputtypeTextfield = array('inputbox', 'dropdown', 'user dropdown');
        for ($i=0; $i < count($columnId); $i++) {
            if (in_array('show', $specialName[$i])) {
                if ($specialNameExtra[$i] != "") {
                    // Transfering special name extra 2
                    $html .= "\n var isValue = ".json_encode($specialNameExtra2[$i]).";";
                    if (in_array($columnTypeID[$specialNameExtra[$i]], $inputtypeTextfield)) {
                        // Use -input
                        if ($formatID[$specialNameExtra[$i]] == 'number' AND $columnTypeID[$specialNameExtra[$i]] == 'inputbox') {
                            if (preg_match('/[<>=!]{1}+[=]?+\d+/', htmlspecialchars_decode($specialNameExtra2[$i][0]), $output_array)) {
                                $html .= "\n thatValue = document.getElementById('$specialNameExtra[$i]-input').value;";
                                $html .= "\n if (thatValue $output_array[0]) 
                                    document.getElementById('$columnId[$i]-div').classList.remove('hidden'); 
                                    else document.getElementById('$columnId[$i]-div').classList.add('hidden');";
                            }
                        } else {
                            $html .= "\n thatValue = document.getElementById('$specialNameExtra[$i]-input').value;";
                            $html .= "\n if (isValue.includes(thatValue)) 
                                document.getElementById('$columnId[$i]-div').classList.remove('hidden'); 
                                else document.getElementById('$columnId[$i]-div').classList.add('hidden');";
                        }
                    } else if ($columnTypeID[$specialNameExtra[$i]] == 'radio') {
                        // Use -radio
                        $html .= "\n
                        $('.$specialNameExtra[$i]-radio').each(function() {
                            thatValue = $(this).val()
                            if($(this).is(':checked')) {
                                if (isValue.includes(thatValue)) 
                                    document.getElementById('$columnId[$i]-div').classList.remove('hidden'); 
                            } else {
                                if (isValue.includes(thatValue)) 
                                    document.getElementById('$columnId[$i]-div').classList.add('hidden');
                            }
                        });";
                    } else if ($columnTypeID[$specialNameExtra[$i]] == 'checkbox') {
                        // Use -checkbox
                        $html .= "\nvar checked = false;
                        $('.$specialNameExtra[$i]-checkbox').each(function() {
                            thatValue = $(this).val();
                            if($(this).is(':checked')) {
                                if (isValue.includes(thatValue)) {
                                    checked = true;
                                    document.getElementById('$columnId[$i]-div').classList.remove('hidden'); 
                                }
                            } else {
                                if (isValue.includes(thatValue)) 
                                    document.getElementById('$columnId[$i]-div').classList.add('hidden');
                            }
                        });";
                    } else {
                        // do nothing
                    }
                }
            }
        }
        $html .= "\n};setTimeout(() => {HTX_frontend_js()}, 500);</script>";
        
        $html .= "\n<input name='tableId' value='$tableId' style='display: none'></p>";

        // Ending form with submit and reset buttons
        $table_name = $wpdb->prefix . 'htx_form_tables';
        $stmt = $link->prepare("SELECT * FROM $table_name WHERE id = ?");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) exit('Something went wrong...');
        while($row = $result->fetch_assoc()) {
            $html .= "\n<p><input type='hidden' name='postForm' value='new'><input type='hidden' id='email-$tableId' name='form-email-$tableId' value='$formEmail'><button onclick='HTX_submit_form($tableId);'>";
            if ($row['registration'] == 1) {
                $html .= "Tilmeld";
            } else {
                $html .= "Indsend";
            }
            $html .= "</button> <button type='reset' name='reset'>Nulstil</button></p></form>";
        }
        $stmt->close();

        // Success handling - Give information via popup window, that the regristration have been saved

        // Returning html code
        return $html;
    }

    // Perform shortcode for participant count
    function HTX_lan_participantCount_function($atts = array()) {
        // Custom connection to database
        $link = database_connection();
        global $wpdb;

        // add to $html, to return it at the end -> It is how to do shortcodes in Wordpress
        $html = HTX_load_standard_frontend();

        // Check and get form from shortcode
        if (!isset($atts['form'])) $tableId = 0; else $tableId = intval($atts['form']);

        // Checking form id
        $table_name = $wpdb->prefix . 'htx_form_tables';
        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE id = ?");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) return "<i>Ups, der gik noget galt.</i>";
        $stmt->close();

        // Get participant count
        $table_name = $wpdb->prefix . 'htx_form_users';
        $stmt = $link->prepare("SELECT tableId FROM `$table_name` WHERE tableId = ?");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) $number = 0;
        else if ($result->num_rows < 0) return "<i>Ups, der gik noget galt.</i>";
        else $number = $result->num_rows;
        $stmt->close();

        if (isset($atts['countdown']) and $atts['countdown'] == 'true' and isset($atts['countdownfrom']) and intval($atts['countdownfrom']) >= 0) {
            $number = intval($atts['countdownfrom'])-$number;

            if ($number < 0) $number = 0;
        } else {
            $atts['countdown'] = 'false';
            $atts['countdownfrom'] = 0;
        }

        if (isset($atts['live']) and $atts['live'] == 'true') {
            $html .= "\n
            <script>setTimeout(function(){
                function liveParticipant() {\n
                    liveParticipantCount(\"".$tableId."\",\"".$atts['countdown']."\",\"".$atts['countdownfrom']."\",\"liveUpdateCount$tableId\")}\n
                    setTimeout(liveParticipant(), 1000);\n
                }, 500);\n
            </script>";
        }

        $html .= "\n<span id='liveUpdateCount$tableId'>$number</span>";

        return $html;

    }
    
?>
