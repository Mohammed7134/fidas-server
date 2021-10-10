<?php
session_start();
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    require_once '../includes/DbOperations.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == "true") {
    if (isset($_POST['searchText'])) {
        $db = new DbOperations();
        // $result = empty($db->getOnePatientAdmissionsForUser($_POST['searchText']));
        // if ($result === false) {
            $response['error'] = false;
            $response['patients'] = $db->getOnePatientAdmissionsForUser($_POST['searchText'], true, $_POST['hospital']);
        // } else {
        //     $response['error'] = true;
        //     $response['message'] = "no patient with such file number";
        // }
    } else {
        $response['error'] = true;
        $response['message'] = "Parameters are missing";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Request not allowed";
}
} else {
    $response['error'] = true;
    $response['message'] = "Not Logged In";
}
echo json_encode($response);