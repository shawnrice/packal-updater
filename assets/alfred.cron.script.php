<?php

/*********
 *	This script is for Alfred Cron to interface with the Packal Updater
 *********/

$data = exec( 'echo $HOME' ) . '/Library/Application Support/Alfred 2/Workflow Data/com.packal';

if ( ! file_exists( "$data/config/config.xml" ) )
	die();

if ( ! file_exists( "$data/endpoints/endpoints.json" ) )
	die();

if ( ! file_exists( "$data/manifest.xml" ) )
	die();

if ( ! file_exists( "$data/config/blacklist.json" ) )
	die();

$config = simplexml_load_file( "$data/config/config.xml" );
$endpoints = json_decode( file_get_contents( "$data/endpoints/endpoints.json" ), TRUE );

$manifest  = @simplexml_load_file( "$data/manifest.xml" );
$blacklist = json_decode( file_get_contents( "$data/config/blacklist.json" ), TRUE );

require_once( $endpoints[ 'com.packal'] . '/functions.php' );

if ( checkConnection() === FALSE )
	die();

// Report usage data if config option is set to yes 
if ( isset( $config->workflowReporting ) && ( $config->workflowReporting == 1 ) )
	exec( "php '" . $endpoints[ 'com.packal' ] . "/report-usage-data.php'" );


// Grab manifest information.
foreach ( $manifest as $m ) :
  $manifestBundles[]               = $m->bundle;
  $wf[ "$m->bundle" ][ 'name' ]    = $m->name;
  $wf[ "$m->bundle" ][ 'author' ]  = $m->author;
  $wf[ "$m->bundle" ][ 'version' ] = $m->version;
endforeach;

$updates = FALSE;
foreach ( $endpoints as $k => $v ) :
if ( file_exists( "$v/packal/package.xml" ) ) {
  $w = simplexml_load_file( "$v/packal/package.xml" );

  if ( in_array( $k, $manifestBundles ) ) {
    if ( $wf[ "$k" ][ 'version' ] != (string) $w->version ) {
      if ( ! in_array( $k, $blacklist ) ) {
        $updates = TRUE;
        break;
      }
    }
  }
}
endforeach;

if ( $updates ) {
	require_once( $endpoints[ 'com.packal'] . '/alfred.bundler.php' );
	$tn = __load( 'terminal-notifier' , 'default' , 'utility' );
	$cmd = 'osascript -e "tell application \"Alfred 2\" to run trigger \"com.packal.start\" in workflow \"com.packal\" with argument \"updates\""';
	exec( "$tn -title 'Packal Updater' -message 'You have updates for your Alfred 2 workflows available.' -group 'packal-updater' -execute '$cmd'" );
}