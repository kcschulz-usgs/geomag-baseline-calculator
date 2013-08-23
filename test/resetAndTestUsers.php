<?php

include_once '../conf/config.inc.php';
include_once '../lib/classes/UserFactory.php';

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
$user->setName('Name');
$user->setUsername('Username');
$user->setEmail('Email');
$user->setPassword('Password');
if ($users->create($user)) {
	print SUCCESS;
} else {
	die('failure with create()... ' . $users->getError());
}

print 'update user name: ';
$user->setName('newName');
$id = $users->getIdByUsername('Username');
if ($id === null) {
	die('user id is null');
}
if ($users->update($id, $user)) {
	if ($users->read('Username')->getName() === 'newName') {
		print SUCCESS;
	} else {
		die('failure with update()... ' . $users->getError());
	}
} else {
	die('failure with update()... ' . $users->getError());
}

print 'fail to create user with duplicate email: ';
$user2 = new User();
$user2->setName('Name2');
$user2->setUsername('Username2');
$user2->setEmail('Email');
$user2->setPassword('Password2');
if ($users->create($user2)) {
	die('created user');
} else {
	print SUCCESS;
}

print 'fail to update user with duplicate email: ';
$user2->setEmail('Email2');
$users->create($user2);
$user2->setEmail('Email');
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

$user->setName('Admin Account');
$user->setUsername('Admin');
$user->setEmail('admin@usgs.gov');
$users->create($user);

$user->setName('Member Account');
$user->setUsername('Member');
$user->setEmail('member@email.com');
$user->setPassword('password');
$users->create($user);