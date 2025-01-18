<?php
    $servername = "localhost";
    $username = "root"; // default username for XAMPP
    $password = ""; // default password is empty
    $dbname = "test-database";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
?>