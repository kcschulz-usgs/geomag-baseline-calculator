<?php

include_once '../src/conf/config.inc.php';
include_once '../src/lib/classes/UserFactory.php';

$users = new UserFactory($DB);

define ('SUCCESS', '<span style="color:lightgray;">success!</span><br />');

print 'reseting database: ';
if ($users->reset()) {
	print SUCCESS;
} else {
	die('failure with reset()...' . $users->getError());
}

print 'verifying empty database: ';
$userList = $users->read();
if (sizeof($userList) === 0) {
	print SUCCESS;
} else {
	die('failure with read()... ' . $users->getError());
}

print 'fail to create invalid user: ';
$user = new User();
if ($users->create($user)) {
	die('invalid user created');
} else {
	print SUCCESS;
}

print 'create valid user: ';
$user->name = 'Name';
$user->username = 'Username';
$user->email = 'Email';
$user->password = 'Password';
if ($users->create($user)) {
	print SUCCESS;
} else {
	die('failure with create()... ' . $users->getError());
}

print 'update user name: ';
$user->name = 'newName';
$id = $users->getIdByUsername('Username');
if ($id === null) {
	die('user id is null');
}
if ($users->update($id, $user)) {
	if ($users->read('Username')->name === 'newName') {
		print SUCCESS;
	} else {
		die('failure with update()... ' . $users->getError());
	}
} else {
	die('failure with update()... ' . $users->getError());
}

print 'fail to create user with duplicate email: ';
$user2 = new User();
$user2->name = 'Name2';
$user2->username = 'Username2';
$user2->email = 'Email';
$user2->password = 'Password2';
if ($users->create($user2)) {
	die('created user');
} else {
	print SUCCESS;
}

print 'fail to update user with duplicate email: ';
$user2->email = 'Email2';
$users->create($user2);
$user2->email = 'Email';
$id = $users->getIdByUsername('Username2');
if ($users->update($id, $user2)) {
	die('updated user');
} else {
	print SUCCESS;
}

print 'delete user: ';
if ($users->delete('Username2')) {
	if ($users->getIdByUsername('Username2') === null) {
		print SUCCESS;
	} else {
		die('user still exists');
	}
} else {
	die('failure with delete()... ' . $users->getError());
}

$user->name = 'Admin Account';
$user->username = 'Admin';
$user->email = 'admin@usgs.gov';
$user->roles = [1, 2];
$users->create($user);

$user->name = 'Member Account';
$user->username = 'Member';
$user->email = 'member@email.com';
$user->password = 'password';
$user->roles = [1];
$users->create($user);