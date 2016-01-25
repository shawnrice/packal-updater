<?php

// Placeholder used while refactoring...

function submit_theme( $params ) {
	$submission = new Submit( 'theme', $params );
	return $submission->execute();
}

function submit_workflow( $params ) {
	$submission = new Submit( 'workflow', $params );
	return $submission->execute();
}

function submit_report( $params ) {
	$submission = new Submit( 'report', $params );
	return $submission->execute();
}

function get_icon( $url, $ttl = -1 ) {
	$cache_bin = CACHE . PRIMARY_CACHE_BIN . '-icons/';

	if ( ! file_exists ( $cache_bin ) ) {
		$dir_exists = mkdir( $cache_bin, 0775, true );
	}

	$parts = explode( '/', $url );
	$filename = $cache_bin . $parts[ count( $parts ) - 2 ] . '.png';

	if ( 'package.png' == end( explode( '/', $url ) ) ) {
		return __DIR__ . '/../Resources/images/package.png';
	}

	if ( check_icon_cache( $filename, $ttl ) ) {
		return $filename;
	}
	// Add something to the queue
	file_put_contents(  CACHE . 'queue-' . ENVIRONMENT . '.txt', $url . "\n", FILE_APPEND | LOCK_EX );
	return __DIR__ . '/../Resources/images/package.png';
}

function check_icon_cache( $file, $ttl ) {
	if ( ! file_exists( $file ) || 0 === $ttl ) {
		return false;
	}
	if ( -1 === $ttl ) {
		return true;
	}
	if ( ! ( time() - filemtime( $file ) > $ttl ) ) {
		return false;
	}
	return true;
}