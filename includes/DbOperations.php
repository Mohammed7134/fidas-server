<?php
    class DbOperations {
        private $conn;
        function __construct() {
            require_once dirname(__FILE__) . '/Constants.php';
            require_once dirname(__FILE__) . '/DbConnect.php';
            // opening db connection
            $db = new DbConnect();
            $this->conn = $db->connect();
        }

       
    public function createPatientAndOrAdmission($patient, $ward, $weights, $height, $admissionDate, $pharmacistId, $patientConditions, $patientCurrentConditions, $beds, $createPatient) {
        if ($createPatient) {
            if (!$this->isPatientExist($patient['patientFileNumber'])) {
                $dobString = date("Y-m-d H:i:s", $patient['dob']);
                $stmt = $this->conn->prepare("INSERT INTO patients (patientInitials, patientFileNumber, sex, dob) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $patient['patientInitials'], $patient['patientFileNumber'], $patient['sex'], $dobString);
                if ($stmt->execute()) {
                    $uid = $this->retreivePatientId($patient['patientFileNumber']);
                    $this->createPatientAdmission($uid, $ward, $weights, $height, $admissionDate, $pharmacistId, $patientConditions, $patientCurrentConditions, $beds);
                    return OBJECT_CREATED;
                } else {
                    $stmt->execute();
                    printf("Error: %s.\n", $stmt->error);
                    return OBJECT_NOT_CREATED;
                }
            } else {
                return OBJECT_ALREADY_EXIST;
            }
        }
        else {
            $uid = $this->retreivePatientId($patient['patientFileNumber']);
            return $this->createPatientAdmission($uid, $ward, $weights, $height, $admissionDate, $pharmacistId, $patientConditions, $patientCurrentConditions, $beds);
        }
    }
    private function isPatientExist($patientFileNumber) {
        $stmt = $this->conn->prepare("SELECT patientId FROM patients WHERE patientFileNumber = ?");
        $stmt->bind_param("s", $patientFileNumber);
        $stmt->execute();
        $stmt->store_result();
        return $stmt-> num_rows > 0;
    }

///creating admission part

    private function createPatientAdmission($patientId, $ward, $weights, $height, $admissionDate, $pharmacistId, $patientPastConditions, $patientCurrentConditions, $beds) {
        $admissionDateString = date("Y-m-d H:i:s", $admissionDate);
        $stmt = $this->conn->prepare("INSERT INTO patient_admissions (ward, height, admissionDate, pharmacistId, patientId) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisii", $ward, $height, $admissionDateString, $pharmacistId, $patientId);
        if ($stmt->execute()) {
            $admissionId = $this->retreiveFirstAdmissionId($patientId, $admissionDateString);
                foreach ($patientPastConditions as $condition) {
                    $this->createPatientCondition($admissionId, $condition, "past_medical_history");
                }
                foreach ($weights as $weight) {
                    $this->createWeight($admissionId, $weight);
                }
                foreach ($patientCurrentConditions as $condition) {
                    $this->createPatientCondition($admissionId, $condition, "current_conditions");
                }
                foreach ($beds as $bed) {
                    $this->createBed($admissionId, $bed);
                }
                return OBJECT_CREATED;
        } else {
            $stmt->execute();
            printf("Error: %s.\n", $stmt->error);
            return OBJECT_NOT_CREATED;
        }
    }

   

    private function retreivePatientId($patientFileNumber) {
        $stmt = $this->conn->prepare("SELECT patientId FROM patients WHERE patientFileNumber = ?");
        $stmt->bind_param("s", $patientFileNumber);
        $stmt->execute();
        $stmt->bind_result($uid);
        $stmt->fetch();
        return $uid;
    }

    private function retreiveFirstAdmissionId($patientId, $admissionDate) {
        $stmt = $this->conn->prepare("SELECT admissionId FROM patient_admissions WHERE patientId = ? AND admissionDate = ?");
        $stmt->bind_param("is", $patientId, $admissionDate);
        $stmt->execute();
        $stmt->bind_result($uid);
        $stmt->fetch();
        return $uid;
    }

    private function createWeight($admissionId, $weight) {
            $weightDateString = date("Y-m-d H:i:s", $weight['date']);
            $stmt = $this->conn->prepare("INSERT INTO weights (admissionId, date, value) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $admissionId, $weightDateString, $weight['value']);
            $stmt->execute();
    }
    private function createBalance($admissionId, $balance) {
        $balanceDateString = date("Y-m-d H:i:s", $balance['date']);
        if ($balance['sign'] = '-'){
            $value = 0 - $balance['value'];
        } else {
            $value = $balance['value'];
        }
        $stmt = $this->conn->prepare("INSERT INTO balances (admissionId, date, value) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $admissionId, $$balanceDateString, $value);
        $stmt->execute();
    }

    private function createPatientCondition($admissionId, $patientCondition, $table) {
        if (!$this->isConditionExist($admissionId, $patientCondition, $table)) {
            $stmt = $this->conn->prepare("INSERT INTO $table (admissionId, conditionName) VALUES (?, ?)");
            $stmt->bind_param("is", $admissionId, $patientCondition);
            $stmt->execute();
        }
    }
    private function isConditionExist($admissionId, $condition, $table) {
        $stmt = $this->conn->prepare("SELECT conditionName FROM $table WHERE admissionId = ? AND conditionName = ?");
        $stmt->bind_param("is", $patientId, $condition);
        $stmt->execute();
        $stmt->store_result();
        return $stmt-> num_rows > 0;
    }

    private function createBed($admissionId, $bed) {
        $bedDate =  date("Y-m-d H:i:s", $bed['date']);
        $bedNumber = $bed['name'];
        if (!$this->isBedExist($admissionId, $bedNumber, $bedDate)) {
            $stmt = $this->conn->prepare("INSERT INTO beds (admissionId, bedDate, bedNumber) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $admissionId, $bedDate, $bedNumber);
            $stmt->execute();
        }
    }
    private function isBedExist($admissionId, $bedNumber, $bedDate) {
        $stmt = $this->conn->prepare("SELECT bedNumber FROM beds WHERE admissionId = ? AND bedNumber = ? AND bedDate = ?");
        $stmt->bind_param("iss", $admissionId, $bedNumber, $bedDate);
        $stmt->execute();
        $stmt->store_result();
        return $stmt-> num_rows > 0;
    }

    private function createLFTLab($admissionId, $lab) {
        $resultDateString = date("Y-m-d H:i:s", $lab['date']);
        $stmt = $this->conn->prepare("INSERT INTO LFT (ALT, Bili, AlkPhos, GGT, Alb, Prot, AST, resultDate, admissionId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiiiisi", $lab['values']['ALT'], $lab['values']['Bili'], $lab['values']['AlkPhos'], $lab['values']['GGT'], $lab['values']['Alb'], $lab['values']['Prot'], $lab['values']['AST'], $resultDateString, $admissionId);
        $stmt->execute();
    }
    private function createRFTLab($admissionId, $lab) {
        $resultDateString = date("Y-m-d H:i:s", $lab['date']);
        $stmt = $this->conn->prepare("INSERT INTO RFT (Na, K, Ca, CorrectedCa, Urea, Gluc, Creat, resultDate, admissionId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiiiisi", $lab['values']['Na'], $lab['values']['K'], $lab['values']['Ca'], $lab['values']['CorrectedCa'], $lab['values']['Urea'], $lab['values']['Gluc'], $lab['values']['Creat'], $resultDateString, $admissionId);
        $stmt->execute();
    }

    private function createMedicine($admissionId, $medicine) {
        $startDateString = date("Y-m-d H:i:s", $medicine['startDate']);
        if (isset($medicine['stopDate'])) {
            $stopDateString = date("Y-m-d H:i:s", $medicine['stopDate']);
        } else {
            $stopDateString = date("Y-m-d H:i:s", 946684800);
        }
        $stmt1 = $this->conn->prepare("INSERT INTO medicines (medicineId, listType, indication, name, form, dose, unit, route, frequency, startDate, stopDate, admissionId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt1->bind_param("sssssisssssi", $medicine['medicineId'], $medicine['listType'], $medicine['indication'], $medicine['name'], $medicine['form'], $medicine['dose'], $medicine['unit'], $medicine['route'], $medicine['frequency'], $startDateString, $stopDateString, $admissionId);
        $stmt1->execute();
        foreach($medicine['thingsToMonitor'] as $thingToMonitor) {
            $stmt2 = $this->conn->prepare("INSERT INTO things_to_monitor (medicineId, thingToMonitor) VALUES (?, ?)");
            $stmt2->bind_param("ss", $medicine['medicineId'], $thingToMonitor);
            $stmt2->execute();
        }
    }
////loading patients part

    public function getAllPatientAdmissionsForUser($userId)
    {
        $stmt = $this->conn->prepare("SELECT admissionId, ward, height, admissionDate, dischargeDate, pharmacistId, pa.patientId, dob, sex, patientInitials, patientFileNumber FROM patient_admissions AS pa
        INNER JOIN
            patients AS pts
            ON pts.patientId = pa.patientId
            WHERE pharmacistId = ? ORDER BY admissionDate DESC LIMIT 499");
        $stmt->bind_param("i", $userId);
        $result = $this->loadPatients($stmt);
        return $result;
    }

    public function getOnePatientAdmissionsForUser($patientFileNumber, $search, $hospital)
    {
        if ($search) {
            $comp = "LIKE";
            $searchText = $patientFileNumber."%";
        } else {
            $comp = "=";
            $searchText = $patientFileNumber;
        }
        $stmt = $this->conn->prepare("SELECT admissionId, ward, height, admissionDate, dischargeDate, pharmacistId, pa.patientId, dob, sex, patientInitials, patientFileNumber FROM patient_admissions AS pa
        INNER JOIN
            patients AS pts
            ON pts.patientId = pa.patientId
            INNER JOIN 
            users AS usrs
            ON usrs.id = pa.pharmacistId
            WHERE usrs.hospital = ? AND patientFileNumber $comp ? ORDER BY admissionDate DESC");
        $stmt->bind_param("ss", $hospital, $searchText);
        $result = $this->loadPatients($stmt);
        return $result;
    }

    private function loadPatients($stmt) {
        $stmt->execute();
        $stmt->bind_result($admissionId, $ward, $height, $admissionDate, $dischargeDate, $pharmacistId, $patientId, $dob, $sex, $patientInitials, $patientFileNumber);
        $patients = array();
        $patientIds = array();
        while($stmt->fetch()) {
            //if patients does not contain patientId = patientId
            if (!in_array($patientId, $patientIds)) {
                $patient = array();
                $patient['patientId'] = $patientId;
                $patient['dob'] = strtotime($dob);
                $patient['sex'] = $sex;
                $patient['patientInitials'] = $patientInitials;
                $patient['patientFileNumber'] = $patientFileNumber;
                $patient['patientAdmissions'] = array();
                $patientAdmission = array();
                $patientAdmission['admissionId'] = $admissionId;
                $patientAdmission['ward'] = $ward;
                $patientAdmission['height'] = $height;
                $patientAdmission['admissionDate'] = strtotime($admissionDate);
                $patientAdmission['dischargeDate'] = strtotime($dischargeDate);
                $patientAdmission['pharmacistId'] = $pharmacistId;
                array_push($patientIds, $patientId);
                array_push($patient['patientAdmissions'], $patientAdmission);
                array_push($patients, $patient);
            } else {
                //find patient with patientId in patients array and append his patientAdmissions array with the new admission
                foreach($patients as &$pt) {
                    if ($pt['patientId'] == $patientId) {
                        $patientAdmission = array();
                        $patientAdmission['admissionId'] = $admissionId;
                        $patientAdmission['ward'] = $ward;
                        $patientAdmission['height'] = $height;
                        $patientAdmission['admissionDate'] = strtotime($admissionDate);
                        $patientAdmission['dischargeDate'] = strtotime($dischargeDate);
                        $patientAdmission['pharmacistId'] = $pharmacistId;
                        array_push($pt['patientAdmissions'], $patientAdmission);
                    }
                }
            }
        }
        foreach($patients as &$pt) {
            foreach($pt['patientAdmissions'] as &$admission) {
                $admission['pastMedicalHistory'] = $this->getConditions($admission['admissionId'], "past_medical_history");
                $admission['beds'] = $this->getBeds($admission['admissionId']);
                $admission['balances'] = $this->getDateValuePairs($admission['admissionId'], "balances");
                $admission['presentingComplaints'] = $this->getConditions($admission['admissionId'], "current_conditions");
                $admission['weights'] = $this->getDateValuePairs($admission['admissionId'], "weights");
                $LFTs = $this->getLFT($admission['admissionId']);
                $RFTs = $this->getRFT($admission['admissionId']);
                $admission['labs'] = array_merge($LFTs,$RFTs);
                $admission['medicines'] = $this->getMedicines($admission['admissionId']);
            }
        }
        return $patients;
    }


    private function getBeds($admissionId) {
        $stmt2 = $this->conn->prepare("SELECT bedDate, bedNumber FROM beds WHERE admissionId = ?");
        $stmt2->bind_param("i", $admissionId);
        $stmt2->execute();
        $stmt2->bind_result($bedDateColumn, $bedNumberColumn);
        $beds = array();
        while($stmt2->fetch()) {
            $bed = array();
            $bed['date'] = strtotime($bedDateColumn);
            $bed['name'] = $bedNumberColumn;
            array_push($beds, $bed);
        }
        return $beds;
    }

    private function getConditions($admissionId, $table) {
        $stmt2 = $this->conn->prepare("SELECT conditionName FROM $table WHERE admissionId = ?");
        $stmt2->bind_param("i", $admissionId);
        $stmt2->execute();
        $stmt2->bind_result($conditionsColumn);
        $conditions = array();
        while($stmt2->fetch()) {
            array_push($conditions, $conditionsColumn);
        }
        return $conditions;
    }
    private function getRFT($admissionId) {
        $stmt2 = $this->conn->prepare("SELECT id, Na, K, Ca, CorrectedCa, Urea, Gluc, Creat, resultDate FROM RFT WHERE admissionId = ?");
        $stmt2->bind_param("i", $admissionId);
        $stmt2->execute();
        $stmt2->bind_result($id, $Na, $K, $Ca, $CorrectedCa, $Urea, $Gluc, $Creat, $resultDate);
        $RFTs = array();
        while($stmt2->fetch()) {
            $Lab = array();
            $Lab['values'] = array();
            $Lab['values']['Na'] = $Na;
            $Lab['values']['K'] = $K;
            $Lab['values']['Ca'] = $Ca;
            $Lab['values']['CorrectedCa'] = $CorrectedCa;
            $Lab['values']['Urea'] = $Urea;
            $Lab['values']['Gluc'] = $Gluc;
            $Lab['values']['Creat'] = $Creat;
            $Lab['date'] = strtotime($resultDate);
            $Lab['type'] = "RFT";
            array_push($RFTs, $Lab);
        }
        return $RFTs;
    }
    private function getLFT($admissionId) {
        $stmt2 = $this->conn->prepare("SELECT id, ALT, Bili, AlkPhos, GGT, Alb, Prot, AST, resultDate FROM LFT WHERE admissionId = ?");
        $stmt2->bind_param("i", $admissionId);
        $stmt2->execute();
        $stmt2->bind_result($id, $ALT, $Bili, $AlkPhos, $GGT, $Alb, $Prot, $AST, $resultDate);
        $LFTs = array();
        while($stmt2->fetch()) {
            $Lab = array();
            $Lab['values'] = array();
            $Lab['values']['ALT'] = $ALT;
            $Lab['values']['Bili'] = $Bili;
            $Lab['values']['AlkPhos'] = $AlkPhos;
            $Lab['values']['GGT'] = $GGT;
            $Lab['values']['Alb'] = $Alb;
            $Lab['values']['Prot'] = $Prot;
            $Lab['values']['AST'] = $AST;
            $Lab['date'] = strtotime($resultDate);
            $Lab['type'] = "LFT";
            array_push($LFTs, $Lab);
        }
        return $LFTs;
    }
    private function getMedicines($admissionId) {
        $stmt = $this->conn->prepare("SELECT medicineId, listType, indication, name, form, dose, unit, route, frequency, startDate, stopDate FROM medicines WHERE admissionId = ?");
        $stmt->bind_param("i", $admissionId);
        $stmt->execute();
        $stmt->bind_result($medicineId, $listType, $indication,	$name,	$form,	$dose,	$unit,	$route,	$frequency,	$startDate,	$stopDate);
        $medicines = array();
        while($stmt->fetch()) {
            $medicine = array();
            $medicine['medicineId'] = $medicineId;
            $medicine['listType'] = $listType;
            $medicine['indication'] = $indication;
            $medicine['name'] = $name;
            $medicine['form'] = $form;
            $medicine['dose'] = $dose;
            $medicine['unit'] = $unit;
            $medicine['route'] = $route;
            $medicine['frequency'] = $frequency;
            $medicine['startDate'] = strtotime($startDate);
            $medicine['stopDate'] = strtotime($stopDate);
            array_push($medicines, $medicine);
        }
        foreach($medicines as &$medicine) {
            $stmt2 = $this->conn->prepare("SELECT thingToMonitor FROM things_to_monitor WHERE medicineId = ?");
            $stmt2->bind_param("s", $medicine['medicineId']);
            $stmt2->execute();
            $stmt2->bind_result($thingToMonitor);
            $medicine['thingsToMonitor'] = array();
            while($stmt2->fetch()) {
                array_push($medicine['thingsToMonitor'], $thingToMonitor);
            }
        }
        return $medicines;
    }
    private function getDateValuePairs($admissionId, $table) {
        $stmt2 = $this->conn->prepare("SELECT date, value FROM $table WHERE admissionId = ?");
        $stmt2->bind_param("i", $admissionId);
        $stmt2->execute();
        $stmt2->bind_result($datesColumn, $valuesColumn);
        $dateValuePairs = array();
        while($stmt2->fetch()) {
            $arr = array();
            $arr['date'] = strtotime($datesColumn);
            $arr['value'] = $valuesColumn;
            array_push($dateValuePairs, $arr);
        }
        return $dateValuePairs;
    }
    ////adding new admission
   /////updating admission
   public function updateAdmission($patientFileNumber, $admissionId, $ward, $height, $dischargeDate, $weights, $patientConditions, $patientCurrentConditions, $beds, $balances, $labs, $medicines) {
        if ($this->isPatientExist($patientFileNumber)) {
            $dischargeDateString = date("Y-m-d H:i:s", $dischargeDate);
            $stmt = $this->conn->prepare("UPDATE patient_admissions SET ward = ?, height = ?, dischargeDate = ? WHERE admissionId = ?");
            $stmt->bind_param("sisi", $ward, $height, $dischargeDateString, $admissionId);
            $stmt->execute();
            //updating past_medical_history
            $stmt2 = $this->conn->prepare("DELETE FROM past_medical_history WHERE admissionId = ?");
            $stmt2->bind_param("i", $admissionId);
            $stmt2->execute();
            foreach($patientConditions as $condition) {
                $this->createPatientCondition($admissionId, $condition, "past_medical_history");
            }
            //updating beds
            $stmt3 = $this->conn->prepare("DELETE FROM beds WHERE admissionId = ?");
            $stmt3->bind_param("i", $admissionId);
            $stmt3->execute();
            foreach($beds as $bed) {
                $this->createBed($admissionId, $bed);
            }
            //updating current_conditions
            $stmt4 = $this->conn->prepare("DELETE FROM current_conditions WHERE admissionId = ?");
            $stmt4->bind_param("i", $admissionId);
            $stmt4->execute();
            foreach($patientCurrentConditions as $condition) {
                $this->createPatientCondition($admissionId, $condition, "current_conditions");
            }
            //updating balances
            $stmt5 = $this->conn->prepare("DELETE FROM balances WHERE admissionId = ?");
            $stmt5->bind_param("i", $admissionId);
            $stmt5->execute();
            foreach($balances as $balance) {
                $this->createBalance($admissionId, $balance);
            }
            //updating weights
            $stmt6 = $this->conn->prepare("DELETE FROM weights WHERE admissionId = ?");
            $stmt6->bind_param("i", $admissionId);
            $stmt6->execute();
            foreach($weights as $weight) {
                $this->createWeight($admissionId, $weight);
            }
            //updating Labs
            $stmt7 = $this->conn->prepare("DELETE FROM RFT WHERE admissionId = ?");
            $stmt7->bind_param("i", $admissionId);
            $stmt7->execute();
            $stmt8 = $this->conn->prepare("DELETE FROM LFT WHERE admissionId = ?");
            $stmt8->bind_param("i", $admissionId);
            $stmt8->execute();
            foreach($labs as $lab) {
                if ($lab['type'] == "RFT") {
                    $this->createRFTLab($admissionId, $lab);
                } elseif ($lab['type'] == "LFT") {
                    $this->createLFTLab($admissionId, $lab);
                }
            }

            //updating medicines
            $stmt9 = $this->conn->prepare("DELETE FROM medicines WHERE admissionId = ?");
            $stmt9->bind_param("i", $admissionId);
            $stmt9->execute();
            foreach($medicines as $medicine) {
                $this->createMedicine($admissionId, $medicine);
            }
        } else {
            return OBJECT_NOT_CREATED;
        }
    }

    public function deleteAdmission($admissionId) {
        $stmt = $this->conn->prepare(" SELECT patientId FROM patient_admissions WHERE admissionId = ? ");
        $stmt->bind_param("i", $admissionId);
        $stmt->execute();
        $stmt->bind_result($patientId);
        $stmt->fetch();
        $stmt->close();
        $stmt2 = $this->conn->prepare(" SELECT * FROM patient_admissions WHERE patientId = ? ");
        $stmt2->bind_param("i", $patientId);
        $stmt2->execute();
        $stmt2->store_result();
        if ($stmt2-> num_rows > 1) {
            $stmt3 = $this->conn->prepare("DELETE FROM patient_admissions WHERE admissionId = ?");
            $stmt3->bind_param("i", $admissionId);
            if ($stmt3->execute()) {
                return OBJECT_CREATED;
            } else {
                return OBJECT_NOT_CREATED;
            }
        } else {
            $stmt4 = $this->conn->prepare("DELETE FROM patients WHERE patientId = ?");
            $stmt4->bind_param("i", $patientId);
            if ($stmt4->execute()) {
                return OBJECT_CREATED;
            } else {
                return OBJECT_NOT_CREATED;
            }
        }
    }


    function searchMedicine($medicineName) {
        $medName = "%".$medicineName."%";
        $stmt = $this->conn->prepare("SELECT name FROM medicines_list WHERE name LIKE ? ORDER BY name DESC LIMIT 30");
        $stmt->bind_param("s", $medName);
        $stmt->execute();
        $stmt->bind_result($medColumn);
        $meds = array();
        while($stmt->fetch()) {
            array_push($meds, $medColumn);
        }
        return $meds;
    }
    function countPharmacistAdmissions($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(admissionId) FROM patient_admissions WHERE pharmacistId = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        return $count;
    }
                                     
     function searchMedicineLocation($medicineName, $hospital, $pharmacy) {
         $medName = "%".$medicineName."%";
         $stmt = $this->conn->prepare("SELECT id, name, pharmacy, location1, location2, location3, location4 FROM $hospital WHERE name LIKE ? AND pharmacy = ? ORDER BY name DESC LIMIT 30");
         $stmt->bind_param("ss", $medName, $pharmacy);
         $stmt->execute();
         $stmt->bind_result($id, $medName, $pharmacy, $location1, $location2, $location3, $location4);
         $meds = array();
         while($stmt->fetch()) {
             $medicine = array();
             $medicine['id'] = $id;
             $medicine['name'] = $medName;
             $medicine['pharmacy'] = $pharmacy;
             $medicine['locations']['location1'] = $location1;
             $medicine['locations']['location2'] = $location2;
             $medicine['locations']['location3'] = $location3;
             $medicine['locations']['location4'] = $location4;
             array_push($meds, $medicine);
         }
         return $meds;
     }
     function postReport($report) {
         $time = date("Y-m-d H:i:s", $report['time']);
         $converted_rev = $report['reviewed'] ? 'true' : 'false';
         $stmt = $this->conn->prepare("INSERT INTO reports (medicineId, time, reviewed, hospital) VALUES (?, ?, ?, ?)");
         $stmt->bind_param("isss", $report['medicineId'], $time, $converted_rev, $report['hospital']);
         if ($stmt->execute()) {
             return OBJECT_CREATED;
         } else {
             $stmt->execute();
             printf("Error: %s.\n", $stmt->error);
             return OBJECT_NOT_CREATED;
         }
     }
     function getAllReports($hospital) {
         $stmt = $this->conn->prepare("SELECT lc.id, name, pharmacy, location1, location2, location3, location4, rp.id, reviewed, time, hospital FROM reports AS rp
         INNER JOIN
            $hospital AS lc
             ON lc.id = rp.medicineId
             ORDER BY time DESC");
         $stmt->execute();
         $stmt->bind_result($medId, $medicineName, $pharmacy, $location1, $location2, $location3, $location4, $rpId, $reviewed, $time, $hospital);
         $reports = array();
         while($stmt->fetch()) {
            $medicine = array();
            $medicine['id'] = $medId;
            $medicine['name'] = $medicineName;
            $medicine['pharmacy'] = $pharmacy;
            $medicine['locations']['location1'] = $location1;
            $medicine['locations']['location2'] = $location2;
            $medicine['locations']['location3'] = $location3;
            $medicine['locations']['location4'] = $location4;
            $report = array();
            $report['id'] = $rpId;
            $report['reviewed'] = $reviewed == 'true' ? true : false;
            $report['time'] = strtotime($time);
            $report['hospital'] = $hospital;
            $report['medicine'] = $medicine;
             
            array_push($reports, $report);
        }
        return $reports;
     }
                                      
    function reportAction($rejected, $reportId, $medicineLocations, $place) {
      if ($rejected == 'false') {
          $locations = $medicineLocations['locations'];
          $location1 = $locations['location1'];
          $location2 = $locations['location2'];
          $location3 = $locations['location3'];
          $location4 = $locations['location4'];
          $medicineId = $medicineLocations['id'];
          $stmt = $this->conn->prepare("UPDATE $place SET location1 = ?, location2 = ?, location3 = ?, location4 = ? WHERE id = ?;");
          $stmt->bind_param("ssssi", $location1, $location2, $location3, $location4, $medicineId);
          return $stmt->execute();
      } else {
          $stmt = $this->conn->prepare("DELETE FROM reports WHERE id = ?;");
          $stmt->bind_param("i", $reportId);
          return $stmt->execute();
      }
    }
    function getAllAnnouncements($hosp) {
        $stmt = $this->conn->prepare("SELECT id, title, content, category, hospital, date, status, photos FROM announcements WHERE hospital = ? ORDER BY date");
        $stmt->bind_param("s", $hosp);
        $stmt->execute();
        $stmt->bind_result($id, $title, $content, $category, $hospital, $date, $status, $photosPaths);
        $announcements = array();
        while($stmt->fetch()) {
            $announcement = array();
            $announcement['id'] = $id;
            $announcement['title'] = $title;
            $announcement['content'] = $content;
            $announcement['category'] = $category;
            $announcement['hospital'] = $hospital;
            $announcement['date'] = strtotime($date);
            $announcement['status'] = $status;
            $photosPathsArray = explode(',', $photosPaths);
            $announcement['photos'] = $photosPathsArray;
            array_push($announcements, $announcement);
        }
        return $announcements;
    }
    function writeAnnouncement($announcement) {
        $time = date("Y-m-d H:i:s", $announcement['date']);
        if (!empty($announcement['photos'])) {
            $photosPaths = implode(",", $announcement['photos']);
        } else {
            $photosPaths = "";
        }
        $stmt = $this->conn->prepare("INSERT INTO announcements (title, content, photos, category, hospital, date, author) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $announcement['title'], $announcement['content'], $photosPaths, $announcement['category'], $announcement['hospital'], $time, $announcement['author']);
        if ($stmt->execute()) {
            $stmt1 = $this->conn->prepare("SELECT deviceToken FROM users WHERE devicePlatform = 'IOS' AND hospital = ?");
            $stmt1->bind_param("s", $announcement['hospital']);
            if ($stmt1->execute()) {
                $stmt1->bind_result($tokens);                    
                $ch = curl_init();
                while($stmt1->fetch()) {
                    if (!empty($tokens)) {
                    $data =
                    array(
                        "recipient" => 
                            array(
                                "transportType" => "apns",
                                "deviceToken" => $tokens
                            ),
                        "notification" => 
                            array(
                                "title" => $announcement['title']
                            ), 
                            "apns" => array(
                                "content-available" => 1,
                                "aps" => array(
                                  "alert" => array(
                                    "title" => $announcement['title'],
                                    "body" => $announcement['content']
                                  ),
                                  "class" => "T"
                                )
                            )
                        );               
                    curl_setopt_array($ch,
                        array(
                            CURLOPT_URL => "https://rest.ably.io/push/publish",
                            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
                            CURLOPT_USERPWD => "8Ju7-w.vGy0Hg:MgU3tDzbt_bwAQbM",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_HTTPHEADER => array('Content-type: application/json'),
                            CURLOPT_POST => true,
                            // The data to transfer with the response.
                            CURLOPT_POSTFIELDS => json_encode($data),
                            CURLOPT_RETURNTRANSFER => true,
                        )
                    );
                    $result = curl_exec($ch);
                    echo($result);
                }
                }
                curl_close($ch);
                // return OBJECT_CREATED;
            }
        } else {
            $stmt->execute();
            printf("Error: %s.\n", $stmt->error);
            return OBJECT_NOT_CREATED;
        }
    }
    function updateAnnouncement($id, $status) {
        $stmt = $this->conn->prepare("UPDATE announcements SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            return OBJECT_CREATED;
        }
    }
    function deleteAnnouncement($id, $images) {
        $stmt = $this->conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);
        foreach ($images as $img) {
            $filename = $img;
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
        if ($stmt->execute()) {
            return OBJECT_CREATED;
        } else {
            return OBJECT_ALREADY_EXIST;
        }
        
    }
}
