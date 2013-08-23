<?php

include_once('userInfo.inc.php');

// redirect to login if no session exists
if (!isset($_SESSION['userid'])) {
	header("location: login.php");
}