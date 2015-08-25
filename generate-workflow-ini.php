<?php

// This script works ONLY from, well, nothing at this point. Make sure that it works from the
// workflow, and abstract it into functions, please.

require_once __DIR__ . '/Libraries/Alphred.phar';
$alphred = new Alphred;
use Alphred\Ini as Ini;

$args = json_decode( $argv[1], true );

$ini = $argv[1] . '/workflow.ini';
if ( file_exists( $ini ) ) {
	$workflow = Ini::read_ini( $ini, false );
} else {
	$workflow = [];
}

if ( count( $workflow ) > 0 ) {
	if ( count( $workflow['packal']['tags'] ) > 0 ) {
		$workflow['packal']['tags'] = explode(',', $workflow['packal']['tags'] );
		array_walk($workflow['packal']['tags'], create_function('&$val', '$val = trim($val);'));
	}
	if ( count( $workflow['packal']['categories'] ) > 0 ) {
		$workflow['packal']['categories'] = explode(',', $workflow['packal']['categories']);
		array_walk($workflow['packal']['categories'], create_function('&$val', '$val = trim($val);'));
	}
	ksort( $workflow['packal'] );
}

Ini::write_ini( $workflow, $ini );

$path = __DIR__ . '/Pashua.app/Contents/MacOS/Pashua';

$conf = file_get_contents( __DIR__ . '/Resources/pashau-workflow-config.ini' );

$values = [ 'name' => 'This is a name',
						'bundle' => 'com.spr.bundle.id',
						'short' => 'This is a short description of the workflow'

];

foreach ( $values as $key => $val ) :
	$conf = str_replace( '%%' . $key . '%%', $val, $conf );
endforeach;

// Do some wonky stuff to get the categories. I'm doing this so that I can
// keep the categories in a json file.
$alphabet = 'abcdefghijklmnopqrstuvwxyz';
$categories = json_decode( file_get_contents( __DIR__ . '/assets/categories.json' ), true );
$end = substr( $alphabet, count( $categories ) - 1, 1 );
$temp = '';
$y = 300;
foreach ( range( 'a', $end ) as $key => $suffix ) :
	if ( 0 === $key % 2 ) {
		$temp .= "cat_{$suffix}.x = 600\r\n";
		$y -= 20;
	} else {
		$temp .= "cat_{$suffix}.x = 400\r\n";
	}
	$temp .= "cat_{$suffix}.y = {$y}\r\n";
	$temp .= "cat_{$suffix}.type = checkbox\r\n";
	$temp .= "cat_{$suffix}.label = {$categories[ $key ]}\r\n";
	$temp .= "cat_{$suffix}.default = 0\r\n";
endforeach;
$conf = str_replace( '##CATEGORIES##', $temp, $conf );


$config = tempnam( '/tmp', 'Pashua_' );
if ( false === $fp = @fopen( $config, 'w' ) ) {
    throw new \RuntimeException( "Error trying to open $configfile" );
}
fwrite( $fp, $conf );
fclose( $fp );

// Call pashua binary with config file as argument and read result
$result = shell_exec( escapeshellarg( $path ) . ' ' . escapeshellarg( $config ) );
@unlink( $config );
//   // Parse result
$parsed = array();
foreach (explode("\n", $result) as $line) {
    preg_match('/^(\w+)=(.*)$/', $line, $matches);
    if (empty($matches) or empty($matches[1])) {
        continue;
    }
    $parsed[$matches[1]] = $matches[2];
}

foreach ( [
  'yosemite' => 10,
  'mavericks' => 9,
  'mountain' => 8,
  'lion' => 7,
  'snow' => 6
] as $name => $version ) :
	if ( 1 == $parsed[ $name ] ) {
		$workflow['osx'][] = $version;
	}
endforeach;

print_r( $workflow );

foreach ( $parsed as $key => $value ) :

	$alphred->console( "{$key}: {$value}\r\n", 4 );

endforeach;




