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

// Set the user's home directory
$HOME = exec( 'echo $HOME' );
// The location of the data directory
$data = "$HOME/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice/usage";

// Check if the usage directory exists; make it if it doesn't.
if ( ! file_exists( $data ) ) {
	mkdir( $data );
}

// Create the filename
$date = date( 'Y-W' , time() );
$file = $data . '/' . $date . "-data.json";

// This means that we've already generated and sent the file. So, let's just stop now.
// if ( file_exists( $file ) ) {
// 	return FALSE;
// }

$unique = hash( "sha256", exec( 'ioreg -rd1 -c IOPlatformExpertDevice | awk \'/IOPlatformUUID/ { split($0, line, "\""); printf("%s\n", line[4]); }\'' ) );

$me = substr( __DIR__, strrpos( __DIR__, '/' ) + 1 );

$workflows = array_diff( scandir( '../' ) , array( '.', '..', '.DS_Store' ) );

foreach ( $workflows as $w ) :
	$t = getWorkflowData( dirname( __DIR__ ) . "/$w" );
if ( ! empty( $t ) )
	$report[] = $t;
endforeach;

// Total number of workflows installed.
$total = count( $report );

// We'll send the post array to server. Here's the identifying information for the server
$post = array();
$post[ 'meta' ] = array( 'unique' => $unique , 'date' => $date , 'total' => $total );


// Add the Workflows to the post array
$post[ 'workflows' ] = $report;
$json = json_encode( $post );
file_put_contents( $file , $json );
$data = array( 'info' => $json, 'unique' => $unique );

$ch = curl_init();
curl_setopt( $ch, CURLOPT_URL, 'http://api.packal.org/index.php' );
curl_setopt( $ch, CURLOPT_USERAGENT, 'packal-workflow-' . hash( 'sha256', $unique ) );
curl_setopt( $ch, CURLOPT_POST, 1 );
curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
curl_setopt( $ch, CURLOPT_TIMEOUT, 15  );
// curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
$header = curl_getinfo ( $ch );
// $result = curl_exec( $ch );
echo curl_exec( $ch );
print_r( curl_error( $ch ) );
curl_close($ch);
die();
if ( $result != ( 'Request Received and is valid. Signed: ' ) . hash( 'sha256', 'valid' ) )
	unlink( $file );

print_r( $result );

function getWorkflowData( $dir ) {
	// We'll just use Plist Buddy to get everything from the directory.
	$plist = "$dir/info.plist";
	$PlistBuddy = "/usr/libexec/PlistBuddy -c ";

	$bundle = exec( $PlistBuddy . "'print :bundleid' $plist 2> /dev/null" );

	if ( ! $bundle ) {
		return FALSE;
	}

	// Get the Workflow name
	$name = exec( $PlistBuddy . "'print :name' $plist 2> /dev/null" );

	// Figure out if it's disabled.
	$disabled = exec( $PlistBuddy . "'print :disabled' $plist 2> /dev/null" );

	// Strangely, sometimes these weren't set on my computer, so, well, that's strange.
	// My guess is that they were older workflows, so maybe the problems was older plist
	// structures from way-back Alfred 2.
	if ( empty( $disabled ) ) $disabled = 'TRUE';
	$disabled = strtoupper( $disabled );
	if ( file_exists( $dir . '/packal/package.xml' ) )
		$packal = 'TRUE';
	else
		$packal = 'FALSE';

	return array( 'name' => $name , 'bundle' => $bundle , 'disabled' => $disabled, 'packal' => $packal );

}
