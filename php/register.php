<?php 
    include 'Connection.php';
    $obj = new Connection();
    $connection = $obj->getConnection() or die('"1"');

    $user = addslashes($_POST['username']);
    $pass = addslashes($_POST['password']);
    $email = addslashes($_POST['email']);

    $sql = "INSERT INTO usuario(username,password,email) VALUES ('$user',AES_ENCRYPT('$pass','5p01l3r'),'$email')";

    mysqli_query($connection,$sql) or die('"2"');

    echo json_encode("3");
?>