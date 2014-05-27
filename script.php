<?php

require_once( 'libraries/workflows.php' );

$w = new Workflows;
$q = $argv;

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


$w->result( '', '', 'Open GUI', 'Open GUI', '', 'yes', '');
$w->result( '', '', 'Manifest', "There are $count workflows in the manifest.", '', 'no', '');
$w->result( '', '', 'Packal', "Of which, you have " . count( $packal ) . " installed.", '', 'no', '');
$w->result( '', '', 'Packal', "And you wrote " . count( $mywf ) . " of those.", '', 'no', '');

echo $w->toxml();


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
