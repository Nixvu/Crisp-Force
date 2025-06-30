<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/includes/config.php';

echo "<h1>System Check</h1>";
echo "PHP Version: ".phpversion()."<br>";
echo "Database: ".($conn->ping() ? "Connected" : "Error: ".$conn->error)."<br>";

$res = $conn->query("SELECT * FROM User LIMIT 1");
echo "User table: ".($res ? "Exists (".$res->num_rows." rows)" : "Error: ".$conn->error);