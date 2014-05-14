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
require_once '../resources/includes/date-and-time.php';


// Set the user's home directory
$home = exec( 'echo $HOME' );
// The location of the data directory
$data = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice/usage";

// Create the filename
$date = date( 'Y-W' , time() );
$file = $date . "-data.json";

// Check if the usage directory exists; make it if it doesn't.
if ( ! file_exists( $data ) ) {
	mkdir( $data );
}

// This is the blacklist file
$file = $data . "/$file";

// This means that we've already generated and sent the file. So, let's just stop now.
// if ( file_exists($file) ) {
//  echo "0";
//  return false;
// }

// Let's get a unique identifer. This is just a one line bash script
// because it's complex and the escaping it was a bitch.
$unique = exec( "sh get-identifier.sh" );

// And let's hash it to make it harder to read.
$unique = hash( "sha256", $unique );

// So, this script should be in the directory "scripts" above the regular
// workflow folder, so we'll just scan two directories down.
$dir = scandir( "../../" );
unset( $dir[0] ); // These are just . & ..
unset( $dir[1] );
if ( in_array( '.DS_Store', $dir ) ) {
	unset( $dir[array_search( '.DS_Store', $dir )] );

}

// Total number of workflows installed.
$total = count( $dir );

// We'll send the post array to server. Here's the identifying information for the server
$post = array();
$post['meta'] = array( 'unique' => $unique , 'date' => $date , 'total' => $total );

// Let's get all the workflow information now.
$workflows = array();
foreach ( $dir as $d ) {
	$tmp = getWorkflowData( $d );
	if ( $tmp ) {
		$workflows[] = $tmp;
	}
}

// Add the Workflows to the post array
$post['workflows'] = $workflows;
// Encode it
$json = json_encode( $post );

file_put_contents( $file , $json );

// Let's encode it.
$json = urlencode( $json );
$data_string = $json;

// This needs to be changed to the final end point.
$ch = curl_init( 'http://packal.dev/response.php' );
curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen( $data_string ) )
);

$result = curl_exec( $ch );

// Now, I just need to code something that will send a response from the server.
// If there is a positive response, then we keep the file. If not, then we delete
// the file. Also, this file should be called only if there is an active internet
// connection.

function getWorkflowData( $dir ) {
	// We'll just use Plist Buddy to get everything from the directory.
	$plist = "../../$dir/info.plist";
	$PlistBuddy = "/usr/libexec/PlistBuddy -c ";

	$cmd = $PlistBuddy . "'print :bundleid' $plist";
	$bundle = exec( $cmd );

	if ( ! $bundle ) {
		return false;
	}

	// Get the Workflow name
	$cmd = $PlistBuddy . "'print :name' $plist";
	$name = exec( $cmd );

	// Figure out if it's disabled.
	$cmd = $PlistBuddy . "'print :disabled' $plist";
	$disabled = exec( $cmd );

	// Strangely, sometimes these weren't set on my computer, so, well, that's strange.
	// They were older workflows, so maybe it was an older settings from way-back Alfred.
	if ( empty( $disabled ) ) $disabled = "true";

	return array( 'name' => $name , 'bundle' => $bundle , 'disabled' => $disabled );

}
