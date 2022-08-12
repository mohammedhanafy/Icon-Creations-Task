<?php

    session_start();

    if(isset($_SESSION['fullname'])) {
        header("location: index.php");
    }
    
    if(isset($_SESSION['successMessage'])) {
        $successMessage = $_SESSION['successMessage'];
    }

    include('dbconnect.php');

    $errorMessage = null;

    if(isset($_POST["check_email"])) {

        $email = $_POST['email'];

        if($email == '') {
            $errorMessage = 'Email is Required';
        } else {
            
            $sql = $conn->prepare("SELECT * FROM users WHERE email = ?");
            
            $sql->execute(array($email));

            $rowCount = $sql->rowCount();
            
            if($rowCount == 1) {

                $result = $sql->fetch();

                $user_otp = rand(100000, 999999);
            
                $sql = $conn->prepare("UPDATE users SET otp = :otp WHERE email = :email");
            
                $sql->execute(array(
                    'otp' => $user_otp,
                    'email' => $result['email']
                ));
            
                require 'PHPMailer/PHPMailerAutoload.php';
            
                $mail = new PHPMailer;

                $mail->SMTPDebug  = 3; 
            
                $mail->isSMTP();
                $mail->Host = 'smtp.mailtrap.io';
                $mail->SMTPAuth = true;
                $mail->Username = '8a8bd5b8f8fd61';
                $mail->Password = '342f7e6a23f617';
                $mail->Port = 587;
            
                $mail->setFrom('careers@icon-creations.com', 'Icon Creations');
                $mail->addAddress($result["email"], $result['fullname']);
            
                $mail->isHTML(true);
            
                $mail->Subject = 'Password reset request for your account';
            
                $messageBody = '
                    <p>For reset your password, you have to enter this verification code when prompted: <b>'.$user_otp.'</b>.</p>
                    <p>Sincerely,</p>
                ';
            
                $mail->Body = $messageBody;
            
                if($mail->send()) {
                    $_SESSION['successMessage'] = 'Please Check Your Email for password reset code'; 
                    header("location: forget_password.php?step2=1&email=".$result["email"]);
                    exit();
                } else {    
                    $errorMessage = 'Something went wrong while sending the email';
                }
                
            } else {
                $errorMessage = 'This Email is not found in our database';
            }

        }

    }

    if(isset($_POST["check_otp"])) {

        $email = $_POST["email"];
        $user_otp = $_POST['user_otp'];

        if($user_otp == '') {
            $errorMessage = 'Enter OTP Number';
        } else {

            $sql = $conn->prepare("SELECT * FROM users WHERE email = ?");
            
            $sql->execute(array($email));

            $rowCount = $sql->rowCount();

            if($rowCount == 1) {

                $sql = $conn->prepare("SELECT * FROM users WHERE email = :email AND otp = :otp");
                
                $sql->execute(array(
                    'email' => $email,
                    'otp' => $user_otp,
                ));
    
                $rowCount = $sql->rowCount();
            
                if($rowCount > 0) {
                    $_SESSION['successMessage'] = 'OTP is correct. Please enter your new password'; 
                    header("location: forget_password.php?step3=1&email=".$email);
                    exit();
                } else {
                    $errorMessage = 'Wrong OTP Number';
                }

            } else {
                $errorMessage = 'Wrong Email Address';
            }


        }

    }

    if(isset($_POST["change_password"])) {

        $email = $_POST["email"];
        $new_password = $_POST["new_password"];
        $confirm_password = $_POST["confirm_new_password"];

        if($new_password == '' || $confirm_password == '') {
            $errorMessage = 'Enter New Password and Confirm New Password';
        } else {

            $sql = $conn->prepare("SELECT * FROM users WHERE email = ?");
                
            $sql->execute(array($email));
    
            $rowCount = $sql->rowCount();
    
            if($rowCount == 1) {
    
                if($new_password == $confirm_password) {
                    $options = [
                        'cost' => 12,
                    ];
                    
                    $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT, $options);
        
                    $sql = $conn->prepare("UPDATE users SET password = :hashedPassword WHERE email = :email");
                        
                    $sql->execute(array(
                        'hashedPassword' => $hashedPassword,
                        'email' => $_POST["email"],
                    ));
        
                    $_SESSION['successMessage'] = 'Password updated successfully. Please login now with your new password'; 
                    header("location: login.php");
                    exit();
                } else {
                    $errorMessage = 'Password does not match Confirm Password';        
                }
            
            } else {
                $errorMessage = 'Wrong Email Address';
            }

        }


    }

?>
<html>
    <head>
        <title>Forgot Password</title>
    </head>
    <body>
        <?php

        if(isset($errorMessage)) {
            echo '<div style="color:red;"> '. $errorMessage .' </div>';
        }

        if(isset($successMessage)) {
            echo '<div style="color:green;"> '. $successMessage .' </div>';
        }

        if(isset($_GET["step1"])) {
        ?>
            <form action="" method="POST">
                <h1>Step 1</h1>
                <hr>
                <label>Enter Your Email</label> <br><br>
                <input type="text" name="email" class="form-control" /> <br><br>

                <button type="submit" name="check_email">Send OTP to Email</button>
            </form>
        <?php
        }

        if(isset($_GET["step2"], $_GET["email"])) {
        ?>
            <form action="" method="POST">
                <h1>Step 2</h1>
                <hr>
                <label>Enter OTP Number</label> <br><br>
                <input type="text" name="user_otp" class="form-control" /> <br><br>

                <input type="hidden" name="email" value="<?php echo $_GET["email"]; ?>" />
                <button type="submit" name="check_otp">Verify OTP</button>
            </form>
        <?php
        }

        if(isset($_GET["step3"], $_GET["email"])) {
        ?>
            <form action="" method="POST">
                <h1>Step 3</h1>
                <hr>
                <label>Enter New Password</label> <br><br>
                <input type="password" name="new_password" class="form-control" /> <br><br>

                <label>Enter Confirm Password</label> <br><br>
                <input type="password" name="confirm_new_password" class="form-control" /> <br><br>

                <input type="hidden" name="email" value="<?php echo $_GET["email"]; ?>" />
                <button type="submit" name="change_password">Change Password</button>
            </form>
        <?php 
        }
        ?>

        <?php
            unset($_SESSION['successMessage']);
        ?>
    </body>
</html>