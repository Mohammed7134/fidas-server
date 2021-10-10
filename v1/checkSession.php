<?php
session_start();
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && $logState == "true") {   
        if (session_status() === PHP_SESSION_NONE) {
            $response['error'] = true;
            $response['message'] = 'LOGOUT';
        } else {
            $response['error'] = false;
            $response['message'] = 'LOGIN';
        }
    }
} else {
    $response['error'] = true;
    $response['message'] = 'SESSION NOT SET';
}
echo json_encode($response);