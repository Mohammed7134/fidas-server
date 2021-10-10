<?php 
    if ($_GET['newPwd'] == "passwordUpdated") {
        echo "Password Updated";
    } else {
        echo $_GET['newPwd'];
    }