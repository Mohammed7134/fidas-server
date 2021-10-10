<?php
session_start();
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState = 'true') {
        if (isset($_POST['userId'])) {
            require_once '../includes/DbOperations.php';
            $userId = $_POST['userId'];
            $db = new DbOperations();
            $response['error'] = false;
            $response['count'] = $db->countPharmacistAdmissions($userId);
        } else {
            $response['error'] = true;
            $response['message'] = 'Required parameters are missing';
            }
    } else {
        $response['error'] = true;
        $response['message'] = 'Invalid request';
    }
} else {
    $response['error'] = true;
    $response['message'] = 'Not logged in';
}
echo json_encode($response);