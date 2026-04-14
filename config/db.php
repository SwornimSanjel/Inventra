<?php

<<<<<<< HEAD
$host = "localhost";
$user = "root";
$pass = "";
$db   = "inventra";   // ← change here

$conn = new mysqli($host,$user,$pass,$db);

if($conn->connect_error){
die("Connection failed: " . $conn->connect_error);
}

?>
=======
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'inventra_merge_app';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

?>
>>>>>>> a797a55778273531d31c9c9dc672c4d4fa66ebad
