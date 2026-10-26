
<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "fun_kids_book";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $dbname
);

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");
?>
