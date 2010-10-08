<?php
	/**
	 * This is a proxy to be used for requesting data from external APIs
	 * across domains. This is a secure method of "dodging" the same-origin
	 * security policy, but you should only use it in the case that you
	 * understand the reasons that this policy exists in the first place. If
	 * you don't understand why the same origin policy is in place, you will
	 * most likely break the system's security completely.
	 *
	 * You should never use a proxy unless completely necessary, because you
	 * are increasing server overhead as well as risking security issues in the
	 * case that you don't understand why the same-origin policy exists in the
	 * first place.
	 *
	 * This uses an array of regex strings to test that the URL being requested
	 * is an "allowed" URL or not. In that case that it is, a request is made
	 * from the server with this proxy and the result is sent back to the client
	 * along with any important headers that might need to be forwarded. This
	 * effectively allows you to receive data from other domain's APIs over an
	 * AJAX request.
	 *
	 * Please be cautious with what you allow, so that you don't end up with any
	 * XSS attacks regarding the use of this. Adding any regular expressions that
	 * aren't _thorughly_ tested should be considered a major security issue,
	 * and in doing so - you are basically asking attackers to have their fun
	 * on your systems.
	 *
	 * Simple PHP Proxy; Brandon R. Stoner <monokrome@monokro.me>
	 */

	try
	{
		// Allows us to see all errors, notice, warnings, and obscurities
		error_reporting(E_ALL | E_STRICT);

		// Defines a bunch of errors that this file uses.
		require_once('errors.php');

		// A standard set of methods for RESTful services.
		$allowed_request_methods = Array('GET', 'POST', 'PUT', 'DELETE');

		// Check that we are using a supported HTTP method
		if (in_array(strtoupper($_SERVER['REQUEST_METHOD']), $allowed_request_methods))

			if (isset($_REQUEST['destination']))
				$server_url = $_REQUEST['destination'];
			else
				throw new NoDestinationError();

		else
			throw new HTTPMethodError();

		// Remove this from the request array, because other data will be passed to the proxy.
		unset($_REQUEST['destination']);

		// Gets the array of useful expressions from expressions.php
		$transferable_headers = require('headers.php');
		$url_expressions = require('expressions.php');

		$url_accepted = false; // Has our URL passed an expression test?

		// Loop through our expressions searching for a match
		for ($i=0; $i < count($url_expressions); ++$i)
		if (preg_match($url_expressions[$i], $server_url))
		{
			$url_accepted = true;
			break;
		}

		// This URL is not trusted, so exit from this process.
		if ($url_accepted == false)
			throw new UntrustedURLError();

		// Since our URL was trusted, make sure the server know this was forwarded
		if (isset($_SERVER['HTTP_REFERER']))
			$request_headers = Array(
				'X-Forward-For: ' . $_SERVER['HTTP_REFERER'],
			);
		else
			$request_headers = Array();

		// Set up a sane set of default options for cURL to request with.
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
			CURLOPT_CUSTOMREQUEST => strtoupper($_SERVER['REQUEST_METHOD']),
			CURLOPT_POSTFIELDS => $_REQUEST,
		);

		// Initialize cURL, and provide it the options array that we just created.
		$curl_descriptor = curl_init($server_url);
		curl_setopt_array($curl_descriptor, $curl_opts);

		// Make a request over the cURL descriptor 
		$response = curl_exec($curl_descriptor);

		// When this occurs, cURL failed.
		if ($response === false)
			throw new CurlExecFailureError();

		// Get the size of our header, so we know where to split the content from it.
		$header_size = curl_getinfo($curl_descriptor, CURLINFO_HEADER_SIZE);
		curl_close  ($curl_descriptor);

		// Place the received headers into an array and remove the original HTTP header
		$headers = explode("\n", substr($response, 0, $header_size));

		header($header);

		// Print the final contents retreived from the cURL request, excluding headers
		print substr($response, $header_size, strlen($response)-$header_size);

	}
	catch (Error $e)
	{
		print $e->getMessage();
	}
?>
