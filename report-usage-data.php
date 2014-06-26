<?php
/**
 * This file is called via ajax; it receives no data but does some processing on the
 * workflows' info.plist files and then sends a json file via a post request to
 * the server. Then, if we get a response, we keep the json file on the user's system.
 * When the script is invoked, then we see if the file already exists. If so, exit. Else,
 * send the request via curl.
 *
 * I don't have to worry about the reporting config option in this file because that is
 * taken care of before we call the file.
 *
 */


// Since we're going to use date functions, we'll just make sure that we set the date
// to avoid any dumb warnings that php will give us.
// Set date/time to avoid warnings/errors.
if ( ! ini_get('date.timezone') ) {
	$tz = exec( 'tz=`ls -l /etc/localtime` && echo ${tz#*/zoneinfo/}' );
	ini_set( 'date.timezone', $tz );
}

$bundle = 'com.packal';

// Set the user's home directory
$HOME = exec( 'echo $HOME' );
// The location of the data directory
$data = "$HOME/Library/Application Support/Alfred 2/Workflow Data/$bundle";

// If the data directory doesn't exist, then we'll just wait for another time.
if ( ! file_exists( $data ) )
	die();


// If the config file doesn't exist, then we'll just wait for another time.
if ( ! file_exists( "$data/config/config.xml" ) )
	die();

// Load the config
$config = simplexml_load_file( "$data/config/config.xml" );

// Check if workflow reporting is enabled. If not. Die.
if ( ! ( isset( $config->workflowReporting ) && ( (string) $config->workflowReporting == '1' ) ) )
	die();

// Make sure that we have an Internet connection before continuing.
if ( checkConnection() == FALSE )
	die();

// Check if the usage directory exists; make it if it doesn't.
if ( ! file_exists( "$data/usage" ) ) {
	mkdir( "$data/usage" );
}

// This is your unique identifier. If you're reading this, just run the command and see what's up.
$unique = hash( "sha256", 
	exec( 'ioreg -rd1 -c IOPlatformExpertDevice | awk \'/IOPlatformUUID/ { split($0, line, "\""); printf("%s\n", line[4]); }\'' ) );

$unique = utf8_encode( $unique );

// Create the filename
$date = date( 'Y-W' , time() );
$file = $data . '/usage/' . $date . "-data-$unique.json";

// This means that we've already generated and sent the file. So, let's just stop now.
if ( file_exists( $file ) ) {
	echo "Already submitted this week.";
	die();
}


// $me = substr( __DIR__, strrpos( __DIR__, '/' ) + 1 );

// If the endpoints file does not exist, then we're calling this too early. Just wait a bit.
if ( ! file_exists( "$data/endpoints/endpoints.json" ) )
	die();

$workflows = json_decode( file_get_contents( "$data/endpoints/endpoints.json" ) );

foreach ( $workflows as $dir ) :
	$w = getWorkflowData( $dir );
	if ( is_array( $w ) )
		$report[] = $w;
endforeach;


// Total number of workflows installed.
$total = count( $report );

// We'll send the post array to server. Here's the identifying information for the server
$post = array();
$post[ 'meta' ] = array( 'date' => $date , 'total' => $total );

// Add the Workflows to the post array
$post[ 'workflows' ] = $report;
$json = utf8_encode( json_encode( $post ) );

$data = array( 'info' => $json, 'unique' => $unique, 'task' => 'reporting', 'date' => $date );

$ch = curl_init();
curl_setopt( $ch, CURLOPT_URL, 'http://api.packal.org' );
curl_setopt( $ch, CURLOPT_USERAGENT, 'packal-workflow-' . hash( 'sha256', $unique ) );
curl_setopt( $ch, CURLOPT_POST, 1 );
curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
curl_setopt( $ch, CURLOPT_TIMEOUT, 15  );
// curl_setopt( $ch, CURLOPT_HEADER, 1 );
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
curl_setopt( $ch, CURLOPT_ENCODING, 'gzip' );

$header = curl_getinfo ( $ch );
$result = curl_exec( $ch );
curl_close($ch);

if ( $result == "It's all good." ) {
	// It went through. Yay!
	if ( file_put_contents( $file , $json ) !== FALSE ) {
		// Recorded the file, so let's move on.
		die();
	} else {
		echo "We ran into an error when recording the file.";
		die();
	}
}

echo "Error: request not accepted.";
// We're done.
die();

// Some helpful functions.

function getWorkflowData( $dir ) {
	$plist = "$dir/info.plist";	
	if ( ! file_exists( $plist ) )
		return FALSE;
	
	$bundle = utf8_encode( trim( readPlistValue( 'bundleid', $plist ) ) );
	
	if ( ! $bundle )
		return FALSE;

	// Get the Workflow name
	$name = utf8_encode( trim( readPlistValue( 'name', $plist ) ) );

	// Get the author
	$author = utf8_encode( trim( readPlistValue( 'createdby', $plist ) ) );

	// Figure out if it's disabled.
	$disabled = utf8_encode( readPlistValue( 'disabled', $plist ) );

	// Strangely, sometimes these weren't set on my computer, so, well, that's strange.
	// My guess is that they were older workflows, so maybe the problems was older plist
	// structures from way-back Alfred 2.
	if ( empty( $disabled ) ) $disabled = 'TRUE';
		$disabled = strtoupper( $disabled );
	if ( file_exists( $dir . '/packal/package.xml' ) )
		$packal = 'TRUE';
	else
		$packal = 'FALSE';

	return array( 'name' => $name , 'bundle' => $bundle , 'author' => $author, 'disabled' => $disabled, 'packal' => $packal );

}

function checkConnection() { 
	ini_set( 'default_socket_timeout', 1);

	// First test
	exec( "ping -c 1 -t 1 www.google.com", $pingResponse, $pingError);
	if ( $pingError == 14 )
		return FALSE;

	// Second Test
    $connection = @fsockopen("www.google.com", 80, $errno, $errstr, 1);

    if ( $connection ) { 
        $status = TRUE;  
        fclose( $connection );
    } else {
        $status = FALSE;
    }
    return $status; 
}

function readPlistValue( $key, $plist ) {
  return exec( "/usr/libexec/PlistBuddy -c \"Print :$key\" '$plist' 2> /dev/null" );
}