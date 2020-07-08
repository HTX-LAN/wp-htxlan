<?php

function htx_parse_dangerzone_request() {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['postType'])) {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        header('Content-type: application/json');
        switch($_POST['postType']) {
            case 'resetDB':
                try {
                    drop_db();
                    create_db();
                    $response->success = true;
                } catch(Exception $e) {
                    $response->success = false;
                    $response->error = $e->getMessage();
                }
                echo json_encode($response);
                die();
                break;
            case 'downloadParticipants':
                try {
                    $csv = to_csv("htx_form_users");
                    $response->success = true;
                    $response->csv = $csv;
                    $response->filename = "htx_data_" . date("dmY-His") . ".csv";
                } catch(Exception $e) {
                    $response->success = false;
                    $response->error = $e->getMessage();
                }
                echo json_encode($response);
                die();
                break;
        }
    }
}

?>
