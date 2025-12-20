<?php

$server = "localhost";
$user = "root";
$pass = "";
$dbname = "padron_2025";

$conn = @new mysqli($server, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("ERROR DE MYSQL: " . $conn->connect_error);
}

echo "Conexión exitosa!";
?>

