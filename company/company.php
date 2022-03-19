<?php
require_once('../class.php');
$sessionData = $payroll->getSessionData();
$payroll->verifyUserAccess($sessionData['access'], $sessionData['fullname'], 2);
$payroll->addcompany3();

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
        #map, #map_b, 
        #map-addmodal, #map_b-addmodal,
        #map-viewmodal,
        #map-editmodal, #map_b-editmodal
         {
            height: 400px;
            width: 400px;
        }

        .eks {
            height: 100px;
            width: 100px;
            background: hotpink;
            display: block;
        }

        .add-modal {
            display: none;
            position: absolute;
            top: 0; left: 0;
            background: rgb(0 0 0 / 1);
            height: 200vh;
            width: 100vw;
            z-index: 99;
        }
    </style>
</head>
<body>
    <?php 
        // for entire company info
        $payroll->editcompanymodalinfo(); 
        $payroll->deleteCompanyFinal();
        // for position only 
        $payroll->editSpecificPosition();
        $payroll->deleteSpecificPosition();
    ?>
    <div class='main-container'>
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
                    <a href="../logout.php">Logout</a>
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
                    <?php $payroll->newlyaddedcompany(); ?>
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
                                    <th>Email</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $payroll->companylist(); ?>
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
                    <!-- <a id='addmodal-show'>modal</a> -->
                    <button type='button' id='open-modal'>open modal</button>
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
                                <option value="Day">Day</option>
                                <option value="Night">Night</option>
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
                                <option value="06:00 am">06:00 AM</option>
                                <option value="07:00 am">07:00 AM</option>
                            </select>
                        </div>
                        <div class="addhere">
                            <label for="">Position</label>
                            <input type="number" style="display:none;" id="lengthInput" name="lengthInput" value="0" />
                            <input type="text" name="position0" value="Officer in Charge" readonly/>
                            <input type="text" name="price0" placeholder="price0" autocomplete="off"/>
                            <input type="text" name="ot0" placeholder="ot0" autocomplete="off"/>
                        </div>
                        <div class="addnew-container">
                            <button type="button" id="addnew">+ Add new</button>
                        </div>
                        <button type="submit" name="addcompany">Add Company</button><br/>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <!-- add modal -->
    <div class="modal-viewcompany">
        <div class="modal-holder">
            <div class="viewcompany-header">
                <h1>Add Company</h1>
                <span id="exit-modal-viewcompany" class="material-icons">close</span>
            </div>
            <div class="viewcompany-content">
                <form id="myForm" method="post">
                    <div>
                        <label for="company_name">Company</label>
                        <input type="text" name="company_name2" autocomplete="off" required/>
                    </div>
                    <div>
                        <label for="cpnumber">Contact Number</label>
                        <input type="text" name="cpnumber2" autocomplete="off"/>
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input type="email" name="email2" autocomplete="off" required/>
                    </div>
                    <div>
                        <label for="">Trace Location</label>
                        <div id="map-addmodal" class="trace-addmodal"></div>
                    </div>
                    <div>
                        <label for="location_name">Address</label>
                        <input type="text" id="location_name" name="comp_location2" required/>
                        <input type="hidden" id="longitude-addmodal" name="longitude2" placeholder="Longitude" required/>
                        <input type="hidden" id="latitude-addmodal" name="latitude2" placeholder="Latitude" required/>
                    </div>
                    <div>
                        <label for="">Set Boundary</label>
                        <div id="map_b-addmodal"></div>
                        <input type="hidden" name="boundary_size2" placeholder="Boundary size" class="map_b_size-addmodal" required/>
                    </div>
                    <div>
                        <!-- must removed -->
                        <label for="">Type</label>
                        <select name="type" required>
                            <option value="">Select type</option>
                            <option value="manual">Manual</option>
                            <option value="automatic">Automatic</option>
                        </select>
                    </div>
                    <div>
                        <label for="">Shift</label>
                        <select name="shift2" required>
                            <option value="">Select shift</option>
                            <option value="Day">Day</option>
                            <option value="Night">Night</option>
                        </select>
                    </div>
                    <div>
                        <label for="">Shift Span</label>
                        <select name="shift_span2" required>
                            <option value="">Select span</option>
                            <option value="8">8 hrs</option>
                            <option value="12">12 hrs</option>
                        </select>
                    </div>
                    <div>
                        <label for="">Day Start</label>
                        <select name="day_start2" required>
                            <option value="">Select day start</option>
                            <option value="06:00 am">06:00 AM</option>
                            <option value="07:00 am">07:00 AM</option>
                        </select>
                    </div>
                    <div class="addhere-addmodal">
                        <label for="">Position</label>
                        <input type="text" name="position0" value="Officer in Charge" readonly/>
                        <input type="text" name="price0" placeholder="price0" autocomplete="off" required/>
                        <input type="text" name="ot0" placeholder="ot0" autocomplete="off" required/>
                        <input type="number" id="lengthInput-addmodal" name="lengthInput2" style="display:none;" value="0" />
                    </div>
                    <div class="addnew-container">
                        <button type="button" id="addnew-addmodal">+ Add new</button>
                    </div>
                    <div>
                        <button type="submit" name="addcompany2">Add Company</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

     <!-- crud functionality for company -->
     <?php if(isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] == 'view'){ 
        $payroll->viewcompanymodal($_GET['id']);
    } ?>

    <?php if(isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] == 'edit'){ 
        $payroll->editcompanymodal($_GET['id']);
    } ?>

    <?php if(isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] == 'delete'){ 
        $payroll->deletecompanymodal($_GET['id']);
    } ?>


    <!-- when user wants to view the company positions -->
    <?php if(isset($_GET['company'])){?>
        <table>
            <thead>
                <tr>
                    <th>id</th>
                    <th>position</th>
                    <th>price</th>
                    <th>overtime_rate</th>
                    <th>action</th>
                </tr>
            </thead>
            <tbody>
                <?php $payroll->editpositions($_GET['company']); ?> 
            </tbody>
        </table>
        <a href="./company.php?company=<?=$_GET['company'];?>&action=addnewpos">Add Position</a>
    <?php } ?>

    <?php if(isset($_GET['company']) && isset($_GET['action']) && $_GET['action'] == 'addnewpos'){ ?>
        <div class='addnewpos-modal'>
            <h1>Add New Position</h1>
            <form method="POST">
                <input type="text" name='position_name' placeholder='Position Name'/>
                <input type="text" name='price' placeholder='Price'/>
                <input type="text" name='ot' placeholder='Overtime Rate'/>
                <button type='submit' name='addnewpos-btn'>Add Position</button>
            </form>
        </div>
    <?php $payroll->addnewpos($_GET['company']); // action: add
    } ?>

    <!-- when user wants to edit specific position -->
    <?php if(isset($_GET['idPos']) && isset($_GET['actionPos']) && $_GET['actionPos'] == 'edit'){ 
        $payroll->editSpecificPositionModal($_GET['idPos']);
    } ?>

    <!-- when user wants to delete specific position -->
    <?php if(isset($_GET['idPos']) && isset($_GET['actionPos']) && $_GET['actionPos'] == 'delete'){ 
        $payroll->deleteSpecificPositionModal($_GET['idPos']);
    } ?>
    <script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
    <script>
        const addnew = document.querySelector('#addnew');
        addnew.onclick = () => {

            let addhere = document.querySelector('.addhere');
            let inputLength = document.querySelector('#lengthInput');

            // convert to int
            let totalInput = parseInt(inputLength.value);
            inputLength.value = parseInt(totalInput + 1);

            // create elements
            let div = document.createElement('div');
            let pos = document.createElement('input');
            let price = document.createElement('input');
            let ot = document.createElement('input');
            let eks = document.createElement('span');

            pos.setAttribute('name', `position${inputLength.value}`);
            pos.setAttribute('placeholder', `position${inputLength.value}`);
            pos.setAttribute('type', 'text');
            price.setAttribute('name', `price${inputLength.value}`);
            price.setAttribute('placeholder', `price${inputLength.value}`);
            price.setAttribute('type', 'text');
            ot.setAttribute('name', `ot${inputLength.value}`);
            ot.setAttribute('placeholder', `ot${inputLength.value}`);
            ot.setAttribute('type', 'text');
            eks.setAttribute('onclick', 'getParentElement(this)');
            eks.setAttribute('class', 'eks');


            // place to created div
            div.appendChild(pos);
            div.appendChild(price);
            div.appendChild(ot);
            div.appendChild(eks);

            // add to existing parent element
            addhere.appendChild(div);
        }

        function getParentElement(e){

            let lengthInput = document.querySelector('#lengthInput');
            lengthInput.value = parseInt(lengthInput.value) - parseInt(1);
            // lengthInput.value = parseInt(lengthInput.value);

            e.parentElement.children[0].value = ''; //position
            e.parentElement.children[1].value = ''; //price
            e.parentElement.children[2].value = ''; //ot

            let myparent = e.parentElement; // div na walang att


            let addhere = e.parentElement.parentElement; // addhere
            let mydiv = addhere.querySelectorAll('div'); // object

            const mydivArray = Object.values(mydiv); // array

            // object
            let filteredDiv = mydivArray.filter( div => { return div != myparent; });
            const filteredDivArray = Object.values(filteredDiv); // array
            console.log(filteredDiv);
            for(let i = 0; i < filteredDivArray.length; i++){
                filteredDivArray[i].children[0].setAttribute('name', `position${i+1}`);
                filteredDivArray[i].children[1].setAttribute('name', `price${i+1}`);
                filteredDivArray[i].children[2].setAttribute('name', `ot${i+1}`);
            }

            myparent.remove();
        }

        // open add modal
        const openModalBtn = document.querySelector('#open-modal');
        openModalBtn.onclick = () => {
            let addModal = document.querySelector('.modal-viewcompany');
            addModal.style.display = 'flex';

        }

        // close add modal
        let exitModalViewCompany = document.querySelector("#exit-modal-viewcompany");
        exitModalViewCompany.addEventListener('click', e => {
            let viewcompanyModal = document.querySelector('.modal-viewcompany');
            viewcompanyModal.style.display = "none";
        });

        // for add modal
        const addnewAddModal = document.querySelector('#addnew-addmodal');
        addnewAddModal.onclick = () => {

            let addhere = document.querySelector('.addhere-addmodal');
            let inputLength = document.querySelector('#lengthInput-addmodal');

            // convert to int
            let totalInput = parseInt(inputLength.value);
            inputLength.value = parseInt(totalInput + 1);

            // create elements
            let div = document.createElement('div');
            let pos = document.createElement('input');
            let price = document.createElement('input');
            let ot = document.createElement('input');
            let eks = document.createElement('span');

            pos.setAttribute('name', `position${inputLength.value}`);
            pos.setAttribute('placeholder', `position${inputLength.value}`);
            pos.setAttribute('type', 'text');
            price.setAttribute('name', `price${inputLength.value}`);
            price.setAttribute('placeholder', `price${inputLength.value}`);
            price.setAttribute('type', 'text');
            ot.setAttribute('name', `ot${inputLength.value}`);
            ot.setAttribute('placeholder', `ot${inputLength.value}`);
            ot.setAttribute('type', 'text');
            eks.setAttribute('onclick', 'getParentElement2(this)');
            eks.setAttribute('class', 'eks');


            // place to created div
            div.appendChild(pos);
            div.appendChild(price);
            div.appendChild(ot);
            div.appendChild(eks);

            // add to existing parent element
            addhere.appendChild(div);
        }

        function getParentElement2(e){

            let lengthInput = document.querySelector('#lengthInput-addmodal');
            lengthInput.value = parseInt(lengthInput.value) - parseInt(1);
            // lengthInput.value = parseInt(lengthInput.value);

            e.parentElement.children[0].value = ''; //position
            e.parentElement.children[1].value = ''; //price
            e.parentElement.children[2].value = ''; //ot

            let myparent = e.parentElement; // div na walang att


            let addhere = e.parentElement.parentElement; // addhere
            let mydiv = addhere.querySelectorAll('div'); // object

            const mydivArray = Object.values(mydiv); // array

            // object
            let filteredDiv = mydivArray.filter( div => { return div != myparent; });
            const filteredDivArray = Object.values(filteredDiv); // array
            console.log(filteredDiv);
            for(let i = 0; i < filteredDivArray.length; i++){
                filteredDivArray[i].children[0].setAttribute('name', `position${i+1}`);
                filteredDivArray[i].children[1].setAttribute('name', `price${i+1}`);
                filteredDivArray[i].children[2].setAttribute('name', `ot${i+1}`);
            }

            myparent.remove();
        }
    </script>
    <script src='../scripts/comp-location.js'></script>
</body>
</html>