<?php 

    session_start();

    if(isset($_SESSION['fullname'])) {
        header("location: index.php");
    }

    include('dbconnect.php'); 
    
    if(isset($_POST['register'])) {
        $fullname =  $_POST['fullname'];
        $email =  $_POST['email'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];

        $formErros = array();

        if($fullname == '') {
            $formErros[] = 'Full Name Field is Required';
        }

        if($email == '') {
            $formErros[] = 'Email Field is Required';
        }

        if($email != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formErros[] = 'Email is not Valid';
        }

        if($password == '') {
            $formErros[] = 'Password Field is Required';
        }

        if($password != '' && strlen($password) < 6) {
            $formErros[] = 'Password is too short';
        }

        if($password != $confirmPassword) {
            $formErros[] = 'Password does not match Confirm Password';
        }

        if(count($formErros) == 0) {

            $sql = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $sql->execute(array($email));
            $rowCount = $sql->rowCount();
    
            if($rowCount == 1) {

                $formErros[] = 'Email is already taken';

            } else {

                $options = [
                    'cost' => 12,
                ];
                  
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT, $options);

                $sql = $conn->prepare("INSERT INTO users(`fullname`,`email`,`password`) VALUES ('$fullname', '$email', '$hashedPassword')");
                $sql->execute();

                if($sql) {
                    $_SESSION['successMessage'] = 'Your account created successfully. Please login'; 
                    header("location: login.php");
                    exit();
                } else {
                    echo "Something went wrong. Please try again later";
                }

            }
        }

    }
?>
<html>
    <head>
        <title>Register</title>
    </head>
    <body>
        <?php
            if(!empty($formErros)) {
                foreach($formErros as $error) {
                    echo '<div style="color:red;"> '. $error .' </div>';
                }
            }
        ?>
        <form action="" method="POST">
                <h1>Sign Up</h1>
                <p>Please fill in this form to create an account.</p>
                <hr>

                <label for="fullname"><b>Full Name</b></label> <br><br>
                <input type="text" placeholder="Enter Full Name" name="fullname" required> <br><br>

                <label for="email"><b>Email</b></label> <br><br>
                <input type="email" placeholder="Enter Email" name="email" required> <br><br>

                <label for="password"><b>Password</b></label> <br><br>
                <input type="password" placeholder="Enter Password" name="password" required> <br><br>
 
                <label for="confirmPassword"><b>Confirm Password</b></label> <br><br>
                <input type="password" placeholder="Confirm Password" name="confirmPassword" required> <br><br>

                <div>
                    <button type="submit" name="register">Sign Up</button>
                </div>

                <p>Already have an account ? <a href="login.php">Login Now</a></p>
        </form>

        <?php
            unset($_SESSION['successMessage']);
        ?>
        
    </body>
</html>