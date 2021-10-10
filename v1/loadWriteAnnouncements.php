<?php
session_start();
$response = array();
if (isset($_SESSION['logged in'])) {
    $logState = $_SESSION['logged in'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logState == "true") {
        require_once '../includes/DbOperations.php';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['hospital'])) {
                $db = new DbOperations();
                $response['error'] = false;
                $response['announcements'] = $db->getAllAnnouncements($_POST['hospital']);
            } else {
                $inputJSON = file_get_contents('php://input');
                $input = json_decode($inputJSON, TRUE); //convert JSON into array
                if (isset($input['title']) && isset($input['content']) && isset($input['date'])) {
                    $db = new DbOperations();
                    $announcement = array();
                    $announcement['title'] = $input['title'];
                    $announcement['content'] = $input['content'];
                    $announcement['date'] = $input['date'];
                    $announcement['author'] = $input['author'];
                    if (!empty($input['photos'])) {
                        $pictures = $input['photos'];
                        $photosPaths = array();
                        $count = 0;
                        foreach ($pictures as $picture) {
                            $count = $count + 1; 
                            $picPath = "images/" . time() . "-" . $count . ".png";
                            $data = str_replace('data:image/png;base64,', '', $picture);
                            $data = str_replace(' ', '+', $data);
                            $pic = base64_decode($data);
                            file_put_contents($picPath, $pic);
                            array_push($photosPaths, $picPath);
                        } 
                        $announcement['photos'] = $photosPaths;
                    }
                    $announcement['category'] = $input['category'];
                    $announcement['hospital'] = $input['hospital'];
                    $announcement['status'] = $input['status'];
                    if ($db->writeAnnouncement($announcement) == OBJECT_CREATED) {
                        $response['error'] = false;
                        $response['message'] = "Successfully added";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Some error occured";
                    }
                }
            }  
        } else {
            $response['error'] = true;
            $response['message'] = "Request not allowed";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Not logged in";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Request is not allowed";
}
echo json_encode($response);