<?php
class DbAuth
{
    private $conn;

    function __construct()
    {
        require_once dirname(__FILE__) . '/Constants.php';
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
    public function __destruct()
    {
        $this->conn->close();
    }

    public function userLogin($username, $password)
    {
        $usernameExists = $this->isUserExist($username, $username);
        if ($usernameExists === OBJECT_NOT_EXIST) {
            return OBJECT_NOT_EXIST;
        } else if (isset($usernameExists['id'])) {
            if ($usernameExists['active'] === 'false') {
                return ACCOUNT_INACTIVE;
            } else if ($usernameExists['active'] === 'true') {
                $storedPassword = $usernameExists['password'];
                $passwordVerify = password_verify($password, $storedPassword);
                if ($passwordVerify === false) {
                    return INVALID_CREDENTIALS;
                } else if ($passwordVerify === true) {
                    $user = array();
                    $user['id'] = $usernameExists['id'];
                    $user['username'] = $usernameExists['username'];
                    $user['email'] = $usernameExists['email'];
                    $user['hospital'] = $usernameExists['hospital'];
                    $user['unit'] = $usernameExists['unit'];
                    $user['admin'] = $usernameExists['admin'] == "true" ? true : false;
                    return $user;
                }
            }
        }
    }

    public function createUser($username, $pass, $email, $hospital, $unit, $user_activation_code, $deviceToken, $devicePlatform)
    {
        if ($this->isUserExist($username, $email) == OBJECT_NOT_CREATED) {
            return OBJECT_NOT_CREATED;
        } else if ($this->isUserExist($username, $email) == OBJECT_NOT_EXIST) {
            $sql = "INSERT INTO users (username, password, email, hospital, unit, code, deviceToken, devicePlatform) VALUES (?, ?, ?, ?, ?, ?, ?, ?);";
            $stmt = mysqli_stmt_init($this->conn);
            if (!mysqli_stmt_prepare($stmt, $sql)) {
                return OBJECT_NOT_CREATED;
            }
            $password = password_hash($pass, PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt, "ssssssss", $username, $password, $email, $hospital, $unit, $user_activation_code, $deviceToken, $devicePlatform);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return OBJECT_CREATED;
        } else {
            return OBJECT_ALREADY_EXIST;
        }
    }
    private function isUserExist($username, $email)
    {
        $sql = "SELECT id, username, password, email, hospital, unit, active, admin, emailVerified FROM users WHERE username = ? OR email = ?;";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return OBJECT_NOT_CREATED;
        }
        $stmt->bind_param("ss", $username, $email);

        if ($stmt->execute()) {
            $stmt->bind_result($id, $uname, $password, $email, $hospital, $unit, $active, $admin, $emailVerified);
            $user = array();
            while ($stmt->fetch()) {
                $user['username'] = $uname;
                $user['id'] = $id;
                $user['password'] = $password;
                $user['admin'] = $admin;
                $user['email'] = $email;
                $user['hospital'] = $hospital;
                $user['unit'] = $unit;
                $user['active'] = $active;
                $user['admin'] = $admin;
                $user['emailVerified'] = $emailVerified;
            }
            return $user;
        } else {
            return OBJECT_NOT_EXIST;
        }
        $stmt->close();
    }

    function verifyEmail($username, $verificationCode)
    {
        $usernameExists = $this->isUserExist($username, $username);
        if ($usernameExists === OBJECT_NOT_EXIST) {
            return OBJECT_NOT_EXIST;
        } else if (isset($usernameExists['emailVerified'])) {
            if ($usernameExists['emailVerified'] === 'false') {
                $stmt = $this->conn->prepare("UPDATE users SET emailVerified = 'true' WHERE active = 'false' AND code = ? AND username = ?");
                $stmt->bind_param("ss", $verificationCode, $username);
                $stmt->execute();
                return ACCOUNT_ACTIVE;
            } else {
                return ACCOUNT_ALREADY_ACTIVE;
            }
        } else {
            return ERROR;
        }
    }

    public function getAllUsers($hosp)
    {
        $stmt = $this->conn->prepare("SELECT id, username, email, hospital, unit, admin FROM users WHERE active = 'false' AND emailVerified = 'true' AND hospital = ?");
        $stmt->bind_param("s", $hosp);
        $stmt->execute();
        $stmt->bind_result($id, $uname, $email, $hospital, $unit, $admin);
        $users = array();
        while ($stmt->fetch()) {
            $user = array();
            $user['id'] = $id;
            $user['username'] = $uname;
            $user['email'] = $email;
            $user['hospital'] = $hospital;
            $user['unit'] = $unit;
            $user['admin'] = $admin == "true" ? true : false;
            array_push($users, $user);
        }
        $stmt->close();
        return $users;
    }

    function adminAction($accept, $userId)
    {
        if ($accept == 'true') {
            $stmt = $this->conn->prepare("UPDATE users SET active = ? WHERE id = ?;");
            $stmt->bind_param("si", $accept, $userId);
            return $stmt->execute();
        } else {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?;");
            $stmt->bind_param("i", $userId);
            return $stmt->execute();
        }
    }

    function resetPwdSubmit($selector, $currentDate, $password, $validator)
    {
        $sql = "SELECT * FROM pwdReset WHERE pwdResetSelector=? AND pwdResetExpires >= ?;";
        $stmt = mysqli_stmt_init($this->conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            header("Location: ../v1/setNewPwdPage1.php?newPwd=error7");
            exit();
        } else {
            mysqli_stmt_bind_param($stmt, "ss", $selector, $currentDate);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (!$row = mysqli_fetch_assoc($result)) {
                header("Location: ../v1/setNewPwdPage1.php?newPwd=error6");
                exit();
            } else {
                $tokenBin = hex2bin($validator);
                $tokenCheck = password_verify($tokenBin, $row['pwdResetToken']);

                if ($tokenCheck === false) {
                    header("Location: ../v1/setNewPwdPage1.php?newPwd=error5");
                    exit();
                } elseif ($tokenCheck === true) {
                    $tokenEmail = $row['pwdResetEmail'];

                    $sql = "SELECT * FROM users WHERE email=?;";
                    $stmt = mysqli_stmt_init($this->conn);
                    if (!mysqli_stmt_prepare($stmt, $sql)) {
                        header("Location: ../v1/setNewPwdPage1.php?newPwd=error4");
                        exit();
                    } else {
                        mysqli_stmt_bind_param($stmt, "s", $tokenEmail);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        if (!$row = mysqli_fetch_assoc($result)) {
                            header("Location: ../v1/setNewPwdPage1.php?newPwd=error3");
                            exit();
                        } else {
                            $sql = "UPDATE users SET password=? WHERE email=?";
                            if (!mysqli_stmt_prepare($stmt, $sql)) {
                                header("Location: ../v1/setNewPwdPage1.php?newPwd=error2");
                                exit();
                            } else {
                                $newHashedPwd = password_hash($password, PASSWORD_DEFAULT);
                                mysqli_stmt_bind_param($stmt, "ss", $newHashedPwd, $tokenEmail);
                                mysqli_stmt_execute($stmt);

                                $sql = "DELETE FROM pwdReset WHERE pwdResetEmail=?;";
                                $stmt = mysqli_stmt_init($this->conn);
                                if (!mysqli_stmt_prepare($stmt, $sql)) {
                                    header("Location: ../v1/setNewPwdPage1.php?newPwd=error1");
                                    exit();
                                } else {
                                    mysqli_stmt_bind_param($stmt, "s", $tokenEmail);
                                    mysqli_stmt_execute($stmt);
                                    header("Location: ../v1/setNewPwdPage1.php?newPwd=passwordUpdated");
                                }
                            }
                        }
                    }
                    mysqli_stmt_close($stmt);
                    mysqli_close($this->conn);
                }
            }
        }
    }

    function resetPwdRequest($email, $token, $selector, $expires)
    {
        $sql = "DELETE FROM pwdReset WHERE pwdResetEmail = ?;";
        $stmt = mysqli_stmt_init($this->conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            return false;
            exit();
        } else {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
        }
        $sql = "INSERT INTO pwdReset (pwdResetEmail, pwdResetSelector, pwdResetToken, pwdResetExpires) VALUES (?, ?, ?, ?);";
        $stmt = mysqli_stmt_init($this->conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            return false;
            exit();
        } else {
            $hashedToken = password_hash($token, PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt, "ssss", $email, $selector, $hashedToken, $expires);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
        return true;
    }

    function sendEmail($email, $subject, $message)
    {
        require_once 'mailer/class.phpmailer.php';
        //Create a new PHPMailer instance
        $mail = new PHPMailer;
        $mail->SMTPDebug = 0;  // Enable verbose debug output

        //SMTP settings start
        $mail->isSMTP(); // Set mailer to use SMTP
        $mail->Host = 'localhost'; // Specify main and backup SMTP servers
        $mail->SMTPAuth = false; // Enable SMTP authentication
        $mail->Username = ''; // SMTP username
        $mail->Password = ''; // SMTP password
        $mail->SMTPAutoTLS = false;
        $mail->SMTPSecure = false; // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 25;

        //Sender
        $mail->setFrom(EMAIL);
        //Receiver
        $mail->addAddress($email);

        //Email Subject & Body
        $mail->Subject = $subject;
        //Form Fields
        $message = $message;

        $mail->Body = $message;
        $mail->isHTML(true); // Set email format to HTML
        $mail->send();
    }
}
