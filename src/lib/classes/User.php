<?php

class User {

	public $name;
	public $username;
	public $email;
	public $password;
	public $lastLogin;

	public function __construct($name = NULL, $username = NULL, $email = NULL,
			$password = NULL) {
		$this->name = $name;
		$this->username = $username;
		$this->email = $email;
		$this->password = $password;;
	}

	// Helpers

	public function validate() {
		if ($this->name === NULL || $this->username === NULL ||
				$this->password === NULL) {
			return false;
		}
		return true;
	}
}