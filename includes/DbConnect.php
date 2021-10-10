<?php 
    class DbConnect {
        private $conn;
        function __construct() {

        }

        function connect() {
            require_once 'Constants.php';
            $this->conn = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

            if (!$this->conn) {
                die("Failed to connect to MySQL: ".mysqli_conncet_error());
            } 
            return $this->conn;
        }
    }

