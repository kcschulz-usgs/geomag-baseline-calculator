<?php

	include_once('login.inc.php');

	if (isset($LOGIN_ERROR)) {
		print "<div class=\"error\">{$LOGIN_ERROR}</div>";
	}
?>

<form action="" method="post" name="implogin" id="login">
	<input type="hidden" name="enter" value="yes">
	<label for="input-username">Username:</label>
	<input id="input-username" type="text" name="username" class="input1">
	<label for="input-password">Password:</label>
	<input id="input-password" type="password" name="password" class="input1">
	<input type="submit" name="submit" value="login" onclick="return submit_login();">
</form>

<script language="JavaScript" type="text/javascript"><!--
	function setFocus(){
	    document.implogin.uname.focus();
	}
	
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
//--></script>