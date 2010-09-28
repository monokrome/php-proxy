<?php
	/**
	 * Simple PHP Proxy; Brandon R. Stoner <monokrome@monokro.me>
	 *
	 * TODO: This currently doesn't return proper HTTP response codes. It always returns 200.
	 */

	// Allows us to see all errors, notice, warnings, and obscurities
	error_reporting(E_ALL | E_STRICT);

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
	$transferable_headers = require('headers.php');
	$url_expressions = require('expressions.php');

	$url_accepted    = false; // Has our URL passed an expression test?

	// Loop through our expressions searching for a match
	for ($i=0; $i < count($url_expressions); ++$i)
		if (preg_match($url_expressions[$i], $_GET['destination']))
		{
			$url_accepted = true;
			break;
		}

	// This URL is not trusted, so exit from this process.
	if ($url_accepted == false)
		exit(UNTRUSTED_URL);

	if (isset($_SERVER['HTTP_REFERER']))
		$request_headers = Array(
			'X-Forward-For: ' . $_SERVER['HTTP_REFERER'],
		);
	else
		$request_headers = Array();

	$curl_opts = Array(
		CURLOPT_AUTOREFERER => true,
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

	if ($response === false)
		exit(CURL_EXEC_FAILURE);

	// Get the size of our header, so we know where to split the content from it.
	$header_size = curl_getinfo($curl_descriptor, CURLINFO_HEADER_SIZE);
	curl_close  ($curl_descriptor);

	// Place the received headers into an array and remove the original HTTP header
	$headers = explode("\n", substr($response, 0, $header_size));

	// TODO: Make this a bit less insane/obstrusive
	foreach ($headers as $header)
	{
		$matches = Array();

		if (preg_match('/^([^:]+)\:\s*(.+)$/i', $header, $matches))
		{
			if (in_array($matches[1], $transferable_headers))
			{
				header($header);
			}
		}
	}

	print substr($response, $header_size, strlen($response)-$header_size);
?>
