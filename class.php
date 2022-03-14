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
    private $username = "root";
    private $password = "";

    private $dns = "mysql:host=localhost;dbname=payroll";
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
            $realPassword = $this->generatedPassword2();
            $dbPassword = $this->generatedPassword($realPassword);
            $isDeleted = FALSE;

            $timer = NULL;

            if(empty($fullname) &&
               empty($email) &&
               empty($gender) &&
               empty($address) &&
               empty($realPassword) &&
               empty($dbPassword) &&
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
                    $stmt->execute([$fullname, $gender, $cpnumber, $address, $email, $dbPassword[0], $timer, $id, $access, $isDeleted]);
                    $users = $stmt->fetch();
                    $countRow = $stmt->rowCount();

                    if($countRow > 0){
                        echo 'A new date was added';

                        // gagamitin pang login sa employee dashboard
                        $sqlSecretKeySecretary = "INSERT INTO secret_diarys(se_id, secret_key)
                                                    VALUES(?, ?)";
                        $stmtSecretKeySecretary = $this->con()->prepare($sqlSecretKeySecretary);
                        $stmtSecretKeySecretary->execute([$email, $realPassword]);
                        // send user credentials
                        $this->sendEmail($email, $realPassword);

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

    // check password
    public function generatedPassword($pword)
    {
        $keyword = "%15@!#Fa4%#@kE";
        $generatedPassword = md5($pword.$keyword);
        return array($generatedPassword, $pword.$keyword);
    }

    // create password
    public function generatedPassword2(){
        $pword = "abcdefghijklmnopqrstuvwxyz0123456789@#$%^&*()_+";
        $pword = str_shuffle($pword);
        $pword = substr($pword, 0, 8); // length of pass
        return $pword;
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
            $number = $_POST['cpnumber'];
            $address = $_POST['address'];

            // oks lang ket walang number
            if(empty($fullname) &&
            empty($gender) &&
            empty($email) &&
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

                            // after mo maupdate kunin mo yung data
                            $sql2 = "SELECT s.email, 
                                            se.se_id, 
                                            se.secret_key as secret_key
                                     FROM secretary s
                                     INNER JOIN secret_diarys se
                                     ON s.email = se.se_id

                                     WHERE s.email = ?";
                            $stmt2 = $this->con()->prepare($sql2);
                            $stmt2->execute([$email]);
                            $users2 = $stmt2->fetch();
                            $countRow2 = $stmt2->rowCount();

                            if($countRow2 > 0){
                                $this->sendEmail($users2->email, $users2->secret_key);
                                echo "<script>window.location.assign('secretary.php');</script>";
                            } else {
                                echo 'There was something wrong in our codes';
                            }

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
                $sql = "UPDATE secretary SET isDeleted = ? WHERE id = ?";
                $stmt = $this->con()->prepare($sql);
                $stmt->execute([1, $id]);
                $countRow = $stmt->rowCount();

                if($countRow > 0){
                    echo 'Data was successfully deleted';
                } else {
                    echo 'There was something wrong in our codes';
                }
            }
        }
        
    }



    public function recentAssignedGuards()
    {
        $sql = "SELECT * FROM employee 
                WHERE availability = 'unavailable' 
                AND date BETWEEN CURRENT_DATE - 15 
                             AND CURRENT_DATE
                ORDER BY date DESC
                LIMIT 3";
        $stmt = $this->con()->query($sql);
        while($users = $stmt->fetch()){
            $fullname = $users->lastname.", ".$users->firstname;
            echo "<div class='assignedguard-row'>
                      <div class='assignedguard-row-text'>
                          <p>$fullname</p>
                          <span>Position to <b>$users->position</b></span>
                      </div>
                      <div class='assignedguard-row-button'>
                          <div class='btn-delete'>
                              <a href='./employee.php?idDelete=$users->empId' class='btn-delete-icon'>
                                  <span class='material-icons'>delete</span>
                              </a>
                          </div>
                            
                      </div>
                  </div>";
        }

    }

    public function deleteRecentGuardModal($id)
    {
        $sql = "SELECT * FROM schedule WHERE empId = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        $countRow = $stmt->rowCount();

        if($countRow > 0){
            echo "<div class='modal-holder'>
                    <div class='deleteguard-header'>
                        <h1>Delete Employee</h1>
                        <span id='exit-modal-deleteguard' class='material-icons'>close</span>
                    </div>
                    <div class='deleteguard-content'>
                        <h1>Are you sure you want to transfer this employee to available guard?</h1>
                        <form method='post'>
                            <input type='hidden' name='empId' value='$user->empId' required/>
                            <button type='submit' name='deleteRecord'>Delete</button>
                        </form>
                    </div>
                </div>
                  ";
        }
    }
    
    public function deleteRecentGuard()
    {
        if(isset($_POST['deleteRecord'])){
            $empId = $_POST['empId'];
            if(empty($empId)){
                echo 'Input must contain id to delete';
            } else {
                // delete record in leave request
                $sqlLeave = "DELETE FROM leave_request WHERE empId = ?";
                $stmtLeave = $this->con()->prepare($sqlLeave);
                $stmtLeave->execute([$empId]);

                // find company in schedule before deleting it
                $sqlFindCompany = "SELECT * FROM schedule WHERE empId = ?";
                $stmtFindCompany = $this->con()->prepare($sqlFindCompany);
                $stmtFindCompany->execute([$empId]);
                $userFindCompany = $stmtFindCompany->fetch();
                $countRowFindCompany = $stmtFindCompany->rowCount();

                if($countRowFindCompany > 0){
                    // find how many guards in specific company
                    $sqlTotalGuards = "SELECT hired_guards FROM company WHERE company_name = ?";
                    $stmtTotalGuards = $this->con()->prepare($sqlTotalGuards);
                    $stmtTotalGuards->execute([$userFindCompany->company]);
                    $userTotalGuards = $stmtTotalGuards->fetch();
                    $countRowTotalGuards = $stmtTotalGuards->rowCount();

                    $hiredGuards = 0;
                    $intUsersHR = intval($userTotalGuards->hired_guards);

                    if($countRowTotalGuards > 0){
                        if($intUsersHR == 0 || 
                           $intUsersHR == NULL ||
                           $intUsersHR == 'NULL' ||
                           $intUsersHR == ''
                        ){
                           $hiredGuards = intval($intUsersHR) - 1;
                           echo "mabas if ELSE ".$hiredGuards;
                        } else {
                            $hiredGuards = intval($intUsersHR) - 1;
                            echo "mabas else ELSE ".$hiredGuards;
                        }
                    } 

                    // minus 1 in hired_guards inside company table
                    $sqlCompany = "UPDATE company SET hired_guards = ? WHERE company_name = ?";
                    $stmtCompany = $this->con()->prepare($sqlCompany);
                    $stmtCompany->execute([$hiredGuards, $userFindCompany->company]);
                    

                    // delete in schedule
                    $sqlSched = "DELETE FROM schedule WHERE empId = ?";
                    $stmtSched = $this->con()->prepare($sqlSched);
                    $stmtSched->execute([$empId]);

                    // delete in update employee details
                    $makeItNull = NULL;
                    $availability = 'Available';
                    $sqlEmp = "UPDATE employee
                               SET position = ?,
                                   ratesperDay = ?,
                                   watType = ?,
                                   availability = ?
                               WHERE empId = ?
                               ";
                    $stmtEmp = $this->con()->prepare($sqlEmp);
                    $stmtEmp->execute([$makeItNull, $makeItNull, $makeItNull, $availability, $empId]);
                }

                
            }
        }
    }




    public function addEmployee(){
        
        if(isset($_POST['addemployee'])){

            
            date_default_timezone_set('Asia/Manila'); // set default timezone to manila
            $curr_year = date("Y"); // year

            $empId = $curr_year."-".$this->createEmpId(); // generated empId

            if($this->createEmpId() == NULL || $this->createEmpId() == 0 || $this->createEmpId() == ""){
                $empId = $curr_year."-0";
            }

            $firstname = $_POST['firstname'];
            $lastname = $_POST['lastname'];
            $address = $_POST['address'];
            $email = $_POST['email'];
            // $password = $this->generatedPassword2($firstname." ".$lastname);
            $realPassword = $this->generatedPassword2();
            $dbPassword = $this->generatedPassword($realPassword); // md5, pass with keyword
            $browserfingerprint = $_POST['browserfingerprint'];
            $devicefingerprint = $_POST['devicefingerprint'];
            $qrcode = $_POST['qrcode'];
            $number = $_POST['number'];
            $access = "employee";
            $availability = "Available";

            $fullname = $firstname.$lastname;

            if(empty($firstname) &&
               empty($lastname) &&
               empty($number) &&
               empty($address) &&
               empty($email) &&
               empty($dbPassword) &&
               empty($browserfingerprint) &&
               empty($devicefingerprint) &&
               empty($qrcode) &&
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
                                                 browserfingerprint,
                                                 devicefingerprint,
                                                 qrcode,
                                                 access,
                                                 availability,
                                                 time,
                                                 date)
                            VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$empId, $firstname, $lastname, $number, $address, $email, $dbPassword[0], $browserfingerprint, $devicefingerprint, $qrcode, $access, $availability, $time, $date]);
                    $countRow = $stmt->rowCount();

                    if($countRow > 0){
                        echo 'A new data was added';

                        // gagamitin pang login sa employee dashboard
                        $sqlSecretKeyEmployee = "INSERT INTO secret_diarye(e_id, secret_key)
                                                    VALUES(?, ?)";
                        $stmtSecretKeyEmployee = $this->con()->prepare($sqlSecretKeyEmployee);
                        $stmtSecretKeyEmployee->execute([$email, $realPassword]);
                        // send user credentials
                        $this->sendEmail($email, $realPassword);

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
        $sql = "SELECT * FROM employee WHERE availability = 'Available'";
        $stmt = $this->con()->query($sql);

        while($row = $stmt->fetch()){
            $type = $row->watType == NULL ? 'None' : $row->watType;

            echo "<tr>
                    <td><input type='checkbox' id='c$row->id' onclick='setVal(this, $row->id);'/></td>
                    <td><label for='c$row->id'>$row->lastname, $row->firstname</label></td>
                    <td>$row->email</td>
                    <td>$row->address</td>
                    <td>
                       <div class='buttons'>
                            <div class='buttons-edit'>
                                <a href='showEmployees.php?id=$row->id&email=$row->email&action=edit'>
                                    <span class='material-icons'>edit</span>
                                </a>
                            </div>
                            <div class='buttons-delete'>
                                <a href='showEmployees.php?id=$row->id&action=delete'>
                                    <span class='material-icons'>delete</span>
                                </a>
                            </div>
                        </div>
                    </td>
                  </tr>";
        }
    }



    // showEmployees.php      td with actions for unavailable
    public function showAllUnavailableEmpActions(){
        $sql = "SELECT 
                       s.id,
                       s.empId,
                       s.company,
                       e.empId,
                       e.firstname AS firstname,
                       e.lastname AS lastname,
                       e.email,
                       c.company_name AS companyname,
                       c.comp_location AS location
                FROM schedule s
                INNER JOIN employee e
                ON s.empId = e.empId
                
                INNER JOIN company c
                ON s.company = c.company_name";
        $stmt = $this->con()->query($sql);
        
        while($row = $stmt->fetch()){
            $fullname = $row->lastname.", ".$row->firstname;
            echo "<tr>
                     <td>$fullname</td>
                     <td>$row->companyname</td>
                     <td>$row->location</td>
                     <td>
                        <div class='buttons'>
                            <div class='buttons-view'>
                                <a href='unavailable.php?sid=$row->id'>
                                    <span class='material-icons'>visibility</span>
                                </a>
                            </div>
                            <div class='buttons-delete'>
                                <a href='unavailable.php?sidDelete=$row->id'>
                                    <span class='material-icons'>delete</span>
                                </a>
                            </div>
                        </div>
                    </td>
                  </tr>";
        }
    }


    public function viewModalShow()
    {
        if(isset($_GET['sid'])){
            $id = $_GET['sid'];

            $sql = "SELECT 
                          s.id,
                          s.empId,
                          s.company,
                          s.expiration_date AS expdate,
                          e.empId AS empId,
                          e.firstname AS firstname,
                          e.lastname AS lastname,
                          e.position AS position,
                          e.ratesperDay AS price,
                          e.address AS address,
                          e.email AS email,
                          e.cpnumber as cpnumber,
                          c.company_name AS companyname,
                          c.comp_location AS location,
                          c.watType AS watType

                    FROM schedule s
                    INNER JOIN employee e
                    ON s.empId = e.empId

                    INNER JOIN company c
                    ON s.company = c.company_name
                    WHERE s.id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            $countRow = $stmt->rowCount();

            if($countRow > 0){
                $expdateArray = explode('-', $user->expdate);
                $year = $expdateArray[0];
                $month = $expdateArray[1];
                $day = $expdateArray[2];


                echo "<script>
                         let viewModal = document.querySelector('.modal-viewguard');
                         viewModal.style.display = 'flex';

                         let firstname = document.querySelector('#firstname').value = '$user->firstname';
                         let lastname = document.querySelector('#lastname').value = '$user->lastname';
                         let company = document.querySelector('#company').value = '$user->companyname';
                         let comp_location = document.querySelector('#comp_location').value = '$user->location';
                         let year = document.querySelector('#year').value = '$year';
                         let month = document.querySelector('#month').value = '$month';
                         let day = document.querySelector('#day').value = '$day';
                         let position = document.querySelector('#position').value = '$user->position';
                         let price = document.querySelector('#price').value = '$user->price';
                         let watType = document.querySelector('#watType').value = '$user->watType';
                         let empAddress = document.querySelector('#empAddress').value = '$user->address';
                         let email = document.querySelector('#email').value = '$user->email';
                         let cpnumber = document.querySelector('#cpnumber').value = '$user->cpnumber';

                      </script>";
            }
        }
    }

    // for unavailable guards
    public function getDuration($id)
    {
        $sql = "SELECT expiration_date FROM schedule WHERE id = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        $expdate = $user->expiration_date;
        $exdateArray = explode('-', $expdate);
        return $exdateArray;
    }

    public function deleteModalShow($id)
    {
        if(isset($_GET['sidDelete'])){
            $id = $_GET['sidDelete'];

            echo "<script>
                    let viewModal = document.querySelector('.modal-viewguard');
                    if(viewModal.style.display == 'block'){
                        viewModal.style.display = 'none';
                    }
                    
                    let removeModal = document.querySelector('.modal-deleteguard');
                    removeModal.style.display = 'flex';
                    let empId = document.querySelector('#rEmpId');
                    empId.value = '$id';
                  </script>";
        }
    }

    public function deleteUnavailableGuards()
    {
        if(isset($_POST['deleteUnavailable'])){
            $id = $_GET['sidDelete'];

            $sql = "SELECT * FROM schedule WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$id]);
            $users = $stmt->fetch();
            $countRow = $stmt->rowCount();

            if($countRow > 0){
                $empId = $users->empId;
                $company = $users->company;

                $sqlEmployee = "SELECT * FROM employee WHERE empId = ?";
                $stmtEmployee = $this->con()->prepare($sqlEmployee);
                $stmtEmployee->execute([$empId]);
                $usersEmployee = $stmt->fetch();
                $countRowEmployee = $stmt->rowCount();

                if($countRowEmployee > 0){
                    $position = NULL;
                    $price = NULL;
                    $watType = NULL;
                    $availability = 'Available';
                    
                    // delete someone in schedule
                    $sqlUpdateSched = "DELETE FROM schedule WHERE id = ?";
                    $stmtUpdateSched = $this->con()->prepare($sqlUpdateSched);
                    $stmtUpdateSched->execute([$id]);

                    // delete in leave request
                    $sqlUpdateLeave = "DELETE FROM leave_request WHERE empId = ?";
                    $stmtUpdateLeave = $this->con()->prepare($sqlUpdateLeave);
                    $stmtUpdateLeave->execute([$empId]);

                    // remove position, price, type and availability 
                    $sqlUpdateEmp = "UPDATE employee
                                     SET position = ?,
                                         ratesperDay = ?,
                                         watType = ?,
                                         availability = ?
                                     WHERE empId = ?"; 
                    $stmtUpdateEmp = $this->con()->prepare($sqlUpdateEmp);
                    $stmtUpdateEmp->execute([$position, $price, $watType, $availability, $empId]);

                    // get current number of guards in company table
                    $sqlCompany = "SELECT * FROM company WHERE company_name = ?";
                    $stmtCompany = $this->con()->prepare($sqlCompany);
                    $stmtCompany->execute([$company]);
                    $userCompany = $stmtCompany->fetch();
                    $countRowCompany = $stmtCompany->rowCount();
                    $hiredGuards = 0;
                    $intHiredGuards = intval($userCompany->hired_guards);

                    if($countRowCompany > 0){

                        $hiredGuards = intval($intHiredGuards) - 1;

                        // decrease 1 in hiredguards inside company table
                        $sqlUpdateComp = "UPDATE company 
                                          SET hired_guards = ?
                                          WHERE company_name = ?";
                        $stmtUpdateComp = $this->con()->prepare($sqlUpdateComp);
                        $stmtUpdateComp->execute([$hiredGuards, $company]);
                    }
                }
                
            }
        }
    }








    // blabla
    public function addNewSelectedGuard()
    {
        $sql = "SELECT * FROM employee WHERE availability = 'Available'";
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
                      <td><input type='hidden' name='email$i' value='$user->email'/>$user->email</td>
                      <td>$date</td>
                      <td>
                          <span data-deleteId='$rowId' onclick='removeMe(this)'class='material-icons'>delete</span>
                      </td>
                  </tr>";
        }
    }



    public function sendEmailForEmployee($email, $empId, $company, $expdate)
    {

        $sqlEmployee = "SELECT * FROM employee WHERE empId = ?";
        $stmtEmployee = $this->con()->prepare($sqlEmployee);
        $stmtEmployee->execute([$empId]);
        $userEmployee = $stmtEmployee->fetch();
            
        $empPosition = $userEmployee->position;
        $empPrice = $userEmployee->ratesperDay;

        if($empPosition == 'Officer in Charge'){
            $sqlCompany = "SELECT * FROM company WHERE company_name = ?";
            $stmtCompany = $this->con()->prepare($sqlCompany);
            $stmtCompany->execute([$company]);
            $userCompany = $stmtCompany->fetch();
            $countRowCompany = $stmtCompany->rowCount();

            if($countRowCompany > 0){
                $empLocation = $userCompany->comp_location;
                $empShiftSpan = $userCompany->shift_span;

                $empShift = $userCompany->shifts;
                $empDayStart = "";
                $empDayEnd = "";

                if($empShift == 'night'){
                    $empDayStart = date("h:i a", strtotime($userCompany->day_start." +".$userCompany->shift_span." hours"));
                    $empDayEnd = date("h:i a", strtotime($empDayStart." +".$userCompany->shift_span." hours"));
                } else {
                    $empDayStart = date("h:i a", strtotime($userCompany->day_start));
                    $empDayEnd = date("h:i a", strtotime($userCompany->day_start." +".$userCompany->shift_span." hours"));
                }


                $name = 'JTDV Incorporation';
                $subject = 'subject kunwari';
                $body = "Congratulations! You have been assigned to $company. The company located at $empLocation. <br/>
                         Shift type: $empShift <br/>
                         Your schedule: $empDayStart - $empDayEnd <br/>
                         Position: $empPosition <br/>
                         Rate per hour: $empPrice <br/>
                         Contract: $expdate
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


            } else {
                echo 'Your company is not exist';
            }
        } else {
            // not officer in charge

            $sqlCompany = "SELECT * FROM company WHERE company_name = ?";
            $stmtCompany = $this->con()->prepare($sqlCompany);
            $stmtCompany->execute([$company]);
            $userCompany = $stmtCompany->fetch();
            $countRowCompany = $stmtCompany->rowCount();

            if($countRowCompany > 0){
                $empLocation = $userCompany->comp_location;
                

                $name = 'JTDV Incorporation';
                $subject = 'subject kunwari';
                $body = "Congratulations! You have been assigned to $company. The company located at $empLocation. <br/>
                         Position: $empPosition <br/>
                         Rate per hour: $empPrice <br/>
                         Contract: $expdate
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


            } else {
                echo 'Your company is not exist';
            }
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
                $year = $_POST['year'];
                
                if(isset($_POST['month']) && isset($_POST['day'])){
                    $month = $_POST['month'];
                    $day = $_POST['day'];
                } else {
                    $month = 0;
                    $day = 0;
                }

                // date now - input fields
                $expiration_date = date('Y-m-d', strtotime("+$year years $month months $day days"));

                $availability = "Unavailable";

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
                    $email = $_POST["email$i"];

                    if($position == 'Officer in Charge'){
                        $companyType = $userCompany->watType;

                        // kapag manual la ka na sched
                        if($companyType == 'manual'){
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

                                    $intUsersHR = intval($usersHR->hired_guards);
                                    
                                    if($countRowHR > 0){
                                        if($intUsersHR == 0 || 
                                           $intUsersHR == NULL ||
                                           $intUsersHR == 'NULL' ||
                                           $intUsersHR == ''
                                        ){
                                           $hiredGuards = intval($intUsersHR) + 1;
                                           echo "mabas if ".$hiredGuards;
                                        } else {
                                            $hiredGuards = intval($intUsersHR) + 1;
                                            echo "mabas else ".$hiredGuards;
                                        }
                                    }

                                    

                                    $sqlHiredGuards = "UPDATE company SET hired_guards = ? WHERE company_name = ?";
                                    $stmtHiredGuards = $this->con()->prepare($sqlHiredGuards);
                                    $stmtHiredGuards->execute([$hiredGuards, $company]);
                                    $countRowHiredGuards = $stmtHiredGuards->rowCount();

                                    if($countRowHiredGuards > 0){
                                        $this->sendEmailForEmployee($email, $empId, $company, $expdate);
                                    }


                                    // echo "<script>window.location.assign('showEmployees.php');</script>";
                                }
                            }
                        } else {
                            // pag di ka manual meron ka sched
                            $companyShift = $userCompany->shifts;
                            $companyShiftSpan = $userCompany->shift_span;
                            $companyStart = "";

                            if($companyShift == 'night'){
                                $companyStart = date("h:i a", strtotime($userCompany->day_start." +".$companyShiftSpan." hours"));
                            } else {
                                $companyStart = $userCompany->day_start;
                            }

                            $companyEnd = date("h:i a", strtotime($companyStart." +".$companyShiftSpan." hours"));

                            $sql = "INSERT INTO schedule(empId, 
                                                         company, 
                                                         scheduleTimeIn, 
                                                         scheduleTimeOut, 
                                                         shift,
                                                         shift_span,
                                                         expiration_date
                                                        )
                                    VALUES(?, ?, ?, ?, ?, ?, ?)";
                            $stmt = $this->con()->prepare($sql);
                            $stmt->execute([$empId, 
                                            $company, 
                                            $companyStart,
                                            $companyEnd,
                                            $companyShift,
                                            $companyShiftSpan,
                                            $expiration_date
                                           ]);
                            $countRow = $stmt->rowCount();

                            if($countRow > 0){
                                echo 'masok na';
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

                                    $intUsersHR = intval($usersHR->hired_guards);
                                    
                                    if($countRowHR > 0){
                                        if($intUsersHR == 0 || 
                                           $intUsersHR == '0' || 
                                           $intUsersHR == NULL ||
                                           $intUsersHR == 'NULL' ||
                                           $intUsersHR == ''
                                        ){
                                            $hiredGuards = intval($intUsersHR) + 1;
                                            echo "mabas if ".$hiredGuards;
                                        } else {
                                            $hiredGuards = intval($intUsersHR) + 1;
                                            echo "mabas else ".$hiredGuards;
                                        }
                                    }

                                    

                                    $sqlHiredGuards = "UPDATE company SET hired_guards = ? WHERE company_name = ?";
                                    $stmtHiredGuards = $this->con()->prepare($sqlHiredGuards);
                                    $stmtHiredGuards->execute([$hiredGuards, $company]);
                                    $countRowHiredGuards = $stmtHiredGuards->rowCount();

                                    if($countRowHiredGuards > 0){
                                        $this->sendEmailForEmployee($email, $empId, $company, $expiration_date);
                                    }

                                    // echo "<script>window.location.assign('showEmployees.php');</script>";
                                }
                            } else {
                                echo 'di pa masok';
                            }
                        }

                    } else {
                        // if not equal to officer in charge do not set schedule
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

                                $intUsersHR = intval($usersHR->hired_guards);
                                    
                                    if($countRowHR > 0){
                                        if($intUsersHR == 0 || 
                                           $intUsersHR == NULL ||
                                           $intUsersHR == 'NULL' ||
                                           $intUsersHR == ''
                                        ){
                                           $hiredGuards = intval($intUsersHR) + 1;
                                           echo "mabas if ELSE ".$hiredGuards;
                                        } else {
                                            $hiredGuards = intval($intUsersHR) + 1;
                                            echo "mabas else ELSE ".$hiredGuards;
                                        }
                                    }

                                

                                $sqlHiredGuards = "UPDATE company SET hired_guards = ? WHERE company_name = ?";
                                $stmtHiredGuards = $this->con()->prepare($sqlHiredGuards);
                                $stmtHiredGuards->execute([$hiredGuards, $company]);
                                $countRowHiredGuards = $stmtHiredGuards->rowCount();

                                if($countRowHiredGuards > 0){
                                    $this->sendEmailForEmployee($email, $empId, $company, $expiration_date);
                                }

                                // echo "<script>window.location.assign('showEmployees.php');</script>";
                            }
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
            $number = $_POST['number'];

            if(empty($firstname) &&
               empty($lastname) &&
               empty($number) &&
               empty($address) &&
               empty($email)
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
                        echo '<br/>Data was updated successfully.';
                    } else {
                        echo '<br/>No data was updated. There was something wrong in our codes';
                    }
                }
                    
            }
        }
    }


    public function deleteEmployee($id){
        if(isset($_POST['deleteemployee'])){

            $sqlFind = "SELECT * FROM employee WHERE id = ?";
            $stmt = $this->con()->prepare($sqlFind);
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            $countRow = $stmt->rowCount();

            if($countRow > 0){
                $empEmail = $user->email;

                $sqlDiary = "DELETE FROM secret_diarye WHERE e_id = ?";
                $stmtDiary = $this->con()->prepare($sqlDiary);
                $stmtDiary->execute([$empEmail]);
                $countRowDiary = $stmtDiary->rowCount();

                if($countRowDiary > 0){
                    $sqlEmployee = "DELETE FROM employee WHERE id = ?";
                    $stmtEmployee = $this->con()->prepare($sqlEmployee);
                    $stmtEmployee->execute([$id]);
                    $countRowEmployee = $stmtEmployee->rowCount();
                    if($countRowEmployee > 0){
                        echo 'nadelete na employee';
                    } else {
                        echo 'di pa nadelete employee';
                    }
                    echo "nadelete na secret diary";
                } else {
                    echo 'di pa nadelete secret diary';
                }
            }


            // $sql = "DELETE FROM employee WHERE id = ?";
            // $stmt = $this->con()->prepare($sql);
            // $stmt->execute([$id]);
            // $countRow = $stmt->rowCount();

            // if($countRow > 0){
            //     echo 'Data was successfully deleted';
            // } else {
            //     echo 'There was something wrong in our code';
            // }
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


    // add company modal
    // company
    public function addCompany2()
    {
        if(isset($_POST['addcompany2'])){
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
                        <a href='company.php?id=$row->id&act=view'>
                            <span class='material-icons'>visibility</span>
                        </a>
                        <a href='company.php?id=$row->id&act=edit'>
                            <span class='material-icons'>edit</span>
                        </a>
                        <a href='company.php?id=$row->id&act=delete'>
                            <span class='material-icons'>delete</span>
                        </a>
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

                // if employee position exists in company positions

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
                    position0_m.disabled = true;

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


                        // for distance
                        const map_b2 = new mapboxgl.Map({
                            container: 'map_b_m',
                            style: 'mapbox://styles/mapbox/satellite-streets-v9',
                            center: [$longitude, $latitude],
                            zoom: 18
                        });


                        const distanceContainer2 = document.getElementById('distance_m');
                        const map_b_size2 = document.querySelector('.map_b_size_m');

                        // GeoJSON object to hold our measurement features
                        const geojson = {
                            'type': 'FeatureCollection',
                            'features': []
                        };

                        // Used to draw a line between points
                        const linestring = {
                            'type': 'Feature',
                            'geometry': {
                                'type': 'LineString',
                                'coordinates': []
                            }
                        };

                        map_b2.on('load', () => {
                            map_b2.addSource('geojson', {
                                'type': 'geojson',
                                'data': geojson
                            });
            
                            // Add styles to the map
                            map_b2.addLayer({
                                id: 'measure-points',
                                type: 'circle',
                                source: 'geojson',
                                paint: {
                                    'circle-radius': 5,
                                    'circle-color': '#000'
                                },
                                filter: ['in', '\$type', 'Point']
                            });
            
                            map_b2.addLayer({
                                id: 'measure-lines',
                                type: 'line',
                                source: 'geojson',
                                layout: {
                                    'line-cap': 'round',
                                    'line-join': 'round'
                                },
                                paint: {
                                    'line-color': '#000',
                                    'line-width': 2.5
                                },
                                filter: ['in', '\$type', 'LineString']
                            });
            
                            map_b2.on('click', (e) => {
                                const features = map_b2.queryRenderedFeatures(e.point, {
                                    layers: ['measure-points']
                                });
            
                                // Remove the linestring from the group
                                // so we can redraw it based on the points collection.
                                if (geojson.features.length > 1) geojson.features.pop();
            
                                // Clear the distance container to populate it with a new value.
                                distanceContainer2.innerHTML = '';
            
                                // If a feature was clicked, remove it from the map.
                                if (features.length) {
                                    const id = features[0].properties.id;
                                    geojson.features = geojson.features.filter(
                                        (point) => point.properties.id !== id
                                    );
                                } else {
                                    const point = {
                                        'type': 'Feature',
                                        'geometry': {
                                            'type': 'Point',
                                            'coordinates': [e.lngLat.lng, e.lngLat.lat]
                                        },
                                        'properties': {
                                            'id': String(new Date().getTime())
                                        }
                                    };
            
                                    geojson.features.push(point);
                                }
            
                                if (geojson.features.length > 1) {
                                    linestring.geometry.coordinates = geojson.features.map(
                                        (point) => point.geometry.coordinates
                                    );
            
                                    geojson.features.push(linestring);
            
                                    // Populate the distanceContainer with total distance
                                    const value2 = document.createElement('pre');
                                    const distance2 = turf.length(linestring);
                                    value2.textContent = `Total distance: \${distance2.toLocaleString()}km`;
            
                                    distanceContainer2.appendChild(value2);
                                    map_b_size2.value = `\${distance2.toLocaleString()}km`;
                                }
            
                                map_b2.getSource('geojson').setData(geojson);
                            });
                        });
            
                        // for distance
                        map_b2.on('mousemove', (e) => {
                            const features = map_b2.queryRenderedFeatures(e.point, {
                                layers: ['measure-points']
                            });
                            // Change the cursor to a pointer when hovering over a point on the map.
                            // Otherwise cursor is a crosshair.
                            map_b2.getCanvas().style.cursor = features.length
                                ? 'pointer'
                                : 'crosshair';
                        });

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
                    position0_m.disabled = true;

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

                        // for distance
                        const map_b2 = new mapboxgl.Map({
                            container: 'map_b_m',
                            style: 'mapbox://styles/mapbox/satellite-streets-v9',
                            center: [$longitude, $latitude],
                            zoom: 18
                        });


                        const distanceContainer2 = document.getElementById('distance_m');
                        const map_b_size2 = document.querySelector('.map_b_size_m');

                        // GeoJSON object to hold our measurement features
                        const geojson = {
                            'type': 'FeatureCollection',
                            'features': []
                        };

                        // Used to draw a line between points
                        const linestring = {
                            'type': 'Feature',
                            'geometry': {
                                'type': 'LineString',
                                'coordinates': []
                            }
                        };

                        map_b2.on('load', () => {
                            map_b2.addSource('geojson', {
                                'type': 'geojson',
                                'data': geojson
                            });
            
                            // Add styles to the map
                            map_b2.addLayer({
                                id: 'measure-points',
                                type: 'circle',
                                source: 'geojson',
                                paint: {
                                    'circle-radius': 5,
                                    'circle-color': '#000'
                                },
                                filter: ['in', '\$type', 'Point']
                            });
            
                            map_b2.addLayer({
                                id: 'measure-lines',
                                type: 'line',
                                source: 'geojson',
                                layout: {
                                    'line-cap': 'round',
                                    'line-join': 'round'
                                },
                                paint: {
                                    'line-color': '#000',
                                    'line-width': 2.5
                                },
                                filter: ['in', '\$type', 'LineString']
                            });
            
                            map_b2.on('click', (e) => {
                                const features = map_b2.queryRenderedFeatures(e.point, {
                                    layers: ['measure-points']
                                });
            
                                // Remove the linestring from the group
                                // so we can redraw it based on the points collection.
                                if (geojson.features.length > 1) geojson.features.pop();
            
                                // Clear the distance container to populate it with a new value.
                                distanceContainer2.innerHTML = '';
            
                                // If a feature was clicked, remove it from the map.
                                if (features.length) {
                                    const id = features[0].properties.id;
                                    geojson.features = geojson.features.filter(
                                        (point) => point.properties.id !== id
                                    );
                                } else {
                                    const point = {
                                        'type': 'Feature',
                                        'geometry': {
                                            'type': 'Point',
                                            'coordinates': [e.lngLat.lng, e.lngLat.lat]
                                        },
                                        'properties': {
                                            'id': String(new Date().getTime())
                                        }
                                    };
            
                                    geojson.features.push(point);
                                }
            
                                if (geojson.features.length > 1) {
                                    linestring.geometry.coordinates = geojson.features.map(
                                        (point) => point.geometry.coordinates
                                    );
            
                                    geojson.features.push(linestring);
            
                                    // Populate the distanceContainer with total distance
                                    const value2 = document.createElement('pre');
                                    const distance2 = turf.length(linestring);
                                    value2.textContent = `Total distance: \${distance2.toLocaleString()}km`;
            
                                    distanceContainer2.appendChild(value2);
                                    map_b_size2.value = `\${distance2.toLocaleString()}km`;
                                }
            
                                map_b2.getSource('geojson').setData(geojson);
                            });
                        });
            
                        // for distance
                        map_b2.on('mousemove', (e) => {
                            const features = map_b2.queryRenderedFeatures(e.point, {
                                layers: ['measure-points']
                            });
                            // Change the cursor to a pointer when hovering over a point on the map.
                            // Otherwise cursor is a crosshair.
                            map_b2.getCanvas().style.cursor = features.length
                                ? 'pointer'
                                : 'crosshair';
                        });

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

            // update table data, bawal edit yung dropdown
            if(isset($_POST['editcompany']) && $editDropdown == false){
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
                    
                    $storeMeSome = 0;

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
                            $storeMeSome++;
                        } 
                    }

                    // inform employees here
                    $sqlInformEmloyees = "SELECT
                                                s.empId,
                                                s.company,
                                                e.empId,
                                                e.email as empEmail,
                                                c.*,
                                                c.company_name,
                                                c.cpnumber,
                                                c.email,
                                                c.comp_location,
                                                c.longitude,
                                                c.latitude,
                                                c.boundary_size
                                          FROM schedule s
                                          INNER JOIN employee e
                                          ON s.empId = e.empId 
                                          
                                          INNER JOIN company c
                                          ON s.company = c.company_name
                                          
                                          WHERE c.id = $id";
                    $stmtInformEmployees = $this->con()->query($sqlInformEmloyees);
                    while($usersInformEmployees = $stmtInformEmployees->fetch(PDO::FETCH_ASSOC)){

                        $secLoopLength = intval($storeMeSome) + intval($lengthInputOriginal);
                        $informPositions = array();
                        $informPrices = array();

                        for($j = 0; $j <= $secLoopLength; $j++){
                            
                            // habang may nadedetect push lang ng push
                            if(isset($usersInformEmployees["position$j"])){
                                // kapag may laman
                                if($usersInformEmployees["position$j"] != NULL ||
                                    $usersInformEmployees["position$j"] != ''
                                ){
                                    // position
                                    $currPosition = $usersInformEmployees["position$j"];
                                    array_push($informPositions, $currPosition);

                                    // price
                                    $currPrice = $usersInformEmployees["price$j"];
                                    array_push($informPrices, $currPrice);
                                } else {
                                    break;
                                }
                            } else {
                                break;
                            }
                        }

                        $empEmail = $usersInformEmployees["empEmail"];
                        // company info
                        $eCompanyName = $usersInformEmployees["company_name"];
                        $eCpNumber = $usersInformEmployees["cpnumber"];
                        $eEmail = $usersInformEmployees["email"];
                        $eCompLocation = $usersInformEmployees["comp_location"];
                        $eLongitude = $usersInformEmployees["longitude"];
                        $eLatitude = $usersInformEmployees["latitude"];
                        $eBoundarySize = $usersInformEmployees["boundary_size"];
                        
                        $this->informEmployeeInComp($empEmail, $eCompanyName, $eCpNumber, $eEmail, $eCompLocation, $eLongitude, $eLatitude, $eBoundarySize, $informPositions, $informPrices);
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
            



            // no employee found in company, pwede edit dropdown
            if(isset($_POST['editcompany']) && $editDropdown == true){
                
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

    
    public function informEmployeeInComp($email, $eCompanyName, 
                                                 $eCpNumber,
                                                 $eEmail,
                                                 $eCompLocation,
                                                 $eLongitude,
                                                 $eLatitude,
                                                 $eBoundarySize,
                                                 $posArray,
                                                 $priArray
    )
    {


        $posWithPri = "";

        for($i = 0; $i < sizeof($posArray); $i++){
            $posWithPri .= $posArray[$i] . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Rate: " . $priArray[$i] . "<br/>";
        }

        $name = 'JTDV Incorporation';
        $subject = 'subject kunwari';
        $body = "Company Details has been updated. <br/>
                 <br/>
                 Company Name: $eCompanyName <br/>
                 Contact Number: $eCpNumber <br/>
                 Company Email: $eEmail <br/>
                 Company Location: $eCompLocation <br/>
                 Longitude: $eLongitude <br/>
                 Latitude: $eLatitude <br/>
                 Boundary: $eBoundarySize <br/>
                 <br/>
                 <h4>Positions</h4>
                 $posWithPri
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



    public function recentactivityleave()
    {
        $sql = "SELECT 
                        l.*,
                        l.date_admin,
                        e.empId,
                        e.firstname as firstname,
                        e.lastname as lastname
                FROM leave_request l
                INNER JOIN employee e
                ON l.empId = e.empId
                WHERE 
                    status != 'pending' 
                AND 
                    date_admin BETWEEN date_sub(curdate(),interval 30 day) AND curdate()";
        $stmt = $this->con()->query($sql);
        while($row = $stmt->fetch()){
            $fullname = $row->lastname . ", " . $row->firstname;

            echo "<div>
                    <div class'headline'>
                        <div class='title'>
                            <div class='$row->status'></div>
                            <h2>$row->status</h2>
                        </div>
                        <form method='post' class='removeRecent'>
                            <input type='text' name='removeDate' value='$row->date_admin' required/>
                            <input type='text' name='removeId' value='$row->id' required/>
                            <button type='submit' name='removeRecentBtn'>X</button>
                        </form>
                    </div>
                    <div class='content'>
                        <h3>$fullname</h3>
                        <p>$row->date_admin</p>
                    </div>
                  </div>";
        }
    }

    public function removeRecentFunction()
    {
        if(isset($_POST['removeRecentBtn'])){
            $removeId = $_POST['removeId'];
            $removeDate = $_POST['removeDate'];

            $sql = "UPDATE leave_request 
                    SET date_admin = date_sub(?, interval 31 day)
                    WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute([$removeDate, $removeId]);
            $countRow = $stmt->rowCount();

            if($countRow > 0){
                echo 'nag update na yung date';
            } else {
                echo 'di pa nag update yung date';
            }
        }
    }


    public function listofleaverequest()
    {
        $sql = "SELECT 
                        l.*, 
                        l.id as id,
                        e.empId,
                        e.firstname as firstname,  
                        e.lastname as lastname
                FROM leave_request l
                INNER JOIN employee e
                ON l.empId = e.empId
                WHERE status = 'pending'";
        $stmt = $this->con()->query($sql);
        while($row = $stmt->fetch()){
            $fullname = $row->firstname ." ". $row->lastname;
            echo "<tr>
                    <td>$fullname</td>
                    <td>$row->typeOfLeave</td>
                    <td>$row->reason</td>
                    <td>$row->days</td>
                    <td>$row->leave_start</td>
                    <td>$row->leave_end</td>
                    <td>
                        <a href='leave.php?id=$row->id&act=viewing'>View</a>
                        <a href='leave.php?id=$row->id&act=approve'>Approve</a>
                        <a href='leave.php?id=$row->id&act=reject'>Reject</a>
                    </td>
                  </tr>";
        }
    }



    public function listoffreeguard()
    {
        $sql = "SELECT * FROM employee WHERE availability = 'Available'";
        $stmt = $this->con()->query($sql);
        while($row = $stmt->fetch()){
            $addressArr = explode(' ', $row->address);
            $fullname = $row->firstname ." ". $row->lastname;
            if(in_array('City', $addressArr)){
                $cityIndex = array_search('City', $addressArr);
                $cityName = $cityIndex - 1;
                $filteredAdd = $addressArr[$cityName] . " City";
                echo "<option value='$row->empId'>$fullname($filteredAdd)</option>";
            }

            
        }
    }


    public function viewRequest($id)
    {
        $sql = "SELECT * FROM leave_request WHERE id = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        $countRow = $stmt->rowCount();

        if($countRow > 0){
            $empId = $user->empId;

            $sqlFind = "SELECT * FROM employee WHERE empId = ?";
            $stmtFind = $this->con()->prepare($sqlFind);
            $stmtFind->execute([$empId]);
            $userFind = $stmtFind->fetch();
            $countRowFind = $stmtFind->rowCount();

            if($countRowFind > 0){
                $fullname = $userFind->firstname." ".$userFind->lastname;
                $email = $userFind->email;
                $days = $user->days;
                $leave_start = $user->leave_start;
                $leave_end = $user->leave_end;
                $type = $user->typeOfLeave;
                $reason = $user->reason;

                echo "<script>
                        let requestId = document.querySelector('#requestId');
                        let fullname = document.querySelector('#fullname');
                        let email = document.querySelector('#email');
                        let daysleave = document.querySelector('#daysleave');
                        let leave_start = document.querySelector('#leave_start');
                        let leave_end = document.querySelector('#leave_end');
                        let type = document.querySelector('#type');
                        let reason = document.querySelector('#reason');

                        requestId.value = '$id';

                        fullname.value = '$fullname';
                        fullname.setAttribute('readonly', 'readonly');
                        email.value = '$email';
                        email.setAttribute('readonly', 'readonly');

                        let option = document.createElement('option');
                        option.value = '$days';
                        option.innerText = '$days';

                        daysleave.appendChild(option);

                        daysleave.value = '$days';
                        daysleave.setAttribute('readonly', 'readonly');


                        leave_start.value = '$leave_start';
                        leave_start.setAttribute('readonly', 'readonly');
                        leave_end.value = '$leave_end';
                        leave_end.setAttribute('readonly', 'readonly');

                        type.value = '$type';
                        type.setAttribute('readonly', 'readonly');

                        reason.value = '$reason';
                        reason.setAttribute('readonly', 'readonly');

                      </script>";
            }

        }
    }

    public function viewApproveReject()
    {
        

        if(isset($_POST['approveRequest'])){
            $id = $_POST['requestId'];

            $sqlFind = "SELECT 
                                l.*,
                                l.leave_start as leaveStart,
                                l.leave_end as leaveEnd,
                                l.empId as leaveEmpId,
                                s.empId as empId,
                                s.company as company,
                                s.scheduleTimeIn as timein,
                                s.scheduleTimeOut as timeout,
                                s.shift as shift,
                                s.shift_span as shift_span,
                                s.expiration_date as expdate,

                                e.position as position,
                                e.ratesperDay as price,
                                e.watType as watType,

                                c.comp_location as c_address
                        FROM leave_request l
                        INNER JOIN schedule s
                        ON l.empId = s.empId

                        INNER JOIN employee e
                        ON l.empId = e.empId

                        INNER JOIN company c
                        ON s.company = c.company_name 
                        WHERE l.id = ?";
            $stmtFind = $this->con()->prepare($sqlFind);
            $stmtFind->execute([$id]);
            $userFind = $stmtFind->fetch();
            $countRowFind = $stmtFind->rowCount();

            if($countRowFind > 0){

                $status = 'approved';
                $substiEmpId = $_POST['substitute'];
                $expDateNew = $userFind->leaveEnd;

                $substiPosition = $userFind->position;
                $substiPrice = $userFind->price;
                $substiType = $userFind->watType;

                $availability = 'Unavailable';

                // set timezone and get date and time
                $datetime = $this->getDateTime();
                $date = $datetime['date'];


                $sqlSubstiUpdate = "UPDATE employee 
                                    SET position = ?,
                                        ratesperDay = ?,
                                        watType = ?,
                                        availability = ?
                                    WHERE empId = ?";

                $stmtSubstiUpdate = $this->con()->prepare($sqlSubstiUpdate);
                $stmtSubstiUpdate->execute([$substiPosition, $substiPrice, $substiType, $availability, $substiEmpId]);
                $countRowSubstiUpdate = $stmtSubstiUpdate->rowCount();
                if($countRowSubstiUpdate > 0){
                    echo 'nag update na si employee';

                    $sql = "UPDATE leave_request
                            SET substitute_by = ?,
                                status = ?,
                                date_admin = ?
                            WHERE id = ?
                            ";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$substiEmpId, $status, $date, $id]);
                    $countRow = $stmt->rowCount();

                    if($countRow > 0){
                        echo 'nag update na si leave_request';
                        // to add new schedule
                        $companySched = $userFind->company;
                        $timeinSched = $userFind->timein;
                        $timeoutSched = $userFind->timeout;
                        $shiftSched = $userFind->shift;
                        $shiftSpanSched = $userFind->shift_span;

                        $sqlSched = "INSERT INTO schedule(empId, company, scheduleTimeIn, scheduleTimeOut, shift, shift_span, expiration_date)
                                     VALUES(?, ?, ?, ?, ?, ?, ?)
                                    ";
                        $stmtSched = $this->con()->prepare($sqlSched);
                        $stmtSched->execute([$substiEmpId, $companySched, $timeinSched, $timeoutSched, $shiftSched, $shiftSpanSched, $expDateNew]);
                        $countRowSched = $stmtSched->rowCount();
                        if($countRowSched > 0){
                            echo 'nakapag add na sa schedule';

                            $leaveEmpId = $userFind->leaveEmpId;
                            $sqlDelSched = "UPDATE schedule
                                            SET scheduleTimeIn = ?,
                                                scheduleTimeOut = ?,
                                                shift = ?,
                                                shift_span = ?
                                            WHERE empId = ?";
                            $stmtDelSched = $this->con()->prepare($sqlDelSched);
                            $stmtDelSched->execute([NULL, NULL, NULL, NULL, $leaveEmpId]);
                            $countRowDelSched = $stmtDelSched->rowCount();
                            if($countRowDelSched > 0){
                                echo 'nadelete na sa sched';
                                
                                $comp_address = $userFind->c_address;
                                $leaveStart = $userFind->leaveStart;

                                // get email of substitute guard
                                $sqlFindSubsti = "SELECT * FROM employee WHERE empId = ?";
                                $stmtFindSubsti = $this->con()->prepare($sqlFindSubsti);
                                $stmtFindSubsti->execute([$substiEmpId]);
                                $userFindSubsti = $stmtFindSubsti->fetch();
                                $countRowFindSubsti = $stmtFindSubsti->rowCount();

                                if($countRowFindSubsti > 0){
                                    // inform substitute guard
                                    $this->informSubstitute($userFindSubsti->email, 
                                                        $companySched, 
                                                        $comp_address, 
                                                        $timeinSched,
                                                        $timeoutSched,
                                                        $shiftSched,
                                                        $shiftSpanSched,
                                                        $leaveStart,
                                                        $expDateNew,
                                                        $substiPosition,
                                                        $substiPrice
                                                        );
                                } else {
                                    echo 'no available guard found';
                                }
                            } else {
                                echo 'di pa nadelete sa sched';
                            }
                        } else {
                            echo 'di pa nakapag add sa schedule';
                        }
                    }
                }
            }
        }

        if(isset($_POST['rejectRequest'])){
            $id = $_GET['id'];
            $email = $_POST['email'];
            $days = $_POST['days'];
            $leave_start = $_POST['leave_start'];
            $leave_end = $_POST['leave_end'];
            $reason = $_POST['reason'];

            // set timezone and get date and time
            $datetime = $this->getDateTime();
            $date = $datetime['date'];

            $sql = "UPDATE leave_request
                    SET status = ?,
                        date_admin = ?
                    WHERE id = ?";
            $stmt = $this->con()->prepare($sql);
            $stmt->execute(['rejected', $date, $id]);
            $countRow = $stmt->rowCount();

            if($countRow > 0){
                echo 'rejected';


                $name = 'JTDV Incorporation';
                $subject = 'subject kunwari';
                $body = "Your request has been rejected. <br/>
                        <br/>
                        Days: $days <br/>
                        From: $leave_start to $leave_end <br/>
                        Reason: $reason
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
            } else {
                echo 'di rejected haha';
            }
        }
    }


    public function informSubstitute($email, 
                                     $company, 
                                     $comp_address, 
                                     $timeinSched,
                                     $timeoutSched,
                                     $shiftSched,
                                     $shiftSpanSched,
                                     $leaveStart,
                                     $expDateNew,
                                     $substiPosition,
                                     $substiPrice)
    {

        $name = 'JTDV Incorporation';
        $subject = 'subject kunwari';
        $body = "You have been assigned as a substitute for $company. Located at $comp_address <br/>
                 <br/>
                 <h4>Starting at $leaveStart you may start working on us.</h4> <br/>
                 Shift: $shiftSched <br/>
                 Total hours per day: $shiftSpanSched <br/> 
                 Schedule: $timeinSched to $timeoutSched <br/>
                 Position: $substiPosition <br/>
                 Rate per hour: $substiPrice <br/>
                 <br/>
                 End of Contract: $expDateNew <br/>

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

    public function approveRequest($id)
    {

        echo "<script>
                let appABtn = document.querySelector('#approvebtn');
                appABtn.style.display = 'block';

                let appRBtn = document.querySelector('#rejectbtn');
                appRBtn.style.display = 'none';
              </script>";

        if(isset($_POST['approveRequest'])){
            $id = $_POST['requestId'];

            $sqlFind = "SELECT 
                                l.*,
                                l.leave_start as leaveStart,
                                l.leave_end as leaveEnd,
                                l.empId as leaveEmpId,
                                s.empId as empId,
                                s.company as company,
                                s.scheduleTimeIn as timein,
                                s.scheduleTimeOut as timeout,
                                s.shift as shift,
                                s.shift_span as shift_span,
                                s.expiration_date as expdate,

                                e.position as position,
                                e.ratesperDay as price,
                                e.watType as watType,

                                c.comp_location as c_address
                        FROM leave_request l
                        INNER JOIN schedule s
                        ON l.empId = s.empId

                        INNER JOIN employee e
                        ON l.empId = e.empId

                        INNER JOIN company c
                        ON s.company = c.company_name 
                        WHERE l.id = ?";
            $stmtFind = $this->con()->prepare($sqlFind);
            $stmtFind->execute([$id]);
            $userFind = $stmtFind->fetch();
            $countRowFind = $stmtFind->rowCount();

            if($countRowFind > 0){

                $status = 'approved';
                $substiEmpId = $_POST['substitute'];
                $expDateNew = $userFind->leaveEnd;

                $substiPosition = $userFind->position;
                $substiPrice = $userFind->price;
                $substiType = $userFind->watType;

                $availability = 'Unavailable';

                // set timezone and get date and time
                $datetime = $this->getDateTime();
                $date = $datetime['date'];


                $sqlSubstiUpdate = "UPDATE employee 
                                    SET position = ?,
                                        ratesperDay = ?,
                                        watType = ?,
                                        availability = ?
                                    WHERE empId = ?";

                $stmtSubstiUpdate = $this->con()->prepare($sqlSubstiUpdate);
                $stmtSubstiUpdate->execute([$substiPosition, $substiPrice, $substiType, $availability, $substiEmpId]);
                $countRowSubstiUpdate = $stmtSubstiUpdate->rowCount();
                if($countRowSubstiUpdate > 0){
                    echo 'nag update na si employee';

                    $sql = "UPDATE leave_request
                            SET substitute_by = ?,
                                status = ?,
                                date_admin = ?
                            WHERE id = ?
                            ";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$substiEmpId, $status, $date, $id]);
                    $countRow = $stmt->rowCount();

                    if($countRow > 0){
                        echo 'nag update na si leave_request';
                        // to add new schedule
                        $companySched = $userFind->company;
                        $timeinSched = $userFind->timein;
                        $timeoutSched = $userFind->timeout;
                        $shiftSched = $userFind->shift;
                        $shiftSpanSched = $userFind->shift_span;

                        $sqlSched = "INSERT INTO schedule(empId, company, scheduleTimeIn, scheduleTimeOut, shift, shift_span, expiration_date)
                                     VALUES(?, ?, ?, ?, ?, ?, ?)
                                    ";
                        $stmtSched = $this->con()->prepare($sqlSched);
                        $stmtSched->execute([$substiEmpId, $companySched, $timeinSched, $timeoutSched, $shiftSched, $shiftSpanSched, $expDateNew]);
                        $countRowSched = $stmtSched->rowCount();
                        if($countRowSched > 0){
                            echo 'nakapag add na sa schedule';

                            $leaveEmpId = $userFind->leaveEmpId;
                            $sqlDelSched = "UPDATE schedule
                                            SET scheduleTimeIn = ?,
                                                scheduleTimeOut = ?,
                                                shift = ?,
                                                shift_span = ?
                                            WHERE empId = ?";
                            $stmtDelSched = $this->con()->prepare($sqlDelSched);
                            $stmtDelSched->execute([NULL, NULL, NULL, NULL, $leaveEmpId]);
                            $countRowDelSched = $stmtDelSched->rowCount();
                            if($countRowDelSched > 0){
                                echo 'nadelete na sa sched';
                                
                                $comp_address = $userFind->c_address;
                                $leaveStart = $userFind->leaveStart;

                                // get email of substitute guard
                                $sqlFindSubsti = "SELECT * FROM employee WHERE empId = ?";
                                $stmtFindSubsti = $this->con()->prepare($sqlFindSubsti);
                                $stmtFindSubsti->execute([$substiEmpId]);
                                $userFindSubsti = $stmtFindSubsti->fetch();
                                $countRowFindSubsti = $stmtFindSubsti->rowCount();

                                if($countRowFindSubsti > 0){
                                    // inform substitute guard
                                    $this->informSubstitute($userFindSubsti->email, 
                                                        $companySched, 
                                                        $comp_address, 
                                                        $timeinSched,
                                                        $timeoutSched,
                                                        $shiftSched,
                                                        $shiftSpanSched,
                                                        $leaveStart,
                                                        $expDateNew,
                                                        $substiPosition,
                                                        $substiPrice
                                                        );
                                } else {
                                    echo 'no available guard found';
                                }
                            } else {
                                echo 'di pa nadelete sa sched';
                            }
                        } else {
                            echo 'di pa nakapag add sa schedule';
                        }
                    }
                }
            }
        }
    }

    public function rejectRequest($id)
    {
        echo "<script>
                let rejRBtn = document.querySelector('#rejectbtn');
                rejRBtn.style.display = 'block';

                let rejABtn = document.querySelector('#approvebtn');
                rejABtn.style.display = 'none';
              </script>";
    }







    public function countNewGuardsWelcome($name)
    {
        $user = $name;

        $sql = "SELECT * FROM schedule 
                WHERE date_assigned BETWEEN CURRENT_DATE - 15 
                                        AND CURRENT_DATE";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetch();
        $countRow = $stmt->rowCount();

        if($countRow > 0){

            $guard = "";
            if($countRow > 1){
                $guard = "guards";
            } else {
                $guard = "guard";
            }

            echo "<h2>Welcome $name!</h2>
                  <p>You've assign new tasks to each of the $countRow $guard. To review all the tasks click the button below. </p>
                  <button><a href='./dashboard.php?reviewAll=true'>Review All</a></button>
                  ";
        } else {
            echo "<h2>Welcome $name!</h2>
                  <p>You've assign no task to each of the total guards.</p>
                  <button disabled><a style='background-color:gray' href='#'>Review All</a></button>
                  ";
        }
    }


    public function reviewAll()
    {
        $sql = "SELECT s.empId,
                       s.company as company,
                       s.date_assigned,
                       e.firstname,
                       e.lastname,
                       e.position as position,
                       c.comp_location
                       
                FROM schedule s
                INNER JOIN employee e
                ON s.empId = e.empId

                INNER JOIN company c
                ON s.company = c.company_name
                WHERE s.date_assigned BETWEEN CURRENT_DATE - 15
                                      AND CURRENT_DATE";
        $stmt = $this->con()->query($sql);
        while($row = $stmt->fetch()){
            $fullname = $row->firstname ." ".$row->lastname;
            echo "<tr>
                    <td>$fullname</td>
                    <td>$row->company</td>
                    <td>$row->comp_location</td>
                    <td>$row->position</td>
                    <td>$row->date_assigned</td>
                  </tr>";
        }
    }


    public function dashboardStatistics()
    {
        $sql = "SELECT * FROM employee";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([]);
        $users = $stmt->fetch();
        $countRow = $stmt->rowCount();

        if($countRow > 0){
            $totalEmployees = $countRow;
            
            $sqlCountEmp = "SELECT position, 
                                   COUNT(position) AS positions,
                                   ROUND(100. * count(*) / sum(count(*)) over (), 0) AS percentage
                            FROM employee
                            WHERE position != 'NULL'
                            GROUP BY position
                            ORDER BY percentage DESC
                            LIMIT 4;
                            ";
            $stmtCountEmp = $this->con()->query($sqlCountEmp);
            
            while($usersCountEmp = $stmtCountEmp->fetch()){
                $posName = $usersCountEmp->position;
                $posTotal = $usersCountEmp->positions;
                $posPercentage = $usersCountEmp->percentage . "%";
                // $getTotal = $posTotal / $totalEmployees; // 0.33
                // $decToPercentage = round((float)$getTotal * 100 ) . '%'; // 33%

                echo "<div class='cards'>
                        <div>
                            <h1>$posTotal</h1>
                        </div>
                        <div>
                            <p>$posName</p>
                            <p>Secondary info</p>
                        </div>
                        <div>
                            <div class='outstanding'>
                                <h3>$posPercentage</h3>
                            </div>
                        </div>
                      </div>";
            }

        }
    }

    public function viewAllStatistics() 
    {
        $sql = "SELECT * FROM employee";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([]);
        $users = $stmt->fetch();
        $countRow = $stmt->rowCount();

        if($countRow > 0){
            $totalEmployees = $countRow;
            
            $sqlCountEmp = "SELECT position, 
                                   COUNT(position) AS positions,
                                   ROUND(100. * count(*) / sum(count(*)) over (), 0) AS percentage
                            FROM employee
                            WHERE position != 'NULL'
                            GROUP BY position
                            ORDER BY percentage DESC;
                            ";
            $stmtCountEmp = $this->con()->query($sqlCountEmp);
            
            while($usersCountEmp = $stmtCountEmp->fetch()){
                $posName = $usersCountEmp->position;
                $posTotal = $usersCountEmp->positions;
                $posPercentage = $usersCountEmp->percentage . "%";
                // $getTotal = $posTotal / $totalEmployees; // 0.33
                // $decToPercentage = round((float)$getTotal * 100 ) . '%'; // 33%

                echo "<tr>
                          <td>$posTotal</td>
                          <td>$posName</td>
                          <td>$posPercentage</td>
                      </tr>";
            }

        }
    }

    public function dashboardRecentActivity()
    {
        $sql = "SELECT * FROM company";
        $stmt = $this->con()->query($sql);

        // set timezone and get date and time
        $datetime = $this->getDateTime();
        $date = $datetime['date'];

        while($users = $stmt->fetch()){
            $findColor = $users->date;
            $status = '';
            
            if(strtotime($users->date) <= strtotime($date) && 
               strtotime($users->date) >= strtotime($date.'-15 day')){
                $status = 'recent';

                echo "<tr>
                        <td>$users->company_name</td>
                        <td>$users->comp_location</td>
                        <td>$users->hired_guards</td>
                        <td>
                            <div class='circle-with-text'>
                                <div class='circle $status'></div>
                                <span>$users->date</span>
                            </div>
                        </td>
                      </tr>";
            } 
            
        }
    }

    public function dashboardRecentActivityAll()
    {
        $sql = "SELECT * FROM company";
        $stmt = $this->con()->query($sql);

        // set timezone and get date and time
        $datetime = $this->getDateTime();
        $date = $datetime['date'];

        while($users = $stmt->fetch()){
            $findColor = $users->date;
            $status = '';
            
            if(strtotime($users->date) <= strtotime($date) && 
               strtotime($users->date) >= strtotime($date.'-15 day')){
                $status = 'recent';
            } elseif(strtotime($users->date) >= strtotime($date.'-30 day') && 
                     strtotime($users->date) <= strtotime($date.'-15 day')){
                $status = 'late';
            } else {
                $status = 'old';
            }

            echo "<tr>
                    <td>$users->company_name</td>
                    <td>$users->comp_location</td>
                    <td>$users->hired_guards</td>
                    <td>$users->date</td>
                  </tr>";
        }
    }

    public function dashboardNewGuards()
    {
        $sql = "SELECT * FROM employee ORDER BY date DESC LIMIT 4";
        $stmt = $this->con()->query($sql);
        while($users = $stmt->fetch()){
            $fullname = $users->lastname.", ".$users->firstname;

            echo "<div class='guard-row'>
                        <div class='guard-row-text'>
                            <p>$fullname</p>
                            <span>Date added: <b>$users->date</b></span>
                        </div>
                        <div class='guard-row-button'>
                            <div class='btn-edit'>
                                <a href='./dashboard.php?guardId=$users->id&editGuard=true&email=$users->email' class='btn-edit-icon'>
                                    <span class='material-icons'>edit</span>
                                </a>
                            </div>
                            <div class='btn-delete'>
                                <a href='./dashboard.php?guardId=$users->id&deleteGuard=true' class='btn-delete-icon'>
                                    <span class='material-icons'>delete</span>
                                </a>
                            </div>
                            
                        </div>
                    </div>";
        }
    }

    // modal only
    public function dashboardEditGuardsModal($id)
    {
        $sql = "SELECT * FROM employee WHERE id = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        $countRow = $stmt->rowCount();

        if($countRow > 0){
            echo "<form method='post'>
                    <div>
                        <label for='firstname'>Firstname</label>
                        <input type='text' name='firstname' id='firstname' value='$user->firstname' required/>
                    </div>
                    <div>
                        <label for='lastname'>Lastname</label>
                        <input type='text' name='lastname' id='lastname' value='$user->lastname' required/>
                    </div>
                    <div>
                        <label for='address'>Address</label>
                        <input type='text' name='address' id='address' value='$user->address' required/>
                    </div>
                    <div>
                        <label for='email'>Email</label>
                        <input type='email' name='email' id='email' value='$user->email' required/>
                    </div>
                    <div>
                        <label for='cpnumber'>Contact Number</label>
                        <input type='text' name='cpnumber' id='cpnumber' value='$user->cpnumber' required/>
                    </div>
                    <div>
                        <label for='browserfingerprint'>Browser Fingerprint</label>
                        <input type='text' name='browserfingerprint' id='browserfingerprint' value='$user->browserfingerprint' required/>
                    </div>
                    <div>
                        <label for='devicefingerprint'>Device Fingerprint</label>
                        <input type='text' name='devicefingerprint' id='devicefingerprint' value='$user->devicefingerprint' required/>
                    </div>
                    <div>
                        <label for='qrcode'>Qr Code</label>
                        <input type='text' name='qrcode' id='qrcode' value='$user->qrcode' required/>
                    </div>
                    <div>
                        <button type='submit' name='editGuard'>Edit Guard</button>
                    </div>
                  </form>";
        }

    }

    // new guard edit in modal info
    public function dashboardEditGuards($id, $existingEmail)
    {
        if(isset($_POST['editGuard'])){
            $firstname = $_POST['firstname'];
            $lastname = $_POST['lastname'];
            $address = $_POST['address'];
            $email = $_POST['email'];
            $cpnumber = $_POST['cpnumber'];
            $browserfingerprint = $_POST['browserfingerprint'];
            $devicefingerprint = $_POST['devicefingerprint'];
            $qrcode = $_POST['qrcode'];

            if(empty($firstname) &&
               empty($lastname) &&
               empty($address) &&
               empty($email) &&
               empty($cpnumber) &&
               empty($browserfingerprint) &&
               empty($devicefingerprint) &&
               empty($qrcode)
            ){
                echo 'All input fields are required to edit the employee information';
            } else {

                if($email == $existingEmail){
                    $sql = "UPDATE employee
                            SET firstname = ?,
                                lastname = ?,
                                address = ?,
                                email = ?,
                                cpnumber = ?,
                                browserfingerprint = ?,
                                devicefingerprint = ? ,
                                qrcode = ?
                            WHERE id = ?";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$firstname, $lastname, $address, $email, $cpnumber, $browserfingerprint, $devicefingerprint, $qrcode, $id]);
                } else {
                    // update employee

                    $sqlFindEmail = "SELECT * FROM employee WHERE email = ?";
                    $stmtFindEmail = $this->con()->prepare($sqlFindEmail);
                    $stmtFindEmail->execute([$email]);
                    $userFindEmail = $stmtFindEmail->fetch();
                    $countRowFindEmail = $stmtFindEmail->rowCount();
                    if($countRowFindEmail > 0){
                        echo 'Email is already exist in the system';
                    } else {
                        $sql = "UPDATE employee
                                SET firstname = ?,
                                    lastname = ?,
                                    address = ?,
                                    email = ?,
                                    cpnumber = ?,
                                    browserfingerprint = ?,
                                    devicefingerprint = ? ,
                                    qrcode = ?
                                WHERE id = ?";
                        $stmt = $this->con()->prepare($sql);
                        $stmt->execute([$firstname, $lastname, $address, $email, $cpnumber, $browserfingerprint, $devicefingerprint, $qrcode, $id]);
                        $countRow = $stmt->rowCount();

                        if($countRow > 0){
                            $sqlInform = "SELECT e.*, sd.secret_key as secret_key  
                                        FROM employee e
                                        INNER JOIN secret_diarye sd
                                        ON e.email = sd.e_id
                                        WHERE e.id = ?";
                            $stmtInform = $this->con()->prepare($sqlInform);
                            $stmtInform->execute([$id]);
                            $userInform = $stmtInform->fetch();
                            $countRowInform = $stmtInform->rowCount();
                            if($countRowInform > 0){
                                // send credentials in new email
                                $this->sendEmail($userInform->email, $userInform->secret_key);
                                echo "<script>window.location.assign('./dashboard.php');</script>";
                            }
                        }
                    }
                }
            }
        }
    }


    public function dashboardDeleteGuardsModal($id)
    {
        $sql = "SELECT * FROM employee WHERE id = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        $countRow = $stmt->rowCount();

        if($countRow > 0){
            echo "<div class='modal-holder'>
                    <div class='deleteguard-header'>
                        <h1>Delete Employee</h1>
                        <span id='exit-modal-deleteguard' class='material-icons'>close</span>
                    </div>
                    <div class='deleteguard-content'>
                        <h1>Are you sure you want to delete this employee?</h1>
                        <form method='post'>
                            <input type='hidden' name='empDeleteId' value='$user->id' required/>
                            <button type='submit' name='deleteEmployee'>Delete</button>
                        </form>
                    </div>
                  </div>";
        }
    }

    public function dashboardDeleteGuards()
    {
        if(isset($_POST['deleteEmployee'])){
            $empDeleteId = $_POST['empDeleteId'];
            if(empty($empDeleteId)){
                echo "Id are required to delete the employee record";
            } else {
                $sql = "SELECT * FROM employee WHERE id = ?";
                $stmt = $this->con()->prepare($sql);
                $stmt->execute([$empDeleteId]);
                $user = $stmt->fetch();
                $countRow = $stmt->rowCount();

                if($countRow > 0){
                    $sqlSecret = "DELETE FROM secret_diarye WHERE e_id = ?";
                    $stmtSecret = $this->con()->prepare($sqlSecret);
                    $stmtSecret->execute([$user->email]);
                    $countRowSecret = $stmtSecret->rowCount();

                    if($countRowSecret > 0){
                        $sqlEmp = "DELETE FROM employee WHERE id = ?";
                        $stmtEmp = $this->con()->prepare($sqlEmp);
                        $stmtEmp->execute([$empDeleteId]);
                        echo "<script>window.location.assign('./dashboard.php');</script>";
                    }
                }
            }
        }
    }


    public function dashboardLeaveRequests()
    {
        $sql = "SELECT 
                        l.*,
                        l.id as finalId,
                        e.position AS position,
                        e.firstname AS firstname,
                        e.lastname AS lastname
                FROM leave_request l
                INNER JOIN employee e
                ON l.empId = e.empId
                WHERE status = 'pending'
                ORDER BY id DESC
                LIMIT 4";
        $stmt = $this->con()->query($sql);
        while($row = $stmt->fetch()){
            $fullname = $row->firstname ." ".$row->lastname;
            echo "<div class='request-row'>
                        <div class='request-row-text'>
                            <p>$fullname</p>
                            <span>Position to <b>$row->position</b></span>
                        </div>
                        <div class='request-row-button'>
                            <div class='btn-edit'>
                                <a href='./dashboard.php?id=$row->finalId&act=approve' class='btn-edit-icon'>
                                    <span class='material-icons'>done</span>
                                </a>
                            </div>
                            <div class='btn-delete'>
                                <a href='./dashboard.php?id=$row->finalId&act=reject' class='btn-delete-icon'>
                                    <span class='material-icons'>close</span>
                                </a>
                            </div>
                        </div>
                    </div>";
        }

    }

    public function adminProfile($id)
    {
        $sql = "SELECT * FROM super_admin WHERE id = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        $rowCount = $stmt->rowCount();

        if($rowCount > 0){
            echo "<script>
                      let image = document.querySelector('#image');
                      let firstname = document.querySelector('#firstname');
                      let lastname = document.querySelector('#lastname');
                      let address = document.querySelector('#address');
                      let cpnumber = document.querySelector('#cpnumber');
                      let email = document.querySelector('#email');

                      firstname.value = '$user->firstname';
                      lastname.value = '$user->lastname';
                      address.value = '$user->address';
                      cpnumber.value = '$user->cpnumber';
                      email.value = '$user->username';
                  </script>";
        }
    }

    public function viewAdminImage($id)
    {
        $sql = "SELECT image FROM admin_profile WHERE sa_id = ?";
        $stmt = $this->con()->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        $countRow = $stmt->rowCount();

        if($countRow > 0){
            $myImage = base64_encode($user->image);
            echo "<img src='data:image/jpg;charset=utf8;base64,$myImage'/>";
        } else {
            echo "<p class='status error'>Image(s) not found...</p>";
        }
    }

    public function editAdminProfile($id)
    {
        if(isset($_POST["saveChanges"])){ 

            $firstname = $_POST['firstname'];
            $lastname = $_POST['lastname'];
            $address = $_POST['address'];
            $cpnumber = $_POST['cpnumber'];
            $email = $_POST['email'];

            if(empty($firstname) ||
               empty($lastname) ||
               empty($address) ||
               empty($cpnumber) ||
               empty($email)
            ){
                echo 'All input fields are required to update.';
            } else {

                $sqlAdmin = "UPDATE super_admin
                             SET firstname = ?,
                                 lastname = ?,
                                 address = ?,
                                 cpnumber = ?,
                                 username = ?
                             WHERE id = ?";
                $stmtAdmin = $this->con()->prepare($sqlAdmin);
                $stmtAdmin->execute([$firstname, $lastname, $address, $cpnumber, $email, $id]);
                $countRowAdmin = $stmtAdmin->rowCount();
                if($countRowAdmin > 0){
                    echo 'nag update na yung admin';
                } else {
                    echo 'di pa nag update yung admin';
                }

                $status = 'error'; 
                if(!empty($_FILES["image"]["name"])) {
                    // Get file info 
                    $fileName = basename($_FILES["image"]["name"]); // sample.jpg
                    $fileType = pathinfo($fileName, PATHINFO_EXTENSION); // .jpg
                    
                    // Allow certain file formats 
                    $allowTypes = array('jpg','png','jpeg','gif'); 

                    // kapag jpg yung file or what
                    if(in_array($fileType, $allowTypes)){ 
                        $image = $_FILES['image']['tmp_name']; 
                        $imgContent = addslashes(file_get_contents($image)); 
                    
                        // Delete the existing image because it will fail if we update it
                        $sqlDel = "DELETE FROM admin_profile WHERE sa_id = ?";
                        $stmtDel = $this->con()->prepare($sqlDel);
                        $stmtDel->execute([$id]);
                        $countRowDel = $stmtDel->rowCount();
                        if($countRowDel > 0){
                            // Insert image content into database 
                            $sql = "INSERT INTO admin_profile (image, sa_id, created) 
                                    VALUES ('$imgContent', $id, NOW())";
                            $insert = $this->con()->query($sql); 
                            
                            if($insert){ 
                                echo "File uploaded successfully."; 
                            }else{ 
                                echo "File upload failed, please try again."; 
                            }  
                        }


                        
                    }else{ 
                        echo 'Sorry, only JPG, JPEG, PNG, & GIF files are allowed to upload.'; 
                    } 
                }
            }
        } 
    }


    public function adminChangePassword($id)
    {
        if(isset($_POST['saveChanges'])){
            $email = $_POST['email'];
            $current_password = $_POST['current_password'];
            $confirm_password = $_POST['confirm_password'];

            $checkPass = $this->generatedPassword($current_password);
            $encryptedPass = $this->generatedPassword($confirm_password);

            if(empty($email) ||
               empty($current_password) ||
               empty($confirm_password)
            ){
                echo 'all input fields are required to update password';
            } else {

                $sqlFindUser = "SELECT * FROM super_admin 
                                WHERE username = ?
                                AND password = ?";
                $stmtFindUser = $this->con()->prepare($sqlFindUser);
                $stmtFindUser->execute([$email, $checkPass[0]]);
                $userFindUser = $stmtFindUser->fetch();
                $countRowFindUser = $stmtFindUser->rowCount();

                if($countRowFindUser > 0){
                    echo 'tugma credentials';

                    $currEmail = $userFindUser->username;

                    $sql = "UPDATE super_admin
                            SET password = ?
                            WHERE id = ?";
                    $stmt = $this->con()->prepare($sql);
                    $stmt->execute([$encryptedPass[0], $id]);

                    $sqlOrigPass = "UPDATE secret_diary
                                    SET secret_key = ?
                                    WHERE sa_id = ?";
                    $stmtOrigPass = $this->con()->prepare($sqlOrigPass);
                    $stmtOrigPass->execute([$confirm_password, $currEmail]);

                } else {
                    echo 'password are not match';
                }  
            }
        }
    }


}

$payroll = new Payroll();
?>