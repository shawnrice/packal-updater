<?php

///// I need to add in a check to make sure that the plist has a bundle set in it

$qdir = '/www/data/files/packal/queues/process';
$queue = scandir($qdir);


if ( count($queue) > 2 ) {
	// We have something in the queue, so we'll start.
	// We're just going to process one workflow at a time to make sure we don't overload the server.
	$result = processWorkflow($queue[2]);

	// Start to do the last phase of error handling.
	// We don't have to worry about logging the errors or even reacting on them too much
	// because all of that has happened below. But, just in case, here is a table of errors.
	switch ($result) {
		case 999:
			// Success
			break;
		case 1:
			// No arguments sent to Drush script. Something went terribly wrong with the queue. Problem with "rules."
			break;
		case 2:
			// Drush arguments didn't represent a valid nid/vid. Problem with "rules."
			break;			
		case 15:
			// Bad zip file
			break;
		case 25:
			// Virus detected in workflow file
			break;			
		case 30:
			// Couldn't create directory in zip file
			break;
		case 35:
			// The zip file was found bad on the second try. This should never happen.
			break;				
		default:
			# code...
			break;
	}
	echo $result;
	echo "What";
}

/**
 * The long function to process workflow files
 * @param  string 	$file 	the file with the information of the node revision to parse (filename is nid, contents are vid)
 * @return int       		an error or success code
 */
function processWorkflow( $file ) {
	
	$start = microtime(TRUE);

	$github    = "/www/data/files/packal/repository";
	$queue     = "/www/data/files/packal/queues/process";
	$scripts   = "/www/data/files/packal/scripts";
	$resources = "/www/data/files/packal/resources";
	$logs 	   = "/www/data/files/packal/logs";
	$tmp 	   = "/www/data/files/packal/tmp";

	$nid = $file;
	$vid = file_get_contents("$queue/$file");
	unlink("$queue/$file"); // Delete the file so that another cron doesn't take up the file in case this takes a minute
	unset($file); // Remove the variable. We'll use that space for something else later.

	// This next script is somewhat embarassing, but it's functional and minimizes a performance hit.
	// We create a working directory at github/tmp/UUID where we copy variables into text files.
	$bundle = exec("drush @packal scr $scripts/get-node-properties.php $nid $vid");

	if ( $bundle == 'Arguments not sent.' ) {
		return 1;
	} else if ( $bundle == 'Bad arguments sent.' ) {
		return 2;
	}

	// The set the working directory
	$dir = "$tmp/$bundle";


	$bundle 	= file_get_contents( "$dir/bundle" );
	$version 	= file_get_contents( "$dir/version" );
	$filename 	= file_get_contents( "$dir/filename" );
	$changed 	= file_get_contents( "$dir/changed" );
	$name 		= file_get_contents( "$dir/name" );
	$file_orig 	= file_get_contents( "$dir/file_orig" );
	$fid 		= file_get_contents( "$dir/fid" );
	$uid 		= file_get_contents( "$dir/uid" );

	// copy the workflow file to the tmp directory
	copy("/www/sites/packal/sites/default/files/private/workflow-files/$bundle/workflow/$filename","$tmp/$bundle/files/$filename");

	$file = "$tmp/$bundle/files/$filename"; // So, here's the local copy of the workflow.

	$log  = "/www/data/files/packal/logs/$bundle-".date( 'U' , mktime() ) . ".log";

	writeLog( $log , "Started processing $name ($bundle) version $version at " . date( 'U' , mktime() ) );
	writeLog( $log , "--------" );
	writeLog( $log , "Version submitted for processing at $changed.");

	writeLog( $log , "Checking the integrity of the zip file." );

	$cmd = "unzip -t -qq $file; ";
	$cmd .= 'echo $?';
	$zip = exec($cmd);

	$zip = checkZip( $file );

	if ( $zip != 0 ) {
		writeLog( $log , "ERROR: Workflow zip file failed integrity check." );
		respondToError( $nid , $vid , $uid , $filename , $fid , $bundle , $log , $name , $version , "zip" );

		return 15; // Bad zip file.
	}


	writeLog( $log , "Initializing ClamAV for virus scan.");
	$scan_start = microtime(TRUE);

	//////
	// Files are in place. Start the virus scan.
	//////

	$command = 'clamscan ' . $file;
	$out = '';
	$int = -1;

	exec($command, $out, $int);

	// End scan timer. Convert microseconds to seconds.
	$scan_duration = round( (microtime(TRUE) - $scan_start) / 1000000 , 2 );

	writeLog( $log , "Scan completed in $scan_duration seconds.");

	if ($int != 0) {
		
		writeLog( $log , "ERROR: Virus found in workflow file. Deleting file, file entity, node revision. Notifying Packal and Author." );
		respondToError( $nid , $vid , $uid , $filename , $fid , $bundle , $log , $name , $version , 'virus' ); // Function not written yet.

		return 25;
	}

	// Workflow file is free of virus.
	writeLog( $log , "No virus detected." );
	
	///////
	// Create keys for the workflow itself
	// only if the keys don't already exist
	
	if (! file_exists("/www/data/files/packal/resources/keys/private/$bundle.pem")) {

		$res = openssl_pkey_new();

		// Get private key
		openssl_pkey_export($res, $private);

		// Get public key
		$public = openssl_pkey_get_details($res);

		// Put the Private Key in the directory and fix perms
		file_put_contents("/www/data/files/packal/resources/keys/private/$bundle.pem", $private);
		chmod("/www/data/files/packal/resources/keys/private/$bundle.pem", 0600);

		// Put the Public Key in the directory and fix perms
		file_put_contents("/www/data/files/packal/resources/keys/public/$bundle.pub", $public["key"];);
		chmod("/www/data/files/packal/resources/keys/public/$bundle.pub", 0644);
	}

	///////
	// Modify the workflow by putting in some info.
	
	$pkgData   = generateAppcast( $name , $version , $bundle , $changed , $file_orig , false );
//	print_r($pkgData);
	$key = "/www/data/files/packal/resources/keys/public/$bundle.pub";

	/// Create Packal directory inside 
	$zip = new ZipArchive;
	if ($zip->open("$file") === TRUE) {
	    if($zip->addEmptyDir('packal')) {
	        // echo 'Created a new root directory';
	    } else {
	    	// Couldn't create the damn directory. Something is wrong.
	    	writeLog( $log, "ERROR: Couldn't create packaging directory in Workflow file." );
	        return 30;
	    }
	    // Add the packaging information
    	$zip->addFromString( 'packal/package.xml' , $pkgData );
    	// Add the pubkey
    	$zip->addFile( $key , "packal/$bundle.pub" );
	    $zip->close();
	} else {
		// We've already checked for this error, so we should never get here.
	    return 35;
	}




	// We've finished packaging the Workflow file, so now we sign it and create the appcast
	writeLog( $log , "Generating signature." );
	$signature = generateSignature( $bundle , $file );

	writeLog( $log , "Creating Appcast." );
	$appcast   = generateAppcast( $name , $version , $bundle , $changed , $file_orig , $signature );

	// Okay, everything is processed, so let's move it to Github.
	writeLog( $log , "Everything is processed, starting to move the files to Github from the tmp directory." );

	// Check if the file exists in the Github Repo folder.
	// Folders in the repository are named by the bundle id.
	if (! file_exists( "$github/$bundle" ) ) {
		writeLog( $log , "New Workflow; creating folder." );
		mkdir( "$github/$bundle" );
	} else {
		writeLog( $log , "Workflow update: cleaning out old files" );
		exec( "rm -fR $github/$bundle/*");
	}

	copy( "$file" , "$github/$bundle/$file_orig" );
	file_put_contents( "$github/$bundle/appcast.xml" , "$appcast" );

	// I need to check for errors with the appcast bundle

	// The revision go live on Packal
	makeRevisionLive( $nid , $vid ); // Call a drush file that will publish the revision and change the workflow.

	// Download the new manifest; I need to make sure that this avoids caching.
	$manifest = file_get_contents( 'http://packal.org/manifest' );
	file_put_contents( "$github/manifest.xml" , "$manifest" );

	//////
	// Everything is now in place, so, let's send it to github
	//////

	$gitTree = "$github";

	// Add all the files
	$command = "git --git-dir $github/.git --work-tree $gitTree/ add $github/*";
	exec($command);

	// Commit changes
	$command = "git --git-dir=$github/.git --work-tree $github/ commit -am 'updated for $bundle version: $version'";
	exec($command);

	// Push it to the repo
	$command = "git --git-dir=$github/.git --work-tree $github/ push origin";
	exec($command);

	// I need to check for errors with the git push




	exec("drush @packal scr /www/data/files/packal/scripts/load_user.php $uid $uuid");

	$user = file_get_contents( "$tmp/$bundle/username");
	$email = file_get_contents( "$tmp/$bundle/mail");

	sendMail( $user , $email , $name , $version , 'success' );

	// Clean up the tmp and working files
	garbageCollection( $dir );

	$end = microtime(TRUE);

	writeLog( $log , "Processing script completed at " . date( 'U' , mktime() ) . " taking " . $end/1000000 . " seconds." );

	return 999; // Success!

}

/**
 * Removes the temporary working directory.
 * @param  string 	$dir 	the location of the temporary working directory
 * @return void
 */
function garbageCollection( $dir ) {

	exec("rm -fR $dir");

}

/**
 * Invoke the sendmail drush script.
 * @param  string 	$user    the name of the user
 * @param  string 	$email   the email of the user
 * @param  string 	$name    the name of the workflow
 * @param  string 	$version the version of the workflow
 * @param  string 	$kind    the type of message ('success' , 'virus' , 'zip' )
 * @return void        		 null, for now
 */
function sendMail( $user , $email , $name , $version , $kind ) {

	// Add in error checking.
	
	exec("drush @packal scr /www/files/packal/github/scripts/drush-mail-script.php \"$user\" \"$email\" \"$name\" \"$version\" \"$kind\"");

}

/**
 * Generates a base64 encoded signature using a private key
 * @param  string 	$file 	the location of the file to sign
 * @param  string   $bundle the bundle id
 * @return string       	a base64 encoded signature
 */
function generateSignature ( $bundle , $file ) {

	// Get the hash of the modified Workflow file.
	$data = sha1_file( $file, false );
	$signature = null;

	////////////////////////////
	// SIGN
	////////////////////////////

	// fetch public key from certificate and ready it
 
	$fp = fopen( "/www/data/files/packal/resources/keys/private/$bundle.pem" , "r" );
	$cert = fread( $fp , 8192 );
	fclose( $fp );
	$privateid = openssl_get_privatekey( $cert );

	$sigraw = null;
	openssl_sign( $data , $sigraw , $privateid , OPENSSL_ALGO_SHA1 );

	// Return the encoded signature.
	return base64_encode( $sigraw );

}

/**
 * Generates an xml of relevant info for the appcast
 * @param  string 	$name      	the title of the workflow
 * @param  string 	$version   	the version of the workflow
 * @param  string 	$bundle    	the bundle id of the workflow
 * @param  int 		$changed   	time updated in Unix Epoch
 * @param  string 	$filename  	the name of the workflow file
 * @param  mixed 	$signature 	base64 encoded ssl signature or false
 * @return string            	an xml file as a string
 */
function generateAppcast( $name , $version , $bundle , $changed , $filename , $signature ) {

	$contents  = "<?xml version='1.0' encoding='UTF-8' ?>\n";
	$contents .= "<workflow>\n";
	$contents .= "  <name>$name</name>\n";
	$contents .= "  <version>$version</version>\n";
	$contents .= "  <bundle>$bundle</bundle>\n";
	$contents .= "  <updated>$changed</updated>\n";
	$contents .= "  <file>$filename</file>\n";
	if ( $signature ) $contents .= "  <signature>$signature</signature>\n"; // We don't put the signature in the Workflow file.
	$contents .= "</workflow>\n";

	return $contents;
}

/**
 * Does an unzip test via the shell and returns the code
 * @param  string $file 	the workflow file
 * @return int       		an error code from the zip test (0 is fine)
 */
function checkZip( $file ) {

	return exec("unzip -t $file 2> /dev/null; echo $?");
	
}

/**
 * Uses Drush to get the email address of a user.
 * @param  int 		$uid 	a user id
 * @return string       	the email address of a registered user
 */
function getUserEmail( $uid ) {

	return exec("drush @packal ev '$user = user_load($uid); echo $user->mail;'");

}

function respondToError( $nid , $vid , $uid , $filename , $fid , $bundle , $log , $name , $version , $error ) {
	// Called when we detect a virus in the workflow file or another problem
	// If $error == virus, then we go through the extra reporting.
	// If $error == zip, then the zip file is bad
	/**
	 * 	Steps:
	 *  	1 Delete the revision
	 *  	2 Delete the file
	 *  	3 Delete the file entity (do I need to do step 2 and 3, or does 1 take care of it?)
	 *  	4 Notify the author
	 *  	5 Log error in watchdog
	 *  	6 Notify me and send log
	 *  	-- 	Future, maybe I can have a strike system that would ban a user who continually
	 *  		uploads virii. This is down the road.
	 */
	

	// Delete the revision; returns either true or false.
	$revision = exec("drush @packal ev 'echo node_revision_delete($vid);'");

	// Get user variables for email
	exec("drush @packal scr /www/data/files/packal/scripts/load_user.php $uid $bundle");
	$user = file_get_contents( "$tmp/$bundle/username");
	$email = file_get_contents( "$tmp/$bundle/mail");

	// Send user email notice of error.
	sendMail( $user , $email , $name , $version , $error );

	// Construct the link for the log message
	$link = "http://packal.org/node/$nid";

	// Set the log message
	// Below isn't working.
	//	exec("drush @packal ev 'watchdog(\"Packal Processing\" , \"$name version $version error: $error\" , array() , \"WATCHDOG_ERROR\" , $link)';");

	return true;
}


/**
 * Write a line to a log file
 * @param  string 	$log     	destination of log file
 * @param  string 	$message 	message to log
 * @return void
 */
function writeLog( $log , $message ) {
	
	if (! file_exists( $log ) ) {
		file_put_contents( $log , $message . "\n" );
	} else {
		file_put_contents( $log , $message . "\n" , FILE_APPEND | LOCK_EX );
	}

}

/**
 * Calls Drush commands to publish the node revision on Packal
 * @param  int 	$nid 	The node id
 * @param  int 	$vid 	The revision id
 * @return [type]      [description]
 */
function makeRevisionLive( $nid , $vid ) {
    return exec("drush @packal scr /www/data/files/packal/scripts/drush-publish-revision.php $nid $vid");
//	return exec("drush @packal ev '$node = node_load( $nid , $vid ); $node->status = 1; $msg = node_save($node); print_r($msg);'");

}