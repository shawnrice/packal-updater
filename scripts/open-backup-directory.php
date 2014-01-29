<?php

/**
 * Just a single function to open the backups directory for the user.
 */

	// Set the user's home directory
	$home = exec('echo $HOME');
	
	// The location of the backups directory
	$backup_dir = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice/backups";

	// Escape the directory name because of the spaces...
	$backup_dir = escapeshellarg($backup_dir);

	// Just open it.
	exec("open $backup_dir");

	// Et, Voila! C'est fini!