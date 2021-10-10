<?php
session_start();
require_once '../includes/DbAuth.php';
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == "true") {
        require_once '../includes/DbOperations.php';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON, TRUE); //convert JSON into array
            $report = array();
            if (isset($input['medicine']) && isset($input['time']) && isset($input['reviewed'])) {
                $report['reviewed'] = $input['reviewed'];
                $report['time'] = $input['time'];
                $report['medicineId'] = $input['medicine']['id'];
                $report['hospital'] = $input['hospital'];
                $db = new DbOperations();
                $result = $db->postReport($report);
                if ($result == OBJECT_CREATED) {
                    $response['error'] = false;
                    $response['message'] = "successfull reporting";
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Missing parameter";
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