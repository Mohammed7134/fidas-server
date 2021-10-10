<?php
session_start();
require_once '../includes/DbOperations.php';
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == 'true') {
        if (isset($_POST['admissionId'])) {
            $admissionId = $_POST['admissionId'];
            $db = new DbOperations();
            $result = $db->deleteAdmission($admissionId);
            if ($result == OBJECT_CREATED) {
                $response['error'] = false;
                $response['message'] = 'Admission deleted successfully';
            } elseif ($result == OBJECT_ALREADY_EXIST) {
                $response['error'] = true;
                $response['message'] = 'Admission not deleted';
            } elseif ($result == OBJECT_NOT_CREATED) {
                $response['error'] = true;
                $response['message'] = 'Some error occurred';
            }
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