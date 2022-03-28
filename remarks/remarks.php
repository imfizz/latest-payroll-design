<?php

require_once('../class.php');
$sessionData = $payroll->getSessionData();
$payroll->verifyUserAccess($sessionData['access'], $sessionData['fullname'], 2);

// for success action
$msg = '';
if(isset($_GET['message'])){
    $msg = $_GET['message'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remarks</title>
    <link rel="icon" href="../styles/img/icon.png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../styles/mincss/remarks.min.css">
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
                            <li><a href="../leave/leave.php">Leave</a></li>
                            <li><a href="./remarks.php">Remarks</a></li>
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
                <h1>Remarks</h1>
            </div>
            <div class="welcome-info">
                <div class="welcome-svg">
                    <object data="../styles/SVG_modified/remarks.svg" type="image/svg+xml"></object>
                </div>
                <div class="welcome-box">
                    <div class="welcome-box-content">
                        <h2>To punish is to inflict penalty for violating rules or intentional wrongdoing.</h2>
                        <p>They must have followed the regulations.</p>
                        <button>Add Violation</button>
                    </div>
                </div>
            </div>
            <div class="remarked-violations">
                <div class="remarked-violations-header">
                    <h1>Violations</h1>
                </div>
                <div class="remarked-violations-content">
                    <div class="violations-content-header">
                        <h1>Remarked Violations</h1>
                        <form method="POST">
                            <input type="search" name="search" placeholder="Search" autocomplete="off" id="search">
                            <button type="submit" name="searchBtn"></button>
                        </form>
                    </div>
                    <div class="violations-content-content">
                        <table>
                            <colgroup>
                                <col span="1" style="width:26%"/>
                                <col span="1" style="width:9%"/>
                                <col span="1" style="width:42%"/>
                                <col span="1" style="width:19%"/>
                                <col span="1" style="width:19%"/>
                            </colgroup>

                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Fine</th>
                                    <th>Violation</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Francis Marianne</td>
                                    <td>300</td>
                                    <td>No Belt, No Name Tag, No id</td>
                                    <td>2022/05/19</td>
                                    <td>
                                        <a href='#'><span class='material-icons'>visibility</span> </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Francis Marianne</td>
                                    <td>300</td>
                                    <td>No Belt, No Name Tag, No id</td>
                                    <td>2022/05/19</td>
                                    <td>
                                        <a href='#'><span class='material-icons'>visibility</span> </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Francis Marianne</td>
                                    <td>300</td>
                                    <td>No Belt, No Name Tag, No id</td>
                                    <td>2022/05/19</td>
                                    <td>
                                        <a href='#'><span class='material-icons'>visibility</span> </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Francis Marianne</td>
                                    <td>300</td>
                                    <td>No Belt, No Name Tag, No id</td>
                                    <td>2022/05/19</td>
                                    <td>
                                        <a href='#'><span class='material-icons'>visibility</span> </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Francis Marianne</td>
                                    <td>300</td>
                                    <td>No Belt, No Name Tag, No id</td>
                                    <td>2022/05/19</td>
                                    <td>
                                        <a href='#'><span class='material-icons'>visibility</span> </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Francis Marianne</td>
                                    <td>300</td>
                                    <td>No Belt, No Name Tag, No id</td>
                                    <td>2022/05/19</td>
                                    <td>
                                        <a href='#'><span class='material-icons'>visibility</span> </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Francis Marianne</td>
                                    <td>300</td>
                                    <td>No Belt, No Name Tag, No id</td>
                                    <td>2022/05/19</td>
                                    <td>
                                        <a href='#'><span class='material-icons'>visibility</span> </a>
                                    </td>
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
                    <h3><?= $sessionData['fullname']; ?></h3>
                    <a href="../admin/profile.php">
                        <div class="image-container">
                            <?= $payroll->viewAdminImage($sessionData['id']); ?>
                        </div>
                    </a>
                </div>
            </div>
            <div class="most-violation">
                <div class="most-violation-header">
                    <h1>Most Violation</h1>
                </div>
                <div class="most-violation-content">
                    <div>
                        <h2>Yanyan, Francis</h2>
                        <p>Officer in Charge</p>
                    </div>
                    <div>
                        <p>Recent Violation</p>
                        <h1>Always Absent</h1>
                    </div>
                    <button>See All</button>
                </div>
            </div>
        </div>
    </div>

    <!-- for success action -->
    <input type='hidden' id='msg' value='<?= $msg; ?>' />
    <script>
        let msg = document.querySelector('#msg');
        if(msg.value != ''){
            let successDiv = document.createElement('div');
            successDiv.classList.add('success');
            let iconContainerDiv = document.createElement('div');
            iconContainerDiv.classList.add('icon-container');
            let spanIcon = document.createElement('span');
            spanIcon.classList.add('material-icons');
            spanIcon.innerText = 'done';
            let pSuccess = document.createElement('p');
            pSuccess.innerText = msg.value; // set to $_GET['msg']
            let closeContainerDiv = document.createElement('div');
            closeContainerDiv.classList.add('closeContainer');
            let spanClose = document.createElement('span');
            spanClose.classList.add('material-icons');
            spanClose.innerText = 'close';

            // destructure
            iconContainerDiv.appendChild(spanIcon);
            closeContainerDiv.appendChild(spanClose);

            successDiv.appendChild(iconContainerDiv);
            successDiv.appendChild(pSuccess);
            successDiv.appendChild(closeContainerDiv);
            document.body.appendChild(successDiv);

            // remove after 5 mins
            setTimeout(e => successDiv.remove(), 5000);
        }
    </script>
</body>
</html>