<?php
    session_start();
    require_once '../helper/registerHelper.php';
    $result;
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['email'])) {
            $selector = bin2hex(random_bytes(8));
            $token = random_bytes(32);
            $url = "https://www.farwaniyapharmacist.online/IOSRegistrationForm/v1/setNewPwdPage.php?selector=" . $selector . "&validator=" . bin2hex($token);
            $expires = date('U') + 1800;
            require_once '../includes/DbAuth.php';
            $db = new DbAuth();
            $userEmail = $_POST['email'];
            if (invalidEmail($userEmail) !== false) {
                $response['error'] = true;
                $response['message'] = 'Invalid Email';
            } else {
                if ($db->resetPwdRequest($userEmail, $token, $selector, $expires) === false) {
                    $response['error'] = true;
                    $response['message'] = 'An error happened';
                } else if ($db->resetPwdRequest($userEmail, $token, $selector, $expires) === true) {
                    session_start();
                    require_once '../emails/resetPwdEmail.php';
                    $subject = "Reset Password Request";
                    $message = 
                        $_SESSION['pwdMsg1'] .
                        "<div style='text-align: center'>
                        <a href=" . $url .  ">Confirm email</a>
                        </div>"
                        . $_SESSION['pwdMsg2'];

                    $db->sendEmail($userEmail, $subject, $message);
                    
                    $response['error'] = false;
                    $response['message'] = 'An email will be sent to you shortly to reset your password';
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Unknown error occured';
                }
            }     
        } else {
            $response['error'] = true;
            $response['message'] = "Missing parameters";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Request not allowed";
    }
    echo json_encode($response);