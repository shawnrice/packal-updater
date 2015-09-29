<?php
/**
 * Script is used to download files in the background
 */

// Set date/time to avoid warnings/errors.
if ( ! ini_get( 'date.timezone' ) ) {
	ini_set( 'date.timezone', exec( 'tz=`ls -l /etc/localtime` && echo ${tz#*/zoneinfo/}' ) );
}

require_once( 'Libraries/FileSystem.php' );

if ( ! isset( $argv ) || 4 != count( $argv ) ) {
	print "Error: invalid use. Example usage:\n\t`php '" . __FILE__ . "' '<url>' '<download_directory>' '<ttl>'`\n";
	exit(1);
}

$url         = $argv[1];
$destination = $argv[2];
$ttl = isset( $argv[3] ) ? $argv[3] : -1;

if ( ! file_exists( $destination ) ) {
	print "Error: `{$destination}` does not exist.\n";
	exit(1);
}

if ( ! FileSystem::verify_url( $url ) ) {
	print "Error: `{$url}` is not a valid URL.\n";
	exit(1);
}

$path = get_icon_path( $url, $destination );
if ( check_cache( $path, $ttl ) ) {
	exit(0);
}
download_file( $url, $destination );

// Functions

function download_file( $url, $destination ) {
	$dir = FileSystem::make_random_temp_dir();
	$filename = get_icon_name( $url );
	file_put_contents( "{$dir}/{$filename}" , file_get_contents( $url ) );
	if ( file_exists( "{$destination}/{$filename}" ) ) {
		unlink( "{$destination}/{$filename}" );
	}
	rename( "{$dir}/{$filename}", "{$destination}/{$filename}.png" );
	return "{$destination}/{$filename}";
}

function get_icon_path( $url, $destination ) {
	$filename = get_icon_name( $url );
	return "{$destination}/{$filename}";
}

function check_directory( $destination ) {
	if ( ! file_exists( dirname( $destination ) ) ) {
		return mkdir( dirname( $destination ), 0775, true ) ;
	}
	return true;
}

function get_icon_name( $url ) {
	$parts = explode( '/', $url );
	return $parts[ count( $parts ) - 2 ] . '.png';
}

function check_cache( $file, $ttl ) {
	if ( ! file_exists( $file ) ) {
		return false;
	}
	if ( -1 === $ttl || false === $ttl ) {
		return true;
	}
	if ( ( time() - filemtime( $file ) ) > $ttl ) {
		return false;
	}
	return true;
}