<?php

include_once 'User.php';

class UserFactory {

	public $db = null;
	private $error = null;
	const INSERT = 'INSERT INTO user (name, username, email, password) VALUES (:name, :username, :email, :password)';
	const UPDATE = 'UPDATE user SET name=:name, username=:username, email=:email, last_login = :lastLogin where id=:id';
	const SET_PASSWORD = 'UPDATE user SET password = :password where id=:id';
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
			$email = $user->email;
			$id = null;
			if ($email !== null) {
				$id = $this->getIdByEmail($email);
			}

			// email is either unique or empty... create the user
			if ($id === null) {
				try {
					$s = $this->db->prepare($this::INSERT);
					$s->execute(array(
						'name' => $user->name,
						'username' => $user->username,
						'email' => $email,
						'password' => md5($user->password)
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
							$row['password'], $row['last_login']));
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
							$row['password'], $row['last_login']);
				}
			} catch (PDOException $error) {
				$this->error = $error.getMessage();
				return null;
			}
		}
	}

	/**
	 * Udates user based on an id.  The database will enforce a unique username
	 * but a unique email must be enforced manually.  This will not update the
	 * password unless one is provided (prevents double MD5-ing of existing
	 * password).
	 *
	 * @param {int} id
	 * @param {String} username
	 * @param {String} password
	 *
	 * @return {boolean} successful or not
	 */
	public function update($id, $user, $password = null) {

		// is the provided user valid?
		if ($user->validate()) {

			// validate the email is unique, if any is provided
			$email = $user->email;
			$emailId = null;
			if ($email !== null) {
				$emailId = $this->getIdByEmail($email);
			}

			// email is either unique, empty or an update... update the user
			if ($emailId === null || $emailId === $id) {
				try {
					$s = $this->db->prepare($this::UPDATE);
					$s->execute(array(
						'name' => $user->name,
						'username' => $user->username,
						'email' => $email,
						'lastLogin' => $user->lastLogin,
						'id' => $id
					));
				} catch (PDOException $error) {
					$this->error = $error->getMessage();
					return false;
				}

				// new password provided, update the password
				if ($password !== null) {
					try {
						$s = $this->db->prepare($this::SET_PASSWORD);
						$s->execute(array(
							'password' => md5($password),
							'id' => $id
						));
					} catch (PDOException $error) {
						$this->error = $error->getMessage();
						return false;
					}
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
