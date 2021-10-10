<?php
session_start();
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == "true") {
        if (isset($_POST['searchText'])) {
            require_once '../includes/DbOperations.php';
            $db = new DbOperations();
            $response['error'] = false;
            $response['medicines'] = $db->searchMedicine($_POST['searchText']);
        } else {
            $response['error'] = true;
            $response['message'] = "problem 1";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "problem 3";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Not Logged In";
}
echo json_encode($response);