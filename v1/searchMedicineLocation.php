<?php
session_start();
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState = 'true') {
        require_once '../includes/DbOperations.php';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['searchText']) && isset($_POST['hospital']) && isset($_POST['pharmacy'])) {
                $searchText = $_POST['searchText'];
                $hospital = $_POST['hospital'];
                $pharmacy = $_POST['pharmacy'];
                if (!empty($hospital)) {
                    $db = new DbOperations();
                    $response['error'] = false;
                    $response['medicines'] = $db->searchMedicineLocation($searchText, $hospital, $pharmacy);
                } else {
                    $response['error'] = true;
                    $response['message'] = "This database does not exist";
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Parameters missing";
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
