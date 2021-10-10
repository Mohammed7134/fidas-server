<?php
session_start();
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    $admin = $_SESSION['admin'];
    require_once '../includes/DbAuth.php';
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == "true" && $admin == "true") {
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE); //convert JSON into array
        if (isset($_POST['hospital'])) {
            $db = new DbAuth();
            $response['error'] = false;
            $response['users'] = $db->getAllUsers($_POST['hospital']);
        } else if (isset($input['accept']) && isset($input['userId']) && isset($input['email'])) {
                $userId = $input['userId'];
                $accept = $input['accept'];
                $email = $input['email'];  
                $db = new DbAuth();
                if ($db->adminAction($accept, $userId)) {
                        $response['error'] = false;
                        $response['message'] = "successfully done";
                        if ($input['accept'] == 'true') {
                            $subject = 'Clinical Activity Application Account Activation';
                            require_once '../emails/activationEmail.php';
                            $message = $_SESSION['actMsg'];
                            $db->sendEmail($email, $subject, $message);
                        }
                } else {
                        $response['error'] = true;
                        $response['message'] = 'This action can not be performed';
                }
            } else if (isset($input['reject']) && isset($input['reportId']) && isset($input['medicineLocations']) && isset($input['place'])) {
                require_once '../includes/DbOperations.php';
                $db = new DbOperations();

                $medicineLocations = $input['medicineLocations'];
                $reject = $input['reject'];
                $reportId = $input['reportId'];
                $place = $input['place'];

                if ($db->reportAction($reject, $reportId, $medicineLocations, $place)) {
                    $response['error'] = false;
                    $response['message'] = 'successfully done';
                } else {
                    $response['error'] = true;
                    $response['message'] = 'This action can not be performed';
                }
        } else {
            $response['error'] = true;
            $response['message'] = 'Parameters are missing';
        }
    } else {
            $response['error'] = true;
            $response['message'] = 'Request not allowed';
    }
} else {
    $response['error'] = true;
    $response['message'] = "Not Logged In";
}
echo json_encode($response);
