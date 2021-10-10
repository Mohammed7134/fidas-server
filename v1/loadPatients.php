<?php
    session_start();
    $response = array();
    if (isset($_SESSION['logged in'])) {
        $logState = $_SESSION['logged in'];
        require_once '../includes/DbOperations.php';
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == "true") {    
        if (isset($_POST['userId'])) {
            $db = new DbOperations();
            $result = !empty($db->getAllPatientAdmissionsForUser($_POST['userId']));
               if ($result == true) {
                    $response['error'] = false;
                    $response['patients'] = $db->getAllPatientAdmissionsForUser($_POST['userId']);
               } else {
                   $response['error'] = true;
                   $response['message'] = 'Empty patients list';
               }
                
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
    $json = json_encode($response);
    if ($json) {
        echo $json;
    } else {
        $response['error'] = true;
        $response['message'] = json_last_error_msg();
        echo $response;
    }