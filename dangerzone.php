<?php

function htx_parse_dangerzone_request() {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['postType'])) {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        switch($_POST['postType']) {
            case 'resetDB':
                header('Content-type: application/json');
                try {
                    drop_db();
                    create_db();
                    $response->success = true;
                    echo json_encode($response);
                } catch(Exception $e) {
                    $response->success = false;
                    $response->error = $e->getMessage();
                    echo json_encode($response);
                }
                die();
                break;
        }
    }
}

?>
