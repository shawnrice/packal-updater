<?php

/**
 *
 * This is the file that receives the input from the script filter and then starts everything.
 * 
 */

$q = $argv[1];
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
		// Not using Mavericks, so php 5.3 is installed. We'll use the custom build.
		exec( "nohup $dir/php-5.5.13-cli -S localhost:7893 -t gui/ > /dev/null 2>&1 &" );	
	}
	
	// Start the webserver kill script
	exec( "nohup $dir/scripts/check-and-kill-webserver.sh  > /dev/null 2>&1 &" );

	// // Wait a second so that we can make sure that the webserver has started before we open the viewer.
	sleep(1);

	// Open the gui in the viewer
	exec( "nohup open $dir/resources/applications/viewer.app --args http://localhost:7893  > /dev/null 2>&1 &" );

}

?>