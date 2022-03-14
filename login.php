<?php
require_once('class.php');
$payroll->login();

// if not allowed to login get the message
if(isset($_GET['message'])){
    echo $_GET['message'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login to JTDV</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link rel="stylesheet" href="./styles/css/login.css">
    <link rel="icon" href="./styles/img/icon.png">
</head>
<body>
    <div class="main-container">
        <div class="leftbar">
            <nav>
                <div class="logo-container">
                    <div class="logo"></div>
                    <h3>JTDV</h2>
                </div>
            </nav>
            <div class="content">
                <div class="content-svg">
                    <object data="./styles/SVG_modified/login.svg" type="image/svg+xml"></object>
                </div>
                <div class="content-info">
                    <div class="center">
                        <h2>Engage with people you work with.</h1>
                        <p>Security system will help you manage your people with better user experience. Sign in now.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rightbar">
            <div class="welcome-container">
                <h1>Welcome Back</h1>
                <p>Sign in your account</p>
            </div>
            <form method="POST">
                <!-- insert here -->
                <div class="message error">
                    <p>It appears that your credentials is incorrect. Try it again</p>
                </div>

                <div class="input-container1">
                    <label for="username">Username</label>
                    <div class="icon1">
                        <input type="email" id="username" name="username" placeholder="Enter username" autocomplete="off" required/>
                    </div>
                </div>

                <div class="input-container2">
                    <label for="password">Password</label>
                    <div class="icon2">
                        <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="off" required/>
                    </div>
                </div>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </div>
</body>
</html>