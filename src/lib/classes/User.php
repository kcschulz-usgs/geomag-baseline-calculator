<?php

class User {

	private $name;
	private $username;
	private $email;
	private $password;
	private $lastLogin;

	public function __construct($name = NULL, $username = NULL, $email = NULL,
			$password = NULL) {
		$this->setName($name);
		$this->setUsername($username);
		$this->setEmail($email);
		$this->setPassword($password);
	}

	// Helpers

	public function validate() {
		if ($this->name === NULL || $this->username === NULL ||
				$this->password === NULL) {
			return false;
		}
		return true;
	}

	// Getters

	public function getName() {
		return $this->name;
	}

	public function getUsername() {
		return $this->username;
	}

	public function getEmail() {
		return $this->email;
	}

	public function getPassword() {
		return $this->password;
	}

	// Setters

	public function setName($name) {
		$this->name = $name;
	}

	public function setUsername($username) {
		$this->username = $username;
	}

	public function setEmail($email) {
		$this->email = $email;
	}

	public function setPassword($password) {
		$this->password = $password;
	}
}