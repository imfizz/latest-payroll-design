<?php
require_once('class.php');
$sessionData = $payroll->getSessionData();
$payroll->verifyUserAccess($sessionData['access'], $sessionData['fullname'], 1);
$payroll->viewApproveReject();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to JTDV</title>
    <link rel="icon" href="./styles/img/icon.png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="./styles/mincss/dashboard.min.css">
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
                    <li class="active-parent"><a href="./dashboard.html">Dashboard</a></li>
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
                            <li><a href="./remarks/remarks.html">Remarks</a></li>
                        </ul>
                    </li>
                    <li><a href="./activity/activity.html">Activities</a></li>
                </ul>
                <div>
                    <a href="#">Logout</a>
                </div>
            </div>
        </div>
        <div class="centerbar">
            <div class="header-info">
                <h1>Dashboard</h1>
            </div>
            <div class="welcome-info">
                <div class="welcome-box">
                    <?= $payroll->countNewGuardsWelcome($sessionData['fullname']); ?>
                </div>
                <div class="welcome-svg">
                    <object data="./styles/SVG_modified/dashboard.svg" type="image/svg+xml"></object>
                </div>
            </div>
            <div class="statistics-info">
                <div class="statistics-header">
                    <h2>Statistics</h2>
                    <a href="dashboard.php?viewAllStat=true">view all</a>
                </div>
                <div class="statistics-cards">
                    <?php $payroll->dashboardStatistics(); ?>
                </div>
            </div>
            <div class="activity-info">
                <div class="activity-header">
                    <h2>Recent Activity</h2>
                    <button><a href="./dashboard.php?seeAll=true">See All</a></button>
                </div>
                <div class="activity-table">
                    <table cellspacing="0" cellpadding="0">
                        <colgroup>
                            <col span="1" style="width: 28%;"/>
                            <col span="1" style="width: 47%;"/>
                            <col span="1" style="width: 15%;"/>
                            <col span="1" style="width: 10%;"/>
                        </colgroup>

                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Guards</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?= $payroll->dashboardRecentActivity(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="rightbar">
            <div class="profile-container">
                <div class="profile-setter">
                    <h3><?= $sessionData['fullname']; ?></h3>
                    <a href="./admin/profile.php">
                        <div class="image-container">
                            <?= $payroll->viewAdminImage($sessionData['id']); ?>
                        </div>
                    </a>
                </div>
                
            </div>
            <div class="guards-container">
                <div class="guards-header">
                    <h1>New Guards</h1>
                    <a href="./employee/showEmployees.php">see all</a>
                </div>
                <div class="guards-content">
                    <?= $payroll->dashboardNewGuards(); ?>
                </div>
            </div>
            <div class="request-container">
                <div class="request-header">
                    <h1>Leave Requests</h1>
                    <a href="./leave/leave.php">see all</a>
                </div>
                <div class="request-content">
                    <?= $payroll->dashboardLeaveRequests(); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- for review all modal -->
    <?php if(isset($_GET['reviewAll']) && $_GET['reviewAll'] == true){ ?>
        <!-- modals -->
        <div class="modal-review">
            <div class="table-container">
                <div class="table-header">
                    <h1>Newly Assigned Guards</h1>
                    <span id="exit-modal-review" class="material-icons">close</span>
                </div>
                <div class="table-content">
                    <table cellspacing="0" cellpadding="0">
                        <colgroup>
                            <col span="1" style="width: 22%;" />
                            <col span="1" style="width: 15%;" />
                            <col span="1" style="width: 30%;" />
                            <col span="1" style="width: 21%;" />
                            <col span="1" style="width: 12%;" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Company</th>
                                <th>Company Address</th>
                                <th>Position</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $payroll->reviewAll(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
            // review modal exit btn
            let exitModalReview = document.querySelector("#exit-modal-review");
            exitModalReview.addEventListener('click', e => {
                let reviewModal = document.querySelector('.modal-review');
                reviewModal.style.display = "none";
            });
        </script>
    <?php } ?>

    <!-- for statistics modal -->
    <?php if(isset($_GET['viewAllStat']) && $_GET['viewAllStat'] == true){ ?>
        <div class="modal-statistics">
            <div class="cards-container">
                <div class="cards-header">
                    <h1>Statistics</h1>
                    <span id="exit-modal-statistics" class="material-icons">close</span>
                </div>
                <div class="cards-content">
                    <table cellspacing="0" cellpadding="0">
                        <colgroup>
                            <col span="1" style="width: 20%;" />
                            <col span="1" style="width: 33%;" />
                            <col span="1" style="width: 33%;" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Guards</th>
                                <th>Position</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $payroll->viewAllStatistics(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
            // statistics modal exit btn
            let exitModalStatistics = document.querySelector("#exit-modal-statistics");
            exitModalStatistics.addEventListener('click', e => {
                let statisticsModal = document.querySelector('.modal-statistics');
                statisticsModal.style.display = "none";
            });
        </script>
    <?php } ?>

    <!-- for company modal -->
    <?php if(isset($_GET['seeAll']) && $_GET['seeAll'] == true){ ?>
        <div class="modal-company">
            <div class="activity-container">
                <div class="activity-header">
                    <h1>Recent Activity</h1>
                    <span id="exit-modal-company" class="material-icons">close</span>
                </div>
                <div class="activity-content">
                    <table cellspacing="0" cellpadding="0">
                        <colgroup>
                            <col span="1" style="width: 22%;" />
                            <col span="1" style="width: 40%;" />
                            <col span="1" style="width: 17%;" />
                            <col span="1" style="width: 21%;" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Guards</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $payroll->dashboardRecentActivityAll(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
            // company modal exit btn
            let exitModalCompany = document.querySelector("#exit-modal-company");
            exitModalCompany.addEventListener('click', e => {
                let companyModal = document.querySelector('.modal-company');
                companyModal.style.display = "none";
            });
        </script>
    <?php } ?>

    <!-- for edit guard modal -->
    <?php if(isset($_GET['guardId']) && isset($_GET['editGuard']) && $_GET['editGuard'] == true && isset($_GET['email'])){ ?>
        <div class="modal-editguard">
            <div class="modal-holder">
                <div class="editguard-header">
                    <h1>Edit Guard Details</h1>
                    <span id="exit-modal-editguard" class="material-icons">close</span>
                </div>
                <div class="editguard-content">
                <?php 
                    $payroll->dashboardEditGuardsModal($_GET['guardId']); // get info
                    $payroll->dashboardEditGuards($_GET['guardId'], $_GET['email']); // edit info
                ?>
                </div>
            </div>
        </div>
        <script>
            // editguard modal exit btn
            let exitModalEditGuard = document.querySelector("#exit-modal-editguard");
            exitModalEditGuard.addEventListener('click', e => {
                let editguardModal = document.querySelector('.modal-editguard');
                editguardModal.style.display = "none";
            });
        </script>
    <?php } ?>

    <!-- for delete guard modal -->
    <?php if(isset($_GET['guardId']) && isset($_GET['deleteGuard']) && $_GET['deleteGuard'] == true){ ?>
        <div class="modal-deleteguard">
            <?php 
                $payroll->dashboardDeleteGuardsModal($_GET['guardId']); // get info
                $payroll->dashboardDeleteGuards(); // delete info
            ?>
        </div>
        <script>
            // deleteguard modal exit btn
            let exitModalDeleteGuard = document.querySelector("#exit-modal-deleteguard");
            exitModalDeleteGuard.addEventListener('click', e => {
                let deleteguardModal = document.querySelector('.modal-deleteguard');
                deleteguardModal.style.display = "none";
            });
        </script>
    <?php } ?>

    <!-- for approve, reject leave modal -->
    <?php
    if(isset($_GET['id']) && isset($_GET['act']) && $_GET['act'] == 'approve'){ ?>
        <div class="modal-approverequest">
            <div class="modal-holder">
                <div class="approverequest-header">
                    <h1>Approve Request Leave</h1>
                    <span id="exit-modal-approverequest" class="material-icons">close</span>
                </div>
                <div class="approverequest-content">
                    <form method="POST">
                        <div>
                            <input type="hidden" name="requestId" id='requestId'/>
                            <label for="substitute">Substitute</label>
                            <select name="substitute" id="substitute">
                                <?= $payroll->listoffreeguard(); ?>
                            </select>
                        </div>
                        <div>
                            <label for="fullname">Name</label>
                            <input type="text" name="fullname" id='fullname' disabled/>
                        </div>
                        <div>
                            <label for="email">Email</label>
                            <input type="email" name="email" id='email' disabled/>
                        </div>
                        <div>
                            <label for="daysleave">Days Leave</label>
                            <div class="daysleave-info">
                                <div>
                                    <select name="days" id="daysleave" disabled></select>
                                </div>
                                <div>
                                    <span>From
                                        <input type="date" name="leave_start" id='leave_start' disabled/> 
                                    </span>
                                    <span>To
                                        <input type="date" name="leave_end" id='leave_end' disabled/>
                                    </span>
                                </div>
                            </div>
                            
                        </div>
                        <div>
                            <label for="type">Type</label>
                            <input type="text" name="type" id='type' disabled/>
                        </div>
                        <div>
                            <label for="reason">Reason</label>
                            <input type="text" name="reason" id='reason' disabled/>
                        </div>
                        <div>
                            <button type='submit' name='approveRequest' id='approvebtn'>Approve Request</button>
                            <button type='submit' name='rejectRequest' id='rejectbtn'>Reject Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            // approverequest modal exit btn
            let exitModalApproveRequest = document.querySelector("#exit-modal-approverequest");
            exitModalApproveRequest.addEventListener('click', e => {
                let approverequestModal = document.querySelector('.modal-approverequest');
                approverequestModal.style.display = "none";
            });
        </script>
        <?php
        $payroll->viewRequest($_GET['id']);
        $payroll->approveRequest($_GET['id']);
    }
    
    if(isset($_GET['id']) && isset($_GET['act']) && $_GET['act'] == 'reject'){ ?>
        <div class="modal-rejectrequest">
            <div class="modal-holder">
                <div class="rejectrequest-header">
                    <h1>Reject Request Leave</h1>
                    <span id="exit-modal-rejectrequest" class="material-icons">close</span>
                </div>
                <div class="rejectrequest-content">
                    <form method="POST">
                        <div>
                            <input type="hidden" name="requestId" id='requestId'/>
                            <label for="substitute">Substitute</label>
                            <select name="substitute" id="substitute" disabled>
                                <?= $payroll->listoffreeguard(); ?>
                            </select>
                        </div>
                        <div>
                            <label for="fullname">Name</label>
                            <input type="text" name="fullname" id='fullname' disabled/>
                        </div>
                        <div>
                            <label for="email">Email</label>
                            <input type="email" name="email" id='email' disabled/>
                        </div>
                        <div>
                            <label for="daysleave">Days Leave</label>
                            <div class="daysleave-info">
                                <div>
                                    <select name="days" id="daysleave" disabled></select>
                                </div>
                                <div>
                                    <span>From
                                        <input type="date" name="leave_start" id='leave_start' disabled/> 
                                    </span>
                                    <span>To
                                        <input type="date" name="leave_end" id='leave_end' disabled/>
                                    </span>
                                </div>
                            </div>
                            
                        </div>
                        <div>
                            <label for="type">Type</label>
                            <input type="text" name="type" id='type' disabled/>
                        </div>
                        <div>
                            <label for="reason">Reason</label>
                            <input type="text" name="reason" id='reason' disabled/>
                        </div>
                        <div>
                            <button type='submit' name='approveRequest' id='approvebtn'>Approve Request</button>
                            <button type='submit' name='rejectRequest' id='rejectbtn'>Reject Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            // approverequest modal exit btn
            let exitModalRejectRequest = document.querySelector("#exit-modal-rejectrequest");
            exitModalRejectRequest.addEventListener('click', e => {
                let rejectrequestModal = document.querySelector('.modal-rejectrequest');
                rejectrequestModal.style.display = "none";
            });
        </script>
        <?php 
        $payroll->viewRequest($_GET['id']);
        $payroll->rejectRequest($_GET['id']);
    }
    ?>

</body>
</html>