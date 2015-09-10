<?php

$environments = [
	'development' => 'http://localhost:3000', // Local Passenger Server
	'dev-staging' => 'http://packal.dev', // Local nginx proxying to Passenger
	'staging' => 'https://mellifluously.org', // Staging Server
	'production' => 'https://www.packal.org', // Actual Production (not setup yet)
];

$environment = $environments[ file_get_contents( __DIR__ . '/environment.txt' ) ];

define( 'PACKAL_BASE_API_URL', "{$environment}/api/v1/" );
define( 'PACKAL_BASE_URL', $environment );