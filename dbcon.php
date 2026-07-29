<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "ecommerce";

$con = mysqli_connect($host, $user, $password, $database);

if (!$con) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($con, "utf8");
?>