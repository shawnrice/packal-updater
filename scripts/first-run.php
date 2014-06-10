<?php

$bundle = 'com.packal';

function checkCreateFolders() {
	global $bundle;

	// Set the user's home directory
	$home = exec('echo $HOME');
	// The location of the config file
	$data = "$home/Library/Application Support/Alfred 2/Workflow Data/$bundle/";
	// Create the file path name
	$cache = "$home/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/$bundle/";

	// Make the cache directory
	if (! file_exists( $cache ) ) {
		mkdir( $cache );
	}

	// Make the temp directory for workflow manipulation
	if (! file_exists( $cache . '/workspace' ) ) {
		mkdir( $cache . '/workspace' );
	}

	// Make the temp directory for the script to know whether or not to shutdown the temporary webserver
	if (! file_exists( $cache . '/webserver' ) ) {
		mkdir( $cache . '/webserver' );
	}



	// Make the data directory
	if (! file_exists( $data ) ) {
		mkdir( $data );
	}

	// Create the configuation directory
	if (! file_exists( $data . '/config' ) ) {
		mkdir( $data . '/config' );
	}
	
	// Create the exclude directory
	if (! file_exists( $data . '/config/exclude' ) ) {
		mkdir( $data . '/config/exclude' );
	}

	// Create the directory to store workflow-specific data
	if (! file_exists( $data . '/workflows' ) ) {
		mkdir( $data . '/workflows' );
	}

	/*
	// Create the directory to store icons
	if (! file_exists( $data . '/icons' ) ) {
		mkdir( $data . '/icons' );
	}
	*/

	// Create the global directory for things like the manigest file
	if (! file_exists( $data . '/global' ) ) {
		mkdir( $data . '/global' );
	}

	// Make the backups directory for updating.
	if (! file_exists( $data . '/backups' ) ) {
		mkdir( $data . '/backups' );
	}
}

function firstRun() {
	global $bundle;

	// Set the user's home directory
	$home = exec('echo $HOME');
	// The location of the data directory
	$data = "$home/Library/Application Support/Alfred 2/Workflow Data/$bundle/";
	// Create the file path name
	$cache = "$home/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/$bundle/";

	// Download a copy of the manifest so that we can continue our configuration
	// Currently, we're getting it directly from Packal, but we'll change this to Github later.
	$manifest = file_get_contents('http://packal.org/manifest.xml');
	file_put_contents("$data/manifest.xml", $manifest );

	file_put_contents("$data/config/first-run", "done");
	// Setup is done. Now we need to run the configuration.
}
?>