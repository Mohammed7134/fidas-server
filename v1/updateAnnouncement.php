<?php
session_start();
    $response = array();
    if (isset($_SESSION['logged in'])) {
        $logState = $_SESSION['logged in'];        
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == "true") {
        
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE); //convert JSON into array

        $id = $input['id'];
        $status = $input['status'];


        require_once '../includes/DbOperations.php';
            $db = new DbOperations();
            $result = $db->updateAnnouncement($id, $status);

            if ($result == OBJECT_CREATED) {

                $response['error'] = false;
                $response['message'] = 'Announcement updated successfully';

            } elseif ($result == OBJECT_ALREADY_EXIST) {

                $response['error'] = true;
                $response['message'] = 'Announcement already exist';

            } elseif ($result == OBJECT_NOT_CREATED) {

                $response['error'] = true;
                $response['message'] = 'Some error occurred';
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