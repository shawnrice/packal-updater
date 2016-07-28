<?php

$version        = array_shift( explode( '.', $_SERVER['alfred_version'] ) );
$alfred_version = ( 3 === $version ) ? 'Alfred 3' : 'Alfred 2';
$data_dir       = $_SERVER['alfred_workflow_data'];
$cache_dir      = $_SERVER['alfred_workflow_cache'];
$workflows_dir  = $_SERVER['alfred_preferences'];

if ( ! empty( $data_dir ) ) {
	// save this data elsewhere so that it can be used for the gui
	$operation_data = [
		'alfred_version' => $alfred_version,
		'cache_dir'      => $cache_dir,
		'data_dir'       => $data_dir,
		'workflows_dir'  => $workflows_dir . '/workflows',
	];
	if ( ! file_exists( '/tmp/com.packal' ) ) {
		mkdir( '/tmp/com.packal' );
	}
	file_put_contents( '/tmp/com.packal/config.json', json_encode( $operation_data ) );
	define( 'ALFRED_VERSION', $alfred_version );
	define( 'CACHE_DIR', $cache_dir );
	define( 'DATA_DIR', $data_dir );
	define( 'WORKFLOWS_DIR', $workflows_dir . '/workflows' );
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

