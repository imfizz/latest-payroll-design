<?php
require_once('../class.php');
$sessionData = $payroll->getSessionData();
$payroll->verifyUserAccess($sessionData['access'], $sessionData['fullname'], 2);
$payroll->viewApproveReject();
$payroll->removeRecentFunction();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave</title>
    <link rel="icon" href="../styles/img/icon.png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../styles/mincss/leave.min.css">
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
                            <li><a href="../employee/employee.php">Employee</a></li>
                            <li><a href="../company/company.php">Company</a></li>
                            <li><a href="../secretary/secretary.php">Secretary</a></li>
                        </ul>
                    </li>
                    <li class="active-parent">Manage Report
                        <ul>
                            <li><a href="./leave.php">Leave</a></li>
                            <li><a href="../remarks/remarks.php">Remarks</a></li>
                        </ul>
                    </li>
                    <li><a href="../activity/activity.php">Activities</a></li>
                </ul>
                <div>
                    <a href="#">Logout</a>
                </div>
            </div>
        </div>
        <div class="centerbar">
            <div class="header-info">
                <h1>Leave</h1>
            </div>

            <div class="recent-activity">
                <div class="recent-activitiy-header">
                    <h1>Recent Activity</h1>
                </div>
                <div class="recent-activity-content">
                    <?= $payroll->recentactivityleave(); ?>
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
            <div class="leave-welcome">
                <div class="leave-box">
                    <div class="svg-container">
                        <object data="../styles/SVG_modified/leave.svg" type="image/svg+xml"></object>
                    </div>
                    <div class="welcome-headline">
                        <h1>Hello Francis!</h1>
                        <p>You approved 3 request leave. It is a lot. Keep up the good work. Let's go 😘</p>
                    </div>
                </div>
            </div>

            <div class="employee-request">
                <div class="employee-request-header">
                    <h1>Employee Request</h1>
                </div>
                <div class="table-container">
                    <div class="table-header">
                        <h1>List of Request</h1>
                    </div>
                    <div class="table-content">
                        <table>

                            <colgroup>
                                <col span="1" style="width:20.28%" />
                                <col span="1" style="width:14.28%" />
                                <col span="1" style="width:14.28%" />
                                <col span="1" style="width:14.28%" />
                                <col span="1" style="width:14.28%" />
                                <col span="1" style="width:14.28%" />
                                <col span="1" style="width:14.28%" />
                            </colgroup>

                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Reason</th>
                                    <th>Days</th>
                                    <th>Date Start</th>
                                    <th>Date End</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?= $payroll->listofleaverequest(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

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