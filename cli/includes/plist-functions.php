<?php

/**
 * Removes the hotkey and modifiers from a hotkey action in a workflow's info.plist file. Use pre-migration.
 *
 * @param string  $location The location in the plist in PlistBuddy speak
 * @param string  $plist    The location of the plist file
 * @return void
 */
function stripHotkey( $location, $plist ) {
	// I might consider adding error handling in this.

	// Just the first part of the command that finds PlistBuddy
	$PlistBuddy = "/usr/libexec/PlistBuddy -c ";

	// Reset the Hotstring (key)
	$cmd = $PlistBuddy . "\"set $location:hotstring \" '$plist'";
	exec( $cmd );

	// Reset the hotkey
	$cmd = $PlistBuddy . "\"set $location:hotkey 0\" '$plist'";
	exec( $cmd );

	// Reset the modifier keys
	$cmd = $PlistBuddy . "\"set $location:hotmod 0\" '$plist'";
	exec( $cmd );

}

/**
 * Set a value in a plist file.
 *
 * @param [type]  $location The key in PlistBuddy speak
 * @param [type]  $value    The value of the key
 * @param [type]  $plist    The plist file
 */
function setPlistValue( $location, $value, $plist ) {
	// I might consider adding error handling in this.

	// Just the first part of the command that finds PlistBuddy
	$PlistBuddy = "/usr/libexec/PlistBuddy -c ";

	// Set the value
	$cmd = $PlistBuddy . "\"set $location $value\" '$plist'";
	// echo $cmd;
	exec( $cmd );

}

/**
 * Sanitizes plist before import. To be called before the new Workflow is installed.
 *
 * @param string  $plist Location of the plist file
 * @return bool          Return true on success and false otherwise.
 */
function importSanitize( $plist ) {

	$wflow = new CFPropertyList( $plist );

	$tmp = $wflow->toArray();

	if ( ! isset( $tmp['objects'] ) ) {
		// This is a blank Workflow. Why are you updating it?
		return true;
	}

	foreach ( $tmp['objects'] as $key => $object ) {
		if ( $object['type'] == 'alfred.workflow.trigger.hotkey' ) {
			// We've found a hotkey, so let's strip it.
			$location = ":objects:$key:config";
			stripHotkey( $location, $plist );
		}
	}

	return true;
}

/**
 * Gets the name of a workflow from its info.plist file
 *
 * @param string  $dir the directory name of the workflow
 * @return string      the name of the workflow
 */
function getName( $dir ) {
	$PlistBuddy = "/usr/libexec/PlistBuddy -c ";
	$cmd = $PlistBuddy . "'print :name' $dir/info.plist";
	return exec( "$cmd" );
}

/**
 * Gets the bundle of a workflow from its info.plist file
 *
 * @param string  $dir the directory name of the workflow
 * @return string      the bundleid of the workflow
 */
function getBundle( $dir ) {
	$PlistBuddy = "/usr/libexec/PlistBuddy -c ";
	$cmd = $PlistBuddy . "'print :bundleid' $dir/info.plist";
	return exec( "$cmd" );
}


?>
