<?php

include_once '../conf/config.inc.php';
include_once '../lib/classes/UserFactory.php';
include_once '../lib/ad/authentication.inc.php';

global $CONFIG;

// does the session exist?
if (!isset($_SESSION['userid']) && isset($_POST['enter'])) {

	// is the username and password set?
	if (!isset($_POST['username']) || !isset($_POST['password'])) {
		$LOGIN_ERROR = "Email and Password are required fields";
		return;
	}

	// authenticate the user
	$user = authenticate($_POST['username'], $_POST['password']);

	// was a user authenticated?
	if ($user === null) {
		header('HTTP/1.0 401 Unauthorized');
		$LOGIN_ERROR = '
			A portion of your login was incorrect, please try again.
			If you are unable to login you may: 
			<ul>
				<li><a href="#">Reset your password</a></li>
				<li>Call us at  303.273.8543 or 626.583.7231</li>
			</ul>
		';
		return;
	}

	// set the session
	$_SESSION['userid'] = $user->getName();
	$_SESSION['username'] = $user->getUsername();
	$_SESSION['useremail'] = $user->getEmail();
	header("location: index.php");
}

/**
	* If it's a usgs user (has a usgs email address), authenticate using LDAP.
	* Otherwise check password hash from database.
	*
	* Param {String} username
	* Param {String} password
	*
	* Return {User} the authenticated User
*/
function authenticate($username, $password) {
	global $DB;
	$users = new UserFactory($DB);
	$user = $users->read($username);

	// was the user found?
	if ($user !== null) {

		// try AD authentication if applicable
		$email = $user->getEmail();

		if ($email !== null && substr_compare($email, '@usgs.gov', -9, 9) === 0) {
			try {
				if (ad_authenticate($email, $password)) {
					return $user;
				} else {
					return null;
				}
			} catch (Exception $e) {
				return null;
			}
		}

		// try user saved in database
		$uPassword = $user->getPassword();
		if ($uPassword !== null && $uPassword === md5(stripslashes($password))) {
			return $user;
		}
	}
	return null;
}