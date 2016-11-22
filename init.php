<?php

require_once( __DIR__ . '/config.php' );
$alfred_version = ( 3 === guess_alfred_version() ) ? 'Alfred 3' : 'Alfred 2';
$data_dir       = $_SERVER['alfred_workflow_data'];
$cache_dir      = $_SERVER['alfred_workflow_cache'];

if ( ! isset( $_SERVER['alfred_preferences'] ) ) {
	$v = 3 === guess_alfred_version() ? '-3' : '';
	$cmd = "defaults read $HOME/Library/Preferences/com.runningwithcrayons.Alfred-Preferences{$v}.plist|grep -hn syncfolder";
	exec( $cmd, $out );
	$dir = preg_match( "/\"(.*)\";/", array_shift( $out ), $matches );
	if ( isset( $matches[1] ) ) {
		$matches[1] = str_replace( '~', $_SERVER['HOME'], $matches[1] );
	}

	$_SERVER['alfred_preferences'] = $matches[1];
}

$workflows_dir  = $_SERVER['alfred_preferences'];
if ( false === strpos( $workflows_dir, 'Alfred.alfredpreferences' ) ) {
	$workflows_dir = $workflows_dir . '/Alfred.alfredpreferences';
}
$workflows_dir = $workflows_dir .'/workflows';

if ( ! empty( $data_dir ) ) {
	// save this data elsewhere so that it can be used for the gui
	$operation_data = [
		'alfred_version'     => $alfred_version,
		'cache_dir'          => $cache_dir,
		'data_dir'           => $data_dir,
		'workflows_dir'      => $workflows_dir,
		'alfred_preferences' => $_SERVER['alfred_preferences'],
		'HOME'               => $_SERVER['HOME'],
	];
	if ( ! file_exists( '/tmp/com.packal' ) ) {
		mkdir( '/tmp/com.packal' );
	}
	file_put_contents( '/tmp/com.packal/config.json', json_encode( $operation_data ) );
	define( 'ALFRED_VERSION', $alfred_version );
	define( 'CACHE_DIR', __CACHE__ );
	define( 'DATA_DIR', __DATA__ );
	define( 'WORKFLOWS_DIR', $workflows_dir );
} else {
	// we're now somewhere else and need this data because it doesn't appear
	if ( file_exists( '/tmp/com.packal' ) ) {
		$operation_data = json_decode( file_get_contents( '/tmp/com.packal/config.json' ), true );
		define( 'ALFRED_VERSION', $operation_data['alfred_version'] );
		define( 'CACHE_DIR', $operation_data['cache_dir'] );
		define( 'DATA_DIR', $operation_data['data_dir'] );
		define( 'WORKFLOWS_DIR', $operation_data['workflows_dir'] );
	}
}
