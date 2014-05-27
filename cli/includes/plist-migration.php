<?php

namespace CFPropertyList;

/**
* Require CFPropertyList
*/
require_once( __DIR__ . '/../../libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php' );
require_once( __DIR__ . '/plist-functions.php' );

if ( isset( $argv ) && ( ! empty( $argv[2] ) ) )
  migratePlist( $argv[1], $argv[2] );

/**
 * Migrates the hotkeys and keywords from the current plist to the new version. It alters the actual files.
 * @param  string $current The current plist file
 * @param  string $new     The new plist file
 * @return mixed           Either "true" or an error code
 */
function migratePlist( $current, $new ) {

	if ( ! file_exists( $current ) ) {
		return 1; // Error Code #1 is original file doesn't exist
	}

	if ( ! file_exists( $new ) ) {
		return 2; // Error Code #2 is new file doesn't exist
	}


	// Construct the workflow plist objects
	$workflow = new CFPropertyList( $current );
	$import   = new CFPropertyList( $new );

	// Declare an array to store the data about the original plist in.
	$original = array();

	// Convert plist object to usable array for processing
	$tmp = $workflow->toArray();
	/**
	 * Load the objects array, scan through it to get everything migratable.
	 * Store the values as an array with the UIDs as the keys.
	 * @var array
	 */
	foreach ( $tmp[ 'objects' ] as $o ) {
		if ( isset( $o[ 'config' ] ) ) {
			$original[ $o[ 'uid' ] ][ 'type' ] = $o[ 'type' ];

			switch ( $o[ 'type' ]) :
				case 'alfred.workflow.trigger.hotkey' :
					$value = array();
          if ( isset( $o[ 'config' ][ 'hotkey' ] ) )
						$value[ 'hotkey' ] = $o[ 'config' ][ 'hotkey' ];
          if ( isset( $o[ 'config' ][ 'hotmod' ] ) )
						$value[ 'hotmod' ] = $o[ 'config' ][ 'hotmod' ];
          if ( isset( $o[ 'config' ][ 'hotstring' ] ) )
						$value[ 'hotstring' ] = $o[ 'config' ][ 'hotstring' ];

					$original[ $o[ 'uid' ] ][ 'config' ] = $value;
					break;
		    case 'alfred.workflow.input.filefilter' :
		    case 'alfred.workflow.input.scriptfilter' :
		    case 'alfred.workflow.input.keyword' :
		    	$value = array(
		        	'keyword' => $o[ 'config' ][ 'keyword' ]
		    	);
    			$original[ $o[ 'uid' ] ][ 'config' ] = $value;
		    break;
			endswitch;

		}
	}



	$tmp = $import->toArray();

	// The uids are stored as the keys of the $original array,
	// so let's grab them to check if we need to migrate anything.
	$uids = array_keys( $original);

	// These are the only types of objects that we need to migrate
	$objects = array( 'alfred.workflow.trigger.hotkey',
        						'alfred.workflow.input.filefilter',
        						'alfred.workflow.input.scriptfilter',
        						'alfred.workflow.input.keyword'
					);

	// We need the key(order) so that we can set things properly
	foreach ( $tmp[ 'objects' ] as $key => $o ) {
		// Use only the things in the types above
		if ( in_array( $o[ 'type' ] , $objects ) ) {
			// Check to see if the objects is one of the original objects with a uid.
			if ( in_array( $o[ 'uid' ] , $uids ) ) {
				echo $o[ 'uid' ]   . "<br >";
				echo $o[ 'type' ]  . "<br> ";
				if ( $o[ 'type' ] == 'alfred.workflow.trigger.hotkey') {
					// We're not really going to bother to check to see if the values match;
					// we'll just migrate them instead.
					//setPlistValue( $location, $value, $plist)
					setPlistValue( ":objects:$key:config:hotmod", $original[ $o[ 'uid' ] ][ 'config' ][ 'hotmod' ], $new);
					setPlistValue( ":objects:$key:config:hotkey", $original[ $o[ 'uid' ] ][ 'config' ][ 'hotkey' ], $new);
					setPlistValue( ":objects:$key:config:hotstring", $original[ $o[ 'uid' ] ][ 'config' ][ 'hotstring' ], $new);
				} else {
					// At this point, the only other thing to migrate is the keyword
					// Check to see if they match; if they don't, then set them to the new one
					if ( $o[ 'config' ][ 'keyword' ] != $original[ $o[ 'uid' ] ][ 'config' ][ 'keyword' ] ) {
						setPlistValue( ":objects:$key:config:keyword", $original[ $o[ 'uid' ] ][ 'config' ][ 'keyword' ], $new);
					}
				}
			}

		}
	}
	return TRUE;

	/**
	 *
	 * Todo:
	 * 		-- add in error checking for correct plist syntax.
	 * 		-- expand migration for desired but non-essential settings
	 *
	 */
}
?>
