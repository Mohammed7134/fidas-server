<?php
session_start();
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == "true") {
            require_once '../includes/DbOperations.php';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['hospital'])) {
                $hospital = $_POST['hospital'];
                if (!empty($hospital)) {
                    $db = new DbOperations();
                    $response['error'] = false;
                    $response['reports'] = $db->getAllReports($hospital);
                } else {
                    $response['error'] = true;
                    $response['message'] = "Hospital was not selected";
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Parameter missing";
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Request not allowed";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Not logged in";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Request is not allowed";
}
echo json_encode($response);