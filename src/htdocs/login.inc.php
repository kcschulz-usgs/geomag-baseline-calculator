<?php

session_start();

if (isset($_SESSION['userid'])) {

	// user is logged in.... display user information
	echo '<div class="user-info">';
		echo 'You are logged in as ' . $_SESSION['userid'] . '. ';
		echo '<a href="logout.php">Log Out</a>';
	echo '</div>';

} else {

	// user is not logged in... display login page
	header("location: login.php");

}