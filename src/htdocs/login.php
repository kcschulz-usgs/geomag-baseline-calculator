<?php

	include_once '../conf/config.inc.php';
	include_once '../lib/classes/UserFactory.php';
	include_once '../lib/ad/authentication.inc.php';

	session_start();

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
			$LOGIN_ERROR = 'A portion of your login was incorrect, please try again.';
			print "<div class=\"error\">{$LOGIN_ERROR}</div>";
		} else {

			// set the session
			$_SESSION['userid'] = $user->name;
			$_SESSION['username'] = $user->username;
			$_SESSION['useremail'] = $user->email;

			// update last login time
			$users = new UserFactory($DB);
			$user = $users->read($_POST['username']);
			$user->lastLogin = time();
			$users->update($users->getIdByUsername($_POST['username']), $user);

			header("location: index.php");
		}
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
			$email = $user->email;

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
			$uPassword = $user->password;
			if ($uPassword !== null && $uPassword === md5(stripslashes($password)) &&
					$user->isEnabled()) {
				return $user;
			}
		}
		return null;
	}

?>

<form action="" method="post" name="implogin" id="login">
	<input type="hidden" name="enter" value="yes">
	<label for="input-username">Username:</label>
	<input id="input-username" type="text" name="username">
	<label for="input-password">Password:</label>
	<input id="input-password" type="password" name="password">
	<input type="submit" name="submit" value="login" id="login-submit">
</form>

<script language="JavaScript" type="text/javascript"><!--
	function submit_login(){
		if (document.implogin.uname.value == "") {
			alert('Please provide your username and password');
			document.implogin.uname.focus();
			return false;
		} else if (document.implogin.passwd.value == "") {
			alert('Please provide your username and password');
			document.implogin.passwd.focus();
			return false;
		} else {
			return true;
		}
	}
	document.getElementById('login-submit').onclick = submit_login;
//--></script>