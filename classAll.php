<?php

require_once "index.php";
$credentials = array('e_username' => $e_username, 
                     'e_password' => $e_password);

// required to para makapag send ng email
use PHPMailer\PHPMailer\PHPMailer;
require_once "PHPMailer/PHPMailer.php";
require_once "PHPMailer/SMTP.php";
require_once "PHPMailer/Exception.php";


Class Payroll
{
    private $username = "u359933141_jtdv";
    private $password = "+Y^HLMVV2h";

    private $dns = "mysql:host=localhost;dbname=u359933141_payroll";
    protected $pdo;

    private $e_username;
    private $e_password;

    public function __construct(){
        global $credentials;

        $this->e_username = &$credentials['e_username'];
        $this->e_password = &$credentials['e_password'];
    }


    public function con()
    {
        $this->pdo = new PDO($this->dns, $this->username, $this->password);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        return $this->pdo;
    }

    // used to set timezone and get date and time
    public function getDateTime()
    {
        date_default_timezone_set('Asia/Manila'); // set default timezone to manila
        $curr_date = date("Y/m/d"); // date
        $curr_time = date("h:i:sa"); // time

        // return date and time in array
        $_SESSION['datetime'] = array('time' => $curr_time, 'date' => $curr_date);
        return $_SESSION['datetime'];
    }  

    public function login()
    {
        // set 5 attempts
        session_start();
        if(!isset($_SESSION['attempts'])){
            $_SESSION['attempts'] = 5;
        }

        // create email and password using session
        if(!isset($_SESSION['reservedEmail']) && !isset($_SESSION['reservedPassword'])){
            $_SESSION['reservedEmail'] = "";
            $_SESSION['reservedPassword'] = "";
        }


        // if attempts hits 2
        if($_SESSION['attempts'] == 2){
            
            if(isset($_POST['login'])){

                $username = $_POST['username'];
                $password = $this->generatedPassword($_POST['password']);

                if(empty($username) && empty($password)){
                    echo 'input fields are required to login';
                } else {
                    
                    // if user input === reservedEmail
                    if($username === $_SESSION['reservedEmail']){

                        $sqlAttempt2 = "SELECT * FROM super_admin WHERE username = ? AND password = ?";
                        $stmtAttempt2 = $this->con()->prepare($sqlAttempt2);
                        $stmtAttempt2->execute([$_SESSION['reservedEmail'], $password[0]]);
                        $usersAttempt2 = $stmtAttempt2->fetch();
                        $countRowAttempt2 = $stmtAttempt2->rowCount();


                        



                        // if no row detected
                        if($countRowAttempt2 < 1){

                            // dito natin gawin
                            $sqlGetPass =  "SELECT 
                                                    sa.username,
                                                    sd.secret_key as secret_key
                                            FROM super_admin sa
                                            INNER JOIN secret_diary sd
                                            ON sa.username = sd.sa_id
                                            WHERE sa.username = '$username'
                                            ";
                            $stmtGetPass = $this->con()->query($sqlGetPass);
                            $userGetPass = $stmtGetPass->fetch();

                            // send user credentials
                            $this->sendEmail($_SESSION['reservedEmail'], $userGetPass->secret_key);
                            $_SESSION['attempts'] -= 1; // decrease 1 attempt to current attempts
                            echo 'No of attempts: '.$_SESSION['attempts'];
                        } else {
                            // if row detected
                            $fullname = $usersAttempt2->firstname." ".$usersAttempt2->lastname;
                            $action = "login";

                            // set timezone and get date and time
                            $datetime = $this->getDateTime(); 
                            $time = $datetime['time'];
                            $date = $datetime['date'];


                            // add to admin_log
                            $sqlLog = "INSERT INTO admin_log(name, action, time, date)
                                       VALUES(?, ?, ?, ?)
                                      ";
                            $stmtLog = $this->con()->prepare($sqlLog);
                            $stmtLog->execute([$fullname, $action, $time, $date]);
                            $countRowLog = $stmtLog->rowCount();

                            // if insert is successful
                            if($countRowLog > 0){

                                $_SESSION['attempts'] = 5; // reset, back to 5
                                unset($_SESSION['reservedEmail']);
                                unset($_SESSION['reservedPassword']);

                                // create user details using session
                                $_SESSION['adminDetails'] = array('fullname' => $fullname,
                                                                'access' => $usersAttempt2->access,
                                                                'id' => $usersAttempt2->id
                                                                );
                                header('Location: dashboard.php'); // redirect to dashboard.php
                                return $_SESSION['adminDetails']; // after calling the function, return session
                            } else {
                                echo 'Di nag insert sa admin_log';
                            }
                        }
                    }
                }

            }

            

        } else if($_SESSION['attempts'] == 0){ // if attempts bring down to 0
            
            // select username na gumamit ng 5 attempts
            $reservedEmail = $_SESSION['reservedEmail'];
            $setTimerSql = "SELECT * FROM super_admin WHERE username = ?";
            $stmtTimer = $this->con()->prepare($setTimerSql);
            $stmtTimer->execute([$reservedEmail]);
            $usersTimer = $stmtTimer->fetch();
            $countRowTimer = $stmtTimer->rowCount();

            // kapag may nadetect na ganong username
            if($countRowTimer > 0){
                // get id of that username
                $userId = $usersTimer->id;
                $userAccess = $usersTimer->access;
                $accessSuspended = "suspended";
                

                // update column timer set value to DATENOW - 6HRS
                
                $updateTimerSql = "UPDATE `super_admin` 
                                   SET `timer` = NOW() + INTERVAL 6 HOUR, 
                                       `access` = '$accessSuspended'
                                   WHERE `id` = $userId;
                
                                   SET GLOBAL event_scheduler='ON';
                                   CREATE EVENT one_time_event
                                   ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 6 HOUR
                                   ON COMPLETION NOT PRESERVE
                                   DO
                                      UPDATE `super_admin` 
                                      SET `timer` = NULL, 
                                          `access` = '$userAccess' 
                                      WHERE `id` = $userId;
                                  ";
                $updateTimerStmt = $this->con()->prepare($updateTimerSql);
                $updateTimerStmt->execute();
                $updateCountRow = $updateTimerStmt->rowCount();

                // checking if the column was updated already
                if($updateCountRow > 0){
                    echo 'System has been locked for 6 hrs';
                    session_destroy(); // destroy all the sessions
                    
                } else {
                    echo 'There was something wrong in the codes';
                    session_destroy();
                }
            } else {
                echo 'Username is not exists';
            }

        } else {
            // if user hit login button
            if(isset($_POST['login'])){

                // get input data
                $username = $_POST['username'];
                // $password = md5($_POST['password']);
                $password = $this->generatedPassword($_POST['password']);
    
                // if username and password are empty
                if(empty($username) && empty($password[0])){
                    echo 'All input fields are required to login.';
                } else {
                    // check if email is exist using a function
                    $checkEmailArray = $this->checkEmailExist($username); // returns an array(true, cho@gmail.com)
                    $passwordArray = $checkEmailArray[1]; // password ni cho

                    // kapag ang unang array ay nag true
                    if($checkEmailArray[0]){

                        $suspendedAccess = 'suspended';
                        

                        // find account that matches the username and password
                        $sql = "SELECT * FROM super_admin WHERE username = ? AND password = ?";
                        $stmt = $this->con()->prepare($sql);
                        $stmt->execute([$username, $password[0]]);
                        $users = $stmt->fetch();
                        $countRow = $stmt->rowCount();
        
                        // if account exists
                        if($countRow > 0){

                            if($users->access != $suspendedAccess){
                                $fullname = $users->firstname." ".$users->lastname; // create fullname
                                $action = "login"; 
                                    
                                // set timezone and get date and time
                                $datetime = $this->getDateTime(); 
                                $time = $datetime['time'];
                                $date = $datetime['date'];
                
                                // insert mo sa activity log ni admin
                                $actLogSql = "INSERT INTO admin_log(`name`, 
                                                                    `action`,
                                                                    `time`,
                                                                    `date`
                                                                    )
                                            VALUES(?, ?, ?, ?)";
                                $actLogStmt = $this->con()->prepare($actLogSql);
                                $actLogStmt->execute([$fullname, $action, $time, $date]);
                
                                // create user details using session
                                session_start();
                                $_SESSION['attempts'] = 5;
                                $_SESSION['adminDetails'] = array('fullname' => $fullname,
                                                                  'access' => $users->access,
                                                                  'id' => $users->id
                                                                  );
                                unset($_SESSION['reservedEmail']);
                                unset($_SESSION['reservedPassword']);

                                header('Location: dashboard.php'); // redirect to dashboard.php
                                return $_SESSION['adminDetails']; // after calling the function, return session
                            } else {
                                $dateExpiredArray = $this->formatDateLocked($users->timer);
                                $dateExpired = implode(" ", $dateExpiredArray);

                                // set timezone and get date and time
                                $datetime = $this->getDateTime();
                                $time = $datetime['time'];
                                $date = $datetime['date'];

                                // format current date and time
                                $checkDateTimeNowArray = $this->formatDateLocked($date." ".$time);
                                $checkDateTimeNow = implode(" ", $checkDateTimeNowArray);

                                // check if user->timer date was expired
                                if(strtotime($dateExpired) < strtotime($checkDateTimeNow)){
                                    echo 'expired na si timer</br>';
                                    
                                    $varNull = NULL;
                                    $setAccess = 'super administrator';
                                    $sqlUpdateTimer = "UPDATE super_admin SET timer = ?, access = ? WHERE id = ?";
                                    $stmtUpdateTimer = $this->con()->prepare($sqlUpdateTimer);
                                    $stmtUpdateTimer->execute([$varNull, $setAccess, $users->id]);

                                } else {
                                    echo 'Your account has been locked until</>'.
                                         'Date: '.$dateExpired;
                                    
                                }
  
                            } 
                        } else {

                            // insert here, pag suspended na tas naglogin ulit same email dapat yung attempt will set to 0


                            echo "Username and password are not matched <br/>";
                            $_SESSION['attempts'] -= 1; // decrease 1 attempt to current attempts
                            echo 'No of attempts: '.$_SESSION['attempts'];
                            
                            $_SESSION['reservedEmail'] = $username; // blank to kanina, nagkaron na ng laman
                            $_SESSION['reservedPassword'] = $passwordArray; // blank to kanina, nagkaron na ng laman
                        }
                    } else {
                        echo 'Your email is not exist in our system';
                    }
                }
            }
        }
    }

    public function formatDateLocked($date)
    {
        $dateArray = explode(" ", $date);

        $dateExpired = date("F j Y", strtotime($dateArray[0])); // date
        $timeExpired = date("h:i:s A", strtotime($dateArray[1])); // time
        return array($dateExpired, $timeExpired);
    }


    public function checkAccountTimer($id)
    {
        $sql = "SELECT * FROM super_admin WHERE id = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$id]);
        $users = $stmt->fetch();
        $countRow = $stmt->rowCount();

        if($countRow > 0){
            if($users->timer != NULL){
                return true;
            } else {
                return false;
            }
        }

    }

    public function checkEmailExist($email)
    {
        // find email exist in the database
        $sql = "SELECT * FROM super_admin WHERE username = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$email]);
        $users = $stmt->fetch();
        $countRow = $stmt->rowCount();

        // kapag may nadetect
        if($countRow > 0){
            return array(true, $users->password); // yung kaakibat na password, return mo
        } else {
            return array(false, ''); // pag walang nakita, return false and null
        }
    }

    public function sendEmail($email, $password)
    {
        
        $name = 'JTDV Incorporation';
        $subject = 'subject kunwari';
        $body = "Credentials
                 Your username: $email <br/>
                 Your password: $password
                ";

        if(!empty($email)){

            $mail = new PHPMailer();

            // smtp settings
            $mail->isSMTP();
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = true;
            $mail->Username = $this->e_username;  // gmail address
            $mail->Password = $this->e_password;  // gmail password

            $mail->Port = 465;
            $mail->SMTPSecure = "ssl";

            // email settings
            $mail->isHTML(true);
            $mail->setFrom($email, $name);              // Katabi ng user image
            $mail->addAddress($email);                  // gmail address ng pagsesendan
            $mail->Subject = ("$email ($subject)");     // headline
            $mail->Body = $body;                        // textarea

            if($mail->send()){
                // $status = "success";
                $response = "Your credentials has been sent to your email";
                echo '<br/>'.$response;
            } else {
                $status = "failed";
                $response = "Something is wrong: <br/>". $mail->ErrorInfo;
                echo '<br/>'.$status."<br/>".$response;
            }
        } 
    }

    public function logout()
    {
        $this->pdo = null;
        session_destroy();
        header('Location: login.php');
    }

    // vonne
    public function mobile_logout() {
        $this->pdo =null;
        session_start();
        session_destroy();
        header('Location: m_login.php');
    }

    // get login session
    public function getSessionData()
    {
        session_start();
        if($_SESSION['adminDetails']){
            return $_SESSION['adminDetails'];
        }
    }

    public function verifyUserAccess($access, $fullname, $level)
    {
        $message = 'You are not allowed to enter the system';
        if($level == 2){
            $level = '../';
            
            if($access == 'super administrator'){
                return;
            } elseif($access == 'secretary'){
                echo 'Welcome '.$fullname.' ('.$access.')';
            } else {
                header("Location: ".$level."login.php?message=$message");
            }
        } else {
            if($access == 'super administrator'){
                return;
            } elseif($access == 'secretary'){
                // red
                echo 'Welcome '.$fullname.' ('.$access.')';
            } else {
                header("Location: login.php?message=$message");
            }
        }
    }

































    // for secretary functionality in admin
    public function addSecretary($id, $fullnameAdmin)
    {
        if(isset($_POST['addsecretary'])){
            $fullname = $_POST['fullname'];
            $cpnumber = $_POST['cpnumber'];
            $email = $_POST['email'];
            $gender = $_POST['gender'];
            $address = $_POST['address'];
            $access = "secretary";
            // generated password
            $password = $this->generatedPassword($fullname);
            $isDeleted = FALSE;

            $timer = NULL;

            if(empty($fullname) &&
               empty($email) &&
               empty($gender) &&
               empty($address) &&
               empty($password) &&
               empty($isDeleted)
            ){
                echo 'All input fields are required!';
            } else {

                // check email if existing
                
                if($this->checkSecEmailExist($email)){
                    echo 'Email is already exist!';
                } else {

                    // set timezone and get date and time
                    $datetime = $this->getDateTime(); 
                    $time = $datetime['time'];
                    $date = $datetime['date'];

                    $sql = "INSERT INTO secretary(fullname, 
                                                  gender, 
                                                  cpnumber, 
                                                  address, 
                                                  email, 
                                                  password,
                                                  timer, 
                                                  admin_id,
                                                  access,
                                                  isDeleted
                                                  )
                            VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$fullname, $gender, $cpnumber, $address, $email, $password[0], $timer, $id, $access, $isDeleted]);
                    $users = $stmt->fetch();
                    $countRow = $stmt->rowCount();

                    if($countRow > 0){
                        echo 'A new date was added';


                        $this->sendEmail($email, $password[1]);

                        $action = "Add Secretary";

                        $sqlAdminLog = "INSERT INTO admin_log(name, action, time, date)
                                        VALUES(?, ?, ?, ?)";
                        $stmtAdminLog = $this->con()->prepare($sqlAdminLog);
                        $stmtAdminLog->execute([$fullnameAdmin, $action, $time, $date]);
                        $countRowAdminLog = $stmtAdminLog->rowCount();

                        if($countRowAdminLog > 0){
                            echo 'pumasok na sa act log';
                        } else {
                            echo 'di pumasok sa act log';
                        }

                    } else {
                        echo 'Error in adding secretary!';
                    }
                }

            }
        }
    }

    public function generatedPassword($fullname)
    {
        $keyword = "%15@!#Fa4%#@kE";
        $generatedPassword = md5($fullname.$keyword);
        return array($generatedPassword, $fullname.$keyword);
    }

    // for secretary table only
    public function checkSecEmailExist($email)
    {
        // find email exist in the database
        $sql = "SELECT * FROM secretary WHERE email = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$email]);
        $users = $stmt->fetch();
        $countRow = $stmt->rowCount();

        // kapag may nadetect
        if($countRow > 0){
            return true; 
        } else {
            return false; 
        }
    }


    // show only 2 record of secretary
    public function show2Secretary()
    {
        $sql = "SELECT fullname, access FROM secretary LIMIT 2";
        $stmt = $this->con()->query($sql);
        while($row = $stmt->fetch()){
            echo "<h1>$row->fullname</h1><br/>
                  <h4>$row->access</h4><br/>";
        }
    }

    public function showAllSecretary()
    {
        $sql = "SELECT * FROM secretary";
        $stmt = $this->con()->query($sql);

        while($row = $stmt->fetch()){
            echo "<tr>
                    <td>$row->fullname</td>
                    <td>$row->gender</td>
                    <td>$row->email</td>
                    <td>
                        <a href='showAll.php?secId=$row->id'>view</a>
                        <a href='showAll.php?secId=$row->id&email=$row->email'>edit</a>
                        <a href='showAll.php?secIdDelete=$row->id'>delete</a>
                    </td>
                  </tr>";
        }
    }

    public function showSpecificSec()
    {
        if(isset($_GET['secId']) && !isset($_GET['email'])){
            $id = $_GET['secId'];

            $sql = "SELECT * FROM secretary WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            $countRow = $stmt->rowCount();

            if($countRow > 0){
                $fullname = $user->fullname;
                $gender = $user->gender;
                $email = $user->email;
                $cpnumber = $user->cpnumber;
                $address = $user->address;

                echo "<script>
                         let viewModal = document.querySelector('.view-modal');
                         viewModal.setAttribute('id', 'show-modal');

                         let fullname = document.querySelector('#fullname').value = '$fullname';
                         let gender = document.querySelector('#gender').value = '$gender';
                         let email = document.querySelector('#email').value = '$email';
                         let cpnumber = document.querySelector('#cpnumber').value = '$cpnumber';
                         let address = document.querySelector('#address').value = '$address';

                      </script>";
            }
        }
    }

    public function editModalShow()
    {
        if(isset($_GET['secId']) && isset($_GET['email'])){
            $id = $_GET['secId'];

            $sql = "SELECT * FROM secretary WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            $countRow = $stmt->rowCount();

            if($countRow > 0){
                $fullname = $user->fullname;
                $gender = $user->gender;
                $email = $user->email;
                $cpnumber = $user->cpnumber;
                $address = $user->address;

                echo "<script>
                         let viewModal = document.querySelector('.view-modal');
                         viewModal.setAttribute('id', 'show-modal');

                         let fullname = document.querySelector('#fullname').value = '$fullname';
                         let gender = document.querySelector('#gender').value = '$gender';
                         let email = document.querySelector('#email').value = '$email';
                         let cpnumber = document.querySelector('#cpnumber').value = '$cpnumber';
                         let address = document.querySelector('#address').value = '$address';

                         let updateBtn = document.querySelector('#updateBtn');
                         updateBtn.style.display = 'block';
                      </script>";
            }
        }
    }


    public function editSecretary($id, $urlEmail)
    {
        if(isset($_POST['updateSec'])){
            $fullname = $_POST['fullname'];
            $gender = $_POST['gender'];
            $email = $_POST['email'];
            $password = $this->generatedPassword($fullname);
            $number = $_POST['cpnumber'];
            $address = $_POST['address'];

            // oks lang ket walang number
            if(empty($fullname) &&
            empty($gender) &&
            empty($email) &&
            empty($password) &&
            empty($address)
            ){
                echo 'All input fields are required';
            } else {

                if($email != $urlEmail){
                    
                    if($this->checkSecEmailExist($email)){
                        echo 'Email is already exists';
                    } else {
                        
                        // update mo na ko
                        $sql = "UPDATE secretary
                                SET fullname = ?, 
                                    gender = ?,
                                    email = ?,
                                    cpnumber = ?, 
                                    address = ?
                                WHERE id = ?";
                        $stmt = $this->con()->prepare($sql);
                        $stmt->execute([$fullname, $gender, $email, $number, $address, $id]);
                        $countRow = $stmt->rowCount();

                        if($countRow > 0){
                            echo 'Data was updated successfully';
                            $this->sendEmail($email, $password[1]);
                        } else {
                            echo 'No data was updated. There was something wrong in our codes';
                        }
                    }
                } else {
                    
                    // update mo na ko
                    $sql = "UPDATE secretary
                    SET fullname = ?, 
                        gender = ?,
                        email = ?,
                        cpnumber = ?, 
                        address = ?
                    WHERE id = ?";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$fullname, $gender, $email, $number, $address, $id]);
                    $countRow = $stmt->rowCount();

                    if($countRow > 0){
                        echo '<br/>Data was updated successfully';
                    } else {
                        echo '<br/>No data was updated. There was something wrong in our codes';
                    }
                }

            }
        }
    }


    public function deleteSecretary($id)
    {
        echo "<script>
                let viewModal = document.querySelector('.view-modal');
                viewModal.setAttribute('id', 'show-modal');

                document.querySelector('#fullname').setAttribute('type', 'hidden');
                document.querySelector('#fullname').removeAttribute('required');

                document.querySelector('#gender').style.visibility = 'hidden';
                document.querySelector('#gender').removeAttribute('required');
                
                document.querySelector('#email').setAttribute('type', 'hidden');
                document.querySelector('#email').removeAttribute('required');

                document.querySelector('#cpnumber').setAttribute('type', 'hidden');
                document.querySelector('#cpnumber').removeAttribute('required');

                document.querySelector('#address').setAttribute('type', 'hidden');
                document.querySelector('#address').removeAttribute('required');

                let updateBtn = document.querySelector('#updateBtn');
                updateBtn.style.display = 'none';

                let deleteBtn = document.querySelector('#deleteBtn');
                deleteBtn.style.display = 'block';

                let deleteHeader = document.querySelector('#myH1');
                deleteHeader.style.display = 'block';

                let labels = document.querySelectorAll('label');
                labels.forEach((label)=>{
                    label.style.display = 'none';
                });
              </script>";

        if(isset($_POST['deleteSec'])){
            if(empty($id)){
                echo 'Id are required to delete this employee';
            } else {
                $sql = "DELETE FROM secretary WHERE id = ?";
                $stmt = $this->con()->prepare($sql);
                $stmt->execute([$id]);
                $countRow = $stmt->rowCount();

                if($countRow > 0){
                    echo 'Data was successfully deleted';
                } else {
                    echo 'There was something wrong in our codes';
                }
            }
        }
        
    }




































    





    public function addEmployee(){
        
        if(isset($_POST['addemployee'])){

            
            date_default_timezone_set('Asia/Manila'); // set default timezone to manila
            $curr_year = date("Y"); // year

            $empId = $curr_year."-".$this->createEmpId(); // generated empId

            if($empId == NULL || $empId == 0 || $empId == ""){
                $empId = $curr_year."-0";
            }

            $firstname = $_POST['firstname'];
            $lastname = $_POST['lastname'];
            $address = $_POST['address'];
            $email = $_POST['email'];
            $password = $this->generatedPassword($firstname." ".$lastname);
            $number = $_POST['number'];
            $access = "employee";
            $availability = "available";

            $fullname = $firstname.$lastname;

            if(empty($firstname) &&
               empty($lastname) &&
               empty($number) &&
               empty($address) &&
               empty($email) &&
               empty($password) &&
               empty($access) &&
               empty($availability)
            ){
                echo 'All input fields are required!';
            } else {

                if($this->checkEmpEmailExist($email)){
                    echo 'Your email is already exists!';
                } else {

                    // set timezone and get date and time
                    $datetime = $this->getDateTime(); 
                    $time = $datetime['time'];
                    $date = $datetime['date'];

                    // add mo na ko
                    $sql = "INSERT INTO employee(empId,
                                                 firstname,
                                                 lastname,
                                                 cpnumber,
                                                 address,
                                                 email,
                                                 password,
                                                 access,
                                                 availability,
                                                 time,
                                                 date)
                            VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$empId, $firstname, $lastname, $number, $address, $email, $password[0], $access, $availability, $time, $date]);
                    $countRow = $stmt->rowCount();

                    if($countRow > 0){
                        echo 'A new data was added';

                        // gagamitin pang login sa employee dashboard
                        $sqlSecretKeyEmployee = "INSERT INTO secret_diarye(e_id, secret_key)
                                                    VALUES(?, ?)";
                        $stmtSecretKeyEmployee = $this->con()->prepare($sqlSecretKeyEmployee);
                        $stmtSecretKeyEmployee->execute([$email, $fullname]);
                        // send user credentials
                        $this->sendEmail($email, $fullname);

                    } else {
                        echo 'No data was added. There was something wrong in our codes';
                    }
                }

            }
        }
    }



    public function createEmpId()
    {
        $sql = "SELECT MAX(id) AS id FROM employee";
        $stmt = $this->con()->query($sql);
        $users = $stmt->fetch();
        $getId = $users->id;
        
        return $getId;
    }


    // employee.php      td without actions
    public function showAllEmp(){
        $sql = "SELECT * FROM employee";
        $stmt = $this->con()->query($sql);

        while($row = $stmt->fetch()){
            $type = $row->watType == NULL ? 'None' : $row->watType;
            echo "<tr>
                    <td>$row->lastname, "."$row->firstname</td>
                    <td>$row->cpnumber</td>
                    <td>$row->availability</td>
                    <td>$type</td>
                    <td>$row->date</td>
                  </tr>";
        }
    }


    // showEmployees.php      td with actions
    public function showAllEmpActions(){
        $sql = "SELECT * FROM employee WHERE availability = 'available'";
        $stmt = $this->con()->query($sql);

        while($row = $stmt->fetch()){
            $type = $row->watType == 'NULL' ? 'None' : $row->watType;

            echo "<tr>
                    <td><input type='checkbox' id='c$row->id' onclick='setVal(this, $row->id);'/></td>
                    <td><label for='c$row->id'>$row->lastname, $row->firstname</label></td>
                    <td>$row->cpnumber</td>
                    <td>$row->availability</td>
                    <td>$type</td>
                    <td>$row->date</td>
                    <td>
                       <a href='showEmployees.php?id=$row->id&email=$row->email'>Edit</a>
                       <a href='showEmployees.php?id=$row->id'>Delete</a>
                    </td>
                  </tr>";
        }
    }

















    // blabla
    public function addNewSelectedGuard()
    {
        $sql = "SELECT * FROM employee WHERE availability = 'available'";
        $stmt = $this->con()->query($sql);
        while($users = $stmt->fetch()){
            echo "<tr class='doDelete' data-empIdDelete='$users->id'>
                    <td><input type='checkbox' id='c$users->id' onclick='setVal(this, $users->id)' /></td>
                    <td><label for='c$users->id'>$users->firstname $users->lastname</label></td>
                    <td><label for='c$users->id'>$users->email</label></td>
                    <td><label for='c$users->id'>$users->address</label></td>
                  </tr>";
        }
    }


































    // redirect to add company
    public function selectguards()
    {
        if(isset($_POST['selectguards']))
        {
            $ids = $_POST['ids'];
            header("Location: selectedGuards.php?ids=$ids");
        }
    }

    public function dropdownCompanyDetails()
    {
        $sql = "SELECT * FROM company";
        $stmt = $this->con()->query($sql);

        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $compId = $row["id"];

            // count columns for looping
            $sqlCount = "SELECT COUNT(*) as total 
                         FROM information_schema.`COLUMNS` 
                         WHERE table_name = 'company'
                         AND TABLE_SCHEMA = 'payroll'";
            $stmtCount = $this->con()->query($sqlCount);
            $usersCount = $stmtCount->fetch(PDO::FETCH_ASSOC);

            $positions = "";
            $prices = "";
            for($i = 0; $i < $usersCount['total']; $i++){
                if($row["position$i"]){
                    $positions .= $row["position$i"] .",";
                    $prices .= $row["price$i"] .",";
                } else {
                    break;
                }
                
            }
            
            $comp_location = $row["comp_location"];
            $company_name = $row["company_name"];
            

            echo "<option data-pos='$positions'
                          data-price='$prices'
                          data-loc='$comp_location'
                          data-comId='$compId'
                          value='$company_name'>$company_name
                  </option>";
        }
    }
    
    public function selectguardsAddCompany($ids)
    {    
        $idArray = explode (",", $ids); 

        $sql = "SELECT * FROM employee WHERE id = ?";
        $stmt = $this->con()->prepare($sql);
        
        // set timezone and get date and time
        $datetime = $this->getDateTime(); 
        $date = $datetime['date'];

        $countInputs = sizeof($idArray);
        echo "<input type='hidden' value='$countInputs' name='countInput' required/>";

        for($i = 0; $i < sizeof($idArray); $i++){
            $rowId = $idArray[$i]; 
            $stmt->execute([$idArray[$i]]);
            $user = $stmt->fetch();
            

            if($user->firstname == '' && 
               $user->firstname == NULL &&
               $user->lastname == '' &&
               $user->lastname == NULL){
                echo "<script>window.location.assign('showEmployees.php');</script>";
            } else {
                $fullname = $user->firstname ." ". $user->lastname;
            }

            echo "<tr>
                    <td><input type='hidden' name='empId$i' value='$user->empId' required/><span>$fullname</span></td>
                    <td>
                       <select onchange='setPrice(this)' class='puthere' name='position$i' required>
                          <option value=''>Select Position</option>
                       </select>
                       <input type='hidden' class='puthere2' name='price$i' required/>
                    </td>
                    <td>$date</td>
                    <td>
                        <span data-deleteId='$rowId' onclick='removeMe(this)'>Delete</span>
                    </td>
                  </tr>";
        }

    }



    // unavailable guards
    public function setUnavailableGuards()
    {
        if(isset($_POST['assignguards']))
        {
            $countInput = $_POST['countInput'];

            if(empty($countInput) || $countInput == '' || $countInput == NULL){
                echo "<script>window.location.assign('showEmployees.php');</script>";
            } else {
                
                // year, month, day
                // $expiration_date = $_POST['year']."/".$_POST['month']."/".$_POST['day'];
                $year = $_POST['year'];
                $month = $_POST['month'];
                $day = $_POST['day'];

                // date now - input fields
                $expiration_date = date('Y-m-d', strtotime("+$year years $month months $day days"));

                $availability = "unavailable";

                $company = $_POST['companyname'];
                
                $sqlCompany = "SELECT * FROM company WHERE company_name = ?";
                $stmtCompany = $this->con()->prepare($sqlCompany);
                $stmtCompany->execute([$company]);
                $userCompany = $stmtCompany->fetch();
                $countRowCompany = $stmtCompany->rowCount();

                $type = "";

                if($countRowCompany > 0){
                    $type .= $userCompany->watType;
                }

                for($i = 0; $i < $countInput; $i++)
                {
                    $empId = $_POST["empId$i"];
                    $position = $_POST["position$i"];
                    $ratesperDay = $_POST["price$i"];
                    
                    $sql = "INSERT INTO schedule(empId, company, expiration_date)
                            VALUES(?, ?, ?)";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$empId, $company, $expiration_date]);
                    $countRow = $stmt->rowCount();

                    if($countRow > 0){
                        $sqlEmpUpdate = "UPDATE employee
                                         SET position = ?,
                                             ratesperDay = ?,
                                             watType = ?,
                                             availability = ?
                                         WHERE empId = ?";
                        $stmtEmpUpdate = $this->con()->prepare($sqlEmpUpdate);
                        $stmtEmpUpdate->execute([$position, $ratesperDay, $type, $availability, $empId]);
                        $countRowEmpUpdate = $stmtEmpUpdate->rowCount();

                        

                        if($countRowEmpUpdate > 0){

                            $sqlHR = "SELECT * FROM company WHERE company_name = ?";
                            $stmtHR = $this->con()->prepare($sqlHR);
                            $stmtHR->execute([$company]);
                            $usersHR = $stmtHR->fetch();
                            $countRowHR = $stmtHR->rowCount();
                            $hiredGuards = 0;

                            if($countRowHR > 0){
                                if($usersHR->hired_guards == 0 || $usersHR->hired_guards == NULL){
                                    $hiredGuards = $countInput;
                                } else {
                                    $hiredGuards = $usersHR->hired_guards + 1;
                                }
                            }

                            

                            $sqlHiredGuards = "UPDATE company SET hired_guards = ? WHERE company_name = ?";
                            $stmtHiredGuards = $this->con()->prepare($sqlHiredGuards);
                            $stmtHiredGuards->execute([$hiredGuards, $company]);
                        }
                    }

                }
            }
        }
    }






    public function updateEmployee($id, $urlEmail)
    {
        if(isset($_POST['editemployee'])){

            $firstname = $_POST['firstname'];
            $lastname = $_POST['lastname'];
            $address = $_POST['address'];
            $email = $_POST['email'];
            $password = $this->generatedPassword($firstname." ".$lastname); // used to send credentials
            $number = $_POST['number'];

            if(empty($firstname) &&
               empty($lastname) &&
               empty($number) &&
               empty($address) &&
               empty($email) &&
               empty($password)
            ){
                echo 'All input fields are required!';
            } else {
                
                if($email != $urlEmail){
                    
                    if($this->checkEmpEmailExist($email)){
                        echo 'Email is already exists';
                    } else {
                        
                        // update mo na ko
                        $sql = "UPDATE employee
                                SET firstname = ?, 
                                    lastname = ?,
                                    cpnumber = ?,
                                    address = ?, 
                                    email = ?
                                WHERE id = ?";
                        $stmt = $this->con()->prepare($sql);
                        $stmt->execute([$firstname, $lastname, $number, $address, $email, $id]);
                        $countRow = $stmt->rowCount();

                        if($countRow > 0){
                            echo 'Data was updated successfully';

                            // after mo maupdate kunin mo yung data
                            $sql2 = "SELECT e.email, 
                                            de.e_id, 
                                            de.secret_key as secret_key
                                     FROM employee e
                                     INNER JOIN secret_diarye de
                                     ON e.email = de.e_id

                                     WHERE e.email = ?";
                            $stmt2 = $this->con()->prepare($sql2);
                            $stmt2->execute([$email]);
                            $users2 = $stmt2->fetch();
                            $countRow2 = $stmt2->rowCount();

                            if($countRow2 > 0){
                                $this->sendEmail($users2->email, $users2->secret_key);
                                echo "<script>window.location.assign('showEmployees.php');</script>";
                            } else {
                                echo 'There was something wrong in our codes';
                            }
                        } else {
                            echo 'No data was updated. There was something wrong in our codes';
                        }
                    }
                } else {
                    
                    // update mo na ko
                    $sql = "UPDATE employee
                    SET firstname = ?, 
                        lastname = ?,
                        cpnumber = ?,
                        address = ?, 
                        email = ?
                    WHERE id = ?";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$firstname, $lastname, $number, $address, $email, $id]);
                    $countRow = $stmt->rowCount();

                    if($countRow > 0){
                        echo '<br/>Data was updated successfully';
                    } else {
                        echo '<br/>No data was updated. There was something wrong in our codes';
                    }
                }
                    
            }
        }
    }


    public function deleteEmployee($id){
        if(isset($_POST['deleteemployee'])){
            $sql = "DELETE FROM employee WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$id]);
            $countRow = $stmt->rowCount();

            if($countRow > 0){
                echo 'Data was successfully deleted';
            } else {
                echo 'There was something wrong in our code';
            }
        }

        if(isset($_POST['cancelemployee'])){
            header("Location: showEmployees.php");
        }
    }



    public function showSpecificEmp()
    {
        if(isset($_GET['id'])){
            $id = $_GET['id'];

            $sql = "SELECT * FROM employee WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            $countRow = $stmt->rowCount();

            if($countRow > 0){
                $firstname = $user->firstname;
                $lastname = $user->lastname;
                $address = $user->address;
                $email = $user->email;
                $number = $user->cpnumber;

                echo "<script>
                         let viewModal = document.querySelector('.modal-edit');
                         

                         let firstname = document.querySelector('#firstname').value = '$firstname';
                         let lastname = document.querySelector('#lastname').value = '$lastname';
                         let address = document.querySelector('#address').value = '$address';
                         let email = document.querySelector('#email').value = '$email';
                         let number = document.querySelector('#number').value = '$number';
                      </script>";
            }
        }
    }


    public function checkEmpEmailExist($email)
    {
        // find email exist in the database
        $sql = "SELECT * FROM employee WHERE email = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$email]);
        $users = $stmt->fetch();
        $countRow = $stmt->rowCount();

        // kapag may nadetect
        if($countRow > 0){
            return true;
        } else {
            return false;
        }
    }







    

























    // company
    public function addCompany()
    {
        if(isset($_POST['addcompany'])){
            $length = $_POST['length'];

            $company_name = $_POST['company_name'];
            $cpnumber = $_POST['cpnumber'];
            $email = $_POST['email'];
            $comp_location = $_POST['comp_location'];
            $longitude = $_POST['longitude'];
            $latitude = $_POST['latitude'];
            $boundary_size = $_POST['boundary_size'];
            $type = $_POST['type'];
            $shift = $_POST['shift'];
            $shift_span = $_POST['shift_span'];
            $day_start = $_POST['day_start'];

            // set timezone and get date and time
            $datetime = $this->getDateTime();
            $date = $datetime['date'];
        
            if($length > 0){
                
                for($i = 0; $i < (int)$length; $i++){
                    $names = "name".$i; // name1
                    $prices = "price".$i; // price1
        
                    if($i === 0){
                        $sqlAddRow = "INSERT INTO company(company_name,
                                                          cpnumber,
                                                          email,
                                                          comp_location,
                                                          longitude,
                                                          latitude,
                                                          boundary_size,
                                                          watType,
                                                          shifts,
                                                          shift_span,
                                                          day_start,
                                                          date
                                                         )
                                      VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                      ";
                        $stmtRow = $this->con()->prepare($sqlAddRow);
                        $stmtRow->execute([$company_name,
                                           $cpnumber,
                                           $email,
                                           $comp_location,
                                           $longitude,
                                           $latitude,
                                           $boundary_size,
                                           $type,
                                           $shift,
                                           $shift_span,
                                           $day_start,
                                           $date
                                          ]);
                        $usersRow = $stmtRow->fetch();
                        $countRowRow = $stmtRow->rowCount();

                        if($countRowRow > 0){
                            echo "A new data was added";
                        } else {
                            echo "There's something wrong in our codes";
                        }
                    }
        
                    if($i > 0){
                        $sql = "ALTER TABLE company ADD position".$i." VARCHAR(100) NULL, 
                                                    ADD price".$i." VARCHAR(100) NULL";
                        $stmt = $this->con()->prepare($sql);
                        $stmt->execute();
                    }
        
                    if(!empty($_POST[$names]) && !empty($_POST[$prices])){
                        $sqlAddPosition = "UPDATE company 
                                           SET position$i = '$_POST[$names]',
                                               price$i = '$_POST[$prices]'
                                           WHERE company_name = '$company_name';";
                        $stmtPosition = $this->con()->prepare($sqlAddPosition);
                        $stmtPosition->execute();
                    } else {
                        echo 'Position and price are required to fill up';
                    }
                    
                }
            }
        }
    }


    public function companyNewlyAdded()
    {
        $sql = "SELECT * FROM company ORDER BY id DESC LIMIT 4";
        $stmt = $this->con()->query($sql);
        // set timezone and get date and time
        $datetime = $this->getDateTime();
        $date = $datetime['date'];

        while($row = $stmt->fetch()){
            
            $status = '';
            
            if(strtotime($row->date) <= strtotime($date) && 
               strtotime($row->date) >= strtotime($date.'-15 day')){
                $status = 'recent';
            } elseif(strtotime($row->date) >= strtotime($date.'-30 day') && 
                     strtotime($row->date) <= strtotime($date.'-15 day')){
                $status = 'late';
            } else {
                $status = 'old';
            }

            echo "<div class='cards'>
                    <div class='circle $status'></div>
                    <h3>$row->company_name</h3>
                    <p>$row->date</p>
                  </div>";
        }
    }



    // show all list of companies
    public function listofcompany()
    {
        $sql = "SELECT * FROM company";
        $stmt = $this->con()->query($sql);

        while($row = $stmt->fetch()){
            $hiredGuards = $row->hired_guards != '' ? $row->hired_guards : 0;

            echo "<tr>
                    <td>$row->company_name</td>
                    <td>$hiredGuards</td>
                    <td>$row->watType</td>
                    <td>$row->date</td>
                    <td>
                        <a href='company.php?id=$row->id&act=view'>View</a>
                        <a href='company.php?id=$row->id&act=edit'>Edit</a>
                        <a href='company.php?id=$row->id&act=delete'>Delete</a>
                    </td>
                  </tr>";
        }
    }

















































    public function viewcompany($id)
    {
        $sql = "SELECT * FROM company WHERE id = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$id]);
        $users = $stmt->fetch(PDO::FETCH_ASSOC);
        $countRow = $stmt->rowCount();

        if($countRow > 0){

            $createInput = "<script>";
            $inputLength = 0;

            for($i = 0; $i < 10; $i++){
                
                if(isset($users["position$i"])){
                    if($users["position$i"] != '' && $users["position$i"] != NULL){
                        $myValue = $users["position$i"];
                        $myValue2 = $users["price$i"];
                        $createInput .= "
                            let myPosition$i = document.createElement('input');
                            myPosition$i.setAttribute('type', 'text');
                            myPosition$i.setAttribute('name', 'position$i');
                            myPosition$i.setAttribute('class', 'added_input');
                            myPosition$i.setAttribute('readonly', 'readonly');
                            myPosition$i.value = '$myValue';

                            let myPrice$i = document.createElement('input');
                            myPrice$i.setAttribute('type', 'text');
                            myPrice$i.setAttribute('name', 'price$i');
                            myPrice$i.setAttribute('class', 'added_input');
                            myPrice$i.setAttribute('readonly', 'readonly');
                            myPrice$i.value = '$myValue2';
                        ";
                        $inputLength++;
                    }
                } else {
                    $createInput .= "
                        let addhere = document.querySelector('#addhere');
                        
                        for(let j = 0; j < $inputLength; j++){
                            let myPos = 'myPosition' + j;
                            let callMe;
                            
                            let myPri = 'myPrice' + j;
                            let callMe2;

                            addhere.append(eval(callMe+'='+myPos));
                            addhere.append(eval(callMe2+'='+myPri));
                        }
                    </script>";
                    break;
                }
                
            }


            $company_name = $users['company_name'];
            $contact_number = $users['cpnumber'];
            $email = $users['email'];
            $comp_location = $users['comp_location'];
            $longitude = $users['longitude'];
            $latitude = $users['latitude'];
            $boundary_size = $users['boundary_size'];
            $watType = $users['watType'];
            $shifts = $users['shifts'];
            $shift_span = $users['shift_span'];
            $day_start = $users['day_start'];
            // date supposed to be here

            echo "<script>

                    let company_name_m = document.querySelector('#company_name_m');
                    company_name_m.value = '$company_name';
                    company_name_m.setAttribute('readonly', 'readonly');

                    let contact_number_m = document.querySelector('#contact_number_m');
                    contact_number_m.value = '$contact_number';
                    contact_number_m.setAttribute('readonly', 'readonly');

                    let email_m = document.querySelector('#email_m');
                    email_m.value = '$email';
                    email_m.setAttribute('readonly', 'readonly');

                    let comp_location_m = document.querySelector('#comp_location_m');
                    comp_location_m.value = '$comp_location';
                    comp_location_m.setAttribute('readonly', 'readonly');

                    let longitude_m = document.querySelector('#longitude_m');
                    longitude_m.value = '$longitude';
                    longitude_m.setAttribute('readonly', 'readonly');

                    let latitude_m = document.querySelector('#latitude_m');
                    latitude_m.value = '$latitude';
                    latitude_m.setAttribute('readonly', 'readonly');

                    let boundary_size_m = document.querySelector('#boundary_size_m');
                    boundary_size_m.value = '$boundary_size';
                    boundary_size_m.setAttribute('disabled', 'disabled');

                    let type_m = document.querySelector('#type_m');
                    type_m.value = '$watType';
                    type_m.setAttribute('disabled', 'disabled');

                    let shift_m = document.querySelector('#shift_m');
                    shift_m.value = '$shifts';
                    shift_m.setAttribute('disabled', 'disabled');

                    let shift_span_m = document.querySelector('#shift_span_m');
                    shift_span_m.value = '$shift_span';
                    shift_span_m.setAttribute('disabled', 'disabled');

                    let day_start_m = document.querySelector('#day_start_m');
                    day_start_m.value = '$day_start';
                    day_start_m.setAttribute('disabled', 'disabled');


                    // detect location
                        let currPosition2 = [];

                        navigator.geolocation.getCurrentPosition((pos) => {
                            currPosition2.push($longitude);
                            currPosition2.push($latitude);
                            
                            let userLongitude2 = document.querySelector('#longitude_m');
                            let userLatitude2 = document.querySelector('#latitude_m');

                            mapboxgl.accessToken = 'pk.eyJ1IjoiamVsbHliZWFucy1zbHkiLCJhIjoiY2t4NmVnYXU5MnJkNjJ1cW92ZDN1b3hndiJ9.FgwIbfJQOkbfbc1OtJHv2Q';
                            const map2 = new mapboxgl.Map({
                                container: 'map2',
                                style: 'mapbox://styles/mapbox/satellite-streets-v9',
                                center: currPosition2,
                                zoom: 18
                            });

                            const marker2 = new mapboxgl.Marker().setLngLat(currPosition2).addTo(map2); 

                        });

                        // disable button for viewing
                        let addnewmodal = document.querySelector('#addnewmodal');
                        let editcompany = document.querySelector('#editcompany');
                        let deletecompany = document.querySelector('#deletecompany');

                        addnewmodal.remove();
                        editcompany.remove();
                        deletecompany.remove();

                  </script>";
            echo $createInput;
        }
    }














































    public function editcompany($id)
    {
        $sql = "SELECT * FROM company WHERE id = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$id]);
        $users = $stmt->fetch(PDO::FETCH_ASSOC);
        $countRow = $stmt->rowCount();
        
        if($countRow > 0){

            $createInput = "<script>";
            $inputLength = 0;

            // 50 position kaya idetect
            for($i = 1; $i <= 50; $i++){
                $j = $i + 1;

                if(isset($users["position$i"])){
                    $myValue = $users["position$i"];
                    $myValue2 = $users["price$i"];

                    if($users["position$i"] != '' && $users["position$i"] != NULL){
                        
                        $createInput .= "
                            let myPosition$i = document.createElement('input');
                            myPosition$i.setAttribute('type', 'text');
                            myPosition$i.setAttribute('name', 'position$i');
                            myPosition$i.setAttribute('class', 'added_input');
                            myPosition$i.required = true;
                            myPosition$i.removeAttribute('readonly');
                            myPosition$i.value = '$myValue';

                            let myPrice$i = document.createElement('input');
                            myPrice$i.setAttribute('type', 'text');
                            myPrice$i.setAttribute('name', 'price$i');
                            myPrice$i.required = true;
                            myPrice$i.removeAttribute('readonly');
                            myPrice$i.value = '$myValue2';

                            
                        ";
                        $inputLength++;
                    }
                } else {
                    $createInput .= "
                        let addhere = document.querySelector('#addhere'); // div
                        let createdLength = 0;
                        
                        let lengthInput = document.createElement('input');
                            lengthInput.setAttribute('type', 'number');
                            lengthInput.setAttribute('name', 'lengthInput');
                            lengthInput.setAttribute('id', 'lengthInput');

                        let lengthInputOriginal = document.createElement('input');
                            lengthInputOriginal.setAttribute('type', 'number');
                            lengthInputOriginal.setAttribute('name', 'lengthInputOriginal');
                            lengthInputOriginal.setAttribute('id', 'lengthInputOriginal');
                            

                        // add all created inputs to form
                        for(let j = 1; j <= $inputLength; j++){
                            let myPos = 'myPosition' + j; let callMe; 
                            let myPri = 'myPrice' + j; let callMe2;

                            let eks = document.createElement('span');
                                eks.setAttribute('class', 'eks');
                                eks.setAttribute('onclick', 'getParentElement(this)');

                            let myDiv = document.createElement('div');

                            myDiv.append(eval(callMe+'='+myPos));
                            myDiv.append(eval(callMe2+'='+myPri));
                            myDiv.append(eks);

                            addhere.append(myDiv);

                            createdLength += 1;
                        }
                        
                        lengthInput.value = createdLength;
                        lengthInputOriginal.value = createdLength;
                        addhere.append(lengthInput);
                        addhere.append(lengthInputOriginal);

                        // for add new button
                        let addnewmodal = document.querySelector('#addnewmodal');
                        addnewmodal.addEventListener('click', ()=>{
                            let addone = parseInt(lengthInput.value);
                            lengthInput.value = parseInt(addone + 1);
                            lengthInputOriginal.value = parseInt(addone + 1);

                            // let decInput = parseInt(lengthInput.value) - parseInt(1);
                            let decInput = parseInt(lengthInput.value);

                            let newpos = document.createElement('input');
                                newpos.setAttribute('type', 'text');
                                newpos.setAttribute('name', 'position'+decInput);
                                newpos.setAttribute('placeholder', 'name');
                                newpos.required = true;
                            
                            let newpri = document.createElement('input');
                                newpri.setAttribute('type', 'text');
                                newpri.setAttribute('name', 'price'+decInput);
                                newpri.setAttribute('placeholder', '00.00');
                                newpri.required = true;

                            let eks = document.createElement('span');
                                eks.setAttribute('class', 'eks');
                                eks.setAttribute('onclick', 'getParentElement(this)');
                            
                            let myDiv = document.createElement('div');
                                
                            myDiv.append(newpos);
                            myDiv.append(newpri);
                            myDiv.append(eks);

                            addhere.append(myDiv);
                        });

                        
                    </script>";
                    break;
                }
                
            }

            // data come from users->columnName();
            $company_name = $users['company_name'];
            $contact_number = $users['cpnumber'];
            $email = $users['email'];
            $comp_location = $users['comp_location'];
            $longitude = $users['longitude'];
            $latitude = $users['latitude'];
            $boundary_size = $users['boundary_size'];
            $watType = $users['watType'];
            $shifts = $users['shifts'];
            $shift_span = $users['shift_span'];
            $day_start = $users['day_start'];
            // date supposed to be here
            $position0 = $users['position0'];
            $price0 = $users['price0'];


            // find employees in schedule table
            $sqlFindEmployees = "SELECT * FROM schedule WHERE company = ?";
            $stmtFindEmployees = $this->con()->prepare($sqlFindEmployees);
            $stmtFindEmployees->execute([$company_name]);
            $usersFindEmployees = $stmtFindEmployees->fetch();
            $countRowFindEmployees = $stmtFindEmployees->rowCount();

            $inputFieldsAttr = "<script>";
            $editDropdown = false;

            if($countRowFindEmployees > 0){

                $editDropdown = false;
                $inputFieldsAttr .= "
                    let company_name_m = document.querySelector('#company_name_m');
                    company_name_m.value = '$company_name';
                    company_name_m.removeAttribute('readonly');

                    let contact_number_m = document.querySelector('#contact_number_m');
                    contact_number_m.value = '$contact_number';
                    contact_number_m.removeAttribute('readonly');

                    let email_m = document.querySelector('#email_m');
                    email_m.value = '$email';
                    email_m.removeAttribute('readonly');

                    let comp_location_m = document.querySelector('#comp_location_m');
                    comp_location_m.value = '$comp_location';
                    comp_location_m.removeAttribute('readonly');

                    let longitude_m = document.querySelector('#longitude_m');
                    longitude_m.value = '$longitude';
                    longitude_m.removeAttribute('readonly');

                    let latitude_m = document.querySelector('#latitude_m');
                    latitude_m.value = '$latitude';
                    latitude_m.removeAttribute('readonly');

                    let boundary_size_m = document.querySelector('#boundary_size_m');
                    boundary_size_m.value = '$boundary_size';
                    boundary_size_m.removeAttribute('disabled');

                    let type_m = document.querySelector('#type_m');
                    type_m.value = '$watType';
                    type_m.disabled = true;

                    let shift_m = document.querySelector('#shift_m');
                    shift_m.value = '$shifts';
                    shift_m.disabled = true;

                    let shift_span_m = document.querySelector('#shift_span_m');
                    shift_span_m.value = '$shift_span';
                    shift_span_m.disabled = true;

                    let day_start_m = document.querySelector('#day_start_m');
                    day_start_m.value = '$day_start';
                    day_start_m.disabled = true;
                    
                    let position0_m = document.querySelector('#position0_m');
                    position0_m.value = '$position0';
                    position0_m.removeAttribute('disabled');

                    let price0_m = document.querySelector('#price0_m');
                    price0_m.value = '$price0';
                    price0_m.removeAttribute('disabled');

                    let currPosition2 = [];

                    navigator.geolocation.getCurrentPosition((pos) => {
                        currPosition2.push($longitude);
                        currPosition2.push($latitude);
                        
                        let userLongitude2 = document.querySelector('#longitude_m');
                        let userLatitude2 = document.querySelector('#latitude_m');

                        mapboxgl.accessToken = 'pk.eyJ1IjoiamVsbHliZWFucy1zbHkiLCJhIjoiY2t4NmVnYXU5MnJkNjJ1cW92ZDN1b3hndiJ9.FgwIbfJQOkbfbc1OtJHv2Q';
                        const map2 = new mapboxgl.Map({
                            container: 'map2',
                            style: 'mapbox://styles/mapbox/satellite-streets-v9',
                            center: currPosition2,
                            zoom: 18
                        });

                        const marker2 = new mapboxgl.Marker().setLngLat(currPosition2).addTo(map2); 

                        function add_marker2(event){
                            var coordinates = event.lngLat;
                            userLongitude2.value = coordinates.lng;
                            userLatitude2.value = coordinates.lat;
                            marker2.setLngLat(coordinates).addTo(map2);
                        }

                        map2.on('click', add_marker2);


                        const geocoder2 = new MapboxGeocoder({
                            accessToken: mapboxgl.accessToken, 
                            mapboxgl: mapboxgl, 
                            marker: false,
                            zoom: 18
                        });

                        map2.addControl(geocoder2);
                    });

                    let deletecompany = document.querySelector('#deletecompany');
                    deletecompany.remove();
                </script>";

                echo $inputFieldsAttr;

            } else {
                $editDropdown = true;
                $inputFieldsAttr .= "
                    let company_name_m = document.querySelector('#company_name_m');
                    company_name_m.value = '$company_name';
                    company_name_m.removeAttribute('readonly');

                    let contact_number_m = document.querySelector('#contact_number_m');
                    contact_number_m.value = '$contact_number';
                    contact_number_m.removeAttribute('readonly');

                    let email_m = document.querySelector('#email_m');
                    email_m.value = '$email';
                    email_m.removeAttribute('readonly');

                    let comp_location_m = document.querySelector('#comp_location_m');
                    comp_location_m.value = '$comp_location';
                    comp_location_m.removeAttribute('readonly');

                    let longitude_m = document.querySelector('#longitude_m');
                    longitude_m.value = '$longitude';
                    longitude_m.removeAttribute('readonly');

                    let latitude_m = document.querySelector('#latitude_m');
                    latitude_m.value = '$latitude';
                    latitude_m.removeAttribute('readonly');

                    let boundary_size_m = document.querySelector('#boundary_size_m');
                    boundary_size_m.value = '$boundary_size';
                    boundary_size_m.removeAttribute('disabled');

                    let type_m = document.querySelector('#type_m');
                    type_m.value = '$watType';
                    type_m.removeAttribute('disabled');

                    let shift_m = document.querySelector('#shift_m');
                    shift_m.value = '$shifts';
                    shift_m.removeAttribute('disabled');

                    let shift_span_m = document.querySelector('#shift_span_m');
                    shift_span_m.value = '$shift_span';
                    shift_span_m.removeAttribute('disabled');

                    let day_start_m = document.querySelector('#day_start_m');
                    day_start_m.value = '$day_start';
                    day_start_m.removeAttribute('disabled');
                    
                    let position0_m = document.querySelector('#position0_m');
                    position0_m.value = '$position0';
                    position0_m.removeAttribute('disabled');

                    let price0_m = document.querySelector('#price0_m');
                    price0_m.value = '$price0';
                    price0_m.removeAttribute('disabled');

                    // detect location
                    let currPosition2 = [];

                    navigator.geolocation.getCurrentPosition((pos) => {
                        currPosition2.push($longitude);
                        currPosition2.push($latitude);
                        
                        let userLongitude2 = document.querySelector('#longitude_m');
                        let userLatitude2 = document.querySelector('#latitude_m');

                        mapboxgl.accessToken = 'pk.eyJ1IjoiamVsbHliZWFucy1zbHkiLCJhIjoiY2t4NmVnYXU5MnJkNjJ1cW92ZDN1b3hndiJ9.FgwIbfJQOkbfbc1OtJHv2Q';
                        const map2 = new mapboxgl.Map({
                            container: 'map2',
                            style: 'mapbox://styles/mapbox/satellite-streets-v9',
                            center: currPosition2,
                            zoom: 18
                        });

                        const marker2 = new mapboxgl.Marker().setLngLat(currPosition2).addTo(map2); 

                        function add_marker2(event){
                            var coordinates = event.lngLat;
                            userLongitude2.value = coordinates.lng;
                            userLatitude2.value = coordinates.lat;
                            marker2.setLngLat(coordinates).addTo(map2);
                        }

                        map2.on('click', add_marker2);


                        const geocoder2 = new MapboxGeocoder({
                            accessToken: mapboxgl.accessToken, 
                            mapboxgl: mapboxgl, 
                            marker: false,
                            zoom: 18
                        });

                        map2.addControl(geocoder2);
                    });

                    let deletecompany = document.querySelector('#deletecompany');
                    deletecompany.remove();
                </script>";

                echo $inputFieldsAttr;
            }

            echo $createInput;



            // update table data
            if(isset($_POST['editcompany']) && $editDropdown == false)
            {
                $company_name_m = $_POST['company_name_m'];
                $contact_number_m = $_POST['contact_number_m'];
                $email_m = $_POST['email_m'];
                $comp_location_m = $_POST['comp_location_m'];
                $longitude_m = $_POST['longitude_m'];
                $latitude_m = $_POST['latitude_m'];
                $boundary_size_m = $_POST['boundary_size_m'];
                $price0_m = $_POST['price0'];

                $lengthInput = $_POST['lengthInput']; // use in for loop
                $lengthInputOriginal = $_POST['lengthInputOriginal']; // use in for loop
                
                if($lengthInput != 0){
                    // ineedit nya rin yung iba pang position1, price1
                    
                    $sqlNotDefault = "UPDATE company SET company_name = ?,
                                                         cpnumber = ?,
                                                         email = ?,
                                                         comp_location = ?,
                                                         longitude = ?,
                                                         latitude = ?,
                                                         boundary_size = ?,
                                                         price0 = ?
                                      WHERE id = ?";
                    $stmtNotDefault = $this->con()->prepare($sqlNotDefault);
                    $stmtNotDefault->execute([$company_name_m,
                                           $contact_number_m,
                                           $email_m,
                                           $comp_location_m,
                                           $longitude_m,
                                           $latitude_m,
                                           $boundary_size_m,
                                           $price0_m,
                                           $id     
                                          ]);
                    
                    
                    // 0 1
                    for($i = 1; $i <= $lengthInputOriginal; $i++){

                        $position = $_POST["position$i"];
                        $price = $_POST["price$i"];

                        
                        if($position == NULL && $position == ''){
                            $position = NULL;
                        }

                        if($price == NULL && $price == ''){
                            $price = NULL;
                        }

                        $sqlPosPri = "UPDATE company 
                                      SET position$i = ?,
                                          price$i = ?
                                      WHERE id = ?
                                      ";
                        $stmtPosPri = $this->con()->prepare($sqlPosPri);
                        $stmtPosPri->execute([$position, $price, $id]);
                        $countRowPosPri = $stmtPosPri->rowCount();

                        // pag di nag update
                        if($countRowPosPri <= 0){
                            $sqlPosPriNew = "ALTER TABLE company ADD position$i VARCHAR(100) NULL,
                                                                 ADD price$i VARCHAR(100) NULL;
                                             UPDATE company 
                                             SET position$i = ?,
                                                 price$i = ?
                                             WHERE id = ?";
                            $stmtPosPriNew = $this->con()->prepare($sqlPosPriNew);
                            $stmtPosPriNew->execute([$position, $price, $id]);
                            
                        } 
                    }

                    echo "<script>window.location.assign('company.php');</script>";

                } else {
                    // update company that has 1 position, 1 price

                    $sqlDefault = "UPDATE company SET company_name = ?,
                                                      cpnumber = ?,
                                                      email = ?,
                                                      comp_location = ?,
                                                      longitude = ?,
                                                      latitude = ?,
                                                      boundary_size = ?,
                                                      price0 = ?
                                   WHERE id = ?";
                    $stmtDefault = $this->con()->prepare($sqlDefault);
                    $stmtDefault->execute([$company_name_m,
                                           $contact_number_m,
                                           $email_m,
                                           $comp_location_m,
                                           $longitude_m,
                                           $latitude_m,
                                           $boundary_size_m,
                                           $price0_m,
                                           $id     
                                          ]);
                    $countRowDefault = $stmtDefault->rowCount();
                    if($countRowDefault > 0){
                        echo "<script>window.location.assign('company.php');</script>";
                    } else {
                        echo "Data was not updated";
                    }
                }
            } 
            
            // no employee found in company
            if(isset($_POST['editcompany']) && $editDropdown == true) {
                
                $company_name_m = $_POST['company_name_m'];
                $contact_number_m = $_POST['contact_number_m'];
                $email_m = $_POST['email_m'];
                $comp_location_m = $_POST['comp_location_m'];
                $longitude_m = $_POST['longitude_m'];
                $latitude_m = $_POST['latitude_m'];
                $boundary_size_m = $_POST['boundary_size_m'];
                $type_m = $_POST['type_m'];
                $shifts_m = $_POST['shift_m'];
                $shifts_span_m = $_POST['shift_span_m'];
                $day_start_m = $_POST['day_start_m'];
                $price0_m = $_POST['price0'];

                $lengthInput = $_POST['lengthInput']; // use in for loop
                $lengthInputOriginal = $_POST['lengthInputOriginal']; // use in for loop
                
                if($lengthInput != 0){
                    // ineedit nya rin yung iba pang position1, price1
                    
                    $sqlNotDefault = "UPDATE company SET company_name = ?,
                                                         cpnumber = ?,
                                                         email = ?,
                                                         comp_location = ?,
                                                         longitude = ?,
                                                         latitude = ?,
                                                         boundary_size = ?,
                                                         watType = ?,
                                                         shifts = ?,
                                                         shift_span = ?,
                                                         day_start = ?,
                                                         price0 = ?
                                      WHERE id = ?";
                    $stmtNotDefault = $this->con()->prepare($sqlNotDefault);
                    $stmtNotDefault->execute([$company_name_m,
                                           $contact_number_m,
                                           $email_m,
                                           $comp_location_m,
                                           $longitude_m,
                                           $latitude_m,
                                           $boundary_size_m,
                                           $type_m,
                                           $shifts_m,
                                           $shifts_span_m,
                                           $day_start_m,
                                           $price0_m,
                                           $id     
                                          ]);
                    
                    
                    
                    for($i = 1; $i <= $lengthInputOriginal; $i++){

                        $position = $_POST["position$i"];
                        $price = $_POST["price$i"];

                        
                        if($position == NULL && $position == ''){
                            $position = NULL;
                        }

                        if($price == NULL && $price == ''){
                            $price = NULL;
                        }

                        $sqlPosPri = "UPDATE company 
                                      SET position$i = ?,
                                          price$i = ?
                                      WHERE id = ?
                                      ";
                        $stmtPosPri = $this->con()->prepare($sqlPosPri);
                        $stmtPosPri->execute([$position, $price, $id]);
                        $countRowPosPri = $stmtPosPri->rowCount();

                        // pag di nag update
                        if($countRowPosPri <= 0){
                            $sqlPosPriNew = "ALTER TABLE company ADD position$i VARCHAR(100) NULL,
                                                                 ADD price$i VARCHAR(100) NULL;
                                             UPDATE company 
                                             SET position$i = ?,
                                                 price$i = ?
                                             WHERE id = ?";
                            $stmtPosPriNew = $this->con()->prepare($sqlPosPriNew);
                            $stmtPosPriNew->execute([$position, $price, $id]);
                            
                        } 
                    }

                    echo "<script>window.location.assign('company.php');</script>";

                } else {
                    // update company that has 1 position, 1 price

                    $sqlDefault = "UPDATE company SET company_name = ?,
                                                      cpnumber = ?,
                                                      email = ?,
                                                      comp_location = ?,
                                                      longitude = ?,
                                                      latitude = ?,
                                                      boundary_size = ?,
                                                      watType = ?,
                                                      shifts = ?,
                                                      shift_span = ?,
                                                      day_start = ?,
                                                      price0 = ?
                                   WHERE id = ?";
                    $stmtDefault = $this->con()->prepare($sqlDefault);
                    $stmtDefault->execute([$company_name_m,
                                           $contact_number_m,
                                           $email_m,
                                           $comp_location_m,
                                           $longitude_m,
                                           $latitude_m,
                                           $boundary_size_m,
                                           $type_m,
                                           $shifts_m,
                                           $shifts_span_m,
                                           $day_start_m,
                                           $price0_m,
                                           $id     
                                          ]);
                    $countRowDefault = $stmtDefault->rowCount();
                    if($countRowDefault > 0){
                        echo "<script>window.location.assign('company.php');</script>";
                    } else {
                        echo "Data was not updated";
                    }
                }
            }
        }
    }































    

    public function deletecompany($id)
    {
        echo "<script>
                document.querySelector('#modal-h1').innerText = 'Delete Company';
                document.querySelector('#company_name_m').parentElement.remove();
                document.querySelector('#contact_number_m').parentElement.remove();
                document.querySelector('#email_m').parentElement.remove();
                document.querySelector('#map2').remove();
                document.querySelector('#comp_location_m').parentElement.remove();
                document.querySelector('#boundary_size_m').parentElement.remove();
                document.querySelector('#addhere').remove(); // position0, price1
                document.querySelector('#addnewmodal').remove();
                document.querySelector('#editcompany').remove();
              </script>";
        if(isset($_POST['deletecompany']))
        {
            $sql = "DELETE FROM company WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$id]);
            $countRow = $stmt->rowCount();
            
            if($countRow > 0){
                echo "<script>window.location.assign('company.php');</script>";
            } else {
                echo 'Data was not successfully deleted';
            }
        }
    }



    /************************************************************************************************************/
    /************************************************************************************************************/
    /************************************************************************************************************/
    /*****************************************   VONNE PROPERTIESSSSSSS   ***************************************/

    public function login_error_message() {
        if(isset($_SESSION['login-error-message'])) {
            echo
            '<div class="error-message">
                <span>
                    '.$_SESSION["login-error-message"].'
                </span>
            </div>';
        }
    }

    public function guard_login() {
        if(!isset($_SESSION['attempts2'])) {
            $_SESSION['attempts2'] = 5;
        }

        if($_SESSION['attempts2'] == 3) {
            $email = $_POST['login-email'];
            $password = md5($_POST['login-password']);
    
            $sqlFindEmail = "SELECT * FROM employee WHERE email = ?";
            $stmtFindEmail = $this->con()->prepare($sqlFindEmail);
            $stmtFindEmail->execute([$email]);

            $usersFind = $stmtFindEmail->fetch();
            $countRow = $stmtFindEmail->rowCount();

            if ($countRow > 0) {
                $sqlVerify = "SELECT * FROM employee WHERE email = ? AND password = ?";
                $stmtVerify = $this->con()->prepare($sqlVerify);
                $stmtVerify->execute([$email, $password]);

                $usersVerify = $stmtVerify->fetch();
                $countRowVerify = $stmtVerify->rowCount();

                if ($countRowVerify > 0) {
                    $suspendedAccess = 'Suspended';
                    $position = 'Officer in Charge';
                    $avail = 'Unavailable';

                    $sql = "SELECT * FROM employee WHERE email = ? AND password = ? AND position != ? AND availability = ?";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$email, $password, $position, $avail]);
                    
            
                    $users = $stmt->fetch();
                    $countRow = $stmt->rowCount();
            
                    if ($countRow > 0) {
                        $getDate = $this->getDateTime();
                        $date = $getDate['date'];

                        $getempId = $users->empId;
                        $sqlschedulecheck = "SELECT * FROM schedule WHERE empId = ? AND expiration_date != ?";
                        $stmtschedulecheck = $this->con()->prepare($sqlschedulecheck);
                        $stmtschedulecheck->execute([$getempId, $date]);

                        $users_sched = $stmtschedulecheck->fetch();

                        $countRow = $stmtschedulecheck->rowCount();

                        if ($countRow > 0) {
                            if ($users->access != $suspendedAccess) {
                                $fullname = $users->firstname.' '. $users->lastname;

                                $_SESSION['GuardsDetails'] = array('fullname' => $fullname,
                                                                'access' => $users->access,
                                                                'position' => $users->position,
                                                                'id' => $users->id,
                                                                'empId' => $users->empId,
                                                                'company' => $users_sched->company,
                                                                'scheduleTimeIn' => $users_sched->scheduleTimeIn,
                                                                'scheduleTimeOut' => $users_sched->scheduleTimeOut,
                                                                'email' => $users->email,
                                                                'contact' => $users->cpnumber,
                                                                'shift' => $users_sched->shift
                                                                );
                                header('Location: employee/Guards.php'); // redirect to dashboard.php
                                return $_SESSION['GuardsDetails']; // after calling the function, return session
                            } else {
                                $dateExpiredArray = $this->formatDateLocked($users->timer);
                                $dateExpired = implode(" ", $dateExpiredArray);
                                
                                $_SESSION['login-error-message'] = 'Your account has been locked until</br>'.
                                    'Date: '.$dateExpired;
                                $_SESSION['attempts2'] = 5;
                            }
                        } else {
                            $sqlexpire = "DELETE FROM schedule wHERE empId = ? AND expiration_date = ?";
                            $stmtexpire = $this->con()->prepare($sqlexpire);
                            $stmtexpire->execute([$getempId, $date]);

                            $countRow = $stmtexpire->rowCount();

                            if ($countRow > 0) {
                                $_SESSION['login-error-message'] = "Your account has already expired.";
                                $_SESSION['attempts2'] = 5;
                            } else {
                                $_SESSION['login-error-message'] = "You have no permission to access <br> this system.";
                                $_SESSION['attempts2'] = 5;
                            }
                        }
                    } else {
                        $_SESSION['login-error-message'] = "You do not have schedule yet";
                        $_SESSION['attempts2'] = 5;
                    }
                } else {
                    $_SESSION['login-error-message'] = "Palitan to ng send <br> email message";
                    $_SESSION['attempts2'] -= 1;
                    //Dito ilalagay yung send email kineme
                }
            } else {
                $_SESSION['login-error-message'] = "Your email does not exist";
                $_SESSION['attempts2'] = 5;
            } // End of finding email does exist in our database
        } else if ($_SESSION['attempts2'] == 1) {
            $_SESSION['login-error-message'] = 'You account has been locked for 6 hours.';
            //SETS THE USER LOCK MORE 2 HOURS
            $_SESSION['attempts2'] = 5;
            unset($_SESSION['login-error-message']);

        } else {
            $email = $_POST['login-email'];
            $password = md5($_POST['login-password']);
    
            $sqlFindEmail = "SELECT * FROM employee WHERE email = ?";
            $stmtFindEmail = $this->con()->prepare($sqlFindEmail);
            $stmtFindEmail->execute([$email]);

            $usersFind = $stmtFindEmail->fetch();
            $countRow = $stmtFindEmail->rowCount();

            if ($countRow > 0) {
                $sqlVerify = "SELECT * FROM employee WHERE email = ? AND password = ?";
                $stmtVerify = $this->con()->prepare($sqlVerify);
                $stmtVerify->execute([$email, $password]);

                $usersVerify = $stmtVerify->fetch();
                $countRowVerify = $stmtVerify->rowCount();

                if ($countRowVerify > 0) {
                    $suspendedAccess = 'Suspended';
                    $position = 'Officer in Charge';
                    $avail = 'Unavailable';

                    $sql = "SELECT * FROM employee WHERE email = ? AND password = ? AND position != ? AND availability = ?";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$email, $password, $position, $avail]);
                    
            
                    $users = $stmt->fetch();
                    $countRow = $stmt->rowCount();
            
                    if ($countRow > 0) {
                        $getDate = $this->getDateTime();
                        $date = $getDate['date'];

                        $getempId = $users->empId;
                        $sqlschedulecheck = "SELECT * FROM schedule WHERE empId = ? AND expiration_date != ?";
                        $stmtschedulecheck = $this->con()->prepare($sqlschedulecheck);
                        $stmtschedulecheck->execute([$getempId, $date]);

                        $users_sched = $stmtschedulecheck->fetch();

                        $countRow = $stmtschedulecheck->rowCount();

                        if ($countRow > 0) {
                            if ($users->access != $suspendedAccess) {
                                $fullname = $users->firstname.' '. $users->lastname;

                                $_SESSION['GuardsDetails'] = array('fullname' => $fullname,
                                                                'access' => $users->access,
                                                                'position' => $users->position,
                                                                'id' => $users->id,
                                                                'empId' => $users->empId,
                                                                'company' => $users_sched->company,
                                                                'scheduleTimeIn' => $users_sched->scheduleTimeIn,
                                                                'scheduleTimeOut' => $users_sched->scheduleTimeOut,
                                                                'email' => $users->email,
                                                                'contact' => $users->cpnumber,
                                                                'shift' => $users_sched->shift
                                                                );
                                header('Location: employee/Guards.php'); // redirect to dashboard.php
                                return $_SESSION['GuardsDetails']; // after calling the function, return session
                            } else {
                                $dateExpiredArray = $this->formatDateLocked($users->timer);
                                $dateExpired = implode(" ", $dateExpiredArray);
                                
                                $_SESSION['login-error-message'] = 'Your account has been locked until</br>'.
                                    'Date: '.$dateExpired;
                                $_SESSION['attempts2'] = 5;
                            }
                        } else {
                            $sqlexpire = "DELETE FROM schedule wHERE empId = ? AND expiration_date = ?";
                            $stmtexpire = $this->con()->prepare($sqlexpire);
                            $stmtexpire->execute([$getempId, $date]);

                            $countRow = $stmtexpire->rowCount();

                            if ($countRow > 0) {
                                $_SESSION['login-error-message'] = "Your account has already expired.";
                                $_SESSION['attempts2'] = 5;
                            } else {
                                $_SESSION['login-error-message'] = "You have no permission to access <br> this system.";
                                $_SESSION['attempts2'] = 5;
                            }
                        }
                    } else {
                        $_SESSION['login-error-message'] = "You do not have schedule yet";
                        $_SESSION['attempts2'] = 5;
                    }
                } else {
                    $_SESSION['login-error-message'] = "Incorrect password";
                    $_SESSION['attempts2'] -= 1;
                }
            } else {
                $_SESSION['login-error-message'] = "Your email does not exist";
                $_SESSION['attempts2'] = 5;
            } // End of finding email does exist in our database
        } // Else of attempts
    }
    
    public function mobile_login() {
        
        session_start();

        if(isset($_POST['login'])) {

            if(!isset($_SESSION['attempts2'])) {
                $_SESSION['attempts2'] = 5;
            }

            if($_SESSION['attempts2'] == 3) {
                $email = $_POST['login-email'];
                $password = md5($_POST['login-password']);
        
                $sqlFindEmail = "SELECT * FROM employee WHERE email = ?";
                $stmtFindEmail = $this->con()->prepare($sqlFindEmail);
                $stmtFindEmail->execute([$email]);

                $usersFind = $stmtFindEmail->fetch();
                $countRow = $stmtFindEmail->rowCount();

                if ($countRow > 0) {
                    $sqlVerify = "SELECT * FROM employee WHERE email = ? AND password = ?";
                    $stmtVerify = $this->con()->prepare($sqlVerify);
                    $stmtVerify->execute([$email, $password]);

                    $usersVerify = $stmtVerify->fetch();
                    $countRowVerify = $stmtVerify->rowCount();

                    if ($countRowVerify > 0) {
                        $suspendedAccess = 'Suspended';
                        $position = 'Officer in Charge';
                        $avail = 'Unavailable';
    
                        $sql = "SELECT * FROM employee WHERE email = ? AND password = ? AND position = ? AND availability = ?";
                        $stmt = $this->con()->prepare($sql);
                        $stmt->execute([$email, $password, $position, $avail]);
                        
                
                        $users = $stmt->fetch();
                        $countRow = $stmt->rowCount();
                
                        if ($countRow > 0) {
                            $getDate = $this->getDateTime();
                            $date = $getDate['date'];
    
                            $getempId = $users->empId;
                            $sqlschedulecheck = "SELECT * FROM schedule WHERE empId = ? AND expiration_date != ?";
                            $stmtschedulecheck = $this->con()->prepare($sqlschedulecheck);
                            $stmtschedulecheck->execute([$getempId, $date]);
    
                            $users_sched = $stmtschedulecheck->fetch();
    
                            $countRow = $stmtschedulecheck->rowCount();
    
                            if ($countRow > 0) {
                                if ($users->access != $suspendedAccess) {
                                    $fullname = $users->firstname.' '. $users->lastname;
    
                                    $_SESSION['OICDetails'] = array('fullname' => $fullname,
                                                                    'access' => $users->access,
                                                                    'position' => $users->position,
                                                                    'id' => $users->id,
                                                                    'empId' => $users->empId,
                                                                    'company' => $users_sched->company,
                                                                    'scheduleTimeIn' => $users_sched->scheduleTimeIn,
                                                                    'scheduleTimeOut' => $users_sched->scheduleTimeOut,
                                                                    'email' => $users->email,
                                                                    'contact' => $users->cpnumber,
                                                                    'shift' => $users_sched->shift
                                                                    );
                                    header('Location: employee/OIC.php'); // redirect to dashboard.php
                                    return $_SESSION['OICDetails']; // after calling the function, return session
                                } else {
                                    $dateExpiredArray = $this->formatDateLocked($users->timer);
                                    $dateExpired = implode(" ", $dateExpiredArray);
                                    
                                    $_SESSION['login-error-message'] = 'Your account has been locked until</br>'.
                                        'Date: '.$dateExpired;
                                    $_SESSION['attempts2'] = 5;
                                }
                            } else {
                                $sqlexpire = "DELETE FROM schedule wHERE empId = ? AND expiration_date = ?";
                                $stmtexpire = $this->con()->prepare($sqlexpire);
                                $stmtexpire->execute([$getempId, $date]);
    
                                $countRow = $stmtexpire->rowCount();
    
                                if ($countRow > 0) {
                                    $_SESSION['login-error-message'] = "Your account has already expired.";
                                    $_SESSION['attempts2'] = 5;
                                } else {
                                    $_SESSION['login-error-message'] = "You have no permission to access <br> this system.";
                                    $_SESSION['attempts2'] = 5;
                                }
                            }
                        } else {
                            $_SESSION['login-error-message'] = "You do not have schedule yet";
                            $_SESSION['attempts2'] = 5;
                        }
                    } else {
                        $_SESSION['login-error-message'] = "Palitan to ng send <br> email message";
                        $_SESSION['attempts2'] -= 1;
                        //Dito ilalagay yung send email kineme
                    }
                } else {
                    $_SESSION['login-error-message'] = "Your email does not exist";
                    $_SESSION['attempts2'] = 5;
                } // End of finding email does exist in our database
            } else if ($_SESSION['attempts2'] == 1) {
                $_SESSION['login-error-message'] = 'You account has been locked for 6 hours.';
                //SETS THE USER LOCK MORE 2 HOURS
                $_SESSION['attempts2'] = 5;
                unset($_SESSION['login-error-message']);

            } else {
                $email = $_POST['login-email'];
                $password = md5($_POST['login-password']);
        
                $sqlFindEmail = "SELECT * FROM employee WHERE email = ?";
                $stmtFindEmail = $this->con()->prepare($sqlFindEmail);
                $stmtFindEmail->execute([$email]);

                $usersFind = $stmtFindEmail->fetch();
                $countRow = $stmtFindEmail->rowCount();

                if ($countRow > 0) {
                    $sqlVerify = "SELECT * FROM employee WHERE email = ? AND password = ?";
                    $stmtVerify = $this->con()->prepare($sqlVerify);
                    $stmtVerify->execute([$email, $password]);

                    $usersVerify = $stmtVerify->fetch();
                    $countRowVerify = $stmtVerify->rowCount();

                    if ($countRowVerify > 0) {
                        $suspendedAccess = 'Suspended';
                        $position = 'Officer in Charge';
                        $avail = 'Unavailable';
    
                        $sql = "SELECT * FROM employee WHERE email = ? AND password = ? AND position = ? AND availability = ?";
                        $stmt = $this->con()->prepare($sql);
                        $stmt->execute([$email, $password, $position, $avail]);
                        
                
                        $users = $stmt->fetch();
                        $countRow = $stmt->rowCount();
                
                        if ($countRow > 0) {
                            $getDate = $this->getDateTime();
                            $date = $getDate['date'];
    
                            $getempId = $users->empId;
                            $sqlschedulecheck = "SELECT * FROM schedule WHERE empId = ? AND expiration_date != ?";
                            $stmtschedulecheck = $this->con()->prepare($sqlschedulecheck);
                            $stmtschedulecheck->execute([$getempId, $date]);
    
                            $users_sched = $stmtschedulecheck->fetch();
    
                            $countRow = $stmtschedulecheck->rowCount();
    
                            if ($countRow > 0) {
                                if ($users->access != $suspendedAccess) {
                                    $fullname = $users->firstname.' '. $users->lastname;
    
                                    $_SESSION['OICDetails'] = array('fullname' => $fullname,
                                                                    'access' => $users->access,
                                                                    'position' => $users->position,
                                                                    'id' => $users->id,
                                                                    'empId' => $users->empId,
                                                                    'company' => $users_sched->company,
                                                                    'scheduleTimeIn' => $users_sched->scheduleTimeIn,
                                                                    'scheduleTimeOut' => $users_sched->scheduleTimeOut,
                                                                    'email' => $users->email,
                                                                    'contact' => $users->cpnumber,
                                                                    'shift' => $users_sched->shift
                                                                    );
                                    header('Location: employee/OIC.php'); // redirect to dashboard.php
                                    return $_SESSION['OICDetails']; // after calling the function, return session
                                } else {
                                    $dateExpiredArray = $this->formatDateLocked($users->timer);
                                    $dateExpired = implode(" ", $dateExpiredArray);
                                    
                                    $_SESSION['login-error-message'] = 'Your account has been locked until</br>'.
                                        'Date: '.$dateExpired;
                                    $_SESSION['attempts2'] = 5;
                                }
                            } else {
                                $sqlexpire = "DELETE FROM schedule wHERE empId = ? AND expiration_date = ?";
                                $stmtexpire = $this->con()->prepare($sqlexpire);
                                $stmtexpire->execute([$getempId, $date]);
    
                                $countRow = $stmtexpire->rowCount();
    
                                if ($countRow > 0) {
                                    $_SESSION['login-error-message'] = "Your account has already expired.";
                                    $_SESSION['attempts2'] = 5;
                                } else {
                                    $_SESSION['login-error-message'] = "You have no permission to access <br> this system.";
                                    $_SESSION['attempts2'] = 5;
                                }
                            }
                        } else {
                            // $_SESSION['login-error-message'] = "You do not have schedule yet";
                            // $_SESSION['attempts2'] = 5;
                            $this->guard_login();
                        }
                    } else {
                        $_SESSION['login-error-message'] = "Incorrect password";
                        echo $email .'<br>'.$password;
                        $_SESSION['attempts2'] -= 1;
                    }
                } else {
                    $_SESSION['login-error-message'] = "Your email does not exist";
                    $_SESSION['attempts2'] = 5;
                } // End of finding email does exist in our database
            } // Else of attempts
        } // End of isset login
    }

    public function sendEmailSchedule($email, $lastname, $timeIn, $timeOut)
    {
        
        $name = 'JTDV Incorporation';
        $subject = 'Schedule';
        $body = "Hello Mr/Ms. $lastname, <br/>
                You now have a schedule. To check, Kindly view the information below <br/><br/>

                Schedule: $timeIn - $timeOut <br/><br/>
                
                This is an automated email. Do not reply to this message.</br></br>
                - JTDV Incorporation

                ";

        if(!empty($email)){

            $mail = new PHPMailer();

            // smtp settings
            $mail->isSMTP();
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = true;
            // $mail->Username = "DammiDoe123@gmail.com";  // gmail address
            // $mail->Password = "dammiedoe123456789";         // gmail password
            $mail->Port = 465;
            $mail->SMTPSecure = "ssl";

            // email settings
            $mail->isHTML(true);
            $mail->setFrom($email, $name);              // Katabi ng user image
            $mail->addAddress($email);                  // gmail address ng pagsesendan
            $mail->Subject = ("$email ($subject)");     // headline
            $mail->Body = $body;                        // textarea

            if($mail->send()){
                $status = "success";
                $response = "Email is sent!";
                echo '<br/>'.$status."<br/>".$response;
            } else {
                $status = "failed";
                $response = "Something is wrong: <br/>". $mail->ErrorInfo;
                echo '<br/>'.$status."<br/>".$response;
            }
        } 
    }

    // get login session: Employee: OIC
    public function getSessionOICData()
    {
        session_start();
        if($_SESSION['OICDetails']){
            return $_SESSION['OICDetails'];
        }
    }

    // get login session: Employee: OIC
    public function getSessionGuardsData()
    {
        session_start();
        if($_SESSION['GuardsDetails']){
            return $_SESSION['GuardsDetails'];
        }
    }

    public function MobileVerifyUserAccess($access, $fullname, $position) {
        $message = 'You are not allowed to enter the system';

        if ($access == 'Employee' && $position == 'Officer in Charge') {
            $position = $_SESSION['OICDetails']['position'];
            $scheduleTimeIn = $_SESSION['OICDetails']['scheduleTimeIn'];
            $scheduleTimeOut = $_SESSION['OICDetails']['scheduleTimeOut'];
        } else if ($access == 'Employee' && $position != 'Officer in Charge') {
            $gposition = $_SESSION['GuardsDetails']['position'];
            $gscheduleTimeIn = $_SESSION['GuardsDetails']['scheduleTimeIn'];
            $gscheduleTimeOut = $_SESSION['GuardsDetails']['scheduleTimeOut'];
        } else {
            header("Location: login.php?message=$message");
        }
    }

    public function submitOICAttendance() {
        if(isset($_POST['timeIn'])) {

            if (isset($_SESSION['GuardsDetails'])) {
                $getSessionEmpId = $_SESSION['GuardsDetails']['empId'];
                $getScheduleTimeIn = $_SESSION['GuardsDetails']['scheduleTimeIn'];
                $getScheduleTimeOut = $_SESSION['GuardsDetails']['scheduleTimeOut'];
            } else {
                $getSessionEmpId = $_SESSION['OICDetails']['empId'];
                $getScheduleTimeIn = $_SESSION['OICDetails']['scheduleTimeIn'];
                $getScheduleTimeOut = $_SESSION['OICDetails']['scheduleTimeOut'];
            }

            $timenow = $_POST['timenow'];
            $newSchedTimeIn = new dateTime($getScheduleTimeIn);
            $newSchedTimeOut = new dateTime($getScheduleTimeOut);
            $newTimeNow = new dateTime($timenow);
            $newSchedTimeIn->sub(new DateInterval('PT1H'));
            $newSchedTimeOut->sub(new DateInterval('PT1H'));
    
            if ($newSchedTimeIn >= $newSchedTimeOut) {

                if ($newTimeNow >= $newSchedTimeOut && $newTimeNow >= $newSchedTimeIn) {
                    $this->TimeInValidate();
                } else if ($newTimeNow <= $newSchedTimeOut && $newTimeNow <= $newSchedTimeIn) {
                    $this->TimeInValidate();
                } else {

                    if (isset($_SESSION['GuardsDetails'])) {
                        $_SESSION['errmsg'] = 'You can only time-in (1 hour) before time schedule';  
                        header('Location: GuardsAttendance.php?msg=time_in_error'); 
                    } else {
                        $_SESSION['errmsg'] = 'You can only time-in (1 hour) before time schedule';  
                        header('Location: OICAttendance.php?msg=time_in_error'); 
                    }

                }

            } else if ($newSchedTimeIn <= $newSchedTimeOut) {
                if ($newTimeNow >= $newSchedTimeIn && $newTimeNow <= $newSchedTimeOut) {
                    $this->TimeInValidate();
                } else {

                    if (isset($_SESSION['GuardsDetails'])) {
                        $_SESSION['errmsg'] = 'You can only time-in (1 hour) before time schedule';  
                        header('Location: GuardsAttendance.php?msg=time_in_error'); 
                    } else {
                        $_SESSION['errmsg'] = 'You can only time-in (1 hour) before time schedule';  
                        header('Location: OICAttendance.php?msg=time_in_error'); 
                    }

                }
            }
        }
    }

    public function TimeOutUpdate() {

        if (isset($_SESSION['GuardsDetails'])) {
            $getempId = $_SESSION['GuardsDetails']['empId'];
        } else {
            $getempId = $_SESSION['OICDetails']['empId'];
        } 

        $strReplace = str_replace('-', '_', $getempId);
        $textformat = "time_in_".$strReplace;
        $login_session = 'false';
        $timeOut = $_POST['timenow'];
        $salary_status = "Unpaid";

        $sqlTimeOutUpdate = "SET GLOBAL event_scheduler='ON';

                             DROP EVENT IF EXISTS `$textformat`;
                             
                             UPDATE emp_attendance
                             SET login_session = ?,
                             timeOut = ?,
                             salary_status = ?
                             WHERE empId = ?";
        $stmtTimeOutUpdate = $this->con()->prepare($sqlTimeOutUpdate);
        $stmtTimeOutUpdate->execute([$login_session, $timeOut, $salary_status, $getempId]);

        $verify = $stmtTimeOutUpdate->fetch();
        $countRowUpdate = $stmtTimeOutUpdate->rowCount();
        
        $_SESSION['successmsg'] = 'Time-out successfully';

        if (isset($_SESSION['GuardsDetails'])) {
            header('location: GuardsAttendance.php?msg=time_out_success');
            echo $_SESSION['successmsg'];
        } else {
            header('location: OICAttendance.php?msg=time_out_success');
        }

        // if ($countRowUpdate > 0) {
        //     echo "Deleting ".$textformat;
        // } else {
        //     echo 'Not working';
        // }



    }

    public function TimeOutAttendance() {
        if(isset($_POST['timeOut'])) {

            if (isset($_SESSION['GuardsDetails'])) {
                $empId = $_SESSION['GuardsDetails']['empId'];
                $scheduleTimeOut = $_SESSION['GuardsDetails']['scheduleTimeOut'];
            } else {
                $empId = $_SESSION['OICDetails']['empId'];
                $scheduleTimeOut = $_SESSION['OICDetails']['scheduleTimeOut'];
            }
            $timenow = $_POST['timenow'];
            $login_session = 'true';

            $NewTimeNow = new dateTime($timenow);
            $NewSchedTimeOutNoInterval = new dateTime($scheduleTimeOut);
            $NewSchedTimeOut = new dateTime($scheduleTimeOut);
            $NewSchedTimeOut->sub(new DateInterval('PT15M'));

            $sql = "SELECT * FROM emp_attendance WHERE empId = ? AND login_session = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$empId, $login_session]);

            $users = $stmt->fetch();
            $countRow = $stmt->rowCount();

            if ($countRow > 0) {
                if ($NewTimeNow <= $NewSchedTimeOut) {
                    if ($NewTimeNow >= $NewSchedTimeOut && $NewTimeNow <= $NewSchedTimeOutNoInterval) {
                        $this->TimeOutUpdate();
                    } else {
                        $_SESSION['errmsg'] = 'You can only time-out 15 mins before your time out schedule.';
                        if (isset($_SESSION['GuardsDetails'])) {
                            header('Location: GuardsAttendance.php?msg=time_out_error');
                        } else {
                            header('Location: OICAttendance.php?msg=time_out_error');
                        } 
                    }
                } else if ($NewTimeNow >= $NewSchedTimeOut) {
                    if ($NewTimeNow >= $NewSchedTimeOut && $NewTimeNow <= $NewSchedTimeOutNoInterval) {
                        $this->TimeOutUpdate();
                    } else if ($NewTimeNow <= $NewSchedTimeOut && $NewTimeNow <= $NewSchedTimeOutNoInterval) {
                        $this->TimeOutUpdate();
                    } else {
                        $_SESSION['errmsg'] = 'You can only time-out 15 mins before your time out schedule.';  
                        if (isset($_SESSION['GuardsDetails'])) {
                            header('Location: GuardsAttendance.php?msg=time_out_error');
                        } else {
                            header('Location: OICAttendance.php?msg=time_out_error');
                        } 
                    }
                }
            } else {
                echo 'User not found.';
            }
        }
    }

    public function TimeInValidate() {

        if (isset($_SESSION['GuardsDetails'])) {
            $getSessionEmpId = $_SESSION['GuardsDetails']['empId'];
            $getScheduleTimeIn = $_SESSION['GuardsDetails']['scheduleTimeIn'];
            $getScheduleTimeOut = $_SESSION['GuardsDetails']['scheduleTimeOut'];
            // $getdateIn = $_SESSION['OICDetails']['datetimeIn'];
            $getid = $_SESSION['GuardsDetails']['id'];
        } else {
            $getSessionEmpId = $_SESSION['OICDetails']['empId'];
            $getScheduleTimeIn = $_SESSION['OICDetails']['scheduleTimeIn'];
            $getScheduleTimeOut = $_SESSION['OICDetails']['scheduleTimeOut'];
            // $getdateIn = $_SESSION['OICDetails']['datetimeIn'];
            $getid = $_SESSION['OICDetails']['id'];
        }

            $empId = $getSessionEmpId;
            $timenow = $_POST['timenow'];
            $datenow = $_POST['datenow'];
            $location = $_POST['location'];
            $login_session = 'true';

            $newScheduleTimeIn = new dateTime($getScheduleTimeIn);
            $newScheduleTimeOut = new dateTime($getScheduleTimeOut);
            $newTimeNow = new dateTime($timenow);

                if($newTimeNow < $newScheduleTimeIn) {
                    $TimeInsert = $getScheduleTimeIn;
                } else {
                    $TimeInsert = $timenow;
                }


                if ($newScheduleTimeIn <= $newTimeNow) {
                    $status = 'Late';
                } else {
                    $status = 'Good';
                }
    
                $sqlgetLoginSession = "SELECT login_session FROM emp_attendance WHERE login_session = ? AND empId = ?";
                $stmtLoginSession = $this->con()->prepare($sqlgetLoginSession);
                $stmtLoginSession->execute([$login_session, $empId]);
    
                $verify = $stmtLoginSession->fetch();
    
                if ($row = $verify) {
                    echo 'You can only login once.';
                } else {    
                    $getHours = abs(strtotime($getScheduleTimeIn) - strtotime($getScheduleTimeOut)) / 3600;
                    $ConcatTimeDate = strtotime($getScheduleTimeIn." ".$datenow."+ ".$getHours." HOURS");
                    $ConvertToDate = date("Y/m/d", $ConcatTimeDate);
                    $ConvertToDateEventName = date("Y_m_d", $ConcatTimeDate);
                    $ConvertToSched = date("Y-m-d H:i:s", $ConcatTimeDate);
                    $NewEmpId = str_replace('-', '_', $getSessionEmpId);
                    $salary_status = "Unpaid";

                    $customEventname = "time_in_$NewEmpId";

                    $sql = "INSERT INTO emp_attendance(empId, timeIn, datetimeIn, datetimeOut, location, login_session, status) VALUES(?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$empId, $TimeInsert, $datenow, $ConvertToDate, $location, $login_session, $status]);
        
                    $users = $stmt->fetch();
                    $countRow = $stmt->rowCount();

                    if($countRow > 0) {
                        $sqlInsertEvent = "SET GLOBAL event_scheduler='ON';
                                            CREATE EVENT `$customEventname`
                                            ON SCHEDULE AT '$ConvertToSched'
                                            ON COMPLETION NOT PRESERVE
                                            DO
                                               UPDATE `emp_attendance`
                                               SET `login_session` = 'false',
                                               `timeOut` = '$getScheduleTimeOut',
                                               `datetimeOut` = '$ConvertToDate',
                                               `salary_status` = '$salary_status'
                                               WHERE `empid` = '$empId'
                                            ";
                        $InsertEventStmt = $this->con()->prepare($sqlInsertEvent);
                        $InsertEventStmt->execute();
                        $CountRowEvent = $InsertEventStmt->rowCount();

                        $_SESSION['successmsg'] = 'Time-in successfully';

                        if (isset($_SESSION['GuardsDetails'])) {
                            header('Location: GuardsAttendance.php?msg=time_in_success');
                        } else {
                            header('Location: OICAttendance.php?msg=time_in_success');
                        }

                    }
                }
    }

    public function alreadyLogin() {

        if (isset($_SESSION['GuardsDetails'])) {
            $getSessionEmpId = $_SESSION['GuardsDetails']['empId'];
        } else {
            $getSessionEmpId = $_SESSION['OICDetails']['empId'];
        }

        $empId = $getSessionEmpId;
        $login_session = 'true';


        $sqlgetLoginSession = "SELECT login_session FROM emp_attendance WHERE login_session = ? AND empId = ?";
        $stmtLoginSession = $this->con()->prepare($sqlgetLoginSession);
        $stmtLoginSession->execute([$login_session, $empId]);

        $verify = $stmtLoginSession->fetch();

        if ($row = $verify) {
            echo '<button type="submit" class="timeOut" name="timeOut">Time-Out</button>';
        } else {
            echo '<button type="submit" class="timeIn" name="timeIn" id="time-in-button">Time-in</button>';
        }

    }

    public function AssignGuards() {
        if(isset($_POST['assign'])) {
            $companyName = $_POST['companyName'];
            $timeIn = $_POST['timeIn'].':00';
            $timeOut = $_POST['timeOut'];
            $shiftSpan = $_POST['shiftSpan'];
            $shift = $_POST['shift'];
            $position = "Security Guard";
            $availability = "Available";
            $empId = $_POST['employeeId'];
    
            $newtimeIn = strtotime($timeIn);
            $newtimeOut = strtotime($timeOut);
    
            $strTimeIn = date('h:i:s A', $newtimeIn);
            $strTimeOut = date('h:i:s A', $newtimeOut);
    
            $sqlfindguards = "SELECT * FROM employee WHERE position = ? AND availability = ? AND empId = ?";
            $stmtfindguard = $this->con()->prepare($sqlfindguards);
            $stmtfindguard->execute([$position, $availability, $empId]);

            $countRow = $stmtfindguard->rowCount();

            if ($countRow > 0) {
                echo 'Find';
            } else {
                echo 'Not found';
            }
        }
    }

    public function ShowAssignGuards() {
        $company = $_SESSION['OICDetails']['company']; //(❁´◡`❁))
        $empId = $_SESSION['OICDetails']['empId'];

        $sql = "SELECT
                    e.firstname,
                    e.lastname,
                    s.scheduleTimeOut,
                    s.scheduleTimeIn,
                    s.id
                FROM schedule s
                INNER JOIN employee e
                ON s.empId = e.empId
                WHERE s.company = ? AND s.empId != ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$company, $empId]);

        while($row = $stmt->fetch()) {
            $fullname = $row->lastname.', '.$row->firstname;
            
            if ($row->scheduleTimeIn === NULL || $row->scheduleTimeOut === NULL) {
                $scheduled = 'No';
            } else {
                $scheduled = 'Yes';
            }

            echo "<tr>
                    <td>$fullname</td>
                    <td class='schedule_table'>$scheduled</td>
                    <td><a href='OICAssignGuards.php?vid=$row->id'>View</a>
                    </td>
                </tr>";
        }
    }

    public function ShowSpecificGuards() {

        if(isset($_GET['vid'])){
            $id = $_GET['vid'];
            $company = $_SESSION['OICDetails']['company']; //(❁´◡`❁))

            $sql = "SELECT * FROM schedule WHERE company = ? AND id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$company, $id]);

            $getUserId = $stmt->fetch();

            $countRow = $stmt->rowCount();

            if($countRow > 0) {
                $companyName = $getUserId->company;
                $employeeId = $getUserId->empId;

                if ($getUserId->scheduleTimeOut === NULL) {
                    $timeOut = NULL;
                } else {
                    $timeOut = $getUserId->scheduleTimeOut;
                }

                if ($getUserId->scheduleTimeIn === NULL) {
                    $timeIn = NULL;
                } else {                
                    $originalTimeIn = $getUserId->scheduleTimeIn;
                    $parseToTimeDate = strtotime($originalTimeIn);
                    $timeIn = date("H", $parseToTimeDate);
                }

                if ($getUserId->shift_span === NULL) {
                    $shift_span = NULL;
                } else {                
                    $shift_span = $getUserId->shift_span;
                }

                if ($getUserId->shift === NULL) {
                    $shift = NULL;
                } else {                
                    $shift = $getUserId->shift;
                }




                echo "<script>
                        let viewModal = document.querySelector('.view-modal');
                        viewModal.setAttribute('id', 'show-modal');

                        let companyName = document.querySelector('#companyName').value = '$companyName';
                        let employeeId = document.querySelector('#employeeId').value = '$employeeId';
                        let timeIn = document.querySelector('#timeIn').value = '$timeIn';
                        let timeOut = document.querySelector('#timeOut').value = '$timeOut';
                        let shift_span = document.querySelector('#shiftSpan').value = '$shift_span';
                        let shift = document.querySelector('#shift').value = '$shift';

                    </script>";
            }
        }
    }

    public function deleteGuards() {
        if (isset($_POST['deleteGuards'])) {
            $id = $_GET['id'];
            $sql = "DELETE FROM schedule WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$id]);

            $countRow = $stmt->rowCount();

            if ($countRow > 0) {
                echo 'Deleted Successfully';
            } else {
                echo 'Error';
            }
        }
    }

    public function updateGuards() {
        if(isset($_POST['updateGuards'])) {
            $getId = $_GET['vid'];
            $timeIn = $_POST['timeIn'].':00';
            $timeOut = $_POST['timeOut'];
            $shiftSpan = $_POST['shiftSpan'];
            $shift = $_POST['shift'];
    
            $newtimeIn = strtotime($timeIn);
            $newtimeOut = strtotime($timeOut);
    
            $strTimeIn = date('h:i:s A', $newtimeIn);
            $strTimeOut = date('h:i:s A', $newtimeOut);

            $sql = "UPDATE schedule
                    SET scheduleTimeIn = ?,
                        scheduleTimeOut = ?,
                        shift_span = ?,
                        shift = ?
                    WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$strTimeIn, $strTimeOut, $shiftSpan, $shift, $getId]);

            $countRow = $stmt->rowCount();

            if ($countRow > 0) {

                $avail = 'Unavailable';
                $postempId = $_POST['employeeId'];

                $sqlavail = "UPDATE employee SET availability = ? WHERE empId = ?";
                $stmtavail = $this->con()->prepare($sqlavail);
                $stmtavail->execute([$avail, $postempId]);

                $countRowavail = $stmtavail->rowCount();

                if ($countRowavail > 0) {

                    $this->ScheduleEmailGuards();
                } else {
                    $this->ScheduleEmailGuards();

                }
            } else {
                $_SESSION['errmsg'] = 'You did not change anything';
                header('location: OICAssignGuards.php?msg=query_schedule_error');
            }

        }
    }

    public function ScheduleEmailGuards() {
        $timeIn = $_POST['timeIn'].':00';
        $timeOut = $_POST['timeOut'];
        $shiftSpan = $_POST['shiftSpan'];
        $shift = $_POST['shift'];

        $newtimeIn = strtotime($timeIn);
        $newtimeOut = strtotime($timeOut);

        $strTimeIn = date('h:i:s A', $newtimeIn);
        $strTimeOut = date('h:i:s A', $newtimeOut);

        $postempId = $_POST['employeeId'];
        $sqlGetInfo = "SELECT * FROM employee WHERE empId = ?";
        $stmtGetInfo = $this->con()->prepare($sqlGetInfo);
        $stmtGetInfo->execute([$postempId]);

        $usersGetInfo = $stmtGetInfo->fetch();
        $countRowGetInfo = $stmtGetInfo->rowCount();

        if ($countRowGetInfo > 0) {
            $email = $usersGetInfo->email;
            $lastname = $usersGetInfo->lastname;

            $this->sendEmailSchedule($email, $lastname, $strTimeIn, $strTimeOut);
            $_SESSION['successmsg'] = 'Schedule updated successfully';
            header('location: OICAssignGuards.php?msg=guard_schedule_updated');
        }
    }
    
    public function submitLeave() {

        if(isset($_POST['submit'])) {

                if (isset($_SESSION['GuardsDetails'])) {
                    $getId = $_SESSION['GuardsDetails']['empId'];
                } else {
                    $getId = $_SESSION['OICDetails']['empId'];
                }
                $statuscheck = 'Pending';
    
                $sqlfind = "SELECT * FROM leave_request WHERE empId = ? AND status = ?";
                $stmtfind = $this->con()->prepare($sqlfind);
                $stmtfind->execute([$getId, $statuscheck]);
    
                $countRowfind = $stmtfind->rowCount();
    
                if ($countRowfind > 0) {        
                    $_SESSION['errmsg'] = 'You already have a pending request. Please wait until it approves.';
                    if (isset($_SESSION['GuardsDetails'])) {
                        header('Location: GuardsLeave.php?msg=request_denied');
                    } else {
                        header('Location: OICLeave.php?msg=request_denied');
                    }  
                } else {
                    $this->insertLeave();
                }
        }
    }

    public function getErrorModalMsg() {
        if (isset($_SESSION['errmsg'])) {
            echo '<header class="modal-message">'.$_SESSION['errmsg'].'</header>';
        }
    }

    public function getSuccessModalMsg() {
        if (isset($_SESSION['successmsg'])) {
            echo '<header class="modal-message">'.$_SESSION['successmsg'].'</header>';
        }
    }

    public function showModalViolation() {
        if(isset($_GET['vid'])){
            $vid = $_GET['vid'];

            $sql = "SELECT * FROM violationsandremarks WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$vid]);

            $getUserId = $stmt->fetch();

            $countRow = $stmt->rowCount();

            if($countRow > 0){
                $empId = $getUserId->empId;
                $violation = $getUserId->violation;
                $remark = $getUserId->remark;
                $date_created = $getUserId->date_created;

                echo "<script>
                        let viewModal = document.querySelector('.view-modal');
                        viewModal.setAttribute('id', 'show-modal');
                        
                        let showempId = document.querySelector('#showempId').value = '$empId';
                        let showViolation = document.querySelector('#showViolation').value = '$violation';
                        let showRemark = document.querySelector('#showRemark').value = '$remark';
                        let date_created = document.querySelector('#showDateCreated').value = '$date_created';
                    </script>";
            }
        }
    }

    public function showMsgModal() { //ShowErrorModal to dati
        if(isset($_GET['msg'])) {
            $getmsg = $_GET['msg'];

            if ($getmsg == 'request_denied') {
                echo "<script>
                        var x = document.getElementsByClassName('view-modal-error');
                        for (var i=0;i<x.length;i+=1) {
                            x[i].style.display = 'block';
                        }
                    </script>";
            } else if ($getmsg == 'time_out_error') {
                echo "<script>
                        let viewModal = document.querySelector('.view-modal-error');
                        viewModal.setAttribute('msg', 'show-modal-error');

                        var x = document.getElementsByClassName('view-modal-error');
                        for (var i=0;i<x.length;i+=1) {
                            x[i].style.display = 'block';
                        }
                    </script>";
            } else if ($getmsg == 'time_in_error') {
                echo "<script>
                        let viewModal = document.querySelector('.view-modal-error');
                        viewModal.setAttribute('msg', 'show-modal-error');

                        var x = document.getElementsByClassName('view-modal-error');
                        for (var i=0;i<x.length;i+=1) {
                            x[i].style.display = 'block';
                        }
                    </script>";
            } else if ($getmsg == 'time_in_success') {
                echo "<script>
                        let viewModal = document.querySelector('.view-modal-success');
                        viewModal.setAttribute('msg', 'show-modal-success');

                        var x = document.getElementsByClassName('view-modal-success');
                        for (var i=0;i<x.length;i+=1) {
                            x[i].style.display = 'block';
                        }
                    </script>"; 
            } else if ($getmsg == 'time_out_success') {
                echo "<script>
                        let viewModal = document.querySelector('.view-modal-success');
                        viewModal.setAttribute('msg', 'show-modal-success');

                        var x = document.getElementsByClassName('view-modal-success');
                        for (var i=0;i<x.length;i+=1) {
                            x[i].style.display = 'block';
                        }
                    </script>"; 
            } else if ($getmsg == 'leave_success') {
                echo "<script>
                        let viewModal = document.querySelector('.view-modal-success');
                        viewModal.setAttribute('msg', 'show-modal-success');

                        var x = document.getElementsByClassName('view-modal-success');
                        for (var i=0;i<x.length;i+=1) {
                            x[i].style.display = 'block';
                        }
                    </script>"; 
            } else if ($getmsg == 'violation_success'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-success');
                viewModal.setAttribute('msg', 'show-modal-success');

                var x = document.getElementsByClassName('view-modal-success');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'update_violation_success'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-success');
                viewModal.setAttribute('msg', 'show-modal-success');

                var x = document.getElementsByClassName('view-modal-success');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'you_did_not_change_anything'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-error');
                viewModal.setAttribute('msg', 'show-modal-error');

                var x = document.getElementsByClassName('view-modal-error');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'delete_violation_success'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-success');
                viewModal.setAttribute('msg', 'show-modal-success');

                var x = document.getElementsByClassName('view-modal-success');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'delete_violation_error'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-error');
                viewModal.setAttribute('msg', 'show-modal-error');

                var x = document.getElementsByClassName('view-modal-error');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'guard_schedule_updated'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-success');
                viewModal.setAttribute('msg', 'show-modal-success');

                var x = document.getElementsByClassName('view-modal-success');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'query_schedule_error'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-error');
                viewModal.setAttribute('msg', 'show-modal-error');

                var x = document.getElementsByClassName('view-modal-error');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'change_email_success'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-success');
                viewModal.setAttribute('msg', 'show-modal-success');

                var x = document.getElementsByClassName('view-modal-success');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'email_already_exist'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-error');
                viewModal.setAttribute('msg', 'show-modal-error');

                var x = document.getElementsByClassName('view-modal-error');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'account_incorrect_password'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-error');
                viewModal.setAttribute('msg', 'show-modal-error');

                var x = document.getElementsByClassName('view-modal-error');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'password_not_match'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-error');
                viewModal.setAttribute('msg', 'show-modal-error');

                var x = document.getElementsByClassName('view-modal-error');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'password_length_error'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-error');
                viewModal.setAttribute('msg', 'show-modal-error');

                var x = document.getElementsByClassName('view-modal-error');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'incorrect_current_password'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-error');
                viewModal.setAttribute('msg', 'show-modal-error');

                var x = document.getElementsByClassName('view-modal-error');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'change_password_successfully'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-success');
                viewModal.setAttribute('msg', 'show-modal-success');

                var x = document.getElementsByClassName('view-modal-success');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'mark_absent_request_denied'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-error');
                viewModal.setAttribute('msg', 'show-modal-error');

                var x = document.getElementsByClassName('view-modal-error');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            } else if ($getmsg == 'mark_absent_success'){
                echo "<script>
                let viewModal = document.querySelector('.view-modal-success');
                viewModal.setAttribute('msg', 'show-modal-success');

                var x = document.getElementsByClassName('view-modal-success');
                for (var i=0;i<x.length;i+=1) {
                    x[i].style.display = 'block';
                }
            </script>"; 
            }
        }
    }

    public function insertLeave() {
        $getDateTime = $this->getDateTime();

        $dateFrom = $_POST['inputFrom'];
        $dateTo = $_POST['inputTo'];
        $reason = $_POST['reason'];

        if (isset($_SESSION['GuardsDetails'])) {
            $getId = $_SESSION['GuardsDetails']['empId'];
        } else {
            $getId = $_SESSION['OICDetails']['empId'];
        }
        $type = 'Automatic';
        $typeOfLeave = $_POST['type'];
        $status = 'Pending';
        $getDateNow = $getDateTime['date'];
        
        $strFrom = str_replace("-","/", $dateFrom);
        $strTo = str_replace("-","/", $dateTo);

        $days = abs(strtotime($dateFrom) - strtotime($dateTo)) / (60 * 60 * 24);

        $sql = "INSERT INTO leave_request (empId, days, leave_start, leave_end, type, typeOfLeave, reason, status, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$getId, $days, $strFrom, $strTo, $type, $typeOfLeave, $reason, $status, $getDateNow]);

        $countRow = $stmt->rowCount();
        
        if ($countRow > 0) {
            $_SESSION['successmsg'] = 'Request added successfully'; 
            if (isset($_SESSION['GuardsDetails'])) {
                header('Location: GuardsLeave.php?msg=leave_success');
            } else {
                header('Location: OICLeave.php?msg=leave_success');
            } 
        } else {
            echo 'Error: Please contact the company for this issue.';
        }
    }

    public function showyourleave() {

        if (isset($_SESSION['GuardsDetails'])) {
            $getempId = $_SESSION['GuardsDetails']['empId'];
        } else {
            $getempId = $_SESSION['OICDetails']['empId'];
        }

        $sqlselect = "SELECT * FROM leave_request WHERE empId = ? ORDER BY id DESC";
        $stmtselect = $this->con()->prepare($sqlselect);
        $stmtselect->execute([$getempId]);

        while ($users = $stmtselect->fetch()) {
        
            $parse_leave_end = strtotime($users->leave_end);
            $getdate = $this->getDateTime();
            $parse_date_now = strtotime("now". ' '.'Asia/Manila');

            if ($parse_date_now >= $parse_leave_end) {
                $status = 'Completed';
            } else {
                $status = $users->status;
            }
        
            if (isset($_SESSION['GuardsDetails'])) {
                echo "<tr>
                        <td>$users->date_created</td>
                        <td class='table_status'>$status</td>
                        <td><a href='GuardsLeave.php?id=$users->id' id='myBtn'>View</a>
                        </td>
                    </tr>";
            } else {
                echo "<tr>
                        <td>$users->date_created</td>
                        <td class='table_status'>$status</td>
                        <td><a href='OICLeave.php?id=$users->id' id='myBtn'>View</a>
                        </td>
                    </tr>";
            }
        }
    }

    public function showyourattendance() {

        if (isset($_SESSION['GuardsDetails'])) {
            $getempId = $_SESSION['GuardsDetails']['empId'];
        } else {
            $getempId = $_SESSION['OICDetails']['empId'];
        }

        $sqlselect = "SELECT * FROM emp_attendance WHERE empId = ? ORDER BY id DESC";
        $stmtselect = $this->con()->prepare($sqlselect);
        $stmtselect->execute([$getempId]);

        while ($users = $stmtselect->fetch()) {

            $timeIn = $users->datetimeIn.' '.$users->timeIn;
            $status = $users->status;
            $timeOutCheck = $users->timeOut;
            if ($timeOutCheck === NULL ) {
                $timeOut = "Waiting for time-out";
            } else {
                $timeOut = $users->datetimeOut.' '.$users->timeOut;
            }
        
                echo "<tr>
                        <td>$timeIn</td>
                        <td>$timeOut</td>
                        <td>$status</td>
                    </tr>";
        }
    }

    public function viewLeave() {
        if(isset($_GET['id'])){
            $id = $_GET['id'];

            if (isset($_SESSION['GuardsDetails'])) {
                $getempId = $_SESSION['GuardsDetails']['empId'];
            } else {
                $getempId = $_SESSION['OICDetails']['empId'];
            }

            $sql = "SELECT * FROM leave_request WHERE empId = ? AND id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$getempId, $id]);

            $getUserId = $stmt->fetch();

            $countRow = $stmt->rowCount();

            if($countRow > 0){
                $parse_leave_end = strtotime($getUserId->leave_end);
                $getdate = $this->getDateTime();
                $parse_date_now = strtotime("now". ' '.'Asia/Manila');
    
                if ($parse_date_now >= $parse_leave_end) {
                    $status = 'Completed';
                } else {
                    $status = $getUserId->status;
                }
                $type = $getUserId->typeOfLeave;
                $leave_start = $getUserId->leave_start;
                $leave_end = $getUserId->leave_end;
                $showReason = $getUserId->reason;
                $date_created = $getUserId->date_created;

                echo "<script>
                        let viewModal = document.querySelector('.view-modal');
                        viewModal.setAttribute('id', 'show-modal');
                        
                        let status = document.querySelector('#showStatus').value = '$status';
                        let typeOfLeave = document.querySelector('#showType').value = '$type';
                        let leave_start = document.querySelector('#showInputFrom').value = '$leave_start';
                        let leave_end = document.querySelector('#showInputTo').value = '$leave_end';
                        let showReason = document.querySelector('#showReason').value = '$showReason';
                        let date_created = document.querySelector('#showDateCreated').value = '$date_created';
                    </script>";
            }
        }
    }

    public function SelectGuardsToSet() {
        $company = $_SESSION['OICDetails']['company']; //(❁´◡`❁))
        $empId = $_SESSION['OICDetails']['empId'];

        $sql = "SELECT
                    e.firstname,
                    e.lastname,
                    s.id,
                    s.empId
                FROM schedule s
                INNER JOIN employee e
                ON s.empId = e.empId
                WHERE s.company = ? AND s.empId != ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$company, $empId]);

        while($row = $stmt->fetch()) {
            $fullname = $row->lastname.', '.$row->firstname;
            $empId = $row->empId;

            echo "<tr>
                    <td>$empId</td>
                    <td>$fullname</td>
                    <td><a href='OICViolations.php?id=$row->id'>Set</a>
                    </td>
                </tr>";
        }
    }

    public function setEmployeeId() {
        if(isset($_GET['id'])){
            $id = $_GET['id'];

            $sql = "SELECT * FROM schedule WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$id]);

            $getUserId = $stmt->fetch();

            $countRow = $stmt->rowCount();

            if($countRow > 0){
                $getEmpId = $getUserId->empId;

                echo "<script>
                         let empId = document.querySelector('#empId').value = '$getEmpId';
                      </script>";
            }
        }
    }

    public function submitViolation() {
        if(isset($_POST['submit'])) {
            $empId = $_POST['empId'];
            $violation = $_POST['violation'];
            $remark = $_POST['remark'];

            $getdate = $this->getDateTime();
            $datenow = $getdate['date'];
            
            $sql = "INSERT INTO violationsandremarks (empId, violation, remark, date_created) VALUES (?, ?, ?, ?)";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$empId, $violation, $remark, $datenow]);

            $users = $stmt->fetch();

            $countRow = $stmt->rowCount();

            if ($countRow > 0) {
                $_SESSION['successmsg'] = 'Violation added successfully';  
                header('Location: OICViolations.php?msg=violation_success');
            } else {
                echo 'Error!';
            }
        }
    }

    public function showViolations() {

        if (isset($_SESSION['GuardsDetails'])) {

        $getEmpId = $_SESSION['GuardsDetails']['empId'];
        $sqlselect = "SELECT
                        v.id,
                        v.violation,
                        v.date_created,
                        e.firstname,
                        e.lastname,
                        e.email,
                        e.empId
                    FROM violationsandremarks v
                    INNER JOIN employee e 
                    ON v.empId = e.empId
                    WHERE v.empId = ? 
                    ORDER BY id DESC";
        $stmtselect = $this->con()->prepare($sqlselect);
        $stmtselect->execute([$getEmpId]);

        while ($usersSelect = $stmtselect->fetch()) {
            // $fullname = $usersSelect->lastname.', '.$usersSelect->firstname;
            echo "<tr>
                    <td>$usersSelect->date_created</td>
                    <td>$usersSelect->violation</td>
                    <td><a href='GuardsViolations.php?vid=$usersSelect->id'>View</a></td>
                </tr>";
        }
        } else {
            $sqlselect = "SELECT
                        v.id,
                        v.violation,
                        e.firstname,
                        e.lastname,
                        e.email,
                        e.empId
                    FROM violationsandremarks v
                    INNER JOIN employee e 
                    ON v.empId = e.empId
                    ORDER BY id DESC";
        $stmtselect = $this->con()->query($sqlselect);

        while ($usersSelect = $stmtselect->fetch()) {
            // $fullname = $usersSelect->lastname.', '.$usersSelect->firstname;
            echo "<tr>
                    <td>$usersSelect->empId</td>
                    <td>$usersSelect->violation</td>
                    <td><a href='GuardsViolations.php?vid=$usersSelect->id'>View</a></td>
                </tr>";
        }
        }

        // $sqlselect = "SELECT
        //                 v.id,
        //                 v.violation,
        //                 e.firstname,
        //                 e.lastname,
        //                 e.email,
        //                 e.empId
        //             FROM violationsandremarks v
        //             INNER JOIN employee e 
        //             ON v.empId = e.empId
        //             ORDER BY id DESC";
        // $stmtselect = $this->con()->query($sqlselect);

        // while ($usersSelect = $stmtselect->fetch()) {
        //     // $fullname = $usersSelect->lastname.', '.$usersSelect->firstname;
        //     echo "<tr>
        //             <td>$usersSelect->empId</td>
        //             <td>$usersSelect->violation</td>
        //             <td><a href='OICViewViolations.php?vid=$usersSelect->id'>View</a></td>
        //         </tr>";
        // }
    }

    public function UpdateViolation() {

        if(isset($_POST['update'])) {
            $getvid = $_GET['vid'];
            $updateviolation = $_POST['showViolation'];
            $updateremark = $_POST['showRemark'];

            $sql = "UPDATE violationsandremarks SET violation = ?, remark = ? WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$updateviolation, $updateremark, $getvid]);

            $users = $stmt->fetch();

            $countRow = $stmt->rowCount();

            if ($countRow > 0) {
                $_SESSION['successmsg'] = 'Violation updated successfully'; 
                header('location: OICViewViolations.php?msg=update_violation_success');
            } else {
                $_SESSION['errmsg'] = 'You did not change anything';
                header('location: OICViewViolations.php?msg=you_did_not_change_anything');
            }
        }

    }

    public function DeleteViolation() {
        if(isset($_POST['delete'])) {
            $getvid = $_GET['vid'];

            $sql = "DELETE FROM violationsandremarks WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$getvid]);

            $user = $stmt->fetch();
            $countRow = $stmt->rowCount();

            if ($countRow > 0) {
                $_SESSION['successmsg'] = 'Violation deleted successfully'; 
                header('location: OICViewViolations.php?msg=delete_violation_success');
            } else {
                $_SESSION['successmsg'] = 'There is an error to the system'; 
                header('location: OICViewViolations.php?msg=delete_violation_error');
            }
        } 
    }

    public function checkStatusProfile() {

        if (isset($_SESSION['GuardsDetails'])) {
            $getEmpId = $_SESSION['GuardsDetails']['empId'];
        } else {
            $getEmpId = $_SESSION['OICDetails']['empId'];
        }
        $login_session = 'true';

        $sql = "SELECT * 
                FROM emp_attendance 
                WHERE empId = ? AND login_session = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$getEmpId, $login_session]);

        $users = $stmt->fetch();
        $countRow = $stmt->rowCount();

        if ($countRow > 0) {
            echo "<span class='material-icons' style='color:rgb(25, 199, 115)'>circle</span>
                  <span>On-duty |&nbsp</span>
                  <span>$users->timeIn</span>";
        } else {
            echo "<span class='material-icons' style='color:#af1f1f'>circle</span>
                  <span>Off-duty</span>";
        }
        
    }

    public function changeEmail() {
        if(isset($_POST['submit'])) {
            $email = $_POST['email'];
            $password = md5($_POST['password']);

            if (isset($_SESSION['GuardsDetails'])) {
                $empId = $_SESSION['GuardsDetails']['empId'];
            } else {
                $empId = $_SESSION['OICDetails']['empId'];
            }
            
            $sql = "SELECT * FROM employee WHERE empId = ? AND password = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$empId, $password]);
    
            $users = $stmt->fetch();
    
            $countRow = $stmt->rowCount();
    
            if ($countRow > 0) {

                $sqlverify = "SELECT * FROM employee WHERE email = ?";
                $stmtVerify = $this->con()->prepare($sqlverify);
                $stmtVerify->execute([$email]);

                $usersVerify = $stmtVerify->fetch();
                $countRowVerify = $stmtVerify->rowCount();


                if ($countRowVerify > 0) {
                    $_SESSION['errmsg'] = "Email is already exist";

                    if (isset($_SESSION['GuardsDetails'])) {
                        header('location: GuardsManageAccount.php?msg=email_already_exist');
                    } else {
                        header('location: OICManageAccount.php?msg=email_already_exist');
                    }
                } else {
                    $sqlupdate = "UPDATE employee SET email = ? WHERE empId = ?";
                    $stmtupdate = $this->con()->prepare($sqlupdate);
                    $stmtupdate->execute([$email, $empId]);
    
                    $usersUpdate = $stmtupdate->fetch();
                    $countRowUpdate = $stmtupdate->rowCount();
    
                    if ($countRowUpdate > 0) {
                        $_SESSION['successmsg'] = "Email was changed successfully";

                        if (isset($_SESSION['GuardsDetails'])) {
                            header('location: GuardsManageAccount.php?msg=change_email_success');
                        } else {
                            header('location: OICManageAccount.php?msg=change_email_success');
                        }
                    } else {
                        // header('location: OICManageAccount.php?msg=invalid_email');
                    }
                }
            } else {
                $_SESSION['errmsg'] = "You entered an incorrect password";

                if (isset($_SESSION['GuardsDetails'])) {
                    header('location: GuardsManageAccount.php?msg=account_incorrect_password');
                } else {
                    header('location: OICManageAccount.php?msg=account_incorrect_password');
                }
            }
        }
    }

    public function changePasswordValidate() {
        if (isset($_POST['submit'])) {

            if (isset($_SESSION['GuardsDetails'])) {
                $empId = $_SESSION['GuardsDetails']['empId'];
            } else {
                $empId = $_SESSION['OICDetails']['empId'];
            }

            $hash_password = md5($_POST['password']);
            $text_password = $_POST['newpassword'];
            $new_password = md5($_POST['newpassword']);
            $confirm_password = md5($_POST['confirmpassword']);

            $sql = "SELECT * FROM employee WHERE empId = ? AND password = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$empId, $hash_password]);

            $users = $stmt->fetch();
            $countRow = $stmt->rowCount();

            if ($countRow > 0) {

                if ($new_password !== $confirm_password) {
                    $_SESSION['errmsg'] = "Your new and confirm password does not match";
                    if (isset($_SESSION['GuardsDetails'])) {
                        header('location: GuardsChangePassword.php?msg=password_not_match');
                    } else {
                        header('location: OICChangePassword.php?msg=password_not_match');
                    }
                } else if (strlen($text_password) < 8) {
                    $_SESSION['errmsg'] = "Password must be greater than 7 alphanumeric characters.";
                    if (isset($_SESSION['GuardsDetails'])) {
                        header('location: GuardsChangePassword.php?msg=password_length_error');
                    } else {
                        header('location: OICChangePassword.php?msg=password_length_error');
                    }
                } else {

                    $sqlUpdate = "UPDATE employee SET password = ? WHERE empId = ?";
                    $stmtUpdate = $this->con()->prepare($sqlUpdate);
                    $stmtUpdate->execute([$new_password, $empId]);

                    $usersUpdate = $stmtUpdate->fetch();
                    $countRow = $stmtUpdate->rowCount();

                    if ($countRow > 0) {
                        $_SESSION['successmsg'] = "Password was changed successfully";
                        if (isset($_SESSION['GuardsDetails'])) {
                            header('location: GuardsChangePassword.php?msg=change_password_successfully');
                        } else {
                            header('location: OICChangePassword.php?msg=change_password_successfully');
                        }
                    }
                }

            } else {
                $_SESSION['errmsg'] = "You entered an incorrect current password";
                if (isset($_SESSION['GuardsDetails'])) {
                    header('location: GuardsChangePassword.php?msg=incorrect_current_password');
                } else {
                    header('location: OICChangePassword.php?msg=incorrect_current_password');
                }
            }
        }
    }

    // public function ShowMonitoringGuards() {
    //     $company = $_SESSION['OICDetails']['company']; //(❁´◡`❁))
    //     $empId = $_SESSION['OICDetails']['empId'];

    //     $sql = "SELECT *
    //             FROM schedule s

    //             INNER JOIN employee e
    //             ON s.empId = e.empId

    //             WHERE s.company = ? 
    //             AND s.empId != ?";

    //     $stmt = $this->con()->prepare($sql);
    //     $stmt->execute([$company, $empId]);

    //     while($row = $stmt->fetch()) {
    //         $fullname = $row->lastname.', '.$row->firstname;
    //         $dateNow = date("Y/m/d");

    //             echo "<tr>
    //                     <td>$fullname</td>
    //                     <td><a href='OICMonitorGuards.php?aid=$row->id'>Absent</a></td>
    //                 </tr>";
    //     }
    // }


    public function ShowMonitoringGuards() {
        $company = $_SESSION['OICDetails']['company']; //(❁´◡`❁))
        $empId = $_SESSION['OICDetails']['empId'];

        $sql = "SELECT
                    s.id,
                    s.empId,
                    e.firstname,
                    e.lastname,
                    att.timeIn,
                    att.login_session
                FROM schedule s
                LEFT JOIN emp_attendance att
                ON s.empId = att.empId AND att.login_session = 'true'
                INNER JOIN employee e
                ON s.empId = e.empId
                WHERE s.company = ? AND s.empId != ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$company, $empId]);

        while($row = $stmt->fetch()) {
            $fullname = $row->lastname.', '.$row->firstname;
            $dateNow = date("Y/m/d");

            if ($row->login_session == 'true') {
                
                $timeIn = 'Yes';
    
                echo "<tr>
                        <td>$fullname</td>
                        <td class='schedule_table'>$timeIn</td>
                        <td><a href='OICMonitorGuards.php?void=$row->empId'>Void</td>
                    </tr>";
            } else {
                $timeIn = 'No';

                echo "<tr>
                        <td>$fullname</td>
                        <td class='schedule_table'>$timeIn</td>
                        <td><a href='OICMonitorGuards.php?aid=$row->empId'>Absent</a></td>
                    </tr>";
            }
        }
    }

    public function ShowAbsentModal() {
        if(isset($_GET['aid'])) {
            $aid = $_GET['aid'];
    
            echo "<script>
                    let viewModal = document.querySelector('.view-modal');
                    viewModal.setAttribute('id', 'show-modal');
                </script>";
        }
    }

    public function ShowVoidModal() {
        if(isset($_GET['void'])) {
            $aid = $_GET['void'];
    
            echo "<script>
                    let viewModal = document.querySelector('.view-modal-void');
                    viewModal.setAttribute('id', 'show-modal');
                </script>";
        }
    }

    public function MarkAsAbsent() {
        if (isset($_POST['proceed'])) {

            $getId = $_GET['aid'];
            $sqlCheck = "SELECT * FROM schedule WHERE empId = ?";
            $stmtCheck = $this->con()->prepare($sqlCheck);
            $stmtCheck->execute([$getId]);
        
            $usersCheck = $stmtCheck->fetch();
            $countRowCheck = $stmtCheck->rowCount();
        
            if ($countRowCheck > 0) {
                $hours = $usersCheck->shift_span;

                date_default_timezone_set('Asia/Manila');
                $timeIn = strtotime($usersCheck->scheduleTimeIn);
                $timeOut = strtotime($usersCheck->scheduleTimeIn) + 60*60*$hours;
                $timeNow = strtotime(date("h:i:s A"));

                if ($timeNow >= $timeIn && $timeNow <= $timeOut) {
                    $dateNow = date("Y/m/d");
                    $remarkmessage = "This guard has violated our rules and no longer be attend to our establishment. Violation Committed: Absent Without Official Leave (AWOL)";
                    $violation = "Absent Without Official Leave (AWOL)";
                    $availability = "Available";
        
                    $sqlUpdate = "BEGIN;
                                    INSERT INTO violationsandremarks (empId, violation, remark, date_created) VALUES (?, ?, ?, ?);
                                    DELETE FROM schedule WHERE empId = ?;
                                    UPDATE employee SET availability = ? WHERE empId = ?;
                                COMMIT;";
        
                    $stmtUpdate = $this->con()->prepare($sqlUpdate);
                    $stmtUpdate->execute([$getId, $violation, $remarkmessage, $dateNow, $getId, $availability, $getId]);
        
                    $_SESSION['successmsg'] = "The selected guard has been removed to the guards panel. Please call the JTDV Agency to ask for a new guard."; 
                    header("location: OICMonitorGuards.php?msg=mark_absent_success");
                } else {
                    $_SESSION['errmsg'] = "You can't mark this as Absent Without Official Leave (AWOL) when this guards' schedule isn't started yet"; 
                    header("location: OICMonitorGuards.php?msg=mark_absent_request_denied");
                }
            }
        }
    }

    public function MarkAsVoid() {

    }


    // public function MarkAsAbsent() {
    //     if(isset($_POST['proceed'])) {

    //         $getId = $_GET['aid'];
    //         $sqlCheck = "SELECT * FROM schedule WHERE empId = ?";
    //         $stmtCheck = $this->con()->prepare($sqlCheck);
    //         $stmtCheck->execute([$getId]);

    //         $usersCheck = $stmtCheck->fetch();
    //         $countRowCheck = $stmtCheck->rowCount();

    //         if ($countRowCheck > 0) {

    //             $hours = $usersCheck->shift_span;

    //             date_default_timezone_set('Asia/Manila');
    //             $timeIn = strtotime($usersCheck->scheduleTimeIn);
    //             $timeOut = strtotime($usersCheck->scheduleTimeIn) + 60*60*$hours;
    //             $timeNow = strtotime(date("h:i:s A"));

    //             if ($timeNow >= $timeIn && $timeNow <= $timeOut) {
    //                 $getId = $_GET['aid'];
    //                 $dateToday = date("Y/m/d");
    //                 $status = "Absent";
    //                 $sqlFind = "SELECT * FROM emp_attendance WHERE empId = ? AND datetimeIn = ? AND datetimeOut = ? AND status = ?";
    //                 $stmtFind = $this->con()->prepare($sqlFind);
    //                 $stmtFind->execute([$getId, $dateToday, $dateToday, $status]);

    //                 $usersFind = $stmtFind->fetch();
    //                 $countRowFind = $stmtFind->rowCount();

    //                 if ($countRowFind > 0) {
    //                     $_SESSION['errmsg'] = "You have already marked this as absent."; 
    //                     header("location: OICMonitorGuards.php?msg=mark_absent_already_requested");
    //                 } else {
    //                     $login_session = "false";
    //                     $sql = "INSERT INTO emp_attendance (empId, datetimeIn, datetimeOut, status, login_session) VALUES (?, ?, ?, ?, ?)";
    //                     $stmt = $this->con()->prepare($sql);
    //                     $stmt->execute([$getId, $dateToday, $dateToday, $status, $login_session]);
            
    //                     $users = $stmt->fetch();
    //                     $countRow = $stmt->rowCount();
            
    //                     if ($countRow > 0) { 
    //                         $dateNow = date("Y/m/d");
    //                         $remarkmessage = "This guard has violated our rules and no longer be attend to our establishment. Violation Committed: Absent Without Official Leave (AWOL)";
    //                         $violation = "Absent Without Official Leave (AWOL)";

    //                         $sqlUpdate = "BEGIN;
    //                                         INSERT INTO violationsandremarks (empId, violation, remark, date_created) VALUES (?, ?, ?, ?);
    //                                         DELETE FROM schedule WHERE empId = ?;
    //                                       COMMIT;";

    //                         $stmtUpdate = $this->con()->prepare($sqlUpdate);
    //                         $stmtUpdate->execute([$getId, $violation, $remarkmessage, $dateNow, $getId]);

    //                         $usersUpdate = $stmtUpdate->fetch();
    //                         $countRowUpdate = $stmtUpdate->rowCount();
                             
    //                         $_SESSION['successmsg'] = "Selected guard has been successfully marked as absent"; 
    //                         header("location: OICMonitorGuards.php?msg=mark_absent_success");
                            
    //                     }
    //                 }
    //             } else {
    //                 $_SESSION['errmsg'] = "You can't mark this as absent when this guards' schedule isn't started yet"; 
    //                 header("location: OICMonitorGuards.php?msg=mark_absent_request_denied");
    //             }
    //             // echo 'Time In: '.$timeIn.' - '.date("h:i:s A", $timeIn).'<br>Time Out: '.$timeOut.' - '.date("h:i:s A", $timeOut).'<br>Time Now: '.$timeNow.' - '.date("h:i:s A", $timeNow);
    //         }
    //     }
    // }


    public function CountAssignGuards() {

        $empId = $_SESSION['OICDetails']['empId'];
        $company = $_SESSION['OICDetails']['company'];

        $sql = "SELECT COuNT(*) FROM schedule WHERE company = ? AND empId != ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$company, $empId]);

        $count = $stmt->fetchColumn();

        echo $count;
    }

    public function CountScheduledGuards() {

        $empId = $_SESSION['OICDetails']['empId'];
        $company = $_SESSION['OICDetails']['company'];

        $sql = "SELECT COUNT(*)
                FROM schedule 
                WHERE company = ?
                AND empId != ? 
                AND scheduleTimeIn 
                AND scheduleTimeOut IS NOT NULL";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$company, $empId]);

        $count = $stmt->fetchColumn();

        echo $count;
    }

    public function CountDayShiftGuards() {

        $empId = $_SESSION['OICDetails']['empId'];
        $company = $_SESSION['OICDetails']['company'];
        $shift = 'Day';
        $sql = "SELECT COUNT(*)
                FROM schedule 
                WHERE company = ?
                AND empId != ? 
                AND shift = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$company, $empId, $shift]);

        $count = $stmt->fetchColumn();

        echo $count;
    }

    public function CountNightShiftGuards() {

        $empId = $_SESSION['OICDetails']['empId'];
        $company = $_SESSION['OICDetails']['company'];
        $shift = 'Night';
        $sql = "SELECT COUNT(*)
                FROM schedule 
                WHERE company = ?
                AND empId != ? 
                AND shift = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$company, $empId, $shift]);

        $count = $stmt->fetchColumn();

        echo $count;
    }
    



    /************************************************************************************************************/
    /************************************************************************************************************/
    /************************************************************************************************************/
    /******************************************   RED PROPERTIESSSSSSS   ****************************************/

    // get login session: Secretary
    public function getSessionSecretaryData()
    {
        session_start();
        if($_SESSION['SecretaryDetails']){
            return $_SESSION['SecretaryDetails'];
        }
    }

    public function displayAttendance()
    {
        $sql ="SELECT emp_info.empId, emp_info.firstname, emp_info.lastname,emp_attendance.company, emp_attendance.timeIn, emp_attendance.datetimeIn,
                        emp_attendance.timeOut, emp_attendance.datetimeOut,
                        emp_attendance.status, emp_attendance.id
        FROM emp_info
        INNER JOIN emp_attendance ON emp_info.empId = emp_attendance.empId WHERE emp_attendance.salary_status != 'paid'
        ORDER BY emp_attendance.id DESC;";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute();
            while($row = $stmt->fetch()){
            echo "<tr>
            <td>&nbsp;$row->empId&nbsp;</td>
            <td>&nbsp;$row->firstname&nbsp;</td>
            <td>&nbsp;$row->lastname&nbsp;</td>
            <td>&nbsp;$row->company&nbsp;</td>
            <td>&nbsp;$row->timeIn&nbsp;</td>
            <td>&nbsp;$row->datetimeIn&nbsp;</td>
            <td>&nbsp;$row->timeOut&nbsp;</td>
            <td>&nbsp;$row->datetimeOut&nbsp;</td>
            <td>&nbsp;$row->status&nbsp;</td>
            </tr>";   
            }
    }

    public function displayGeneratedSalary()
    {
        $sql ="SELECT log, generated_salary.emp_id, emp_info.firstname, emp_info.lastname, generated_salary.location, generated_salary.date
        FROM generated_salary INNER JOIN emp_info WHERE generated_salary.emp_id = emp_info.empId ORDER BY date ASC;";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute();
            while($row = $stmt->fetch()){
            echo "<tr>
            <td>$row->emp_id</td>
            <td>$row->firstname $row->lastname</td>
            <td>$row->location</td>
            <td>$row->date</td>
            <td><a href='viewsalary.php?logid=$row->log'>View </a><a href='updatesalary.php?logid=$row->log'>Update  </a><a href='deletesalary.php?logid=$row->log'>Delete </a></td>
            </tr>";
            $this->deleteSalary($row->log);
            }
    }

    public function deleteSalary($logid)
    {
        if(isset($_POST['delete'])){
        $sessionData = $this->getSessionSecretaryData();
        $fullname = $sessionData['fullname'];
        $secid = $sessionData['id'];
        $datetime = $this->getDateTime();
        $time = $datetime['time'];
        $date = $datetime['date'];
        $empid=$logid;
        $sql= "DELETE FROM generated_salary WHERE log = ?;";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$empid]);
        $countrow = $stmt->rowCount();
        if($countrow > 0) {
            $action = "Delete Salary";
            $sqlSecLog = "INSERT INTO secretary_log (sec_id, name, action, time, date)
                                VALUES(?, ?, ?, ?, ?)";
            $stmtSecLog = $this->con()->prepare($sqlSecLog);
            $stmtSecLog->execute([$secid,$fullname, $action, $time, $date]);
            $countRowSecLog = $stmtSecLog->rowCount();
                if($countRowSecLog > 0){
                    echo 'pumasok na sa act log';
                    header('location:manualpayroll.php');
                } else {
                    echo 'di pumasok sa act log';
                    header('location:manualpayroll.php');
                }
            } else {
                echo 'Error in deleting salary!';
            }
        }
        else if(isset($_POST['cancel'])){
            header('location: manualpayroll.php');
        }else{
        }

    }
    
    public function search()
    {
        if(isset($_POST['bsearch']))
            $search = strtolower($_POST['search']);
    
            if(!empty($search)){
                $sql ="SELECT emp_info.empId, emp_info.firstname, emp_info.lastname, 
                              emp_attendance.company, emp_attendance.timeIn, emp_attendance.datetimeIn, 
                              emp_attendance.timeOut, emp_attendance.datetimeOut,
                              emp_attendance.status
                       FROM emp_info
                       INNER JOIN emp_attendance ON emp_info.empId = emp_attendance.empId WHERE emp_attendance.salary_status != 'paid';";
                
                $found=false;
                $stmt = $this->con()->prepare($sql);
                $stmt->execute();
                $users = $stmt->fetchAll();
                $countRow = $stmt->rowCount();
                foreach($users as $user){
                $lfirstname = strtolower($user->firstname);
                $llastname = strtolower($user->lastname);
                $lcompany = strtolower($user->company);
                $lstatus = strtolower($user->status);
                $timeIn = strtolower($user->timeIn);
                $timeOut = strtolower($user->timeOut);
                if(preg_match("/{$search}/i", $lfirstname) || preg_match("/{$search}/i", $llastname) || preg_match("/{$search}/i", $lcompany) || preg_match("/{$search}/i", $lstatus) || preg_match("/{$search}/i", $timeIn) || preg_match("/{$search}/i", $user->datetimeIn) || preg_match("/{$search}/i", $timeOut) ||preg_match("/{$search}/i", $user->datetimeOut)){
                    echo "<tr>
                    <td>&nbsp;$user->empId&nbsp;</td>
                    <td>&nbsp;$user->firstname&nbsp;</td>
                    <td>&nbsp;$user->lastname&nbsp;</td>
                    <td>&nbsp;$user->company&nbsp;</td>
                    <td>&nbsp;$user->timeIn&nbsp;</td>
                    <td>&nbsp;$user->datetimeIn&nbsp;</td>
                    <td>&nbsp;$user->timeOut&nbsp;</td>
                    <td>&nbsp;$user->datetimeOut&nbsp;</td>
                    <td>&nbsp;$user->status&nbsp;</td>
                    <tr/>";
                    $found=true;
                }
                }
                if($found!==true){
                    echo"No Record Found!";
                    $this->displayAttendance();
                }
                }else{
                echo "Please Input Fields!";
                $this->displayAttendance();
                }
    }


    public function generateSalary($id,$fullname)
    {
        if(isset($_POST['generate']))
        {
            if( isset($_POST['empid']) &&
                isset($_POST['rate']) &&
                isset($_POST['hrsduty']) &&
                isset($_POST['location']) &&
                isset($_POST['noofdayswork']) &&
                isset($_POST['regholiday']) &&
                isset($_POST['daylate']) &&
                isset($_POST['hrslate']) &&
                isset($_POST['sss']) &&
                isset($_POST['pagibig']) &&
                isset($_POST['philhealth']) &&
                isset($_POST['cashbond']) &&
                isset($_POST['specialholiday']) &&
                isset($_POST['thirteenmonth']) &&
                isset($_POST['cvale']))
            {
                if( empty($_POST['rate']) &&
                    empty($_POST['rate'])
                ){
                    echo "All inputs are required";
                } else {
                    $empid=$_POST['empid'];
                    $rate=(int)$_POST['rate'];
                    $hrsduty=(int)$_POST['hrsduty'];
                    $location = $_POST['location'];
                    $noofdayswork = (int)$_POST['noofdayswork'];
                    $regholiday = $_POST['regholiday'];
                    $daylate=$_POST['daylate'];
                    $hrslate=$_POST['hrslate'];
                    $sss=$_POST['sss'];
                    $pagibig=$_POST['pagibig'];
                    $philhealth=$_POST['philhealth'];
                    $cashbond=$_POST['cashbond'];
                    $specialholiday=$_POST['specialholiday'];
                    $thirteenmonth=$_POST['thirteenmonth'];
                    $netpay="";
                    $vale=$_POST['cvale'];
                    $totaldaysalary = $hrsduty * $rate ; // sahod sa isang araw depende sa duty at rate
    
                    $regholidayhoursalary = $regholiday * $rate;
                    $totalregholidaysalary = $regholidayhoursalary;                        // sahod pag regular holiday

                    $totalspecialholidayhoursalary = $specialholiday * $rate;
                    $totalspecialholidayhoursalarypercent = $totalspecialholidayhoursalary * 0.03;
                    $totalspecialholidaysalary = $totalspecialholidayhoursalarypercent + $totalspecialholidayhoursalary;
                    
                    $totalhrs = $hrsduty * $noofdayswork; // oras ng trabaho
                    $totalsalaryfortotalhours = $totalhrs * $rate;  // sahod sa oras nang tinrabaho

                    $totalholidaysalary = (float)$totalregholidaysalary + (float)$totalspecialholidaysalary;
                    $totg = (float)$totalholidaysalary + (float)$thirteenmonth;
                    $totalgross = (float)$totalsalaryfortotalhours + (float)$totg;

                    $totalsalaryforlate = (float)$hrslate * $rate;
                    $totaldeduction = (float)$vale + (float)$cashbond + (float)$sss + (float)$pagibig + (float)$philhealth + (float)$totalsalaryforlate;

                    $netpay = $totalgross - $totaldeduction;
                    // set timezone and get date and time
                    $datetime = $this->getDateTime();
                    $time = $datetime['time'];
                    $date = $datetime['date']; 
                    $sql = "INSERT INTO generated_salary (emp_id,
                                                location,
                                                rate_hour,
                                                date,
                                                hours_duty,
                                                regular_holiday,
                                                special_holiday,
                                                day_late,
                                                hrs_late,
                                                no_of_work,
                                                sss,
                                                pagibig,
                                                philhealth,
                                                cashbond,
                                                vale,
                                                thirteenmonth,
                                                total_hours,
                                                regular_pay,
                                                regular_holiday_pay,
                                                special_holiday_pay,
                                                total_deduction,
                                                total_gross,
                                                total_netpay,
                                                dateandtime_created
                                                )
                            VALUES(?, ?, ?, ?,?, ?,?, ?,?, ?, ?,?,?,?,?,?,?,?,? ,?, ?, ?, ?,?);";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$empid, $location, $rate, $date, $hrsduty,$regholiday, $specialholiday, $daylate, $hrslate,  $noofdayswork, $sss,$pagibig,$philhealth, $cashbond, $vale, $thirteenmonth,$totalhrs, $totalsalaryfortotalhours, $totalregholidaysalary, $totalspecialholidaysalary, $totaldeduction,$totalgross,$netpay, $time]);
                    $users = $stmt->fetch();
                    $countRow = $stmt->rowCount();

                    if($countRow > 0){
                        echo 'Added';

                        $action = "Add Salary";

                        $sqlSecLog = "INSERT INTO secretary_log (sec_id, name, action, time, date)
                                        VALUES(?, ?, ?, ?, ?)";
                        $stmtSecLog = $this->con()->prepare($sqlSecLog);
                        $stmtSecLog->execute([$id,$fullname, $action, $time, $date]);
                        $countRowSecLog = $stmtSecLog->rowCount();

                        if($countRowSecLog > 0){
                            echo 'pumasok na sa act log';
                        } else {
                            echo 'di pumasok sa act log';
                        }

                    } else {
                        echo 'Error in adding salary!';
                    }
                }
            } else {
                echo "All inputs are required!";
            }
        }
    }


    public function showSpecificSalary()
    {
        if(isset($_GET['empid'])){
            $id = $_GET['empid'];
            $sql = "SELECT * FROM generated_salary WHERE emp_id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            $countRow = $stmt->rowCount();

            if($countRow > 0){
                $empid = $user->emp_id;
                $location = $user->location;
                $date = $user->date;
                $late = $user->day_late; 
                $absent = $user->day_absent;
                $noofdayswork = $user->no_of_work; 
                $sss= $user->sss;
                $cashbond = $user->cashbond;
                $vale = $user->vale;
                $thirteenmonth = $user->thirteenmonth;
                $gross = $user->total_gross;
                $netpay = $user->total_netpay;
                $time = $user->dateandtime_created;
                echo"location ".$location;
            }
        }
    }

    public function updateSalary($id,$fullname){
        if(isset($_POST['edit']))
        {
            // if( isset($_POST['empid']) &&
            // !isset($_POST['rate']) &&
            // isset($_POST['hrsduty']) &&
            // isset($_POST['location']) &&
            // isset($_POST['noofdayswork']) &&
            // isset($_POST['regholiday']) &&
            // isset($_POST['daylate']) &&
            // isset($_POST['minlate']) &&
            // isset($_POST['dayabsent']) &&
            // isset($_POST['sss']) &&
            // isset($_POST['cashbond']) &&
            // isset($_POST['specialholiday']) &&
            // isset($_POST['thirteenmonth']) &&
            // isset($_POST['cvale']))
            // {
                $empid=$_POST['empid'];
                $rate=(int)$_POST['rate'];
                $hrsduty=(int)$_POST['hrsduty'];
                $location = $_POST['location'];
                $noofdayswork = (int)$_POST['noofdayswork'];
                $regholiday = $_POST['regholiday'];
                $daylate=$_POST['daylate'];
                $minlate=$_POST['hrslate'];
                // $dayabsent=$_POST['dayabsent'];
                $sss=$_POST['sss'];
                $cashbond=$_POST['cashbond'];
                $specialholiday=$_POST['specialholiday'];
                $thirteenmonth=$_POST['thirteenmonth'];
                $netpay="";
                $vale=$_POST['cvale'];
                $logid=$_GET['logid'];
                $totaldaysalary = $hrsduty * $rate ; // sahod sa isang araw depende sa duty at rate

                $totalregholidayhour = $hrsduty * $regholiday; 
                $totalregholidayhoursalary = $totalregholidayhour * $rate;
                $totalregholidaysalary = $totalregholidayhoursalary;                        // sahod pag regular holiday

                $specialholidayhour = $hrsduty * $specialholiday;
                $totalspecialholidayhoursalary = $specialholidayhour * $rate;
                $totalspecialholidayhoursalarypercent = $totalspecialholidayhoursalary * 0.03;
                $totalspecialholidaysalary = $totalspecialholidayhoursalarypercent;
                
                $totalhrs = $hrsduty * $noofdayswork; // oras ng trabaho
                $totalsalaryfortotalhours = $totalhrs * $rate;  // sahod sa oras nang tinrabaho

                $totalholidaysalary = $totalregholidaysalary + $totalspecialholidaysalary;
                $totg = $totalholidaysalary + $thirteenmonth;
                $totalgross = $totalsalaryfortotalhours + $totg;

                // $totalhourfordayabsent = $dayabsent * $hrsduty; // total hours ng absent
                // $totaldaysalaryfordayabsent = $totalhourfordayabsent * $rate; //sahod absent

                $totalsalaryforlate = $minlate * 59.523;
                $totaldeduction = $vale + $cashbond + $sss  + $totalsalaryforlate;

                $netpay = $totalgross - $totaldeduction;
                        // else if (!empty($empid) &&
                        // !empty($location)&&
                        // !empty($noofdayswork) &&
                        // !empty($cashbond) &&
                        // !empty($hrsduty) &&
                        // !empty($sss) &&
                        // !empty($rate) &&
                        // !empty($vale) &&
                        // !empty($daylate) &&
                        // !empty($hrslate) &&
                        // !empty($dayabsent) &&
                        // !empty($hrsabsent) &&
                        // !empty($thirteenmonth)
                        // ){
                        // set timezone and get date and time
                        $datetime = $this->getDateTime();
                        $time = $datetime['time'];
                        $date = $datetime['date']; 
                    $sql = "UPDATE generated_salary SET emp_id = ?,
                    location = ?,
                    rate_hour = ?,
                    date = ?,
                    hours_duty = ?,
                    regular_holiday = ?,
                    special_holiday = ?,
                    day_late = ?,
                    hrs_late = ?,
                    -- day_absent = ?,
                    -- hours_absent = ?,
                    no_of_work = ?,
                    sss = ?,
                    cashbond = ?,
                    vale = ?,
                    thirteenmonth = ?,
                    total_hours = ?,
                    regular_pay = ?,
                    regular_holiday_pay = ?,
                    special_holiday_pay = ?,
                    total_deduction = ?,
                    total_gross = ?,
                    total_netpay = ?,
                    dateandtime_created = ?
                    WHERE log = $logid;";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$empid, $location, $rate, $date,$hrsduty,$regholiday,$specialholiday,$daylate, $minlate, $noofdayswork, $sss, $cashbond, $vale, $thirteenmonth ,$totalhrs ,$totalsalaryfortotalhours,$totalregholidaysalary,$totalspecialholidaysalary,$totaldeduction,$totalgross,$netpay ,$time]);
                    $users = $stmt->fetch();
                    $countRow = $stmt->rowCount();

                        if($countRow > 0){
                            echo 'Updated';

                            $action = "Edit Salary";

                            $sqlSecLog = "INSERT INTO secretary_log (sec_id, name, action, time, date)
                                            VALUES(?, ?, ?, ?, ?)";
                            $stmtSecLog = $this->con()->prepare($sqlSecLog);
                            $stmtSecLog->execute([$id,$fullname, $action, $time, $date]);
                            $countRowSecLog = $stmtSecLog->rowCount();

                            if($countRowSecLog > 0){
                                echo 'pumasok na sa act log';
                                header('location: manualpayroll.php');
                            } else {
                                echo 'di pumasok sa act log';
                            }

                        } else {
                            echo 'Error in updating salary!';
                        }
            // } else {
            // echo "All inputs are required!";
            // }
        }
    }

    public function employeeList(){
        $sql ="SELECT * FROM emp_info";
        $stmt = $this->con()->prepare($sql);
                        $stmt->execute();
                        $users = $stmt->fetchall();
                        foreach($users as $user){
                            echo "<tr>
                            <td>&nbsp;$user->empId&nbsp;</td>
                            <td>&nbsp;$user->firstname&nbsp;</td>
                            <td>&nbsp;$user->lastname&nbsp;</td>
                            <td>&nbsp;$user->address&nbsp;</td>
                            <td>&nbsp;$user->cpnumber&nbsp;</td>
                            <td>&nbsp;$user->position&nbsp;</td>
                            <td>&nbsp;$user->status&nbsp;</td>
                            <td>&nbsp;<a href='viewemployee.php?empId=$user->empId'>View </a>&nbsp;</td>
                                </tr>";
                        }
    }

    public function searchEmployee(){
            if(isset($_POST['empsearch'])){
                $search = strtolower($_POST['employeesearch']);
        
                if(!empty($search)){
                    $sql ="SELECT empId, firstname, lastname, address, cpnumber, position, status
        FROM emp_info;";
        $found=false;
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute();
                    $users = $stmt->fetchAll();
                    $countRow = $stmt->rowCount();
                    foreach($users as $user){
                    $lfirstname = strtolower($user->firstname);
                    $llastname = strtolower($user->lastname);
                    $laddress = strtolower($user->address);
                    $lstatus = strtolower($user->status);
                    $lposition = strtolower($user->position);
                    if(preg_match("/{$search}/i", $lfirstname) || preg_match("/{$search}/i", $llastname) || preg_match("/{$search}/i", $laddress) || preg_match("/{$search}/i", $lstatus) || preg_match("/{$search}/i", $lposition)){
                        echo "<tr>
                        <td>&nbsp;$user->empId&nbsp;</td>
                        <td>&nbsp;$user->firstname&nbsp;</td>
                        <td>&nbsp;$user->lastname&nbsp;</td>
                        <td>&nbsp;$user->address&nbsp;</td>
                        <td>&nbsp;$user->cpnumber&nbsp;</td>
                        <td>&nbsp;$user->position&nbsp;</td>
                        <td>&nbsp;$user->status&nbsp;</td>
                        <td>&nbsp;<a href='viewemployee.php?empId=$user->empId'>View </a>&nbsp;</td>
                        <tr/>";
                        $found=true;
                    }
                    }
                    if($found!==true){
                        echo"No Record Found!";
                        $this->employeeList();
                    }
                    }else{
                    echo "Please Input Fields!";
                    $this->employeeList();
                    }
        }
    }

    public function automaticGenerateSalary($fullname,$id){
        if(isset($_POST['generateautomatic'])){
            $regholiday = 0;
            $specholiday = 0;
            $empid = $_POST['empid'];
            $sql="SELECT emp_attendance.timeIn, emp_attendance.timeOut, emp_info.rate
            FROM emp_attendance INNER JOIN emp_info ON emp_attendance.empId = emp_info.empId WHERE emp_attendance.empId = ? AND emp_attendance.salary_status != 'paid';";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$empid]);
                    $users = $stmt->fetchAll();
                    $countRow = $stmt->rowCount();
                    if($countRow >= 3){
                    $tothrs = 0;
                    foreach ($users as $user){
                        $rate = $user->rate;
                        $timein= date('H:i:s',strtotime($user->timeIn));
                        $timeout= date('H:i:s',strtotime($user->timeOut));
                        $tothrs += abs(strtotime($timein) - strtotime($timeout)) /3600 ;
                        
                    }
                    $sql0="SELECT emp_attendance.timeIn, emp_attendance.timeOut, emp_info.rate, emp_attendance.datetimeIn, emp_attendance.datetimeOut, emp_info.position
                    FROM emp_attendance INNER JOIN emp_info ON emp_attendance.empId = emp_info.empId WHERE emp_attendance.empId = ?;";
                    $stmt0 = $this->con()->prepare($sql0);
                    $stmt0->execute([$empid]);
                    $users0 = $stmt0->fetch();
                    $countRow0 = $stmt0->rowCount();
                    $hoursduty = 12;                                   //modify pag ayos na sched table
                    if($countRow0 >= 1){
                    $getin=$countRow0;
                    while($countRow0 >= $getin){
                    $start = $users0->datetimeIn;
                    $getin++;
                    }
                    $end = $start;
                    $users01 = $stmt0->fetchall();                        //get start date and end date
                    foreach($users01 as $user0){
                    $end = $user0->datetimeOut;
                    }
                    $sql1="SELECT * FROM cashadvance WHERE empId = ?;";
                    $stmt1 = $this->con()->prepare($sql1);
                    $stmt1->execute([$empid]);
                    $users1 = $stmt1->fetch();
                    $countRow1 = $stmt1->rowCount();
                    if($countRow1 > 0){
                        $vale = $users1->amount;
                    }else{
                        $vale = 0;
                    }
                    $position = $users0->position; //get the position of selected employee
                    if(strtolower($position)=="security officer" || $hoursduty == 12)                          
                    {
                        $rate = 59.523;
                        $philhealth = 0;                                    //modify pag may schedule table na
                                                                            //kapag guard tapos 12 hrs duty
                    $sql2="SELECT * FROM deductions WHERE position = 'security officer' AND duty = 12;";
                    $stmt2 = $this->con()->prepare($sql2);
                    $stmt2->execute();
                    $users2 = $stmt2->fetchall();
                    $countRow2 = $stmt2->rowCount();
                    if($countRow2 > 0){
                        foreach($users2 as $user2){
                            if(strtolower($user2->deduction)=="sss"){
                                $sss = $user2->amount;
                            }else if(strtolower($user2->deduction)=="pagibig"){
                                $pagibig = $user2->amount;
                            }else if(strtolower($user2->deduction)=="philhealth"){
                                $philhealth = $user2->amount;
                            }else{
                                $sss = 0;
                                $pagibig = 0;
                                $philhealth = 0;
                            }
                            }
                    }else {
                        $sss = 0;
                        $pagibig = 0;
                        $philhealth = 0;
                        echo "No deductions set";
                    }


                }else if(strtolower($position)=="security officer" || $hoursduty == 8){                                                     //kapag guard tapos 8 hrs duty
                    $sql2="SELECT * FROM deductions WHERE position = 'security officer' AND duty = 8;";
                    $stmt2 = $this->con()->prepare($sql2);
                    $stmt2->execute();
                    $users2 = $stmt2->fetchall();
                    $countRow2 = $stmt2->rowCount();
                    if($countRow2 > 0){
                        foreach($users2 as $user2){
                            if(strtolower($user2->deduction)=="sss"){
                                $sss = $user2->amount;
                            }else if(strtolower($user2->deduction)=="pagibig"){
                                $pagibig = $user2->amount;
                            }else if(strtolower($user2->deduction)=="philhealth"){
                                $philhealth = $user2->amount;
                            }else{
                                $sss = 0;
                                $pagibig = 0;
                                $philhealth = 0;
                                    }
                                                    }           
                                        }else {
                        $sss = 0;
                        $pagibig = 0;
                        $philhealth = 0;
                        echo "No deductions set";
                                            }

                    } else if (strtolower($position)=="oic" || $hoursduty == 12){
                        $rate= 67.125;
                                                            //modify pag may schedule table na
                    $sql2="SELECT * FROM deductions WHERE position = 'oic' AND duty = 12;";
                    $stmt2 = $this->con()->prepare($sql2);
                    $stmt2->execute();
                    $users2 = $stmt2->fetchall();
                    $countRow2 = $stmt2->rowCount();
                    if($countRow2 > 0){
                        foreach($users2 as $user2){
                            if(strtolower($user2->deduction)=="sss"){
                                $sss = $user2->amount;
                            }else if(strtolower($user2->deduction)=="pagibig"){
                                $pagibig = $user2->amount;
                            }else if(strtolower($user2->deduction)=="philhealth"){
                                $philhealth = $user2->amount;
                            }else{
                                $sss = 0;
                                $pagibig = 0;
                                $philhealth = 0;
                            }
                            }
                    }else {
                        $sss = 0;
                        $pagibig = 0;
                        $philhealth = 0;
                        echo "No deductions set";
                    }
                        
                
                }else if(strtolower($position)=="oic" || $hoursduty == 8){
                    $sql2="SELECT * FROM deductions WHERE position = 'oic' AND duty = 8;";
                    $stmt2 = $this->con()->prepare($sql2);
                    $stmt2->execute();
                    $users2 = $stmt2->fetchall();
                    $countRow2 = $stmt2->rowCount();
                    if($countRow2 > 0){
                        foreach($users2 as $user2){
                            if(strtolower($user2->deduction)=="sss"){
                                $sss = $user2->amount;
                            }else if(strtolower($user2->deduction)=="pagibig"){
                                $pagibig = $user2->amount;
                            }else if(strtolower($user2->deduction)=="philhealth"){
                                $philhealth = $user2->amount;
                            }else{
                                $sss = 0;
                                $pagibig = 0;
                                $philhealth = 0;
                            }
                            }
                    }else {
                        $sss = 0;
                        $pagibig = 0;
                        $philhealth = 0;
                        echo "No deductions set";
                    }
                    }else{
                        echo "error in position";
                    }
                    $sqlhol="SELECT * FROM emp_attendance INNER JOIN holidays ON emp_attendance.datetimeIn = holidays.date_holiday WHERE emp_attendance.empId = ?;";
                    $stmthol = $this->con()->prepare($sqlhol);
                    $stmthol->execute([$empid]);
                    $usershol = $stmthol->fetchall();
                    $countRowhol = $stmthol->rowCount();
                    if($countRowhol > 0){
                        foreach($usershol as $userhol){
                            if(strtolower($userhol->type)=="regular holiday"){
                                $regholiday = $regholiday + 1;
                            }elseif(strtolower($userhol->type)=="special holiday"){
                                $specholiday = $specholiday + 1;
                            }else{

                            }
                        }
                    }
                        $standardpay = $tothrs * $rate;
                        $regholiday = $regholiday * $hoursduty;
                        $regholidaypay = ($regholiday * $rate);
                        $specholiday = $specholiday * $hoursduty;
                        $specrate = $specholiday * $rate;
                        $specpercent = $specrate * 0.30;
                        $specholidaypay = $specpercent;
                        $thirteenmonth = 0;
                        $cashbond = 50;
                        $total_hours_late = 0;                                      //sa attendance ni vonne to
                        $totalgross = ($standardpay + $regholidaypay + $specholidaypay + $thirteenmonth);
                        $totaldeduction = ($sss + $pagibig + $philhealth + $cashbond + $vale);
                        $totalnetpay = $totalgross - $totaldeduction;
                        if($totalnetpay < 0){
                        $forrelease = "**Not for Release!";
                        }else{
                            $forrelease="For Release";
                        }
                        date_default_timezone_set('Asia/Manila');
                        $date = date('F j, Y h:i:s A');
                    if($countRow > 0 ){
                        $sql1="INSERT INTO `automatic_generated_salary`(`emp_id`, `total_hours`,`standard_pay`, `regular_holiday`, 
                        `regular_holiday_pay`, `special_holiday`, `special_holiday_pay`, `thirteenmonth`, `sss`,`pagibig`,`philhealth`, `cashbond`, 
                        `vale`, `total_hours_late`, `total_gross`, `total_deduction`, `total_netpay` ,`start`,`end`,`for_release`,`date_created`) 
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
                        $stmt1 = $this->con()->prepare($sql1);
                        $stmt1->execute([$empid,$tothrs,$standardpay,$regholiday,$regholidaypay,$specholiday,$specholidaypay,$thirteenmonth,$sss,$pagibig,$philhealth,$cashbond,$vale,$total_hours_late,$totalgross,$totaldeduction,$totalnetpay,$start,$end,$forrelease,$date]);
                        $CountRow01 = $stmt1 ->rowCount();
                        if($CountRow01>0){
                            $action = "Add Automated Salary";
                            $secdatetime = $this->getDateTime();
                            $sectime = $secdatetime['time'];
                            $secdate = $secdatetime['date'];
                            $sqlSecLog = "INSERT INTO secretary_log (sec_id, name, action, time, date)
                                        VALUES(?, ?, ?, ?, ?)";
                            $stmtSecLog = $this->con()->prepare($sqlSecLog);
                            $stmtSecLog->execute([$id,$fullname, $action, $sectime, $secdate]);
                            $countRowSecLog = $stmtSecLog->rowCount();
                            if($countRowSecLog > 0){
                                echo 'pumasok na sa act log';
                            } else {
                                echo 'di pumasok sa act log';
                            }
                                            }
                    }
                }else {
                    echo "The selected employee is less than or equal to 5 attendance only, can't generate salary";
                }
            }
        }
    }

    public function displayAutomaticGeneratedSalary(){
            $sql ="SELECT log, automatic_generated_salary.emp_id, automatic_generated_salary.start, automatic_generated_salary.end, emp_info.firstname, emp_info.lastname, automatic_generated_salary.date_created
            FROM automatic_generated_salary INNER JOIN emp_info WHERE automatic_generated_salary.emp_id = emp_info.empId AND for_release !='released'  ORDER BY date_created DESC;";
                $stmt = $this->con()->prepare($sql);
                $stmt->execute();
                while($row = $stmt->fetch()){
                echo "<tr>
                <td>$row->emp_id</td>
                <td>$row->firstname $row->lastname</td>
                <td>$row->start</td>
                <td>$row->end</td>
                <td>$row->date_created</td>
                <td><a href='viewautomatedsalary.php?logid=$row->log'>View </a><a href='releaseautomatedsalary.php?logid=$row->log'>Release </a><a href='deleteautomatedsalary.php?logid=$row->log'>Delete </a></td>
                </tr>";
                // $this->deleteSalary($row->log);
                }
    }

    public function releaseSalary(){
            if(isset($_POST['release'])){
                $logid = $_GET['logid'];
                $sql = "SELECT * FROM automatic_generated_salary WHERE log = ?;";
                $stmt = $this->con()->prepare($sql);
                $stmt->execute([$logid]);
                $user=$stmt->fetch();
                if(strtolower($user->for_release)=='**not for release!'){
                    echo "di pwede irelease";
                    header('location: automaticpayroll.php');
                }else{
                $sql1="UPDATE automatic_generated_salary SET for_release = 'released' WHERE log = $logid;";
                $stmt1 = $this->con()->prepare($sql1);
                $stmt1->execute();
                $CountRow01 = $stmt1 ->rowCount();
                if($CountRow01>0){
                $status='unpaid';
                $sql2="UPDATE emp_attendance SET salary_status = 'paid' WHERE empId = ? AND salary_status = ?;";
                $stmt2 = $this->con()->prepare($sql2);
                $stmt2->execute([$user->emp_id,$status]);
                $CountRow02 = $stmt2 ->rowCount();
                if($CountRow02>0){
                    echo "pasok sa act log";
                    header('automaticpayroll.php');
                }
                }
                }


            }//isset
            else if(isset($_POST['cancel'])){
                header('location: automaticpayroll.php');
            }
    }

    public function deleteautomatedsalary($logid){
        if(isset($_POST['deleteauto'])){
            $sql = "DELETE FROM automatic_generated_salary WHERE log = ?;";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$logid]);
            $countrow = $stmt->rowCount();
            if($countrow > 0) {
            $action = "Delete Automated Salary";
            $sessionData = $this->getSessionSecretaryData();
            $fullname = $sessionData['fullname'];
            $secid = $sessionData['id'];
            $datetime = $this->getDateTime();
            $time = $datetime['time'];
            $date = $datetime['date'];
                $sqlSecLog = "INSERT INTO secretary_log (sec_id, name, action, time, date)
                                    VALUES(?, ?, ?, ?, ?)";
                $stmtSecLog = $this->con()->prepare($sqlSecLog);
                $stmtSecLog->execute([$secid,$fullname, $action, $time, $date]);
                $countRowSecLog = $stmtSecLog->rowCount();
                    if($countRowSecLog > 0){
                        echo 'pumasok na sa act log';
                        header('location:automaticpayroll.php');
                    } else {
                        echo 'di pumasok sa act log';
                        header('location:automaticpayroll.php');
                    }
                } else {
                    echo 'Error in deleting !';
                }
            }
            else if(isset($_POST['cancel'])){
                header('location: automaticpayroll.php');
            }else{
            }
    }

    public function adddeduction($fullname,$id){
                if(isset($_POST['generatededuction'])){
                    $deduction = $_POST['deduction'];
                    $position = $_POST['position'];
                    $duty = $_POST['duty'];
                    if(strtolower($deduction)=="sss"){
                        if(strtolower($position) == "security officer"){
                            $rate = 59.523;
                            $tothrs = $duty * 28;
                            $monthlysalary = $tothrs * $rate;
                            $amount = $monthlysalary * 0.0450 /2;    
                        }else{
                            $rate = 67.125;
                            $tothrs = $duty * 28;
                            $monthlysalary = $tothrs * $rate;
                            $amount = $monthlysalary * 0.0450 /2;
                        }
                    }else if(strtolower($deduction)=="pagibig"){
                        if(strtolower($position) == "security officer"){
                            $rate = 59.523;
                            $tothrs = $duty * 28;
                            $monthlysalary = $tothrs * $rate;
                            $amount = $monthlysalary * 0.02 /2;    
                        }else{
                            $rate = 67.125;
                            $tothrs = $duty * 28;
                            $monthlysalary = $tothrs * $rate;
                            $amount = $monthlysalary * 0.02 /2;
                        }
                    }else if(strtolower($deduction)=="philhealth"){
                        if(strtolower($position) == "security officer"){
                            $rate = 59.523;
                            $tothrs = $duty * 28;
                            $monthlysalary = $tothrs * $rate;
                            $amount = $monthlysalary * 0.035 / 2;    
                        }else{
                            $rate = 67.125;
                            $tothrs = $duty * 28;
                            $monthlysalary = $tothrs * $rate;
                            $amount = $monthlysalary * 0.035 / 2;
                        }
                    }
                    else{
                        echo "Error";
                    }
                    $cutoff = "Bi-weekly";
                    $sql="INSERT INTO  deductions (`deduction`, `position`,`cutoff`, `duty`, `amount`) VALUES (?,?,?,?,?);";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$deduction,$position,$cutoff,$duty, number_format($amount)]);
                    $countrow = $stmt->rowCount();
                    if($countrow > 0) {
                    $action = "Add Deduction";
                    $datetime = $this->getDateTime();
                    $time = $datetime['time'];
                    $date = $datetime['date'];
                    $sqlSecLog = "INSERT INTO secretary_log (sec_id, name, action, time, date)
                                        VALUES(?, ?, ?, ?, ?)";
                    $stmtSecLog = $this->con()->prepare($sqlSecLog);
                    $stmtSecLog->execute([$id,$fullname, $action, $time, $date]);
                    $countRowSecLog = $stmtSecLog->rowCount();
                    if($countRowSecLog > 0){
                        echo 'pumasok na sa act log';
                    } else {
                        echo 'di pumasok sa act log';
                        header('location:deductions.php');
                    }
                }
                }//isset
    }

    public function deletededuction($logid){
        if(isset($_POST['deletededuction'])){
            $sql = "DELETE FROM deductions WHERE id = ?;";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$logid]);
            $countrow = $stmt->rowCount();
            if($countrow > 0) {
            $action = "Delete Deduction";
            $sessionData = $this->getSessionSecretaryData();
            $fullname = $sessionData['fullname'];
            $secid = $sessionData['id'];
            $datetime = $this->getDateTime();
            $time = $datetime['time'];
            $date = $datetime['date'];
                $sqlSecLog = "INSERT INTO secretary_log (sec_id, name, action, time, date)
                                    VALUES(?, ?, ?, ?, ?)";
                $stmtSecLog = $this->con()->prepare($sqlSecLog);
                $stmtSecLog->execute([$secid,$fullname, $action, $time, $date]);
                $countRowSecLog = $stmtSecLog->rowCount();
                    if($countRowSecLog > 0){
                        echo 'pumasok na sa act log';
                        header('location:deductions.php');
                    } else {
                        echo 'di pumasok sa act log';
                        header('location:deductions.php');
                    }
                } else {
                    echo 'Error in deleting salary!';
                }
            }
            else if(isset($_POST['cancel'])){
                header('location: deductions.php');
            }else{

            }

    }

    public function displaydeduction(){
        $sql="SELECT id,deduction,position,cutoff,duty,amount FROM deductions;";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute();
        while($row = $stmt->fetch()){
            echo "<tr>
            <td>$row->deduction</td>
            <td>$row->position</td>
            <td>$row->cutoff</td>
            <td>$row->duty</td>
            <td>$row->amount</td>
            <td><a href='deletededuction.php?logid=$row->id'>Delete </a></td>
            </tr>";
            $this->deletededuction($row->id);
            }
    }

    public function cashadvance($fullname,$id){
        if(isset($_POST['add'])){
            if(!empty($_POST['amount'])){
            $empid = $_POST['empid'];
            $amount = $_POST['amount'];
            date_default_timezone_set('Asia/Manila');
            $date = date('F j, Y');
            if($amount <= 3000){
                $sql="INSERT INTO cashadvance (`empId`,`date`,`amount`) VALUES (?,?,?);";
                $stmt = $this->con()->prepare($sql);
                $stmt->execute([$empid,$date,$amount]);
                $countrow = $stmt->rowCount();
            if($countrow > 0) {
            $action = "Add Cash Advance";
            $datetime = $this->getDateTime();
            $time = $datetime['time'];
            $date = $datetime['date'];
                $sqlSecLog = "INSERT INTO secretary_log (sec_id, name, action, time, date)
                                    VALUES(?, ?, ?, ?, ?)";
                $stmtSecLog = $this->con()->prepare($sqlSecLog);
                $stmtSecLog->execute([$id,$fullname, $action, $time, $date]);
                $countRowSecLog = $stmtSecLog->rowCount();
                    if($countRowSecLog > 0){
                        echo 'pumasok na sa act log';
                    } else {
                        echo 'di pumasok sa act log';
                        header('location:deductions.php');
                    }
                }
            }else {
                echo "Maximum Cash Advance: 3,000 only";
            }
        }//empty
        }//isset
    }

    public function deletecashadv($logid){
        if(isset($_POST['deletecashadv'])){
            $sql = "DELETE FROM cashadvance WHERE id = ?;";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$logid]);
            $countrow = $stmt->rowCount();
            if($countrow > 0) {
            $action = "Delete Cash Advance";
            $sessionData = $this->getSessionSecretaryData();
            $fullname = $sessionData['fullname'];
            $secid = $sessionData['id'];
            $datetime = $this->getDateTime();
            $time = $datetime['time'];
            $date = $datetime['date'];
                $sqlSecLog = "INSERT INTO secretary_log (sec_id, name, action, time, date)
                                    VALUES(?, ?, ?, ?, ?)";
                $stmtSecLog = $this->con()->prepare($sqlSecLog);
                $stmtSecLog->execute([$secid,$fullname, $action, $time, $date]);
                $countRowSecLog = $stmtSecLog->rowCount();
                    if($countRowSecLog > 0){
                        echo 'pumasok na sa act log';
                        header('location:deductions.php');
                    } else {
                        echo 'di pumasok sa act log';
                        header('location:deductions.php');
                    }
                } else {
                    echo 'Error in deleting cash advance!';
                }
            }
            else if(isset($_POST['cancel'])){
                header('location: deductions.php');
            }else{
            }
    }

    public function displaycashadvance(){
        $sql="SELECT cashadvance.id, cashadvance.date, cashadvance.amount, emp_info.firstname, emp_info.lastname FROM cashadvance INNER JOIN emp_info ON cashadvance.empId = emp_info.empId;";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute();
        while($row = $stmt->fetch()){
            echo "<tr>
            <td>$row->firstname $row->lastname</td>
            <td>$row->date</td>
            <td>$row->amount</td>
            <td><a href='deletecashadv.php?logid=$row->id'>Delete </a></td>
            </tr>";
            $this->deletecashadv($row->id);
            }
    }



}

$payroll = new Payroll();

?>