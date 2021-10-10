<?php

function problemInRequiredParameters($required_fields) {
    $request_params = $_REQUEST;
    foreach ($required_fields as $field) {
        //if any required parameter is missing
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            //returning true;
            return true;
        }
    }
    return false;
}

function invalidUsername($username) {
    $result;
    if (!preg_match("/^[A-Z0-9a-z]{2,20}$/", $username)) {
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}
function invalidEmail($email) {
    $result;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}

function invalidPassword($password) {
    $result;
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z]).{8,}$/", $password)) {       
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}