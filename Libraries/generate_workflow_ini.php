<?php
/**
 * Contains functions to generate the workflow.ini files using Pashua as a
 * nice front-end interface. Theoretically, this allows editing of Workflow.ini
 * files as well through the interface, but I need to check that.
 */

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
	$return = pashua_dialog( $path, $workflow );
	$workflow = $return[0];
	if ( $workflow ) {
		Ini::write_ini( $workflow, $ini );
		return [ true, $return[1] ];
	}
	return [ false, $return[1] ];
}

/**
 * [pashua_dialog description]
 *
 * @todo this should be cleaned up
 *
 * @param  [type] $directory [description]
 * @param  [type] $workflow  [description]
 * @return [type]            [description]
 */
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

	if ( isset( $workflow['packal'] ) ) {
		if ( isset( $workflow['packal']['categories'] ) ) {
			$cats = $workflow['packal']['categories'];
		}
		if ( isset( $workflow['packal']['tags'] ) ) {
			$tags = implode( '[return]', $workflow['packal']['tags'] );
		}
	}
	if ( isset( $workflow['workflow'] ) ) {
		if ( isset( $workflow['workflow']['forum'] ) ) {
			$forum = $workflow['workflow']['forum'];
		}
		if ( isset( $workflow['workflow']['github'] ) ) {
			$forum = $workflow['workflow']['github'];
		}
	}

	foreach( [ 'tags', 'github', 'forum' ] as $var ) :
		$$var = ( isset( $$var ) ) ? $$var : '';
	endforeach;

	// Do some wonky stuff to get the categories. I'm doing this so that I can keep the categories in a json file.
	$alphabet = 'abcdefghijklmnopqrstuvwxyz';
	$categories = json_decode( file_get_contents( __DIR__ . '/../assets/categories.json' ), true );
	$cats = ( isset( $cats ) ) ? $cats : [];
	$temp_categories = '';
	$y = 300;
	foreach ( range( 'a', substr( $alphabet, count( $categories ) - 1, 1 ) ) as $key => $suffix ) :
		if ( 0 === $key % 2 ) {
			$temp_categories .= "cat_{$suffix}.x = 600\r\n";
			$y -= 20;
		} else {
			$temp_categories .= "cat_{$suffix}.x = 400\r\n";
		}
		$temp_categories .= "cat_{$suffix}.y = {$y}\r\n";
		$temp_categories .= "cat_{$suffix}.type = checkbox\r\n";
		$temp_categories .= "cat_{$suffix}.label = {$categories[ $key ]}\r\n";

		$temp_categories .= "cat_{$suffix}.default = ";
		$temp_categories .= ( in_array( $categories[ $key ], $cats ) ) ? "1" : "0";
		$temp_categories .= "\r\n";

		$pashua_categories[ "cat_{$suffix}" ] = $categories[ $key ];
	endforeach;

	$values = [
		'name' => $plist['name'],
		'bundle' => $plist['bundleid'],
		'short' => $plist['description'],
		'tags' => $tags,
		'github' => $github,
		'forum' => $forum,
		'CATEGORIES' => $temp_categories,
	];
	if ( ! $parsed = Pashua::go( 'pashau-workflow-config.ini', $values ) ) {
		return false;
	}

	// Initialize some variables.
	$workflow = [];
	$workflow['workflow'] = [];
	$workflow['packal'] = [];
	$workflow['packal']['osx'] = [];

	// Fix the version as a "just-in-case"
	$workflow['workflow']['version'] = SemVer::fix( $parsed['version'] );

	$osx = [
		'capitan' => '10.11',
	  'yosemite' => '10.10',
	  'mavericks' => '10.9',
	  'mountain' => '10.8',
	  'lion' => '10.7',
	  'snow' => '10.6'
	];
	foreach ( $osx as $name => $version ) :
		if ( 1 == $parsed[ $name ] ) $workflow['packal']['osx'][] = $version;
	endforeach;

	$workflow['packal']['osx'] = implode( ',', array_unique( $workflow['packal']['osx'] ) );
	$workflow['packal']['tags'] = implode( ',', array_unique( explode( '[return]', $parsed['tags'] ) ) );

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

	return [ $workflow, $plist['name'] ];
}

/**
 * Verifies (/fixes if possible) the Github repo
 *
 * Note: it does not verify that the repo exists on Github
 *
 * @param  string $repo the repo name as 'owner/repo'
 * @return string|bool  the (fixed) repo or false
 */
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
		// can't parse the repo
		return false;
	}
}

/**
 * Verifies that the string is a URL and that it has `alfredforum` in it
 * @param  string $url an alfredforum url
 * @return string|bool the alfred forum url or false
 */
function verify_forum( $url ) {
	if ( filter_var( $url, FILTER_VALIDATE_URL && FILTER_FLAG_PATH_REQUIRED ) ) {
		return false;
	}
	if ( false === strpos( $url, 'http://www.alfredforum.com/topic/' ) ) {
		return false;
	}
	return $url;
}

















