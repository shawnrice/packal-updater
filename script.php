<?php

require_once( 'libraries/workflows.php' );

// Get the version of OSX; if we aren't using Mavericks, then we'll need to download the PHP binary.
$osx = exec( "sw_vers | grep 'ProductVersion:' | grep -o '10\.[0-9]*'" );

$w      = new Workflows;
$q      = $argv;
$data   = $w->data();
$cache  = $w->cache();
$config = simplexml_load_file( "$data/config/config.xml" );
if ( ! file_exists( $data ) )
  mkdir( $data );
if ( ! file_exists( $cache) )
  mkdir ( $cache );

// Update the manifest if past cache.
exec( __DIR__ . "/cli/packal.sh update" );

// Do the workflow reporting script as long as the config option is set.
if ( $config->workflowReporting == 1 )
  exec( "nohup php " . __DIR__ . "/report-usage-data.php  > /dev/null 2>&1 &" );

$blacklist = json_decode( file_get_contents( "$data/config/blacklist.json" ), TRUE );
$manifest  = simplexml_load_file( "$data/manifest.xml" );
$json      = json_decode( file_get_contents( "$data/endpoints/endpoints.json" ), TRUE );
$mine      = array_keys( $json );
$count     = count( $manifest->workflow );
$me        = exec( "php " . __DIR__ . "/cli/packal.php getOption username" );

foreach ( $manifest->workflow as $wf ) :

  if ( in_array( $wf->bundle, $mine ) ) {
    $packal[] = (string) $wf->bundle;

    if ( $wf->author == $me )
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
  else 
    $w->result( 'updates', 'updates', 'Updates available', "There are " . count( $updates ) . " available updates.", '', 'no', 'update');

  if ( ( date( 'U', mktime() ) - date( 'U', filemtime( "$data/manifest.xml" ) < 86400 ) ) ) {
    $manifestTime = getManifestModTime();
    $w->result( '', '', 'The manifest is up to date.', "Last updated $manifestTime", '', 'yes', '');
  } else {
    $w->result( '', '', 'The manifest is out of date.', "Last updated $manifestTime", '', 'yes', '');
  }

  $w->result( 'open-gui', 'open-gui', 'Open GUI', 'Open GUI', '', 'yes', '');
  $w->result( '', '', 'Packal', "There are $count workflows in the manifest.", '', 'no', '');
  $w->result( '', '', 'Packal', "Of which, you have " . count( $packal ) . " installed.", '', 'no', '');
  $w->result( '', '', 'Packal', "And you wrote " . count( $mywf ) . " of those.", '', 'no', '');
  
  echo $w->toxml();
  die();
}

// $pattern = "/[uU]{1}[pP]{1}/";
// print_r( preg_match( $pattern, $q[1] ) );
// if ( preg_match( $pattern, $q[1] ) ) {
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

function count_files( $dir ) {
  $fi = new FilesystemIterator( "$dir" , FilesystemIterator::SKIP_DOTS);
  return iterator_count($fi);
}

function get_files( $dir ) {
  return array_diff( scandir( $dir ), array( '..', '.', '.DS_Store' ) );
}

function return_backups( $dir, &$w ) {
  global $data;

  $backupDirs = get_files( "$data/backups" );
  foreach ( $backupDirs as $b ) :
    if ( is_dir( "$data/backups/$b" ) ) {
      $backups[ $b ] = count_files( "$data/backups/$b" );
    }
  endforeach;

  if ( count( $backups ) > 0 ) {
    ksort( $backups );
    foreach ( $backups as $name => $count ) :
      $w->result( '', '', $name, "$count backups.", '', 'no', '');
    endforeach;
  }

}

function getManifestModTime() {

  global $data;

    // Set date/time things here.
  $m     = date( 'U', mktime() ) - date( 'U', filemtime( "$data/manifest.xml" ) );
  $days  = floor( $m / 86400 );
  $hours = floor( ( $m - ( $days * 86400 ) ) / 3600 );
  $mins  = floor( ( $m - ( $hours * 3600 ) ) / 60 );
  $secs  = floor( $m % 60 );

  if ( $m > ( 60 * 60 * 24 ) ) {
    if ( $m > ( 60 * 60 * 24 * 7) ) {
      if ( $m > ( 60 * 60 * 24 * 7 * 30) ) {
        if ( $m > ( 60 * 60 * 24 * 7 * 120) ) {
          $time = "a really long time ago.";
        }
        $time = "over a month ago.";
      }
      $time = "over a week ago.";
    } else {
      $time = "over a day ago.";
    }
  } else {
    $time = '';
    if ( $hours > 0 ) {
      $time .= $hours . ' hour';
      if ( $hours > 1 )
        $time .= 's, ';
      else
        $time .= ', ';
    }
    if ( $mins > 0 ) {
      $time .=  $mins . ' minute';
      if ( $mins > 1 )
        $time .= 's';
      else
        $time .= '';
    }
    if ( $hours > 0 && $mins > 0 )
      $time .= ', and ';
    else if ( $hours > 0 || $mins > 0 )
      $time .= ' and ';
    if ( $secs > 0 ) {
      $time .= $secs . ' second';
      if ( $time > 1 )
        $time .= 's';
    }
    $time .= ' ago.';
  }
  return $time;
}
?>
