<?php

// These are just here for now...
$bundle                              = 'com.packal2';
$_SERVER['alfred_workflow_data']     = "{$_SERVER['HOME']}/Library/Application Support/Alfred 2/Workflow Data/{$bundle}/";
$_SERVER['alfred_workflow_cache']    = "{$_SERVER['HOME']}/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/{$bundle}";
$_SERVER['alfred_workflow_bundleid'] = $bundle;

$environments = [
	'development' => 'http://localhost:3000', 		// Local Passenger Server
	'dev-staging' => 'http://packal.dev', 				// Local nginx proxying to Passenger
	'staging'     => 'https://mellifluously.org', // Staging Server
	'production'  => 'https://www.packal.org', 		// Actual Production (not setup yet)
];

// We're using this to make the following lines smaller
$home = $_SERVER['HOME'];

// The current environment is defined in environment.txt
define( 'ENVIRONMENT', 					file_get_contents( __DIR__ . '/environment.txt' ) );
define( 'BASE_URL', 						$environments[ ENVIRONMENT ] );

define( 'BASE_API_URL', 				BASE_URL . '/api/v1/' );
define( 'BUNDLE', 							'com.packal2' );
define( 'CACHE', 								"{$home}/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/" . BUNDLE . '/' );
define( 'DATA', 								"{$home}/Library/Application Support/Alfred 2/Workflow Data/" . BUNDLE . '/' );
define( 'DEVELOPMENT_TESTING',  true );
define( 'PRIMARY_CACHE_BIN', 		parse_url( BASE_URL, PHP_URL_HOST ) );
