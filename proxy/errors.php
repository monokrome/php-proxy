<?php
	class Error extends Exception
	{
		protected $response = 'HTTP/1.1 200 OK';

		function Error()
		{
			header($this->response);
		}
	}

	/**
	 * Reported when an invalid request method is used over the proxy
	 */
	class HTTPMethodError extends Error
	{
		protected $message = 
			'The requested method is not supported by this proxy. 
			Use GET instead. POST is also supported, but should be 
			avoided (to conform with the HTTP standard) unless your 
			destination string is too long to be sent over a GET.';

		protected $response = 'HTTP/1.1 403 Forbidden';

		public function __construct($message = null, $code = 0, Exception $previous = null)
		{
			super::__construct($message, $code, $previous);

			header($this->response);
		}
	}

	// Reported when a "destination" parameter hasn't been provided
	class NoDestinationError extends Error
	{
			protected $message =
				'You must provide a "destination" parementer in your HTTP request.';

			protected $response = 'HTTP/1.1 400 Bad Request';
	}

	// Reported when an untrusted URL is provided to the proxy
	class UntrustedURLError extends Error
	{
		protected $message =
			'The provided URL was not in our list of trusted sources 
			for proxy queries. Check that your expression is right, 
			and try again.';

		protected $response = 'HTTP/1.1 400 Bad Request';
	}

	// When curl_exec fails
	class CurlExecFailureError extends Error
	{
		protected $message =
			'A failure was encounted when attempting the curl request.';

		protected $response ='HTTP/1.1 400 Bad Request';
	}
?>
