<?php

// This is a dumb autoloader
$files = [
	'config.php',
	'Libraries/Alphred.phar',
	'Libraries/BuildWorkflow.php',
	'Libraries/BuildWorkflowMap.php',
	'Libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php',
	'Libraries/FileSystem.php',
	'Libraries/functions.php',
	'Libraries/generate_workflow_ini.php',
	'Libraries/Packal.php',
	'Libraries/Pashua.php',
	'Libraries/PlistMigration.php',
	'Libraries/SemVer.php',
	'Libraries/Submit.php',
	'Libraries/Themes.php',
	'Libraries/Workflows.php',
];

foreach( $files as $file ) :
	require_once( __DIR__ . '/' . $file );
endforeach;

// This is a dumb autoloader for all the different menu files.
foreach ( array_diff( scandir( __DIR__ . '/Menus' ), [ '.', '..', '.DS_Store'] ) as $file ) {
	if ( 'php' === pathinfo( $file )['extension'] ) {
		require_once( __DIR__ . "/Menus/{$file}" );
	}
}