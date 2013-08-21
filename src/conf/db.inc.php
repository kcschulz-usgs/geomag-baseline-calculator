<?php

$dbName = $CONFIG['DB_NAME'];
$dbUser = $CONFIG['DB_USER'];
$dbPass = $CONFIG['DB_PASSWORD'];

try {
	$DB = new PDO($dbName, $dbUser, $dbPass);
} catch (PDOException $error) {
	die('Error - database could not be loaded.');
}