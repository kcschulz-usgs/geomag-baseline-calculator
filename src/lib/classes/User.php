<?php

class User {

	public $name;
	public $username;
	public $email;
	public $password;
	public $lastLogin;
	public $enabled;
	public $defaultObservatoryId;
	public $roles;

	public function __construct($name = NULL, $username = NULL, $email = NULL,
			$password = NULL, $lastLogin = NULL, $enabled = NULL,
			$defaultObservatoryId = NULL, $roles = Array()) {
		$this->name = $name;
		$this->username = $username;
		$this->email = $email;
		$this->password = $password;
		$this->lastLogin = $lastLogin;
		$this->enabled = $enabled;
		$this->defaultObservatoryId = $defaultObservatoryId;
		$this->roles = $roles;
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
		$temp = sizeof($this->roles) > 0 ? '"' : '';
		$o = array(
			'{',
			'"name": "', $this->name, '", ',
			'"username": "', $this->username, '", ',
			'"lastLogin": ', $this->lastLogin, ', ',
			'"enabled": ', $this->isEnabled(), ', ',
			'"defaultObservatoryId": ', $this->defaultObservatoryId, ', ',
			'"roles": [', $temp, join($this->roles, '", "'), $temp, ']',
			'}'
		);
		return join($o, '');
	}
}