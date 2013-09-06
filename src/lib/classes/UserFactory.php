<?php

include_once 'User.php';

class UserFactory {

	public $db = null;
	private $error = null;
	const INSERT = 'INSERT INTO user (name, username, email, password, enabled, default_observatory_id) VALUES (:name, :username, :email, :password, :enabled, :defaultObservatoryId)';
	const UPDATE = 'UPDATE user SET name=:name, username=:username, email=:email, last_login = :lastLogin, enabled = :enabled, default_observatory_id = :defaultObservatoryId where id=:id';
	const SET_PASSWORD = 'UPDATE user SET password = :password where id=:id';
	const DELETE = 'DELETE FROM user WHERE username=:username';
	const SELECT = 'SELECT * FROM user';
	const SELECT_USERNAME = 'SELECT * FROM user where username=:username';
	const SELECT_EMAIL = 'SELECT * FROM user where email=:email';
	const ROLES = 'SELECT name FROM user_role LEFT JOIN role ON role.ID = user_role.role_id WHERE user_id = :id';
	const ALL_ROLES = 'SELECT * FROM role';
	const ADD_ROLE = 'INSERT INTO user_role (user_id, role_id) VALUES (:user_id, :role_id)';
	const RESET_ROLES = 'DELETE FROM user_role WHERE user_id=:user_id';
	const RESET_ALL_ROLES = 'DELETE FROM user_role';
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
						'password' => md5($user->password),
						'defaultObservatoryId' => $user->defaultObservatoryId,
						'enabled' => 'Y'
					));
					$this->addRoles($this->db->lastInsertId(), $user->roles);
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
	* Returns an array of roles from a user id or all roles if id is null.
	*
	* @param {int} id
	*
	* @return {Array} roles
	*/
	public function getRoles($id = null) {
		$roles = array();
		try {

			// return all roles if no id is specified
			if ($id === null) {
				$s = $this->db->prepare($this::ALL_ROLES);
				$s->execute();
				while ($row = $s->fetch()) {
					$roles[$row['name']] = $row['ID'];
				}
			} else {
				$s = $this->db->prepare($this::ROLES);
				$s->execute(array('id' => $id));
				while ($row = $s->fetch()) {
					array_push($roles, $row['name']);
				}
			}	
		} catch (PDOException $error) {
			$this->error = $error->getMessage();
			return null;
		}
		return $roles;
	}

	/**
	* Adds a role to a user.
	*
	* @param {int} userId
	* @param {string} role
	*/
	public function addRole($userId, $role) {
		$roles = $this->getroles();
		if (array_key_exists($role, $roles)) {
			$roleId = $roles[$role];
		} else {
			$this->error = "Role does not exist";
			return null;
		}
		try {
			$s = $this->db->prepare($this::ADD_ROLE);
			$s->execute(array('user_id' => $userId, 'role_id' => $roleId));
		} catch (PDOException $error) {
			$this->error = $error->getMessage();
		}
	}

	/**
	* Adds an array of roles to a user.
	*
	* @param {int} userId
	* @param {array<int>} roles
	*/
	public function addRoles($userId, $roles) {

		// first reset their roles
		try {
			$s = $this->db->prepare($this::RESET_ROLES);
			$s->execute(array('user_id' => $userId));
		} catch (PDOException $error) {
			$this->error = $error->getMessage();
		}

		// then add the (potentially new) roles
		$length = sizeof($roles);
		if ($length > 0) {
			for ($i = 0; $i < $length; $i++) {
				$this->addRole($userId, $roles[$i]);
			}
		}
	}

	/**
	 * Returns an array of Users or a single User based on username.
	 *
	 * @param {String} username
	 *
	 * @return {User} user
	 */
	public function read($username = null) {
		try {

			// return all users if no username is specified
			if ($username === null) {
				$s = $this->db->prepare($this::SELECT);
				$s->execute();
				$users = array();
				while ($row = $s->fetch()) {
					array_push($users, new User($row['name'], $row['username'],
							$row['email'], $row['password'], $row['last_login'],
							$row['enabled'], $row['default_observatory_id'],
							$this->getRoles($row['ID'])));
				}
				return $users;
			} else {
				$s = $this->db->prepare($this::SELECT_USERNAME);
				$s->execute(array('username' => $username));
				while ($row = $s->fetch()) {
					return new User($row['name'], $row['username'], $row['email'],
							$row['password'], $row['last_login'], $row['enabled'],
							$row['default_observatory_id'], $this->getRoles($row['ID']));
				}
			}
		} catch (PDOException $error) {
			$this->error = $error->getMessage();
			return null;
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
			$enabled = $user->enabled;
			if ($enabled === null) {
				$enabled = 'Y';
			}
			if ($emailId === null || $emailId === $id) {
				try {
					$s = $this->db->prepare($this::UPDATE);
					$s->execute(array(
						'name' => $user->name,
						'username' => $user->username,
						'email' => $email,
						'lastLogin' => $user->lastLogin,
						'enabled' => $enabled,
						'defaultObservatoryId' => $user->defaultObservatoryId,
						'id' => $id
					));
					$this->addRoles($id, $user->roles);
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
			$s = $this->db->prepare($this::RESET_ROLES);
			$s->execute(array('user_id' => $this->getIdByUsername($username)));
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
				return $row['ID'];
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
				return $row['ID'];
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
			$s = $this->db->prepare($this::RESET_ALL_ROLES);
			$s->execute();
		} catch (PDOException $error) {
			$this->error = $error->getMessage();
			return false;
		}
		return true;
	}
}
