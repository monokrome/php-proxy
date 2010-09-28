<?php
	/**
	 * Simple PHP Proxy; Brandon R. Stoner <monokrome@monokro.me>
	 *
	 * TODO: This currently doesn't return proper HTTP response codes. It always returns 200.
	 */

	// Allows us to see all errors, notice, warnings, and obscurities
	error_reporting(E_STRICT);

	// Defines a bunch of errors that this file uses.
	require_once('errors.php');

	// Check that we are using a supported HTTP method
	if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST')
		$server_url = $_POST['destination'] or exit(NO_DESTINATION);

	elseif (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET')
		$server_url = $_GET['destination'] or exit(NO_DESTINATION);

	else
		exit(HTTP_METHOD_ERROR);

	// Gets the array of useful expressions from expressions.php
	$url_expressions = require('expressions.php');
	$url_accepted    = false; // Has our URL passed an expression test?

	// Loop through our expressions searching for a match
	for ($i=0; $i < count($url_expressions); ++$i)
		if ($url_accepted == true || preg_match($url_expressions[$i], $_GET['destination']))
		{
			$url_accepted = true;
			break;
		}

	// This URL is not trusted, so exit from this process.
	if ($url_accepted == false)
		exit(UNTRUSTED_URL);

	// Spoof the referrer so that this doesn't behave as an "anonymous" proxy
	if (isset($_SERVER['HTTP_REFERER']))
		$request_headers = Array(
			'X-Forward-For: ' . $_SERVER['HTTP_REFERRER'],
		);
	else
		$request_headers = Array();

	$curl_opts = Array(
		CURALOPT_AUTOREFERRER => true,
		CURLOPT_HEADER => true,
		CURLOPT_HTTPHEADER => $request_headers,
		CURLOPT_FORBID_REUSE => true,
		CURLOPT_FRESH_CONNECT => true, // This is probably a bit redundant with FORBID_REUSE, but better safe than sorry ;)
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_SSL_VERIFYPEER => true, // Set to true to set it on older curl versions
		CURLOPT_UNRESTRICTED_AUTH => false,
		CURLOPT_SSL_VERIFYHOST => 2,
	);

	$curl_descriptor = curl_init($server_url);
	curl_setopt_array($curl_descriptor, $curl_opts);

	$response = curl_exec($curl_descriptor);

	if ($success === false)
		exit(CURL_EXEC_FAILURE);

	curl_close  ($curl_descriptor);
?>
