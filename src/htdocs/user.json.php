<?php

include_once '../conf/config.inc.php';
include_once '../lib/classes/UserFactory.php';

$callback = null;
if (isset($_GET['callback'])) {
	$callback = $_GET['callback'];
}

$users = new UserFactory($DB);

if (isset($_GET['user'])) {
	$name = $_GET['user'];
} else {
	session_start();
	if (isset($_SESSION['username'])) {
		$name = $_SESSION['username'];
	} else {
		echo "No user has been provided or is currently logged in.";
		exit();
	}
}

$user = $users->read($name);
if ($user !== null) {
	if ($callback !== null) {
		echo $callback . '(' . $user->getJson() . ')';
	} else {
		echo $user->getJson();
	}
} else {
	echo "No user by that username was found.";
}