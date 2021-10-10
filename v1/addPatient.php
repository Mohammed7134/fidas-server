<?php
    session_start();
    $response = array();
    if (isset($_SESSION['logged in'])) {
        $logState = $_SESSION['logged in'];
        require_once '../includes/DbOperations.php';
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == "true") {

        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE); //convert JSON into array
        $patient = array();

        if (isset($input['patientInitials']) && isset($input['patientFileNumber']) && isset($input['sex']) && isset($input['dob']) && isset($input['patientAdmissions'])) {

            $patient['patientInitials'] = $input['patientInitials'];
            $patient['patientFileNumber'] = $input['patientFileNumber'];
            $patient['sex'] = $input['sex'];
            $patient['dob'] = $input['dob'];

            $patientAdmission = $input["patientAdmissions"][0];

            $admissionDate = $patientAdmission['admissionDate'];
            $beds = $patientAdmission['beds'];
            $ward = $patientAdmission['ward'];
            $weights = $patientAdmission['weights'];
            $height = $patientAdmission['height'];
            $patientConditions = $patientAdmission['pastMedicalHistory'];
            $patientCurrentConditions = $patientAdmission['presentingComplaints'];
            $pharmacistId = $patientAdmission['pharmacistId'];
            
            $db = new DbOperations();
            $result = $db->createPatientAndOrAdmission($patient, $ward, $weights, $height, $admissionDate, $pharmacistId, $patientConditions, $patientCurrentConditions, $beds, true);
            if ($result == OBJECT_CREATED) {
                $response['error'] = false;
                $response['message'] = 'Patient created successfully';
            } elseif ($result == OBJECT_ALREADY_EXIST) {
                $response['error'] = true;
                $response['message'] = 'Patient already exist';
            } elseif ($result == OBJECT_NOT_CREATED) {
                $response['error'] = true;
                $response['message'] = 'Some error occurred';
            }
            
        } elseif (isset($input['patientFileNumber'])) {

            $beds = $input['beds'];
            $ward = $input['ward'];
            $weights = $input['weights'];
            $height = $input['height'];
            $patientConditions = $input['pastMedicalHistory'];
            $patientCurrentConditions = $input['presentingComplaints'];
            $admissionDate = $input['admissionDate'];
            $pharmacistId = $input['pharmacistId'];
            $patient['patientFileNumber'] = $input['patientFileNumber'];
            
            $db = new DbOperations();
            $result = $db->createPatientAndOrAdmission($patient, $ward, $weights, $height, $admissionDate, $pharmacistId, $patientConditions, $patientCurrentConditions, $beds, false);
            if ($result == OBJECT_CREATED) {
                $response['error'] = false;
                $response['message'] = 'Admission created successfully';
            } elseif ($result == OBJECT_ALREADY_EXIST) {
                $response['error'] = true;
                $response['message'] = 'Admission already exist';
            } elseif ($result == OBJECT_NOT_CREATED) {
                $response['error'] = true;
                $response['message'] = 'Some error occurred';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Parameters are missing';
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