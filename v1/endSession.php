<?php
session_start();
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == "true") {
        $_SESSION['logged in'] = "false";
        session_unset();
        session_destroy();
        $response['error'] = false;
        $response['message'] = 'logged out';
    } else {
        $response['error'] = true;
        $response['message'] = 'Request is not allowed';
    }
} else {
    $response['error'] = true;
    $response['message'] = 'Not Logged In';
}
echo json_encode($response);
