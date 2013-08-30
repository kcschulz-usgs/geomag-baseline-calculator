<?php

class User {

	public $name;
	public $username;
	public $email;
	public $password;
	public $lastLogin;

	public function __construct($name = NULL, $username = NULL, $email = NULL,
			$password = NULL, $lastLogin = NULL) {
		$this->name = $name;
		$this->username = $username;
		$this->email = $email;
		$this->password = $password;
		$this->lastLogin = $lastLogin;
	}

	// Helpers

	public function validate() {
		if ($this->name === NULL || $this->username === NULL ||
				$this->password === NULL) {
			return false;
		}
		return true;
	}

	public function getJson() {
		$o = array(
			'{',
			'"name": ', $this->name, ', ',
			'"username": ', $this->username, ', ',
			'"lastLogin": ', $this->lastLogin,
			'}'
		);
		return join($o, '');
	}
}