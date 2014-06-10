<?php

// Must be in workflow root directory.

require_once( 'libraries/workflows.php' );
require_once( 'functions.php' );

// Set date/time to avoid warnings/errors.
if ( ! ini_get('date.timezone') ) {
  $tz = exec( 'tz=`ls -l /etc/localtime` && echo ${tz#*/zoneinfo/}' );
  ini_set( 'date.timezone', $tz );
}

// Get the version of OSX; if we aren't using Mavericks, then we'll need to download the PHP binary.
$osx = exec( "sw_vers | grep 'ProductVersion:' | grep -o '10\.[0-9]*'" );

firstRun();

$bundle = 'com.packal';
$q     = $argv;
$w     = new Workflows;
$HOME  = exec( 'echo $HOME' );
$data  = "$HOME/Library/Application Support/Alfred 2/Workflow Data/$bundle";
$cache = "$HOME/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/$bundle";

if ( ! file_exists( $data ) )
  mkdir( "$data" );
if ( ! file_exists( $cache) )
  mkdir( "$cache" );
if ( ! file_exists( "$data/config" ) )
  mkdir( "$data/config" );
if ( ! file_exists( "$data/endpoints" ) )
  mkdir( "$data/endpoints" );

if ( ! file_exists( "$data/config/blacklist.json" ) ) {
  // Make an empty blacklist file if one doesn't exist.
  $blacklist = array();
  file_put_contents( "$data/config/blacklist.json", json_encode( $blacklist ) );
}

generateEndpoints();

if ( ! file_exists( "$data/config/config.xml" ) ) {
  $d = '<?xml version="1.0" encoding="UTF-8"?><config></config>';
  $config = new SimpleXMLElement( $d );  
  $config->packalAccount = 0;
  $config->forcePackal = 0;
  $config->backups = 3;
  $config->username = '';
  $config->authorName = '';
  $config->notifications = 'workflow';
  $config->apiKey = '';
  $config->asXML( "$data/config/config.xml" );
  unset( $config );
} else {
  $config = simplexml_load_file( "$data/config/config.xml" );
}

if ( ! file_exists( "$data/manifest.xml" ) ) {
  // This should be taken care of in the firstRun() call,
  // but the redundancy is fine.
    if ( getManifest() == FALSE ) {
      $w->result( '', '', 'Error: Packal Updater', 
        'The workflow manifest is not valid, and there is no valid Internet connection to retrieve a new one.', '', 'no', '');
      echo $w->toxml();
      die();
    }
}

// Update the manifest if past cache.
// This is a potential failure spot.
exec( "'" . __DIR__ . "/cli/packal.sh' update" );

// Do the workflow reporting script as long as the config option is set.
// Disabled for testing.
// if ( $config->workflowReporting == 1 )
//   exec( "nohup php " . __DIR__ . "/report-usage-data.php  > /dev/null 2>&1 &" );

$blacklist = json_decode( file_get_contents( "$data/config/blacklist.json" ), TRUE );
$manifest  = @simplexml_load_file( "$data/manifest.xml" );

// The manifest is not valid. Let's try to get it before we fail.
if ( empty( $manifest ) ) {
  if ( getManifest() == FALSE ) {
    $w->result( '', '', 'Error: Packal Updater', 
      'The workflow manifest is not valid, and there is no valid Internet connection to retrieve a new one.', '', 'no', '');
    echo $w->toxml();
    die();
  }
  // So, the we didn't get an error, but let's try to make sure it is valid XML.
  $manifest = @simplexml_load_file( "$data/manifest.xml" );
  if ( empty( $manifest ) ) {
    $w->result( '', '', 'Error: Packal Updater', 
      'The workflow manifest is not valid, and there is no valid Internet connection to retrieve a new one.', '', 'no', '');
    echo $w->toxml();
    die();
  }
}
    // Okay, we're good. Or, at least we should be.

$json      = json_decode( file_get_contents( "$data/endpoints/endpoints.json" ), TRUE );
$mine      = array_keys( $json );
$count     = count( $manifest->workflow );
$me        = exec( "php '" . __DIR__ . "/cli/packal.php' getOption username" );

foreach ( $manifest->workflow as $wf ) :

  if ( in_array( $wf->bundle, $mine ) ) {
    $packal[] = (string) $wf->bundle;

  if ( isset( $me ) && ( ! empty( $me ) ) && ( $wf->author == $me ) )
    $mywf[] = (string) $wf->bundle;

  }
endforeach;
unset( $wf );

// Grab manifest information.
foreach ( $manifest as $m ) :
  $manifestBundles[]               = $m->bundle;
  $wf[ "$m->bundle" ][ 'name' ]    = $m->name;
  $wf[ "$m->bundle" ][ 'author' ]  = $m->author;
  $wf[ "$m->bundle" ][ 'version' ] = $m->version;
  $wf[ "$m->bundle" ][ 'short' ]   = $m->short;
  $wf[ "$m->bundle" ][ 'updated' ] = $m->updated;
  $wf[ "$m->bundle" ][ 'url' ]     = $m->url;
endforeach;

foreach ( $json as $k => $v ) :
  $pattern = '/[\/A-Za-z0-9-_ .]{1,}\/user\.workflow\.([0-9A-Z.-]{1,})/';
  preg_match($pattern, $v, $matches );
  if ( isset( $matches[1] ) )
    $json[$k] = 'user.workflow.' . $matches[1];
endforeach;
foreach ( $json as $k => $v ) :
  if ( file_exists( "../$v/packal/package.xml" ) ) {
    $p = simplexml_load_file( "../$v/packal/package.xml" );
    if ( in_array( $k, $manifestBundles ) ) {
      if ( ($wf[ "$k" ][ 'updated' ] ) > $p->updated + 500 && ( ! in_array( $k, $blacklist ) ) ) {
        $updates[ $k ][ 'name' ] = (string) $p->name;
        $updates[ $k ][ 'path' ] = "../$v";
        $updates[ $k ][ 'version' ] = (string) $p->version;
      }
    }
  }
endforeach;

if ( empty( $q[1] ) ) {
  if ( ! isset( $updates ) )
    $w->result( 'updates', 'updates', 'All of your workflows are up to date.', "", '', 'no', 'update');
  else  {
    if ( count( $updates ) > 1 )
      $message = "There are " . count( $updates ) . " updates pending.";
    else
      $message = "There is 1 update pending.";

    $w->result( 'updates', 'updates', 'Updates available', $message, '', 'no', 'update');
  }

  if ( ( date( 'U', mktime() ) - date( 'U', filemtime( "$data/manifest.xml" ) < 86400 ) ) ) {
    $manifestTime = getManifestModTime();
    $w->result( '', '', 'The manifest is up to date.', "Last updated $manifestTime", '', 'yes', '');
  } else {
    $w->result( '', '', 'The manifest is out of date.', "Last updated $manifestTime", '', 'yes', '');
  }

  $w->result( 'open-gui', 'open-gui', "Open Graphical 'Application'", 
    'Configure this workflow, Update workflows, and learn about this workflow.', '', 'yes', '');
  if ( isset( $mywf ) )
    $mywf = count( $mywf );
  else
    $mywf = 0;
  $w->result( '', '', 'Informational',
    "There are $count workflows in the manifest," . 
    " of which, you have " . count( $packal ) .
    " installed, and you wrote " . $mywf . " of those.", '', 'no', '');
  
  echo $w->toxml();
  die();
}

if ( strpos( $q[1], 'update' ) !== FALSE ) {
  if ( ! isset( $updates ) )
    $updates = array();
  if ( count( $updates ) > 0 ) {
    if ( count( $updates ) > 1 ) {
      $w->result( 'update-all', 'update-all', "Update all workflows", '', '', '', '');
    }
    foreach( $updates as $k => $v ) :
      $w->result( "update-$k", "update-$k", "Update " . $v[ 'name' ], "Update from version " . $v[ 'version' ] . " to " . $wf[ $k ][ 'version' ], $v[ 'path' ] . "/icon.png", '', '');
    endforeach;
  } else {
    $w->result( '', '', "All of your workflows are up to date.", '', '', 'no', '');
  }
  echo $w->toxml();
  die();
}




  

?>
