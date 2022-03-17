<?php
require_once('../class.php');
$sessionData = $payroll->getSessionData();
$payroll->verifyUserAccess($sessionData['access'], $sessionData['fullname'], 2);
$payroll->addSecretary($sessionData['id'], $sessionData['fullname']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary</title>
    <link rel="icon" href="../styles/img/icon.png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../styles/mincss/secretary.min.css">
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
                    <li class="active-parent">Records
                        <ul>
                            <li><a href="../employee.php">Employee</a></li>
                            <li><a href="../company/company.php">Company</a></li>
                            <li><a href="./secretary.php">Secretary</a></li>
                        </ul>
                    </li>
                    <li>Manage Report
                        <ul>
                            <li><a href="../leave/leave.php">Leave</a></li>
                            <li><a href="../remarks/remarks.php">Remarks</a></li>
                        </ul>
                    </li>
                    <li><a href="../activity/activity.php">Activities</a></li>
                </ul>
                <div>
                    <a href="../logout.php">Logout</a>
                </div>
            </div>
        </div>
        <div class="centerbar">
            <div class="header-info">
                <h1>Secretary</h1>
            </div>
            <div class="recentaccount">
                <div class="recentaccount-header">
                    <h1>Recent Account Added</h1>
                    <a href="./showAll.php">view all</a>
                </div>
                <div class="recentacount-svg">
                    <?= $payroll->show2Secretary(); ?>
                </div>
            </div>
            <div class="activities">
                <div class="activities-header">
                    <h1>Activities</h1>
                </div>
                <div class="activities-content">
                    <div class="activities-content-header">
                        <h2>Secretary Record</h2>
                    </div>
                    <div class="activities-main-contents">
                        <table>
                            <colgroup>
                                <col span="1" style="width:30%"/>
                                <col span="1" style="width:50%"/>
                                <col span="1" style="width:20%"/>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Action</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Marianne Herrera</td>
                                    <td>Generate Payslip</td>
                                    <td>12/02/2021</td>
                                </tr>
                                <tr>
                                    <td>Marianne Herrera</td>
                                    <td>Print Attendance Report</td>
                                    <td>12/02/2021</td>
                                </tr>
                                <tr>
                                    <td>Marianne Herrera</td>
                                    <td>Generate Payslip</td>
                                    <td>12/02/2021</td>
                                </tr>
                                <tr>
                                    <td>Marianne Herrera</td>
                                    <td>Generate Salary</td>
                                    <td>12/02/2021</td>
                                </tr>
                                <tr>
                                    <td>Marianne Herrera</td>
                                    <td>Generate Payslip</td>
                                    <td>12/02/2021</td>
                                </tr>
                                <tr>
                                    <td>Marianne Herrera</td>
                                    <td>Generate Payslip</td>
                                    <td>12/02/2021</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                </div>
            </div>
        </div>
        <div class="rightbar">
            <div class="profile-container">
                <div class="profile-setter">
                    <h3>Ilacad, Francis</h3>
                    <a href="../admin/profile.php"><div class="image-container"></div></a>
                </div>
            </div>
            <div class="sidenav">
                <div class="sidenav-header">
                    <h1>Visit</h1>
                </div>
                <div class="sidenav-content">
                    <div>
                        <span></span>
                        <a href="../employee/showEmployees.php">Available Guard</a>
                    </div>
                    <div>
                        <span></span>
                        <a href="../employee/unavailable.php">Unavailable Guard</a>
                    </div>
                    <div>
                        <span></span>
                        <a href="../company/company.php">Company</a>
                    </div>
                </div>
            </div>
            <div class="add-account">
                <div class="add-account-header">
                    <h1>Add Account</h1>
                </div>
                <div class="add-account-container">
                    <div class="add-account-container-header">
                        <h2>Secretary Details</h2>
                    </div>
                    <div class="add-account-container-form">
                        <form method="post">
                            
                            <div>
                                <label for="name">Name</label>
                                <input type="text" name="fullname" id="fullname" autocomplete="off" required/>
                            </div>
                            <div>
                                <label for="cpnumber">Contact Number</label>
                                <input type="text" name="cpnumber" autocomplete="off" required/>
                            </div>
                            <div>
                                <label for="name">Email</label>
                                <input type="email" name="email" id="email" autocomplete="off" required/>
                            </div>
                            <div>
                                <label for="name">Gender</label>
                                <select name="gender" id="gender" required>
                                    <option value=""></option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div>
                                <label for="address">Address</label>
                                <input type="text" name="address" id="address" autocomplete="off" required/>
                            </div>
                            <button type="submit" name="addsecretary">Add</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>