<?php

require_once( 'libraries/workflows.php' );

$w     = new Workflows;
$q     = $argv;
$data  = $w->data();
$cache = $w->cache();

if ( ! file_exists( $data ) )
  mkdir( $data );
if ( ! file_exists( $cache) )
  mkdir ( $cache );

// Update the manifest if past cache.
exec( __DIR__ . "/cli/packal.sh update" );

$manifest = simplexml_load_file( "$data/manifest.xml" );
$json = json_decode( file_get_contents( "$data/endpoints/endpoints.json" ), TRUE );
$mine = array_keys( $json );
$count = count( $manifest->workflow );
$me = exec( "php " . __DIR__ . "/cli/packal.php getOption username" );

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
        if ( ($wf[ "$k" ][ 'updated' ] ) > $p->updated + 500) {
          $updates[ $k ][ 'name' ] = (string) $p->name;
          $updates[ $k ][ 'path' ] = "../$v";
          $updates[ $k ][ 'version' ] = (string) $p->version;
        }
      }
    }
  endforeach;

if ( empty( $q[1] ) ) {
  $w->result( 'open-gui', 'open-gui', 'Open GUI', 'Open GUI', '', 'yes', '');
  $w->result( '', '', 'Manifest', "There are $count workflows in the manifest.", '', 'no', '');
  $w->result( '', '', 'Packal', "Of which, you have " . count( $packal ) . " installed.", '', 'no', '');
  $w->result( '', '', 'Packal', "And you wrote " . count( $mywf ) . " of those.", '', 'no', '');
  $w->result( 'updates', 'updates', 'Updates available', "There are " . count( $updates ) . " available updates.", '', 'no', 'update');
  echo $w->toxml();
  die();
}

if ( $q[1] == 'update' ) {
  if ( count( $updates ) > 0 ) {
    if ( count( $updates ) > 1 ) {
      $w->result( 'update-all', 'update-all', "Update all workflows", '', '', '', '');
    }
    foreach( $updates as $k => $v ) :
      $w->result( "update-$k", "update-$k", "Update " . $v[ 'name' ], "Update from version " . $v[ 'version' ] . " to " . $wf[ $k ][ 'version' ], $v[ 'path' ] . "/icon.png", '', '');
    endforeach;
  } else {
    $w->result( '', '', "There are no updates", '', '', 'no', '');
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


?>
