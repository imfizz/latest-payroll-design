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
    <title>List of Secretary</title>
    <link rel="icon" href="../styles/img/icon.png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../styles/mincss/showAll.min.css">
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
            <div class="table-container">
                <div class="table-header">
                    <h1>Table</h1>
                </div>
                <div class="table-content">
                    <div class="table-content-header">
                        <h1>List of Secretary</h1>
                        <form method="POST">
                            <input type="search" id="search" name="search" placeholder="Search" autocomplete="off"/>
                            <button type="submit" name="search"></button>
                        </form>
                    </div>
                    <div class="table-content-form">
                        <table>
                            <colgroup>
                                <col span="1" style="width:20%" />
                                <col span="1" style="width:10%" />
                                <col span="1" style="width:55%" />
                                <col span="1" style="width:20%" />
                            </colgroup>

                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>Email</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $payroll->showAllSecretary(); ?>
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
                    <a href="./admin/profile.php">
                        <div class="image-container">
                        </div>
                    </a>
                </div>
                
            </div>
            <div class="relative-links">
                <div class="relative-links-header">
                    <h1>Relative Links</h1>
                </div>
                <div class="top">
                    <div>
                        <span></span>
                    </div>
                    <div>
                        <a href="#">Unavailable Guards</a>
                    </div>
                </div>
                <div class="bottom">
                    <div>
                        <p>Available Guard</p>
                        <div>
                            <span></span>
                        </div>
                    </div>
                    <div>
                        <p>Company</p>
                        <div>
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="view-modal">
        <form method="post">
            <!-- <h1 id="myH1">Are you sure you want to delete?</h1> -->
            <div>
                <label for="fullname">Fullname</label>
                <input type="text" name="fullname" id="fullname" autocomplete="off" required/>
            </div>
            <div>
                <label for="gender">Gender</label>
                <select name="gender" id="gender" required>
                    <option value=""></option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div>
                <label for="email">Email</label>
                <input type="email" name="email" id="email" autocomplete="off" required/>
            </div>
            <div>
                <label for="cpnumber">Contact #</label>
                <input type="text" name="cpnumber" id="cpnumber" autocomplete="off" required/>
            </div>
            <div>
                <label for="address">Address</label>
                <input type="text" name="address" id="address" autocomplete="off" required/>
            </div>
            <button type="submit" name="updateSec" id="updateBtn">Update</button>
            
            <button type="submit" name="deleteSec" id="deleteBtn">Delete</button>
        </form>
    </div>

<?php 
$payroll->showSpecificSec(); 

if(isset($_GET['secId']) && isset($_GET['email'])){
    $payroll->editModalShow();
    $payroll->editSecretary($_GET['secId'], $_GET['email']);
}

if(isset($_GET['secIdDelete'])){
    $payroll->deleteSecretary($_GET['secIdDelete']);
}
?>
</body>
</html>