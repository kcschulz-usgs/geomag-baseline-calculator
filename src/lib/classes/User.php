<?php

class User {

	public $name;
	public $username;
	public $email;
	public $password;
	public $lastLogin;
	public $enabled;

	public function __construct($name = NULL, $username = NULL, $email = NULL,
			$password = NULL, $lastLogin = NULL, $enabled = NULL) {
		$this->name = $name;
		$this->username = $username;
		$this->email = $email;
		$this->password = $password;
		$this->lastLogin = $lastLogin;
		$this->enabled = $enabled;
	}

	// Helpers

	public function validate() {
		if ($this->name === NULL || $this->username === NULL ||
				$this->password === NULL) {
			return false;
		}
		return true;
	}

	public function isEnabled() {
		if (strtoupper($this->enabled) === 'Y') {
			return true;
		}
		return false;
	}

	public function getJson() {
		$o = array(
			'{',
			'"name": ', $this->name, ', ',
			'"username": ', $this->username, ', ',
			'"lastLogin": ', $this->lastLogin, ', ',
			'"enabled": ', $this->isEnabled(),
			'}'
		);
		return join($o, '');
	}
}