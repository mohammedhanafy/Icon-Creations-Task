<?php 
    
    session_start();

    if(isset($_SESSION['fullname'])) {
        header("location: index.php");
    }

    if(isset($_SESSION['successMessage'])) {
        $successMessage = $_SESSION['successMessage'];
    }

    include('dbconnect.php'); 

    $formErros = array();
    $formWrongAttemptsLimit = null;
    $formWrongAttemptsExceedsLimit = null;

    if(isset($_POST['login'])) {
        $email =  $_POST['email'];
        $password = $_POST['password'];

        if($email == '') {
            $formErros[] = 'Email Field is Required';
        }

        if($password == '') {
            $formErros[] = 'Password Field is Required';
        }

        if(count($formErros) == 0) {
            $sql = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $sql->execute(array($email));
            $rowCount = $sql->rowCount();

            if($rowCount == 1) {
                $result = $sql->fetch();
                $sql = $conn->prepare("SELECT `password`, `attempts`, `status` FROM users WHERE email = ?");
                $sql->execute(array($result['email']));
                $row = $sql->fetch();
                $currentTime = date("Y-m-d H:i:s");  
                if(password_verify($password, $row['password'])) {
                    if($row['status'] == 'active') {
                        if($row['attempts'] > 0) {
                            $sql = $conn->prepare("UPDATE users SET attempts = :setAttemptsBackToZero, last_updated = :currentTime WHERE email = :email");
                            $sql->execute(array(
                                'setAttemptsBackToZero' => 0,
                                'currentTime' => $currentTime,
                                'email' => $email
                            ));
                        }
                        $_SESSION['successMessage'] = 'Your have logged in successfully'; 
                        $_SESSION['fullname'] = $result['fullname']; 
                        $_SESSION['email'] = $result['email']; 
                        header("location: index.php");
                        exit();
                    } else {
                        $formWrongAttemptsExceedsLimit = 'Sorry, Your credentials is right but your account is blocked forever for exceeding 3 times wrong';
                    }
                } else {
                    $formErros[] = 'Incorrect Email or Password';

                    $attemptsCount = $row['attempts'];
                    $attemptsIncrement = $attemptsCount + 1;
                    if($attemptsCount < 3) {
                        $sql = $conn->prepare("UPDATE users SET attempts = :attemptsIncrement, last_updated = :currentTime WHERE email = :email");
                        $sql->execute(array(
                            'attemptsIncrement' => $attemptsIncrement,
                            'currentTime' => $currentTime,
                            'email' => $email
                        ));
                        if($attemptsIncrement == 3) {
                            $_SESSION['LastAttemptTimeAfter30Seconds'] = date('Y-m-d H:i:s', strtotime(' +30 seconds '));
                            $formWrongAttemptsLimit = 'You have attempt logging 3 times wrong. Try again after 30 sec. Be aware that the next attempt if it is wrong it will block your account forever';
                        }
                    } else {
                        if($attemptsCount == 3) {
                            $sql = $conn->prepare("UPDATE users SET attempts = :attemptsIncrement, last_updated = :currentTime, status = :status WHERE email = :email");
                            $sql->execute(array(
                                'attemptsIncrement' => $attemptsIncrement,
                                'currentTime' => $currentTime,
                                'status' => 'blocked',
                                'email' => $email
                            ));
                        }
                        $formWrongAttemptsExceedsLimit = 'Sorry, Your account got blocked for exceeding 3 times wrong';
                    }
                    
                    
                }
            } else {
                $formErros[] = 'Incorrect Email or Password';
            }
        }

    }

?>
<html>
    <head>
        <title>Login</title>
    </head>
    <body>
        <?php
            if(!empty($formErros)) {
                foreach($formErros as $error) {
                    echo '<div style="color:red;"> '. $error .' </div>';
                }
            }
            if(isset($formWrongAttemptsLimit)) {
                echo '<div class="attempts_limit" style="color:red;"> '. $formWrongAttemptsLimit .' </div>';
            }
            if(isset($formWrongAttemptsExceedsLimit)) {
                echo '<div class="attempts_exceeds_limit" style="color:red;"> '. $formWrongAttemptsExceedsLimit .' </div>';
            }

            if(isset($successMessage)) {
                echo '<div style="color:green;"> '. $successMessage .' </div>';
            }
        ?>
        <form action="" method="POST">
            <h1>Login</h1>
            <p>Please fill in this form to Login to your account.</p>
            <hr>

            <label for="email"><b>Email</b></label> <br><br>
            <input type="email" placeholder="Enter Email" name="email" required> <br><br>

            <label for="password"><b>Password</b></label> <br><br>
            <input type="password" placeholder="Enter Password" name="password" required> <br><br>

            <?php
                if(isset($_SESSION['LastAttemptTimeAfter30Seconds'])) {
                    $currentTime = date("Y-m-d H:i:s");
                    if($currentTime > $_SESSION['LastAttemptTimeAfter30Seconds']) {
                        unset($_SESSION['LastAttemptTimeAfter30Seconds']);
                        echo '<div class="login_btn">
                                <button type="submit" name="login">Login</button>
                            </div>';
                    } else {
                        if(isset($formWrongAttemptsLimit)) {
                            echo '<div class="login_btn">
                                    <button type="submit" name="login">Login</button>
                                </div>';
                        }
                    }
                } else {
                    echo '<div class="login_btn">
                            <button type="submit" name="login">Login</button>
                        </div>';
                }
            ?>

            <p><a href="forget_password.php?step1=1">Forgot Password ?</a></p>

            <p>You don't have an account ? <a href="signup.php">Register Now</a></p>                       
        </form>

        <script src="jquery-3.2.1.min.js"></script>
        <script>
            $(document).ready(function() {
                var formWrongAttempts = "<?php echo $formWrongAttemptsLimit; ?>";
                if(formWrongAttempts) {
                    $('.login_btn').hide();
                    var counter = 30;
                    var interval = setInterval(function() {
                        counter--;
                        if (counter <= 0) {
                            clearInterval(interval);
                            $('.login_btn').show();
                            $('.attempts_limit').text("");
                        } else {
                            $('.attempts_limit').text("You have attempt logging 3 times wrong. Try again after " + counter + " sec. Be aware that the next attempt if it is wrong it will block your account forever");
                        }
                    }, 1000);
                }
            });
        </script>

        <?php
            unset($_SESSION['successMessage']);
        ?>

    </body>

</html>