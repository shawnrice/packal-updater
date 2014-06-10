<?php

/**
 *
 * This is the file that receives the input from the script filter and then starts everything.
 * 
 */
$bundle = 'com.packal';
require_once( 'functions.php' );
firstRun();

if ( ( ! isset( $argv[1] ) || empty( $argv[1] ) ) ) {
	echo "The controller needs at least one argument.";
	die();
}

$q = $argv[1];

$HOME = exec( 'echo $HOME' );

// Implement bundler.
// Mod to handle more args.

if ( $q == 'open-gui' ) {
	$dir = escapeshellcmd( exec( 'pwd' ) );
	$osx = exec( "sw_vers | grep 'ProductVersion:' | grep -o '10\.[0-9]*'" );

	// Start the webserver
	if ( strpos( $osx, '10.9' ) !== FALSE ) {
		// Since we're using Mavericks, we can just use the native php 5.4 binary.
		exec( "nohup php -S localhost:7893 -t gui/ > /dev/null 2>&1 &" );
	} else {
		// Not using Mavericks, so php 5.3 is installed.
		// For now, fuck it. I've tried to come up with too many workarounds.
		// No GUI for you.
		die();
	}
	
	// Start the webserver kill script
	exec( "nohup '$dir/check-and-kill-webserver.sh'  > /dev/null 2>&1 &" );

	// // Wait a second so that we can make sure that the webserver has started before we open the viewer.
	sleep(1);

	// Open the gui in the viewer
	exec( "nohup open '$dir/resources/applications/viewer.app' --args http://localhost:7893  > /dev/null 2>&1 &" );

} else if ( strpos( $q, 'update-' ) !== FALSE ) {
	$workflows = json_decode( file_get_contents( "$HOME/Library/Application Support/Alfred 2/Workflow Data/$bundle/endpoints/endpoints.json" ), TRUE );
	$wf = array_keys( $workflows );
	if ( in_array( str_replace( 'update-', '', $q ), $wf ) ) {
		$result = exec( 'php cli/packal.php doUpdate ' . str_replace( 'update-', '', $q ) );
		if ( $result == 'TRUE' ) {
			$plist = $workflows[ str_replace( 'update-', '', $q ) ] . '/info.plist';
			$name = exec( "/usr/libexec/PlistBuddy -c \"Print :name\" '$plist' 2> /dev/null" );
			echo $name . " has been successfully updated.";
		} else {
			$plist = $workflows[ str_replace( 'update-', '', $q ) ] . '/info.plist';
			$name = exec( "/usr/libexec/PlistBuddy -c \"Print :name\" '$plist' 2> /dev/null" );
			echo "Error updating " . $name; 
		}
	}
}

?>