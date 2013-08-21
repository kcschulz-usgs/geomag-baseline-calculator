<?php

include_once 'User.php';

class UserFactory {

	public $db = null;
	private $error = null;
	const INSERT = 'INSERT INTO user (name, username, email, password) VALUES (:name, :username, :email, :password)';
	const UPDATE = 'UPDATE user SET name=:name, username=:username, email=:email, password=:password where id=:id';
	const DELETE = 'DELETE FROM user WHERE username=:username';
	const SELECT = 'SELECT * FROM user';
	const SELECT_USERNAME = 'SELECT * FROM user where username=:username';
	const SELECT_EMAIL = 'SELECT * FROM user where email=:email';
	const RESET = 'DELETE FROM user';

	public function __construct($db = null) {
		$this->db = $db;
	}

	// Standard CRUD

	/**
	 * Creates a user based on a User.  The database will enforce a unique
	 * username but a unique email must be enforced manually.
	 *
	 * @param {User} user
	 *
	 * @return {boolean} successful or not
	 */
	public function create($user) {

		// is the provided user valid?
		if ($user->validate()) {

			// validate the email is unique, if any is provided
			$email = $user->getEmail();
			$id = null;
			if ($email !== null) {
				$id = $this->getIdByEmail($email);
			}

			// email is either unique or empty... create the user
			if ($id === null) {
				try {
					$s = $this->db->prepare($this::INSERT);
					$s->execute(array(
						'name' => $user->getName(),
						'username' => $user->getUsername(),
						'email' => $email,
						'password' => md5($user->getPassword())
					));
				} catch (PDOException $error) {
					$this->error = $error.getMessage();
					return false;
				}
				return true;
			}
			$this->error = 'email is already being used';
			return false;
		}
		$this->error = 'user is invalid (missing Name, Username or Password?)';
		return false;
	}

	/**
	 * Returns an array of Users or a single User based on username.
	 *
	 * @param {String} username
	 *
	 * @return {User} user
	 */
	public function read($username = null) {

		// return all users if no username is specified
		if ($username === null) {
			try {
				$s = $this->db->prepare($this::SELECT);
				$users = array();
				while ($row = $s->fetch()) {
					$users.push(new User($row['name'], $row['username'], $row['email'],
							$row['password']));
				}
				return $users;
			} catch (PDOException $error) {
				$this->error = $error.getMessage();
				return null;
			}
		} else {
			try {
				$s = $this->db->prepare($this::SELECT_USERNAME);
				$s->execute(array('username' => $username));
				while ($row = $s->fetch()) {
					return new User($row['name'], $row['username'], $row['email'],
							$row['password']);
				}
			} catch (PDOException $error) {
				$this->error = $error.getMessage();
				return null;
			}
		}
	}

	/**
	 * Udates user based on an id.  The database will enforce a unique username
	 * but a unique email must be enforced manually.
	 *
	 * @param {String} username
	 *
	 * @return {boolean} successful or not
	 */
	public function update($id, $user) {

		// is the provided user valid?
		if ($user->validate()) {

			// validate the email is unique, if any is provided
			$email = $user->getEmail();
			$emailId = null;
			if ($email !== null) {
				$emailId = $this->getIdByEmail($email);
			}

			// email is either unique, empty or an update... update the user
			if ($emailId === null || $emailId === $id) {
				try {
					$s = $this->db->prepare($this::UPDATE);
					$s->execute(array(
						'name' => $user->getName(),
						'username' => $user->getUsername(),
						'email' => $email,
						'password' => md5($user->getPassword()),
						'id' => $id
					));
				} catch (PDOException $error) {
					$this->error = $error.getMessage();
					return false;
				}
				return true;
			}
			$this->error = 'email is already being used';
			return false;
		}
		$this->error = 'user is invalid (missing Name, Username or Password?)';
		return false;
	}

	/**
	 * Deletes user based on username.
	 *
	 * @param {String} username
	 *
	 * @return {boolean} successful or not
	 */
	public function delete($username) {
		try {
			$s = $this->db->prepare($this::DELETE);
			$s->execute(array('username' => $username));
		} catch (PDOException $error) {
			$this->error = $error.getMessage();
			return false;
		}
		return true;
	}

	// Public Methods

	/**
	 * Returns a user's ID if it exists.
	 *
	 * @param {String} username
	 *
	 * @return {int} user's id
	 */
	public function getIdByUsername($username) {
		try {
			$s = $this->db->prepare($this::SELECT_USERNAME);
			$s->execute(array('username' => $username));
			while ($row = $s->fetch()) {
				return $row['id'];
			}
		} catch (PDOException $error) {
			die($e->getMessage());
		}
		return null;
	}

	/**
	 * Returns a user's ID if it exists.
	 *
	 * @param {String} email
	 *
	 * @return {int} user's id
	 */
	public function getIdByEmail($email) {
		try {
			$s = $this->db->prepare($this::SELECT_EMAIL);
			$s->execute(array('email' => $email));
			while ($row = $s->fetch()) {
				return $row['id'];
			}
		} catch (PDOException $error) {
			$this->error = $error->getMessage();
			return null;
		}
		return null;
	}

	/**
	 * Returns the error message, if any
	 *
	 * @return {String} error message
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Resets the database
	 *
	 * @return {boolean} successful or not
	 */
	public function reset() {
		try {
			$s = $this->db->prepare($this::RESET);
			$s->execute();
		} catch (PDOException $error) {
			$this->error = $error->getMessage();
			return false;
		}
		return true;
	}
}
