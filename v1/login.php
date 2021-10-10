<?php
    require_once '../includes/DbAuth.php';
    require_once '../helper/loginHelper.php';
    $response = array();
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];
            if (emptyInputLogin($username, $password)) {
                $response['error'] = true;
                $response['message'] = 'Log in details are not filled';
            }
            $db = new DbAuth();
            if (isset($db->userLogin($username, $password)['id'])) {
                $response['error'] = false;
                $response['user'] = $db->userLogin($username, $password);
                session_start();
                $_SESSION['logged in'] = "true";
                $_SESSION['admin'] = $response['user']['admin'] == 'true' ? true : false;
            }
            else if ($db->userLogin($username, $password) == ACCOUNT_INACTIVE){
                $response['error'] = true;
                $response['message'] = 'Your account is not yet accepted by admin';
            } else if ($db->userLogin($username, $password) == INVALID_CREDENTIALS) {
                $response['error'] = true;
                $response['message'] = 'Invalid username or password';
            } else if ($db->userLogin($username, $password) == OBJECT_NOT_EXIST) {
                $response['error'] = true;
                $response['message'] = 'Invalid username or password';
            } 
    
        } else {
            $response['error'] = true;
            $response['message'] = 'Parameters are missing';
        }
    
    } else {
        $response['error'] = true;
        $response['message'] = "Request not allowed";
    }
    echo json_encode($response);
