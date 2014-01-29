<?php

/**
 *
 *	This file gets information from nodes for the processing script.
 *
 * 	Invoke with drush.
 *
 * 	(e.g. drush @packal scr $nid $vid)
 * 
 */

$counter = 1;
// Stupidly, we have to get the args passed this way.
// The first arg is the nid, and the second is the vid.
while ( $arg = drush_shift() ) {
  if ( $counter == 1 ) $nid = $arg;
  else if ( $counter == 2 ) $vid = $arg;
  $counter++;
}

if ( empty( $nid ) || empty( $vid ) ) {
  print_r( 'Arguments not sent.' );
  return false;
}

$node = node_load( $nid , $vid );

if ( empty( $node ) ) {
	print_r( 'Bad arguments sent.' );
	return false;
}

$bundle = $node->field_bundle_id['und'][0]['value'];

$dir = "/www/files/packal/github/tmp/$bundle";

if ( file_exists( $dir ) ) {
// The file directory already exists, so garbage clean-up
// didn't happen. Let's just delete this and start again.
//  print_r('File already exists.');
  exec( "rm -fR /www/files/packal/github/tmp/$bundle" );
}

mkdir( "$dir" );
mkdir( "$dir/files" );

$changed 	= $node->changed;
$uid 		= $node->uid;
$name 		= $node->title;
$version 	= $node->field_version['und'][0]['value'];
$file_orig 	= $node->field_workflow_file['und'][0]['filename'];
$fid 		= $node->field_workflow_file['und'][0]['fid'];

$file = file_load($fid);
$filename = str_replace("private://workflow-files/$bundle/workflow/", '' , $file->uri);

file_put_contents( "$dir/changed" 	, $changed );
file_put_contents( "$dir/version" 	, $version );
file_put_contents( "$dir/bundle" 	, $bundle );
file_put_contents( "$dir/filename" 	, $filename );
file_put_contents( "$dir/fid" 		, $fid );
file_put_contents( "$dir/uid" 		, $uid );
file_put_contents( "$dir/name" 		, $name );
file_put_contents( "$dir/file_orig" , $file_orig );

// Basically, this returns the bundle. We're executing this script
// via an exec() call, so the output is captured as the variable.
// Thus, we get our bundle using this method.
echo $bundle;