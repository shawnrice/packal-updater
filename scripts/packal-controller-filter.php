<?php

/**
 *
 * This is the script filter called by Alfred that is the first entry into Packal.
 * 
 */

require_once( __DIR__.'/../libraries/workflows.php' );
require_once( 'first-run.php' );

$w = new Workflows;
$data = $w->data();
$cache = $w->cache();

// Check to make sure all the folders are there. If not, make them.
checkCreateFolders();

// Let's make the cache directories.
if (! file_exists( $cache ) ) {
	mkdir( $cache );
}
if (! file_exists( $cache . '/webserver' ) ) {
	mkdir( $cache . '/webserver' );
}


if (! file_exists($data) ) {
	firstRun();
	$updateInformation = false;
}
if (! file_exists( "$data/manifest.xml" ) ) {
	getManifest();
	$updateInformation = false;
}


// Let's check the staleness of the manifest.
// If it's older than three days, well, we'll call it stale.
// Right now, this code downloads a new manifest automatically. We shouldn't leave it like that.
// $now = time();
// if ( ( $now - filemtime( "$data/manifest.xml" ) ) < 172800 ) {
// 	$manifest = file_get_contents('http://packal.org/manifest.xml');
// 	file_put_contents("$data/manifest.xml", $manifest );
// 	$updateInformation = true;
// }


// Write a quick check to see if updates are available. If so, then put in a notification.
if (! $updateInformation ) {
	$w->result( '', 'configure', 'Stale Manifest', 'Update information is over three days. Refresh the manifest.', 'icon.png', 'yes', 'autocomplete' );
}

// If Packal hasn't been configured, then just show the configuration option and the about page.
if (! file_exists( "$data/config/first-run" ) ) {
	firstRun();
	$w->result( '', 'configure', 'Configure Packal', 'Configure Packal', 'icon.png', 'yes', 'autocomplete' );
	$w->result( '', 'about', 'About Packal', 'Show information about Packal', 'icon.png', 'yes', 'autocomplete' );

} else {
	$w->result( 'about', 'about', 'About Packal', 'Show information about Packal', 'icon.png', 'yes', 'autocomplete' );
	$w->result( 'configure', 'configure', 'Configure Packal', 'Configure Packal', 'icon.png', 'yes', 'autocomplete' );
}

echo $w->toxml();

function getManifest() {
	$manifest = file_get_contents('http://packal.org/manifest.xml');
	file_put_contents("$data/manifest.xml", $manifest );
}

?>