<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['reset-password-submit'])) {
            $selector = $_POST['selector'];
            $validator = $_POST['validator'];
            $password = $_POST['pwd'];
            $passwordRepeat = $_POST['pwd-repeat'];

            if (empty($password) || empty($passwordRepeat)) {
                echo("empty");
                exit();
            } else if ($password != $passwordRepeat) {
                echo("not same");
                exit();
            }
            require_once '../includes/DbAuth.php';
            $currentDate = date("U");

            require_once '../includes/DbAuth.php';
            $db = new DbAuth();

            $db->resetPwdSubmit($selector, $currentDate, $passwordRepeat, $validator);

        } else {
            exit();
        }
    }