<?php

/**
 * Prepares and sends the cURL request to the API endpoint
 * @param  string $username The Packal Username
 * @param  string $key      The API Key
 * @param  string $bundle   The BundleID
 * @param  file   $workflow The workflow file path... !!!FULL PATH!!!
 * @return array           	An array of headers, errors, and content response
 */
function curl_request( $username, $key, $bundle, $workflow) {

	// Create the curl object at our endpoint
	$ch = curl_init("https://apidev.packal.org");

	// Create an array of options
	$options = array(
				CURLOPT_HEADER => false,
				CURLOPT_POST => true,
				CURLOPT_FRESH_CONNECT => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
				CURLOPT_POST => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_USERAGENT => "push-update-workflow-packal",
				CURLOPT_AUTOREFERER => true,
				CURLOPT_VERBOSE => true,
				CURLOPT_TIMEOUT => 90,
				CURLOPT_RETURNTRANSFER => true,



				);




	// Look into CURLOPT_PROGRESSFUNCTION for possible notifications.

	// Create post fields
	$data = array(
			// This is a full path to a file, denoted by the @
			'workflow' => "@$workflow",
			'username' => $username,
			'key'      => $key,
			'bundle'   => $bundle
			);

	/**
	 *
	 * I'm not positive that the above way to send the file will work.
	 * If it doesn't, here is another method.
	 *
	 * $file = file_get_contents($workflow);
	 * $size = filesize($workflow);
	 *
	 * curl_setopt($ch, CURLOPT_INFILE, $file);
	 * curl_setopt($ch, CURLOPT_INFILESIZE, $size);
	 *
	 * replace the appropriate line in the $data array:
	 * 	'workflow' => $file,
	 *
	 */


	// Set the options
	curl_setopt_array ( $ch, $options );
	// Set the post data
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	// Execute the curl request
	$content 	= curl_exec($ch);
	// Grab any error
	$err 		= curl_errno ( $ch );
	// Grab any error message
	$errmsg 	= curl_error ( $ch );
	// Grab the headers
	$header 	= curl_getinfo ( $ch );
	// Grab the HTTP Code
	$httpCode 	= curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
	// Close the curl request
	curl_close($ch);

	// Package everything into an array
	$header 			= array();
	$header['errno'] 	= $err;
	$header['errmsg'] 	= $errmsg;
	$header['content'] 	= $content;


	return $header['content'];

}