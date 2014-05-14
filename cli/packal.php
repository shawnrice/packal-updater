<?php

$bundle   = "com.packal.shawn.patrick.rice";
$HOME     = exec( 'echo $HOME' );
$data     = "$HOME/Library/Application Support/Alfred 2/Workflow Data/$bundle";
$cache    = "$HOME/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/$bundle";

$manifest = "$data/manifest.xml";
$config   = "$data/config/config.xml";


$function = $argv[1];
unset( $argv[0] );
unset( $argv[1] );

$argv = array_values( $argv );

if ( function_exists( $function ) )
  call_user_func( "$function", $argv );
else
  echo "Undefined method: $function";



function getOption( $opt = array() ) {
  global $data, $config;

  $config = simplexml_load_file( $config );

  if ( isset( $opt[1] ) && ( $opt[1] == TRUE ) )
    return $config->$opt[0];

  echo $config->$opt[0];

}

function checkUpdate( $wf ) {
  global $manifest;

  $wf = $wf[0];
  $dir = trim( `./packal.sh getDir "$wf" 2> /dev/null` );
  $xml = simplexml_load_file( "$dir/packal/package.xml" );
  $last = $xml->updated;

  $xml = simplexml_load_file( "$manifest" );

  foreach ( $xml as $w ) :
    if ( $w->bundle == $wf ) {
      if ( "$w->updated" > "$last" ) {
        echo "Needs update.";
      }
    }
  endforeach;
}

function checkUpdates() {
  global $manifest, $cache;

  $xml = simplexml_load_file( "$manifest" );
  $me  = getOption( array( 'username', TRUE ) );

  if ( file_exists( "$cache/updates" ) )
    unlink( "$cache/updates" );

  $i = 1;
  foreach( $xml as $w ) :
    $dir = trim( `./packal.sh getDir "$w->bundle" 2> /dev/null` );

    if ( $dir == "FALSE" )
      continue;

    if ( "$w->author" == "$me" )
      continue;

    echo "Checking $i... $w->name";

    if ( file_exists( "$dir/packal/package.xml" ) ) {

      $wf = simplexml_load_file( "$dir/packal/package.xml" );
      $wf->updated += 120; // Compensation for time in the generated packages.
      echo " — Update Available";
      if ( "$w->updated" > "$wf->updated" )
        file_put_contents( "$cache/updates", $w->bundle . "\n", FILE_APPEND );
        $updatable[] = array( (string) $w->name, (string) $w->version );
    }
    echo "\n";

    $i++;
  endforeach;

  if ( ! count( $updatable > 0 ) )
    return FALSE;

  echo "Updates available for: ";
  $count = count( $updatable ) - 1;
  foreach ( $updatable as $u ) {
    echo "$u[0] ($u[1])";
    if ( $count > 0 )
      echo ", ";
    else
      echo ".\n";
    $count--;
  }
  echo "\n";

  $conf = getConfirmation( TRUE );
  print_r($conf);

}

function getConfirmation( $yes = FALSE ) {

  if ( $yes ) {
    echo "Update? (Y/n): y\n";
    return TRUE;
  }

  $conf = readline( "Update? (Y/n): " );

  if ( empty( $conf ) || ( $conf == 'y' ) || ( $conf == 'Y' ) )
    $response = TRUE;
  else if ( $conf == 'n' || $conf == 'N' )
    $response = FALSE;
  else {
    echo "Invalid entry. Please choose y or n.\n";
    $response = getConfirmation();
  }

  return $response;
}

function doUpdate( $bundle ) {

  

}

function setOption( $opt = array(), $value ) {

  global $config;

  $options = array( 'backup',
                    'auto_add',
                    'report',
                    'notify',
                    'username',
                    'api_key' );

  $bool   = array( 'auto_add',
                   'report',
                   'notify' );

  if ( ! in_array( $opt[0] ) ) {
    echo "Error: invalid option.";
    return FALSE;
  } else if ( in_array( $opt[0], $bool ) && ( ! ( $value == 0 || $value == 1 ) ) ) {
    echo "Error: $opt[0] value must be 0 or 1.";
    return FALSE;
  } else if ( ( $opt[0] == "backup" ) && ( ! is_numeric( $value ) ) ) {
    echo "Error: $opt[0] value must be numeric.";
  }

  // Load the config
  $xml = simplexml_load_file( $config );

  $xml->$opt[0] = $value;

  // Save the config again.
  $xml->asXml( $config );
}



function validateAPI( $key ) {
  // To be written.
}
