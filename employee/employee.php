<?php
require_once('../class.php');
$sessionData = $payroll->getSessionData();
$payroll->verifyUserAccess($sessionData['access'], $sessionData['fullname'], 2);
$payroll->deleteRecentGuard();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee</title>
    <link rel="icon" href="../styles/img/icon.png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../styles/mincss/employee.min.css">
</head>
<body>
    <?php $payroll->addEmployee(); ?>
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
                            <li><a href="./employee.php">Employee</a></li>
                            <li><a href="../company/company.php">Company</a></li>
                            <li><a href="../secretary/secretary.php">Secretary</a></li>
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
                <h1>Employee</h1>
            </div>
            <div class="availability-container">
                <div class="available">
                    <object data="../styles/SVG_modified/available.svg" type="image/svg+xml"></object>
                    <div class="svg-info">
                        <h1>Available Guards</h1>
                        <button><a href="./showEmployees.php">View All</a></button>
                    </div>
                </div>
                <div class="unavailable">
                    <object data="../styles/SVG_modified/unavailable.svg" type="image/svg+xml"></object>
                    <div class="svg-info">
                        <h1>Unavailable Guards</h1>
                        <button><a href="./unavailable.php">View All</a></button>
                    </div>
                </div>
                <div class="next">
                    <object data="../styles/SVG_modified/next.svg" type="image/svg+xml"></object>
                    <div class="svg-info">
                        <h1><a href="../company/company.php">Next</a></h1>
                    </div>
                </div>
            </div>
            <div class="employee-record">
                <div class="employee-header">
                    <h2>Employee Records</h2>
                    <form method="POST">
                        <div>
                            <input type="text" name="search" id="search" placeholder="Search" autocomplete="off"/>
                        </div>
                        <button type="submit" name="searchEmp"></button>
                    </form>
                </div>
                <div class="employee-content">
                    <table cellspacing="0" cellpadding="0">
                        <colgroup>
                            <col span="1" style="width:30%"/>
                            <col span="1" style="width:18%"/>
                            <col span="1" style="width:15%"/>
                            <col span="1" style="width:10%"/>
                            <col span="1" style="width:10%"/>
                        </colgroup>

                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contacts</th>
                                <th>Availability</th>
                                <th>Type</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?= $payroll->showAllEmp(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="rightbar">
            <div class="profile-container">
                <div class="profile-setter">
                    <h3><?= $sessionData['fullname']; ?></h3>
                    <a href="../admin/profile.php">
                        <div class="image-container">
                            <?= $payroll->viewAdminImage($sessionData['id']); ?>
                        </div>
                    </a>
                </div>
            </div>
            <div class="assignedguards-container">
                <div class="assignedguards-header">
                    <h1>Recent Assigned Guards</h1>
                </div>
                <div class="assignedguards-content">
                    <?= $payroll->recentAssignedGuards(); ?>
                </div>
            </div>
            <div class="addemployee-container">
                <div class="addemployee-header">
                    <h1>Add Employee</h1>
                    <a href="./employee.php?addEmployee=true">modal</a>
                </div>
                <div class="addemployee-content">
                    <form method="POST">
                        <div class="form-holder">
                            <div>
                                <label for="firstname">Firstname</label>
                                <input type="text" name="firstname" id="firstname" autocomplete="off" required/>
                            </div>
                            <div>
                                <label for="lastname">Lastname</label>
                                <input type="text" name="lastname" id="lastname" autocomplete="off" required/>
                            </div>
                            <div>
                                <label for="address">Address</label>
                                <input type="text" name="address" id="address" required/>
                            </div>
                            <div>
                                <label for="email">Email</label>
                                <input type="text" name="email" id="email" required/>
                            </div>
                            <div>
                                <label for="number">Contact Number</label>
                                <input type="text" name="number" id="number">
                            </div>
                            <div>
                                <label for="qrcode">Qr Code</label>
                                <input type="text" name="qrcode" id="qrcode" required/>
                                <div onclick="generatePassword(this)">Generate</div>
                            </div>
                        </div>
                        <button type="submit" name="addemployee" class="btn_primary">
                            <span class="material-icons">security</span>Add
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- add modal di pa tapos, need irequery para sa modal -->
    <?php if(isset($_GET['addEmployee']) && $_GET['addEmployee'] == true){ ?>
        <div class="modal-addguard">
            <div class="modal-holder">
                <div class="addguard-header">
                    <h1>Add Employee</h1>
                    <span id="exit-modal-addguard" class="material-icons">close</span>
                </div>
                <div class="addguard-content">
                    <form method='POST'>
                        <div>
                            <label for='firstname'>Firstname</label>
                            <input type='text' name='firstname' id='firstname' required/>
                        </div>
                        <div>
                            <label for='lastname'>Lastname</label>
                            <input type='text' name='lastname' id='lastname' required/>
                        </div>
                        <div>
                            <label for='address'>Address</label>
                            <input type='text' name='address' id='address' required/>
                        </div>
                        <div>
                            <label for='email'>Email</label>
                            <input type='email' name='email' id='email' required/>
                        </div>
                        <div>
                            <label for='cpnumber'>Contact Number</label>
                            <input type='text' name='cpnumber' id='cpnumber' required/>
                        </div>
                        <div>
                            <label for='qrcode'>QR Code</label>
                            <input type='text' name='qrcode' id='qrcode' required/>
                        </div>
                        <div>
                            <button type='submit' name='addGuard'>Add Employee</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            // addguard modal exit btn
            let exitModalAddGuard = document.querySelector("#exit-modal-addguard")
            exitModalAddGuard.addEventListener('click', e => {
                let addguardModal = document.querySelector('.modal-addguard');
                addguardModal.style.display = "none";
            });
        </script>
    <?php } ?>

    <!-- delete modal -->
    <?php if(isset($_GET['idDelete'])){ ?>
        <div class="modal-deleteguard">
            <?php $payroll->deleteRecentGuardModal($_GET['idDelete']);  ?>
        </div>
        <script>
            // deleteguard modal exit btn
            let exitModalDeleteGuard = document.querySelector("#exit-modal-deleteguard")
            exitModalDeleteGuard.addEventListener('click', e => {
                let deleteguardModal = document.querySelector('.modal-deleteguard');
                deleteguardModal.style.display = "none";
            });
        </script>
    <?php } ?>


    <script src="../scripts/employee.js"></script>
</body>
</html>