<?php

$manifest = simplexml_load_file( '../manifest.xml' );

$pworkflows = array();

foreach ( $manifest as $entry ) {

	array_push( $pworkflows , (string)$entry->bundle );

}

// Get the file list
$dirs = scandir( '../../' );


// Change the working directory
chdir( '../../' );

$key = array_keys( $dirs , '.' );
unset( $dirs[$key[0]] );
$key = array_keys( $dirs , '..' );
unset( $dirs[$key[0]] );
$key = array_keys( $dirs , '.DS_Store' );
unset( $dirs[$key[0]] );

$workflows = array();

$managed   = array();
$unmanaged   = array();
$ineligible  = array();
$eligible  = array();
$blacklist  = array();

// We're going to implement a
$count = 0;
foreach ( $dirs as $dir ) {
	if ( ( is_dir( $dir ) && file_exists( "$dir/info.plist" ) ) ) {
		$tmp = array();
		$tmp ['bundle'] = exec( "/usr/libexec/PlistBuddy -c 'print :bundleid' $dir/info.plist" );
		$tmp['name']  = exec( "/usr/libexec/PlistBuddy -c 'print :name' $dir/info.plist" );
		$tmp['dir']  = $dir;

		if ( empty( $tmp[ 'bundle' ] ) ) {
			array_push( $ineligible, $tmp );
		} else if ( file_exists( "$dir/packal/package.xml" ) ) {
				array_push( $managed , $tmp );
			} else if ( in_array( $tmp[ 'bundle' ] , $pworkflows ) ) {
				array_push( $eligible , $tmp );
			} else {
			array_push( $unmanaged , $tmp );
		}

		array_push( $workflows, $tmp );
		unset( $tmp );
	}
}

// A quick check to see if the package came from Packal:
echo "Eligible: ";
print_r( $eligible );
echo "Managed: ";
print_r( $managed );
?>
