<?php

/**
 *
 *	This file is called only via ajax. It simply opens the blacklist file and writes some stuff.
 * 
 */

// Check to see if all the relevant variables are set.
if ( (! isset($_POST) ) || (! isset($_POST['bundle']) ) || (! isset($_POST['dir']) ) || (! isset($_POST['method'] ) ) ) {
	echo "0";
	// The return value isn't necessary because it's called by ajax, but it's a simple way to exist the script.
	return false;
}

echo writeBlacklist( $_POST['bundle'] , $_POST['dir'] , $_POST['method'] );

/**
 * Takes an array entry and either adds it or removes it from the blacklist file
 * @param  array 	$blacklist 	an array that represents an xml file
 * @param  bool 	$method 	see if we need to blacklist or whitelist
 * @return bool            		just returns true for now
 */
function writeBlacklist ( $bundle , $dir , $method ) {
// @todo Add in error handling.
// The structure of the blacklist array is [$dir] => $bundle

	// Set the user's home directory
	$home = exec('echo $HOME');
	// The location of the data directory
	$data = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice/";
	// This is the blacklist file
	$file = $data . "blacklist/blacklist.xml";

	$tmp = array();
	$blacklist = getBlacklist();
	foreach ( $blacklist as $w ) {
		foreach ( $w as $k => $v ) {
			$tmp["$k"] = $v;
		}
	}

	$blacklist = $tmp;

// If it's already there and we're to blacklist, then just exit.
//	if ( in_array( $bundle , $blacklist) && ( $method ==  'blacklist') ) return false;
	// If we're trying to whitelist and it's not in the blacklist array, then just exit.
//	if ( (! in_array( $bundle , $blacklist) ) && ( $method == 'whitelist' ) ) return false;

	print_r($method);

	if ( $method == 'whitelist' ) {
		// Unset the blacklist by key
		unset($blacklist[array_search( $bundle , $blacklist )]);
	}

	if ( $method == 'blacklist' ) {
		// Push the new version into the blacklist array
		$blacklist[$dir] = $bundle;
	}
	asort( $blacklist );
	$xml  = "<?xml version='1.0' encoding='UTF-8' ?>\n";
	$xml .= "<blacklist>\n";

	// Since we've already altered the $blacklist array, then we can just rewrite the file
	foreach ( $blacklist as $dir => $bundle ) {
		$xml .= "\t<workflow>\n";
		$xml .= "\t\t<bundle>$bundle</bundle>\n";
		$xml .= "\t\t<dir>$dir</dir>\n";
		$xml .= "\t</workflow>\n";
	}
	$xml .= "</blacklist>\n";

	
	file_put_contents( $file , $xml );
	return true;
}

/**
 * Query the blacklist xml file and returns an array of blacklisted Workflows
 * @return xml object 	an xml object of the blacklist file
 */
function getBlacklist() {
	// Set the user's home directory
	$home = exec('echo $HOME');
	// The location of the data directory
	$data = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice/";
	$blacklist = $data . "blacklist/blacklist.xml";

	// If blacklist directory doesn't exist, then make it.
	if (! file_exists( $data . "blacklist") ) mkdir( $data . "blacklist" );
	// If blacklist file doesn't exist, then put in one that is basically empty.
	if (! file_exists( $data . "blacklist/blacklist.xml" ) ) {
		$blacklist  = "<?xml version='1.0' encoding='UTF-8' ?>\n";
		$blacklist .= "<blacklist>\n";
		$blacklist .= "</blacklist>\n";
		file_put_contents( $data . "blacklist/blacklist.xml" , $blacklist );
	}

	$blacklist = simplexml_load_file( $data . "blacklist/blacklist.xml" );
	$return = array();
	foreach ( $blacklist as $w ) {

		$bundle = (string)$w->bundle;
		$dir = (string)$w->dir;
		array_push( $return , array( $dir => $bundle ) );
	}
	return $return;
}