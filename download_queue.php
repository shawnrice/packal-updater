<?php

// Set date/time to avoid warnings/errors.
if ( ! ini_get( 'date.timezone' ) ) {
	ini_set( 'date.timezone', exec( 'tz=`ls -l /etc/localtime` && echo ${tz#*/zoneinfo/}' ) );
}

require_once( __DIR__ . '/config.php' );
require_once( __DIR__ . '/Libraries/FileSystem.php' );

$queue = CACHE . 'queue-' . ENVIRONMENT . '.txt';
$dir   = CACHE . PRIMARY_CACHE_BIN . '-icons/';

if ( ! file_exists( $dir ) ) {
	mkdir( $dir, 0775, true );
}

run_workflow_queue( $queue );

function run_workflow_queue( $queue ) {
	print "Starting queue for file `{$queue}` at " . date( 'Y-m-d H:i:s', time() ) . ".\n";
	if ( ! file_exists( $queue ) ) {
		print "Error: File `{$queue}` does not exist.\n";
		exit( 1 );
	}
	$count = 0;
	while ( true ) :
		$line = get_and_delete_first_line( $queue );
		if ( empty( $line ) || ! $line ) {
			break;
		}
		if ( download_workflow_icon( $line ) ) {
			print 'Downloaded ' . get_workflow_icon_name( $line ) . ".\n";
			$count++;
			// Sleep for a moment so that we don't spam the server, etc...
			usleep( 250000 );
		}
	endwhile;
	print 'Finished running queue at ' . date( 'Y-m-d H:i:s', time() ) . ". Downloaded {$count} file(s).\n";
}

function download_workflow_icon( $line ) {
	global $dir;
	$icon = $dir . get_workflow_icon_name( $line );
	if ( file_exists( $icon ) && filesize( $icon ) > 0 ) {
		return false;
	}
	file_put_contents( $icon, file_get_contents( $line ) );
	if ( 0 === filesize( $icon ) ) {
		unlink( $icon );
	}
	return true;
}

function download_theme_icon( $line ) {
	global $dir;
	$icon = $dir . get_theme_icon_name( $line );
	file_put_contents( $icon, file_get_contents( $line ) );
	if ( 0 === filesize( $icon ) ) {
		unlink( $icon );
	}
}

function get_workflow_icon_name( $url ) {
	$parts = explode( '/', $url );
	return $parts[ count( $parts ) - 2 ] . '.png';
}

function get_theme_icon_name( $url ) {
	return end( explode( '/', $url ) );
}

function get_and_delete_first_line( $file ) {
	$line = trim( fgets( fopen( $file, 'r' ) ) );
	// Remove the first line of the file. Doing it this way seems to be the fastest.
	exec( "tail -n +2 '{$file}'", $output );
	file_put_contents( $file, implode( "\n", $output ) );
	return $line;
}

function prune_old_files( $directory ) {

}
