<?php

/**
 * Main authentication method. Finds the DN for the given username and then
 * authenticates against the DOI AD servers using the found DN and given
 * password.
 *
 * Note the difference between returning false from this method and throwing an
 * exception. A return value of boolean "false" implies network problems
 * contacting the AD server. A raised exception implies bad user credentials.
 *
 * @return True if credentials validate against the DOI AD servers. False if a
 * connection can not be established.
 *
 * @throws Exception if the supplied credentials are not valid.
 */
function ad_authenticate($username, $password) {

	$dn = '';
	try { $dn = _ad_get_dn($username); }
	catch (Exception $ex) {throw new Exception("Default connection failed.");}

	return _ad_connect($dn, $password);
}

/**
 * HELPER FUNCTION
 *
 * Finds the DN for the given $email. The DN is what we will use to authenticate
 * the user.
 *
 * @param username {String} The user sAMAccountName.
 * @throws Exception If the email does not return exactly one user DN.
 */
function _ad_get_dn($username) {

	// This might throw an exception. We will allow it to be thrown
	$connect = _ad_connect();

	if (!$connect) {
		print "Could not connect to AD authentication.";
		return;
	};

	$result_dn = _ad_search($connect, "sAMAccountName=${username}", array('dn'));

	if (count($result_dn) < 1) {
		// No matches. Fail.
		throw new Exception("No results found for ${username}.\n");
	} else if (count($result_dn) > 1) {
		// Too many matches. Fail.
		throw new Exception("Search for ${username} returned ambiguous matches.\n");
	} else {
		return $result_dn[0]['dn'];
	}
}

/**
 * HELPER FUNCTION
 *
 * Attempts to connect to known active directory servers over SSL using the
 * provided $username and $password (or their defaults if not provided).
 *
 * If the provided credentials are not valid, an exception is thrown.
 *
 * If a connection cannot be established due to network problems, false is
 * returned.
 *
 * If a connection is made successfully, the AD (LDAP) connection is
 * returned.
 *
 * @param username {String} The AD username credential.
 * @param password {String} The AD password credential.
 *
 * @return A valid connection or false if a connection could not be established.
 * 
 * @throws Exception if the given username and password fail to authenticate.
 */
function _ad_connect($username = false, $password = false) {

	global $CONFIG;

	if ($username == '' || $password == '') {
		// Use defaults instead.
		$username = $CONFIG['AD_DEFAULT_USERNAME'];
		$password = $CONFIG['AD_DEFAULT_PASSWORD'];
	}

	$adservers = str_getcsv($CONFIG['AD_SERVERS']);

	foreach ($adservers as $server) {
		// This NEVER returns false, even if the server can't be reached.
		$connect = ldap_connect("ldaps://${server}");

		// Set some options on the connection
		ldap_set_option($connect, LDAP_OPT_TIMELIMIT, 5); // 5 second timeout
		ldap_set_option($connect, LDAP_OPT_REFERRALS, 0); // Don't accept referral

		if (!$connect || !@ldap_bind($connect, $username, $password)) {
			// Check the error codes
			$number = ldap_errno($connect);
			$error  = ldap_error($connect);

			if ($number == 49) {
				// 49 == Invalid credentials. Don't keep trying.
				// If we tried three times, the user would immediately get locked
				// out of their account. That seems less than ideal.
				throw new Exception(
						"Active Directory Connection Error\n" .
						"(${number}) :: ${error}"
					);
			}
		} else {
			// Connection succeeded. Return it.
			return $connect;
		}
	}

	// If we got here, all attempts failed; but not b/c of credentials.
	return false;
}

/**
 * HELPER FUNCTION
 *
 * @param connect {LDAPResource} The connected and bound LDAP connection.
 * @param filter {String} An LDAP query string
 * @param fields {Array} An array of fields to fetch from LDAP. If not
 *      specified, defaults to all fields.
 * 
 * @return An array of results where each result contains the values for the
 *      requested fields.
 */
function _ad_search($connect, $filter, $fields = array('*')) {
	$results = array(); 
	$base_dn = 'DC=gs,DC=doi,DC=net';
	$ldap_results = ldap_search(array($connect), array($base_dn), $filter, $fields);

	// 09/26/12 -- EMM: Not sure why we have to search by arrays, documentation
	//                  says a single search (using a String) should work as
	//                  well, but in practice this will cause an error.
	//$ldap_result = ldap_search($connect, $base_dn, $filter, $fields);

	// Loop over results from each of the Base DN scopes
	/**/foreach($ldap_results as $ldap_result) { /**/
		$ldap_entries = ldap_get_entries($connect, $ldap_result);
		if (intval($ldap_entries['count']) != 0) {
			// Matching result found in this Base DN scope (region)
			array_push($results, _ad_build_results($ldap_entries));
		}
	/**/}/**/

	return $results;
}

/** 
 * HELPER FUNCTION
 * 
 * Recursively flattens the default LDAP result set arrays. The default
 * structure is bloated with indexing etc... that is not needed in PHP.
 *
 * @param ldap {Array} An LDAP array structure to flatten.
 *
 * @return The flattened array more commonly used in PHP.
 */
function _ad_build_results($ldap) {
	$result = array();

	// Flatten all our results so they are less obtuse.
	for ($i = 0; $i < intval($ldap['count']); ++$i) {
		$key = $ldap[$i];

		if (is_array($key)) {
			$result = _ad_build_results($key);
		} else {
			$value = $ldap[$key];

			if (is_array($value)) {
				$result[$key] = _ad_build_results($value);
			} else {
				$result[$key] = $value;
			}
		}
	}

	// Special case. DN is always returned but not indexed.
	if (isset($ldap['dn'])) { $result['dn'] = $ldap['dn']; }

	return $result;
}