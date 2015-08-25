<?php

namespace CFPropertyList;

/**
 * Require CFPropertyList
 */
require_once __DIR__.'/Libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php';
// require_once(__DIR__.'/check-server-status.php');
/**
 * Require David Ferguson's Workflows class
 */
require_once 'Libraries/workflows.php';

// Avoid collisions
if ( ! isset( $push ) ) {
	// Escape the new Workflow object so it doesn't collide withthe CFPropertyList namespace
	$push = new \Workflows();
}

// Set the cache directory
$cache = $push->cache();

// Set the data directory
$data  = $push->data();

// The three following if-statements are basically a first-run script
if ( ! file_exists( $cache ) ) {
	mkdir( $cache );
}
if ( ! file_exists( $data ) ) {
	mkdir( $data );
}
if ( ! file_exists( $data . "/settings.json" ) ) {
	exec( 'osascript settings.scpt' );
}

$data = '/Users/Sven/Library/Application Support/Alfred 2/Workflow Data/com.push.workflow.update.packal';

$json = file_get_contents( $data . "/settings.json" );
$tmp  = json_decode( $json, TRUE );
// The Workflow author's Packal username.
$user  = trim( $tmp['username'] );
// The Packal API Key.
$key  = trim( $tmp['key'] );
// The name that the Workflow author puts into the "Created By" field in the workflow.
$name   = trim( $tmp['name'] );
unset( $tmp );

$workflows = scandir( realpath( dirname( __DIR__ ) ) );

// Ignore these files
$ignore = array( '.' , '..' , '.DS_Store' );
// Initialize an array
$w = array();

/**
 * Cycles through all the workflow directories and reads the plists into an array.
 */
foreach ( $workflows as $dir ) {

	if ( ! is_dir( '../' . $dir ) || ( in_array( $dir , $ignore ) ) ) {
		continue;
	}

	$values = readPlist( '../' . $dir . '/info.plist' );

	$w[$values['name']] = $values;
	$w[$values['name']]['dir'] = $dir;
}

// Sort the workflows by workflow name.
// The SORT_CASE_FLAG is available starting in 5.4, so run the workflow without
// it in OS X Mountain Lion or lesser.
if ( PHP_MINOR_VERSION >= 4 ) {
	ksort( $w , SORT_NATURAL | SORT_FLAG_CASE );
} else {
	ksort( $w , SORT_NATURAL );
}

// Let's cycle through the plist array constructed above
foreach ( $w as $workflow ) {
	// Cut out anything that doesn't match the user
	if ( $workflow['createdby'] == $name ) {
		$push->result( $workflow['bundleid'], $workflow[$workflow['name']]['directory'], $workflow['name'], $workflow['createdby'], '', 'yes', 'autocomplete' );
	}
}

// Send some XML to Alfred
echo $push->toxml();

/**
 * Reads and parses a plist
 *
 * @param string  $plist a plist file as a string
 * @return array         an array of workflow metadata from the plist
 */
function readPlist( $plist ) {
	if ( ! file_exists( $plist ) ) {
		return 1; // Error Code #1 is info file doesn't exist
	}
	// The files exist.

	// Construct the workflow plist objects
	$workflow = new CFPropertyList( $plist );

	// Declare an array to store the data about the info plist in.
	$info = array();

	// Convert plist object to usable array for processing
	$tmp = $workflow->toArray();

	$info['bundleid']   = $tmp['bundleid'];
	$info['name']    = $tmp['name'];
	$info['createdby']   = $tmp['createdby'];
	$info['disabled']   = $tmp['disabled'];
	$info['readme']   = $tmp['readme'];
	$info['webaddress']  = $tmp['webaddress'];
	$info['description'] = $tmp['description'];

	return $info;

}


/**
 * Prepares and sends the cURL request to the API endpoint
 *
 * @param string  $username The Packal Username
 * @param string  $key      The API Key
 * @param string  $bundle   The BundleID
 * @param file    $workflow The workflow file path... !!!FULL PATH!!!
 * @return array            An array of headers, errors, and content response
 */
function curl_request( $username, $key, $bundle, $workflow ) {

	// Create the curl object at our endpoint
	$ch = curl_init( "https://apidev.packal.org" );

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
	 *  'workflow' => $file,
	 *
	 */


	// Set the options
	curl_setopt_array( $ch, $options );
	// Set the post data
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

	// Execute the curl request
	$content  = curl_exec( $ch );
	// Grab any error
	$err   = curl_errno( $ch );
	// Grab any error message
	$errmsg  = curl_error( $ch );
	// Grab the headers
	$header  = curl_getinfo( $ch );
	// Grab the HTTP Code
	$httpCode  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	// Close the curl request
	curl_close( $ch );

	// Package everything into an array
	$header    = array();
	$header['errno']  = $err;
	$header['errmsg']  = $errmsg;
	$header['content']  = $content;


	return $header['content'];

}

?>
