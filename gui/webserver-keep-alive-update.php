<?php

/**
 * This script is invoked via an ajax callback. It simply checks the time and places a file in the workflow cache directory
 * that contains the time.
 */

$bundle = 'com.packal';

// Set date/time to avoid warnings/errors.
if ( ! ini_get('date.timezone') ) {
	$tz = exec( 'tz=`ls -l /etc/localtime` && echo ${tz#*/zoneinfo/}' );
	ini_set( 'date.timezone', $tz );
}
  
// Make the time
$time = time();

// Get the user's home directory
$home = exec('echo $HOME');

// Create the file path name
$file = "$home/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/$bundle/zombie";

// Place the file in the cache directory
file_put_contents( $file, $time );

// C'est fini! This script will be run, probably, in another 30 seconds.

?>