<?php

// This is a simple script I wrote to build a phar. All I/you need to do
// is to change the configuration files.
// Note, you must have enabled phar writing in your php.ini file.
//
// You also need to have an copy of the Alphred library cloned on your computer.
// Set the location somewhere below.


////////////////////////
/// Config
////////////////////////

// Name of the final file
$phar_name    = 'Packal.phar';
// Directories to add wholesale
$directories = [
	'Libraries/CFPropertyList/classes/CFPropertyList',
	'Resources',
];
// Specific files to add into the phar
$other_files = [
	'Libraries/SemVer.php',
	'Libraries/functions.php',
	'Libraries/PlistMigration.php',
	'Libraries/BuildThemeMap.php',
	'Libraries/BuildWorkflowMap.php',
	'Libraries/BuildWorkflow.php',
	'Libraries/Submit.php',
	'Libraries/FileSystem.php',
	'Libraries/Packal.php',
	'Libraries/Themes.php',
	'Libraries/Workflows.php',
	'config.php',
	'environment.txt',
	// 'Resources/help_template.txt',
];
// This is the main file
$main_stub   = 'cli.php';
// Build from this directory; don't add in extra subdirs
$build_dir   = '.';

// Compression settings for the phar itself
$compression = 'BZ2';
$compression = 'GZ';

////////////////////////
/// Do not change below this line
////////////////////////

// Delete the current phar if it exists
@unlink( "{$build_dir}/{$phar_name}" );

// Create a new phar as build_dir/phar_name, and leave the filesystem iterator open
$phar = new Phar(
	"{$build_dir}/{$phar_name}",
	FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
	$phar_name
);
// Start buffering the phar so as to add files
$phar->startBuffering();
// Add in the main stub, i.e. the first "file" or controller file
$default_stub = $phar->createDefaultStub( $main_stub );
$phar[ $main_stub ] = file_get_contents( __DIR__ . "/{$main_stub}" );

// Add an unpacked version of Alphred; we're doing this so we don't have the
// awkwardness of using a phar within a phar. Too many levels and layers that
// way.
$alphred_dir = "{$_SERVER['HOME']}/projects/Alfred/alphred/";

$phar[ "Alphred/Main.php" ] = file_get_contents( $alphred_dir . "/Main.php" );
// foreach( [ 'classes', 'commands' ] as $directory ) :
foreach( [ 'classes' ] as $directory ) :
	foreach( array_diff( scandir( $alphred_dir . $directory ), ['.', '..', '.DS_Store' ] ) as $filename ) :
    $phar[ "Alphred/{$directory}/{$filename}" ] = file_get_contents( $alphred_dir . "/{$directory}/{$filename}" );
	endforeach;
endforeach;
// Done adding Alphred


// Cycle through these directories and include everything
foreach( $directories as $directory ) :
	foreach( array_diff( scandir( $directory ), ['.', '..', '.DS_Store' ] ) as $file ) :
		$phar[ "{$directory}/{$file}" ] = file_get_contents( __DIR__ . "/{$directory}/{$file}" );
	endforeach;
endforeach;

// Cycle through all the other files and include everything
foreach ( $other_files as $file ) :
	$name = explode( '/', $file );
	$name = end( $name );
	$phar[ $name ] = file_get_contents( __DIR__ . '/' . $file );
endforeach;
// Why is this line here again?
$phar[ 'autoloader.php' ] = ''; // This is just something here for now

// Add in the hashbang so we can execute easier from the command line
$stub = "#!/usr/bin/env php\n" . $default_stub;
// Actually set the stub
$phar->setStub( $stub );
// Stop buffering, we're done adding.
$phar->stopBuffering();

////////////////////////
// Delete a file if one already exists with the same compression level
if ( file_exists( $phar_name . '.' . strtolower($compression) ) ) {
	unlink($phar_name . '.' . strtolower($compression) );
}

// $phar->compress(constant('Phar::' . $compression ));
// unlink( $phar_name );
// rename( $phar_name . '.' . strtolower($compression), $phar_name );

// Go ahead and change the permissions to make it executable
exec( "chmod +x {$phar_name}", $return, $code );
// Print messages about build status
if ( 0 === $code ) {
	print "Built Phar {$phar_name}\n";
} else {
	print "Problem occured building {$phar_name}.\n";
}

// Exit with the same code as the last one above
exit( $code );
