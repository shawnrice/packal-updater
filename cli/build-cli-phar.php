<?php

// This is a simple script I wrote to build a phar. All I/you need to do
// is to change the configuration files.
// Note, you must have enabled phar writing in your php.ini file.


////////////////////////
/// Config
////////////////////////

$phar_name    = 'Packal.phar';
$directories = [
	'Libraries/CFPropertyList/classes/CFPropertyList',
	'Resources',
];
$other_files = [
	'Libraries/SemVer.php',
	'Libraries/functions.php',
	'Libraries/PlistMigration.php',
	'Libraries/BuildThemeMap.php',
	'Libraries/BuildWorkflowMap.php',
	'Libraries/BuildWorkflow.php',
	'Libraries/Submit.php',
	// 'Resources/help_template.txt',
];
$main_stub   = 'cli.php';
$build_dir   = '.';

$compression = 'BZ2';
$compression = 'GZ';

////////////////////////
/// Do not change below this line
////////////////////////

@unlink( "{$build_dir}/{$phar_name}" );
$phar = new Phar(
	"{$build_dir}/{$phar_name}",
	FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
	$phar_name
);
$phar->startBuffering();
$default_stub = $phar->createDefaultStub( $main_stub );
$phar[ $main_stub ] = file_get_contents( __DIR__ . "/{$main_stub}" );

// Add an unpacked version of Alphred
$alphred_dir = '/Users/Sven/Documents/Alfred2/alphred/';
$phar[ "Alphred/Main.php" ] = file_get_contents( $alphred_dir . "/Main.php" );
foreach( [ 'classes', 'commands' ] as $directory ) :
	foreach( array_diff( scandir( $alphred_dir . $directory ), ['.', '..', '.DS_Store' ] ) as $filename ) :
    $phar[ "Alphred/{$directory}/{$filename}" ] = file_get_contents( $alphred_dir . "/{$directory}/{$filename}" );
	endforeach;
endforeach;

// Cycle through these directories and include everything
foreach( $directories as $directory ) :
	foreach( array_diff( scandir( $directory ), ['.', '..', '.DS_Store' ] ) as $file ) :
		if ( 'go-pear.phar' != $file ) {
			$phar[ "{$directory}/{$file}" ] = file_get_contents( __DIR__ . "/{$directory}/{$file}" );
		}
	endforeach;
endforeach;
foreach ( $other_files as $file ) :
	$name = end( explode( '/', $file ) );
	$phar[ $name ] = file_get_contents( __DIR__ . '/' . $file );
endforeach;
$phar[ 'autoloader.php' ] = ''; // This is just something here for now
$stub = "#!/usr/bin/env php\n" . $default_stub;
// $stub = "#!/usr/bin/php \n" . $default_stub;
$phar->setStub( $stub );
$phar->stopBuffering();
// print_r( $phar );
////////////////////////
if ( file_exists( $phar_name . '.' . strtolower($compression) ) ) {
	unlink($phar_name . '.' . strtolower($compression) );
}

// $phar->compress(constant('Phar::' . $compression ));
// unlink( $phar_name );
// rename( $phar_name . '.' . strtolower($compression), $phar_name );
exec( "chmod +x {$phar_name}", $return, $code );
if ( 0 == $code ) {
	print "Built Phar {$phar_name}\n";
} else {
	print "Problem occured building {$phar_name}.\n";
}

exit( $code );
