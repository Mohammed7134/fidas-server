<?php
session_start();
    $response = array();
    if (isset($_SESSION['logged in'])) {
        $logState = $_SESSION['logged in'];        
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == "true") {
        
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE); //convert JSON into array

            $patientFileNumber = $input["patientFileNumber"];
            $ward = $input['patientAdmissions'][0]['ward'];
            $weights = $input['patientAdmissions'][0]['weights'];
            $height = $input['patientAdmissions'][0]['height'];
            $dischargeDate = $input['patientAdmissions'][0]['dischargeDate'];
            $beds = $input['patientAdmissions'][0]['beds'];
            $patientConditions = $input['patientAdmissions'][0]['pastMedicalHistory'];
            $patientCurrentConditions = $input['patientAdmissions'][0]['presentingComplaints'];
            $balances = $input['patientAdmissions'][0]['balances'];
            $admId = $input['patientAdmissions'][0]['admissionId'];
            $labs = $input['patientAdmissions'][0]['labs'];
            $medicines = $input['patientAdmissions'][0]['medicines'];

            require_once '../includes/DbOperations.php';
            $db = new DbOperations();
            $result = $db->updateAdmission($patientFileNumber, $admId, $ward, $height, $dischargeDate, $weights, $patientConditions, $patientCurrentConditions, $beds, $balances, $labs, $medicines);

            if ($result == OBJECT_CREATED) {

                $response['error'] = false;
                $response['message'] = 'Admission updated successfully';

            } elseif ($result == OBJECT_ALREADY_EXIST) {

                $response['error'] = true;
                $response['message'] = 'Admission already exist';

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