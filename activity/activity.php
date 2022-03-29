<?php
require_once('../class.php');
$sessionData = $payroll->getSessionData();
$payroll->verifyUserAccess($sessionData['access'], $sessionData['fullname'], 2);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
    <link rel="icon" href="../styles/img/icon.png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../styles/mincss/activity.min.css">
</head>
<body>
    <div class="main-container">
        <div class="leftbar">
            <div class="logo-container">
                <div class="logo"></div>
                <h1>JTDV</h1>
            </div>
            <div class="links-container">
                <ul>
                    <li><a href="../dashboard.php">Dashboard</a></li>
                    <li>Records
                        <ul>
                            <li><a href="./employee/employee.php">Employee</a></li>
                            <li><a href="./company/company.php">Company</a></li>
                            <li><a href="./secretary/secretary.php">Secretary</a></li>
                        </ul>
                    </li>
                    <li>Manage Report
                        <ul>
                            <li><a href="./leave/leave.php">Leave</a></li>
                            <li><a href="./remarks/remarks.php">Remarks</a></li>
                        </ul>
                    </li>
                    <li class="active-parent"><a href="./activity.php">Activities</a></li>
                </ul>
                <div>
                    <a href="./logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>