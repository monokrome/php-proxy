<?php
	// Reported when an invalid request method is used over the proxy
	define('HTTP_METHOD_ERROR',
	       'The requested method is not supported by this proxy. ' .
	       'Use GET instead. POST is also supported, but should be ' .
	       'avoided (to conform with the HTTP standard) unless your ' .
	       'destination string is too long to be sent over a GET.');

	// Reported when a "destination" parameter hasn't been provided
	define('NO_DESTINATION',
	       'You must provide a "destination" parementer in your HTTP request.');

	// Reported when an untrusted URL is provided to the proxy
	define('UNTRUSTED_URL',
	       'The provided URL was not in our list of trusted sources ' .
				 'for proxy queries. Check that your expression is right, ' .
				 'and try again.');

	// When curl_exec fails
	define('CURL_EXEC_FAILURE',
	       'A failure was encounted when attempting the curl request.');
?>
