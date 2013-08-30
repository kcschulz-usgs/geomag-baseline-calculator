<?php

include_once '../conf/config.inc.php';
include_once '../lib/classes/UserFactory.php';

if (isset($_GET['user'])) {
	$name = $_GET['user'];
} else {
	echo "A user name is required.";
	exit();
}

if (isset($_GET['callback'])) {
	$callback = $_GET['callback'];
} else {
	echo "A callback is required.";
	exit();
}

$users = new UserFactory($DB);
$user = $users->read($name);

echo $callback . '(' . $user->getJson() . ')';