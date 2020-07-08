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
                break;
        }
        echo json_encode($response);
        wp_die();
    }
}

function htx_delete_form() {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['formid'])) {
        if(!current_user_can("manage_options"))
            return;
        $response = new stdClass();
        header('Content-type: application/json');
        try {
            global $wpdb;
            $link = database_connection();
            $table_name = $wpdb->prefix . "htx_form_tables";
            $stmt = $link->prepare('DELETE FROM `' . $table_name . '` WHERE `id`=?');
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param('i', $_POST['formid']);
            $stmt->execute();
            $stmt->close();
            $table_name = $wpdb->prefix . "htx_form";
            $stmt = $link->prepare('DELETE FROM `' . $table_name . '` WHERE `tableId`=?');
            if(!$stmt)
                throw new Exception($link->error);
            $stmt->bind_param('i', $_POST['formid']);
            $stmt->execute();
            $stmt->close();
            $link->close();
            $response->success = true;
        } catch(Exception $e) {
            $response->success = false;
            $response->error = $e->getMessage();
        }
        echo json_encode($response);
        wp_die();
    }
}

?>
