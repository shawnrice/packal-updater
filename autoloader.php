<?php

// This is a dumb autoloader
$files = [
	'config.php',
	'Libraries/Alphred.phar',
	'Libraries/BuildWorkflow.php',
	'Libraries/BuildWorkflowMap.php',
	'Libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php',
	'Libraries/generate_workflow_ini.php',
	'Libraries/Pashua.php',
	'Libraries/php-semver/src/vierbergenlars/SemVer/expression.php',
	'Libraries/php-semver/src/vierbergenlars/SemVer/SemVerException.php',
	'Libraries/php-semver/src/vierbergenlars/SemVer/version.php',
	'Libraries/SemVer.php',
	'Libraries/Submit.php',
	'Libraries/tweaked_alphred_request.php',
	'Libraries/functions.php',
	'Libraries/FileSystem.php',
	'Libraries/PlistMigration.php',
];

foreach( $files as $file ) :
	require_once( __DIR__ . '/' . $file );
endforeach;