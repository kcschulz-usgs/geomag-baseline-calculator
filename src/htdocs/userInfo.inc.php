<?php

session_start();

if (isset($_SESSION['userid'])) {
	echo '<div class="user-info">';
		echo 'You are logged in as ' . $_SESSION['userid'] . '. ';
		echo '<a href="logout.php">Log Out</a>';
	echo '</div>';
}