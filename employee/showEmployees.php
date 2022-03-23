<?php
require_once('../class.php');
$sessionData = $payroll->getSessionData();
$payroll->verifyUserAccess($sessionData['access'], $sessionData['fullname'], 2);

if(isset($_GET['id']) && isset($_GET['email'])){
    $payroll->updateEmployee($_GET['id'], $_GET['email']);
}

if(isset($_GET['id'])){
    $payroll->deleteEmployee($_GET['id']);
}

$payroll->selectguards();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Guards</title>
    <link rel="icon" href="../styles/img/icon.png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../styles/mincss/showEmployees.min.css">
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
            </div>
            <div class="table-info">
                <div class="table-header">
                    <h1>Available Guards</h1>
                    <form method="POST">
                        <input type="text" id="search" name="search" placeholder="Search.." autocomplete="off" required/>
                        <button type="submit" name="searchbtn"></button>
                    </form>
                </div>
                <div class="table-content">
                    <table>

                        <colgroup>
                            <col span="1" style="width:5%;" />
                            <col span="1" style="width:15%" />
                            <col span="1" style="width:15%" />
                            <col span="1" style="width:40%" />
                            <col span="1" style="width:10%" />
                        </colgroup>

                        <thead>
                            <tr>
                                <th></th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?= $payroll->showAllEmpActions(); ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-button">
                    <form method="POST">
                        <input type='hidden' name='ids' id='ids'/>
                        <button type="submit" name="selectguards">Select Guards</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['id']) && isset($_GET['email']) && isset($_GET['action']) && $_GET['action'] == 'edit'){ ?>
        <div class="modal-editguard">
            <div class="modal-holder">
                <div class="editguard-header">
                    <h1>Edit Guard Details</h1>
                    <span id="exit-modal-editguard" class="material-icons">close</span>
                </div>
                <div class="editguard-content">
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
                            <button type='submit' name='editemployee'>Edit Guard</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            // editguard modal exit btn
            let exitModalEditGuard = document.querySelector("#exit-modal-editguard")
            exitModalEditGuard.addEventListener('click', e => {
                let editguardModal = document.querySelector('.modal-editguard');
                editguardModal.style.display = "none";
            });
        </script>
    <?php } ?>

    <?php if(isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] == 'delete'){ ?>
    <div class="modal-deleteguard">
        <div class="modal-holder">
            <div class="deleteguard-header">
                <h1>Delete Employee</h1>
                <span id="exit-modal-deleteguard" class="material-icons">close</span>
            </div>
            <div class="deleteguard-content">
                <h1>Are you sure you want to delete this employee?</h1>
                <form method='post'>
                    <input type='hidden' name='empDeleteId' value='$user->id' required/>
                    <button type='submit' name='deleteEmployee'>Delete</button>
                </form>
            </div>
          </div>
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

    <?php $payroll->showSpecificEmp(); ?>
    <script src="../scripts/showEmployees.js"></script>
</body>
</html>