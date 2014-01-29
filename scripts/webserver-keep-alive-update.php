<?php

/**
 * This script is invoked via an ajax callback. It simply checks the time and places a file in the workflow cache directory
 * that contains the time.
 */

require_once('../resources/includes/date-and-time.php');

// Make the time
$time = time();

// Get the user's home directory
$home = exec('echo $HOME');

// Create the file path name
$file = "$home/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/com.packal.shawn.patrick.rice/webserver/zombie";

// Place the file in the cache directory
file_put_contents( $file , $time );

// C'est fini! This script will be run, probably, in another 30 seconds.

?>