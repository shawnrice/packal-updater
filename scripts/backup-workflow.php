<?php

/**
 * This is a script that will backup the workflow to the data/backups directory.
 * Each Workflow gets its own directory, and multiple backups exist because a
 * datestamp is affixed to the backup, down to the minute. This script is called
 * by ajax. I need to put in another one that will prune the backups based on the
 * number of backups to keep as set in the configuration files.
 *
 * Currently, there is no error handling. Todo: implement error handling.
 *
 */

// Include the configuration functions so as to deal with pruning, etc...
require_once 'config-functions.php';
require_once '../resources/includes/date-and-time.php';

if ( isset( $_POST ) && ( ! empty( $_POST ) ) ) {
	if ( isset( $_POST['dir'] ) ) {
		$dir = $_POST['dir'];
	}


	// Okay, so let's set the base directory.
	// We'll just grab it from the shell with 'pwd' although we do need to
	// do a little directory magic first, and we have to kill the output that
	// pushd and popd put out.
	$base_dir = exec( "pushd ../../ > /dev/null; pwd ; popd > /dev/null;" );

	// Back it up
	backupWorkflow( "$base_dir/$dir" );
}

/**
 * Backs up a Workflow. I.e. zips the current Workflow folder, moves it to a backup
 * folder, and renames it.
 *
 * @param string  $dir Workflow directory (a uid)
 * @return bool        just 'true' for now. Will change to int with error handling.
 */
function backupWorkflow( $dir ) {

	// Get the bundle of the Workflow
	$bundle = exec( "/usr/libexec/PlistBuddy -c 'print :bundleid' $dir/info.plist" );

	// Get the name of the Workflow
	$name   = exec( "/usr/libexec/PlistBuddy -c 'print :name' $dir/info.plist" );

	// Create a datestamp to append to the file
	$date = date( 'Y-m-d-H-i' , time() );

	// Set the home directory
	$home = exec( 'echo $HOME' );
	// Set the data directory
	$data = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice";

	// Set the individual backup directory
	$backup_dir = $data . "/backups/$name";

	// If the backup directory doesn't exist, then create it.
	if ( ! file_exists( $backup_dir ) ) {
		mkdir( $backup_dir );
	}

	// zip the workflow into the backups directory with the name and the
	// date stamp with the alfredworkflow extension.
	// $backup_dir is really now the name of the backup file... convenience
	// and laziness? Or just poor naming?
	$backup_dir = $backup_dir . "/$name-$date.alfredworkflow";

	// So, since this is heading through the shell, let's escape the paths...
	$dir = escapeshellarg( $dir );
	$backup_dir = escapeshellarg( $backup_dir );

	// Construct the command by shifting the directories and zipping and moving back.
	// We're pushing and popping to avoid having the full path in the zip file.
	$cmd = "pushd $dir; zip -q -r $backup_dir ./; popd;";
	exec( $cmd );

	pruneBackups( $name );

	// For now, return true. Different return values will exist when error handling is introduced.
	return true;

}

/**
 * Prune backups to delete the older ones based on the number to keep via config file
 *
 * @param string  $name the name of the workflow
 * @return bool        for now, return true
 */
function pruneBackups( $name ) {

	// Set the home directory
	$home = exec( 'echo $HOME' );
	// Set the data directory
	$data = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice";

	// Set the individual backup directory
	$backup_dir = $data . "/backups/$name";

	$dir = scandir( $backup_dir );

	foreach ( $dir as $k => $entry ) {
		// Grab only the alfredworkflow files... nothing else
		// should be there (except for a .DS_Store)
		if ( ! preg_match( "/\.alfredworkflow/" , $entry ) ) {
			unset( $dir[$k] );
		}
	}

	// Rebase the $dir array
	$dir = array_values( $dir );

	// This should be unnecessary, but we'll do it anyway.
	// So, we'll just sort the array "alphabetically," and
	// since we have the regular naming pattern based on the
	// date stamps, we can assume the the first values are
	// the oldest
	asort( $dir );

	// Get the number of backups from the config file
	$options = loadConfig();
	$backup = $options->backup;

	// Get the number of files
	$number = count( $dir );

	// Only prune if the number is greater than the config
	if ( $number > $backup ) {
		$toPrune = $number - $backup;

		for ( $i=0; $i < $toPrune; $i++ ) {
			// Unlink the file (the file itself) with the full path
			unlink( "$backup_dir/" . $dir[$i] );
		}
	}

	// Just return true for now
	// add in some error handling later, although I don't think that
	// we need to do that.
	return true;
}
