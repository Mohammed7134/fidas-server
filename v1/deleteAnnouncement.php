<?php
session_start();
require_once '../includes/DbOperations.php';
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == 'true') {
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE); //convert JSON into array
        if (isset($input['id'])) {
            $id = $input['id'];
            $images = $input['images'];
            $db = new DbOperations();
            $result = $db->deleteAnnouncement($id, $images);
            if ($result == OBJECT_CREATED) {
                $response['error'] = false;
                $response['message'] = 'Announcement deleted successfully';
            } elseif ($result == OBJECT_ALREADY_EXIST) {
                $response['error'] = true;
                $response['message'] = 'Announcement not deleted';
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