<?php

// This script works ONLY from, well, nothing at this point. Make sure that it works from the
// workflow, and abstract it into functions, please.

require_once __DIR__ . '/Libraries/Alphred.phar';
require_once( __DIR__ . '/Libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php' );
use Alphred\Ini as Ini;
use CFPropertyList\CFPropertyList as CFPropertyList;

function generate_ini( $path ) {
	$alphred = new Alphred;
	$ini = "{$path}/workflow.ini";
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
	$workflow = pashua_dialog( $path, $workflow );
	if ( $workflow ) {
		Ini::write_ini( $workflow, $ini );
	}
}



function pashua_dialog( $directory, $workflow ) {
	$alphred = new Alphred;
	$plist = new CFPropertyList( "{$directory}/info.plist", CFPropertyList::FORMAT_XML);
	$plist = $plist->toArray();

	foreach ( [ 'bundleid', 'name', 'description' ] as $key ) :
		if ( ! isset( $plist[ $key ] ) ) {
			print "Error: no {$key} is set in the info.plist. Please update your workflow.";
			return false;
		}
	endforeach;
	$path = __DIR__ . '/Pashua.app/Contents/MacOS/Pashua';
	$conf = file_get_contents( __DIR__ . '/Resources/pashau-workflow-config.ini' );
	$values = [ 'name' => $plist['name'],
							'bundle' => $plist['bundleid'],
							'short' => $plist['description'],
							'tags' => implode( '[return]', $workflow['packal']['tags'] ),
							'github' => $workflow['workflow']['github'],
							'forum' => $workflow['workflow']['forum'],
	];
	foreach ( $values as $key => $val ) :
		$conf = str_replace( '%%' . $key . '%%', $val, $conf );
	endforeach;
	// Do some wonky stuff to get the categories. I'm doing this so that I can
	// keep the categories in a json file.
	$alphabet = 'abcdefghijklmnopqrstuvwxyz';
	$categories = json_decode( file_get_contents( __DIR__ . '/assets/categories.json' ), true );
	if ( isset( $workflow['packal']['categories'] ) && 0 !== count( $workflow['packal']['categories'] ) ) {
		$cats = $workflow['packal']['categories'];
	} else {
		$cats = [];
	}

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
		if ( in_array( $categories[ $key ], $cats ) ) {
			$temp .= "cat_{$suffix}.default = 1\r\n";
		} else {
			$temp .= "cat_{$suffix}.default = 0\r\n";
		}
		$pashua_categories[ "cat_{$suffix}" ] = $categories[ $key ];
	endforeach;
	$conf = str_replace( '##CATEGORIES##', $temp, $conf );
	$config = tempnam( '/tmp', 'Pashua_' );
	if ( false === $fp = @fopen( $config, 'w' ) ) {
	    throw new \RuntimeException( "Error trying to open {$configfile}" );
	}
	fwrite( $fp, $conf );
	fclose( $fp );

	// Call pashua binary with config file as argument and read result
	$result = shell_exec( escapeshellarg( $path ) . ' ' . escapeshellarg( $config ) );
	@unlink( $config );
  // Parse result
	$parsed = array();
	foreach ( explode( "\n", $result ) as $line ) {
    preg_match( '/^(\w+)=(.*)$/', $line, $matches );
    if ( empty( $matches ) or empty( $matches[1] ) ) {
        continue;
    }
    $parsed[ $matches[1] ] = $matches[2];
	}

	if ( 1 == $parsed['cb'] ) {
		return false;
	}

	$workflow['workflow'] = [];
	$workflow['workflow']['version'] = $parsed['version'];
	if ( is_string( $workflow['packal']['osx'] ) ) {
		$workflow['packal']['osx'] = [];
	}
	foreach ( [
		'capitan' => '10.11',
	  'yosemite' => '10.10',
	  'mavericks' => '10.9',
	  'mountain' => '10.8',
	  'lion' => '10.7',
	  'snow' => '10.6'
	] as $name => $version ) :
		if ( 1 == $parsed[ $name ] ) {
			$workflow['packal']['osx'][] = $version;
		}
	endforeach;
	$workflow['packal']['osx'] = implode( ',', array_unique( $workflow['packal']['osx'] ) );
	$tags = array_unique( explode( '[return]', $parsed['tags'] ) );
	$workflow['packal']['tags'] = implode( ',', $tags );
	$categories = [];
	foreach ( $parsed as $key => $value ) :
		if ( false !== strpos( $key, 'cat_' ) ) {
			if ( $value ) {
				$categories[] = $pashua_categories[ $key ];
			}
		}
	endforeach;
	$workflow['packal']['categories'] = implode( ',', array_unique( $categories ) );

	// Do some minimal verification that it's a GH repo
	if ( $repo = verify_github( $parsed['github'] ) ) {
		$workflow['workflow']['github'] = $repo;
	}
	// Do some minimal verification that it's an Alfredforum url
	if ( $forum = verify_forum( $parsed['forum'] ) ) {
		$workflow['workflow']['forum'] = $forum;
	}

	return $workflow;
}

function verify_github( $repo ) {
	$repo = strtolower( $repo );

	if ( empty( $repo ) ) {
		return false;
	}
	if ( false !== strpos( $repo, 'github.com' ) ) {
		// They included a damn URL contrary to instructions, so just parse it and grab the path
		if ( parse_url( $repo, FILTER_VALIDATE_URL && FILTER_FLAG_PATH_REQUIRED ) ) {
			$repo = preg_replace( '/http[s]*:\/\/[w]{0,3}github\.com\//', '', $repo );
		}
	}
	if ( preg_match( '/^([A-Za-z0-9_-]*)(\/)([A-Za-z0-9_-]*)$/', $repo, $match ) ) {
		return $repo;
	} else {
		// Can't parse the repo
		return false;
	}
}

function verify_forum( $url ) {
	if ( filter_var( $url, FILTER_VALIDATE_URL && FILTER_FLAG_PATH_REQUIRED ) ) {
		return false;
	}
	if ( false === strpos( $url, 'alfredforum.com/' ) ) {
		return false;
	}
	return $url;
}

















