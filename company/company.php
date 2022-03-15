<?php
require_once('../class.php');
$sessionData = $payroll->getSessionData();
$payroll->verifyUserAccess($sessionData['access'], $sessionData['fullname'], 2);
$payroll->addCompany();
$payroll->addCompany2(); // for modal

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company</title>
    <link rel="icon" href="../styles/img/icon.png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://api.tiles.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.js"></script>
    <link href="https://api.tiles.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.css" rel="stylesheet" />
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.0/mapbox-gl-geocoder.min.js"></script>
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.0/mapbox-gl-geocoder.css" type="text/css" />
    <link rel="stylesheet" href="../styles/mincss/company.min.css">
    <style>
        #modalform {display: none;}
    </style>
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
                            <li><a href="../employee/employee.php">Employee</a></li>
                            <li><a href="./company.php">Company</a></li>
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
                    <a href="#">Logout</a>
                </div>
            </div>
        </div>
        <div class="centerbar">
            <div class="header-info">
                <h1>Company</h1>
            </div>
            <div class="welcome-info">
                <div class="welcome-box">
                    <h2>Hello Francis!</h2>
                    <p>You have 3 new company added. It is a lot. Keep up the good work. Let's go 😘</p>
                    <button><a href="#">Review All</a></button>
                </div>
                <div class="welcome-svg">
                    <object data="../styles/SVG_modified/company.svg" type="image/svg+xml"></object>
                </div>
            </div>
            <div class="newlyadded-info">
                <div class="newlyadded-header">
                    <h2>Newly Added</h2>
                </div>
                <div class="newlyadded-cards">
                    <?= $payroll->companyNewlyAdded(); ?>
                </div>
            </div>
            <div class="companylist-container">
                <div class="companylist-header">
                    <h2>List of Company</h2>
                </div>
                <div class="table-container">
                    <div class="table-header">
                        <h2>Company</h2>
                        <form method="POST">
                            <input type="text" id="search" name="search" placeholder="Search.." autocomplete="off"/>
                            <button type="submit" name="companysearch"></button>
                        </form>
                    </div>
                    <div class="table-content">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Hired Guards</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?= $payroll->listofcompany(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="rightbar">
            <div class="profile-container">
                <h4>Ilacad, Francis</h4>
                <div class="profile-img"></div>
            </div>
            <div class="form-container">
                <div class="form-header">
                    <h2>Add Company</h2>
                    <a id='addmodal-show'>modal</a>
                </div>
                <div class="form-contents">
                    <form id="myForm" method="post">
                        <div>
                            <label for="company_name">Company</label>
                            <input type="text" name="company_name" autocomplete="off" required/>
                        </div>
                        <div>
                            <label for="cpnumber">Contact Number</label>
                            <input type="text" name="cpnumber" autocomplete="off"/>
                        </div>
                        <div>
                            <label for="email">Email</label>
                            <input type="email" name="email" autocomplete="off" required/>
                        </div>
                        <div>
                            <label for="">Trace Location</label>
                            <div id="map" class="trace"></div>
                        </div>
                        <div>
                            <label for="location_name">Address</label>
                            <input type="text" id="location_name" name="comp_location" required/>
                            <input type="hidden" id="longitude" name="longitude" placeholder="Longitude" required/>
                            <input type="hidden" id="latitude" name="latitude" placeholder="Latitude" required/>
                        </div>
                        <div>
                            <label for="">Set Boundary</label>
                            <div id="map_b"></div>
                            <input type="hidden" name="boundary_size" placeholder="Boundary size" class="map_b_size" required/>
                        </div>
                        <div>
                            <label for="">Type</label>
                            <select name="type" required>
                                <option value="">Select type</option>
                                <option value="manual">Manual</option>
                                <option value="automatic">Automatic</option>
                            </select>
                        </div>
                        <div>
                            <label for="">Shift</label>
                            <select name="shift" required>
                                <option value="">Select shift</option>
                                <option value="day">Day</option>
                                <option value="night">Night</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div>
                            <label for="">Shift Span</label>
                            <select name="shift_span" required>
                                <option value="">Select span</option>
                                <option value="8">8 hrs</option>
                                <option value="12">12 hrs</option>
                            </select>
                        </div>
                        <div>
                            <label for="">Day Start</label>
                            <select name="day_start" required>
                                <option value="">Select day start</option>
                    
                                <!-- day 8hrs -->
                                <option value="6:00 am">6:00 AM</option>
                                <option value="7:00 am">7:00 AM</option>
        
                            </select>
                        </div>
                        <div id="addhere-main">
                            <label for="">Position</label>
                            <input type="number" style="display:none;" class="length-main" value="1" name="length"/>
                            <div class="position-container">
                                <input type="text" class="name" name="name0" autocomplete="off" placeholder="name"/>
                                <input type="text" class="price" name="price0" autocomplete="off" placeholder="00.00"/>
                            </div>
                        </div>
                        <div class="addnew-container">
                            <button type="button" class="addnew-main">+ Add new</button>
                        </div>
                        <button type="submit" name="addcompany">Add Company</button><br/>
                    </form>
                </div>
            </div>
        </div>
    </div>


<div class="modal-viewcompany">
    <div class="modal-holder">
        <div class="viewcompany-header">
            <h1>Add Employee</h1>
            <span id="exit-modal-viewcompany" class="material-icons">close</span>
        </div>
        <div class="viewcompany-content">
            <form id="myForm" method="post">
                <div>
                    <label for="company_name">Company</label>
                    <input type="text" name="company_name" autocomplete="off" required/>
                </div>
                <div>
                    <label for="cpnumber">Contact Number</label>
                    <input type="text" name="cpnumber" autocomplete="off"/>
                </div>
                <div>
                    <label for="email">Email</label>
                    <input type="email" name="email" autocomplete="off" required/>
                </div>
                <div>
                    <label for="">Trace Location</label>
                    <div id="map-addmodal" class="trace-addmodal"></div>
                </div>
                <div>
                    <label for="location_name">Address</label>
                    <input type="text" id="location_name" name="comp_location" required/>
                    <input type="hidden" id="longitude-addmodal" name="longitude" placeholder="Longitude" required/>
                    <input type="hidden" id="latitude-addmodal" name="latitude" placeholder="Latitude" required/>
                </div>
                <div>
                    <label for="">Set Boundary</label>
                    <div id="map_b-addmodal"></div>
                    <input type="hidden" name="boundary_size" placeholder="Boundary size" class="map_b_size-addmodal" required/>
                </div>
                <div>
                    <label for="">Type</label>
                    <select name="type" required>
                        <option value="">Select type</option>
                        <option value="manual">Manual</option>
                        <option value="automatic">Automatic</option>
                    </select>
                </div>
                <div>
                    <label for="">Shift</label>
                    <select name="shift" required>
                        <option value="">Select shift</option>
                        <option value="day">Day</option>
                        <option value="night">Night</option>
                        <option value="both">Both</option>
                    </select>
                </div>
                <div>
                    <label for="">Shift Span</label>
                    <select name="shift_span" required>
                        <option value="">Select span</option>
                        <option value="8">8 hrs</option>
                        <option value="12">12 hrs</option>
                    </select>
                </div>
                <div>
                    <label for="">Day Start</label>
                    <select name="day_start" required>
                        <option value="">Select day start</option>
                    
                        <!-- day 8hrs -->
                                <option value="6:00 am">6:00 AM</option>
                        <option value="7:00 am">7:00 AM</option>
        
                    </select>
                </div>
                <div id="addhere-addmodal">
                    <label for="">Position</label>
                    
                    <div class="position-container">
                        <input type="text" class="name-modal" name="name0" autocomplete="off" placeholder="name"/>
                        <input type="text" class="price-modal" name="price0" autocomplete="off" placeholder="00.00"/>
                        <input type="number" class="length2" style="display:none;" value="1" name="length"/>
                    </div>
                </div>
                <div class="addnew-container">
                    <button type="button" class="addnew2">+ Add new</button>
                </div>
                <div>
                    <button type="submit" name="addcompany2">Add Company</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal-triple">
    <div class="modal-holder">
        <div class="triplecompany-header">
            <h1 id="modal-h1">Edit</h1>
            <span id="exit-modal-triplecompany" class="material-icons">close</span>
        </div>
        <div class="triplecompany-content">
            <form method="post" id="modalform">
                <div>
                    <label for="company_name_m">Name</label>
                    <input type="text" name="company_name_m" id="company_name_m" required/>
                </div>

                <div>
                    <label for="contact_number_m">Contact Number</label>
                    <input type="text" name="contact_number_m" id="contact_number_m" required/>
                </div>

                <div>
                    <label for="email_m">Email</label>
                    <input type="email" name="email_m" id="email_m" required/>
                </div>
                <div>
                    <label for="">Trace Location</label>
                    <!-- this will be a google map -->
                    <div id="map2" class="trace-triplemodal"></div>
                </div>
                

                <div>
                    <label for="comp_location_m">Address</label>
                    <input type="text" name="comp_location_m" id="comp_location_m" required/>
                    <input type="hidden" name="longitude_m" id="longitude_m" placeholder="longitude" required/>
                    <input type="hidden" name="latitude_m" id="latitude_m" placeholder="latitude" required/>
                </div>
                
                <div>
                    <label for="">Set Boundary</label>
                    <div id="map_b_m"></div>
                    <input type="hidden" name="boundary_size_m" id="boundary_size_m" class="map_b_size_m" required/>
                </div>
                <div>
                    <label for="type_m">Type</label>
                    <select name="type_m" id="type_m" required>
                        <option value="manual">Manual</option>
                        <option value="automatic">Automatic</option>
                    </select>
                </div>
                <div>
                    <label for="">Shift</label>
                    <select name="shift_m" id="shift_m" required>
                        <option value="day">Day</option>
                        <option value="night">Night</option>
                        <option value="both">Both</option>
                    </select>
                </div>
                <div>
                    <label for="">Shift Span</label>
                    <select name="shift_span_m" id="shift_span_m" required>
                        <option value="8">8 hrs</option>
                        <option value="12">12 hrs</option>
                    </select>
                </div>
                <div>
                    <label for="">Day Start</label>
                    <select name="day_start_m" id="day_start_m" required>
                        <option value="6:00 am">6:00 AM</option>
                        <option value="7:00 am">7:00 AM</option>
        
                    </select>
                </div>

                <div id="addhere">
                    <label for="">Position</label>
                    <div class="position-container">
                        <input type="text" id="position0_m"/>
                        <input type="text" name="price0" id="price0_m"/>
                    </div>
                </div>

                <div class="addnew-container">
                    <button type="button" id="addnewmodal">Add New</button>
                </div>
                <div>
                    <button type="submit" name="editcompany" id="editcompany">Edit</button>
                    <button type="submit" name="deletecompany" id="deletecompany">Delete</button>
                </div>
            </form>
        </div>
    </div>
    
</div>
<script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
<script src="../scripts/company2.js"></script>

<?php

if(isset($_GET['id']) && isset($_GET['act']) && $_GET['act'] == 'view'){
    $payroll->viewcompany($_GET['id']);
} elseif(isset($_GET['id']) && isset($_GET['act']) && $_GET['act'] == 'edit'){
    $payroll->editcompany($_GET['id']);
} elseif(isset($_GET['id']) && isset($_GET['act']) && $_GET['act'] == 'delete'){
    $payroll->deletecompany($_GET['id']);
}

?>
</body>
</html>