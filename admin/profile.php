<?php
require_once('../class.php');
$sessionData = $payroll->getSessionData();
$payroll->verifyUserAccess($sessionData['access'], $sessionData['fullname'], 2);
$payroll->editAdminProfile($sessionData['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link rel="icon" href="../styles/css/img/icon.png">
    <style>
        .gallery > img {
            height: 150px;
            width: 150px;
            border-radius: 50%;
            background-color: rgba(0,0,0,.6);
        }
        #image { display: none; }
    </style>
</head>
<body>
    <a href="./passInfo.php">Change Password</a>
    <h1>Edit Profile</h1>
    <div class="circle">
        <?= $payroll->viewAdminImage($sessionData['id']); ?>
    </div>
    <form method="post" enctype="multipart/form-data">
        <div>
            <label for="image">Change Profile Photo</label><br/>
            <input type="file" name="image" id='image'/>
        </div>
        <div>
            <label for="firstname">Firstname</label>
            <input type="text" name='firstname' id='firstname' required/>
        </div>
        <div>
            <label for="lastname">Lastname</label>
            <input type="text" name='lastname' id='lastname' required/>
        </div>
        <div>
            <label for="address">Address</label>
            <input type="text" name='address' id='address' required/>
        </div>
        <div>
            <label for="cpnumber">Contact Number</label>
            <input type="text" name='cpnumber' id='cpnumber' required/>
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" name='email' id='email' required/>
        </div>

        <button type='submit' name='saveChanges'>Save Changes</button>
    </form>

<?= $payroll->adminProfile($sessionData['id']); ?>
</body>
</html>