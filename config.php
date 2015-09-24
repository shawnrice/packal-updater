<?php

// These are just here for now...
$bundle = 'com.packal2';
$_SERVER['alfred_workflow_data'] = "{$_SERVER['HOME']}/Library/Application Support/Alfred 2/Workflow Data/{$bundle}/";
$_SERVER['alfred_workflow_cache'] = "{$_SERVER['HOME']}/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/{$bundle}";
$_SERVER['alfred_workflow_bundleid'] = $bundle;

define( 'DEVELOPMENT_TESTING', true );

// We are going to define the environments here; this means we can work with multiple servers easily
$environments = [
	'development' => 'http://localhost:3000', // Local Passenger Server
	'dev-staging' => 'http://packal.dev', 		// Local nginx proxying to Passenger
	'staging' => 'https://mellifluously.org', // Staging Server
	'production' => 'https://www.packal.org', // Actual Production (not setup yet)
];
// The current environment is defined in environment.txt
$environment = $environments[ file_get_contents( __DIR__ . '/environment.txt' ) ];

// Define the URLs that we will use through the workflow
define( 'PACKAL_BASE_API_URL', "{$environment}/api/v1/" );
define( 'PACKAL_BASE_URL', $environment );
define( 'WORKFLOW_ENVIRONMENT', file_get_contents( __DIR__ . '/environment.txt' ) );

define( 'PRIMARY_CACHE_BIN', parse_url( PACKAL_BASE_URL, PHP_URL_HOST ) );

// Just a shortcut for the default matching
@define( 'DEFAULT_FILTER_PARAMS',  MATCH_SUBSTRING | MATCH_ALLCHARS | MATCH_STARTSWITH | MATCH_ATOM );