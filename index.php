<?php 
    
    session_start();

    if(!isset($_SESSION['fullname'])) {
        header("location: login.php");
    }

    if(isset($_SESSION['successMessage'])) {
        $successMessage = $_SESSION['successMessage'];
    }

?>

<html>
    <head>
        <title>Dashboard</title>
    </head>
    <body>

    <?php

    if(isset($successMessage)) {
        echo '<div style="color:green;"> '. $successMessage .' </div>';
    }

    ?>

    <h1>Hey <?php echo $_SESSION['fullname']; ?> ( <?php echo $_SESSION['email'] ?> )
    <h3><a href="logout.php">Logout</a>

    <?php
        unset($_SESSION['successMessage']);
    ?>
    </body>
</html>