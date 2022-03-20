<?php
require_once('../class.php');
$sessionData = $payroll->getSessionData();
$payroll->verifyUserAccess($sessionData['access'], $sessionData['fullname'], 2);
$payroll->adminChangePassword($sessionData['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../styles/css/img/icon.png">
    <title>Change Password</title>
</head>
<body>
    <a href="./profile.php">Edit Profile</a>
    <form method="post">
        <div>
            <label for="username">Username:</label>
            <input type="email" name="email"/>
        </div>
        <div>
            <label for="current_password">Current Password</label>
            <input type="text" name="current_password" id="current_password" />
        </div>
        <div>
            <label for="new_password">New Password</label>
            <input type="text" name="new_password" id="new_password" />
        </div>
        <div>
            <label for="confirm_password">Confirm Password</label>
            <input type="text" name="confirm_password" id="confirm_password" />
        </div>
        <button type="submit" name='saveChanges'>Save Changes</button>
    </form>
</body>
</html>