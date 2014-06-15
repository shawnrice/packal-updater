<?php

require_once( __DIR__ . '/includes/plist-migration.php' );
require_once( __DIR__ . '/../alfred.bundler.php' );
$cliDir = __DIR__;
$bundle   = "com.packal";
$HOME     = exec( 'echo $HOME' );
$data     = "$HOME/Library/Application Support/Alfred 2/Workflow Data/$bundle";
$cache    = "$HOME/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/$bundle";

$manifest = "$data/manifest.xml";
$config   = "$data/config/config.xml";

$repo = "https://github.com/packal/repository/raw/master";

if ( ! isset( $argv[1] ) ) {
  echo "ERROR: You need to specify an action.
";
  die();
}
$function = $argv[1];
unset( $argv[0] );
unset( $argv[1] );

$argv = array_values( $argv );

if ( function_exists( $function ) )
  call_user_func( "$function", $argv );
else
  echo "Undefined method: $function";


////////////////////////////////////////////////////////////////////////////////
/// Option functions

function getOption( $opt = array() ) {
  global $data, $config;

  $config = simplexml_load_file( $config );

  if ( isset( $opt[1] ) && ( $opt[1] == TRUE ) )
    return $config->$opt[0];

  echo $config->$opt[0];

}

function setOption( $opt = array() ) {
  global $config;

  $value = $opt[1];
  if ( $value == 'null' )
    $value = '';
  
  $options = array( 'backups',
                    'auto_add',
                    'workflowReporting',
                    'notify',
                    'username',
                    'authorName',
                    'api_key' );

  $bool   = array(  'auto_add',
                    'report',
                    'notify',
                    'packalAccount' );

  if ( ( ! in_array( $opt[0], $options ) ) && ( ! in_array( $opt[0], $bool ) ) ) {
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
  // To be written, but not needed for this version.
}

////////////////////////////////////////////////////////////////////////////////
/// Check single update

function checkUpdate( $wf ) {
  global $manifest, $cliDir;

  $wf = $wf[0];
  $dir = trim( `"$cliDir/packal.sh" getDir "$wf" 2> /dev/null` );
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

////////////////////////////////////////////////////////////////////////////////
/// Check and do all updates

/**
 * Checks updates for all workflows (that are on Packal)
 */
function checkUpdates( $opt = array() ) {
  global $manifest, $cache, $cliDir;

  print_r( $opt );

  if ( isset( $opt[0] ) ) {
    if ( $opt[0] == 1 )
      $yes = TRUE;
    else
      $yes = FALSE;
  } else {
    $yes = FALSE;
  }
  if ( isset( $opt[1] ) ) {
    if ( $opt[1] == 1 )
      $force = TRUE;
    else
      $force = FALSE;
  } else {
    $force = FALSE;
  }

  $xml = simplexml_load_file( "$manifest" );
  $me  = getOption( array( 'username', TRUE ) );

  if ( file_exists( "$cache/updates" ) )
    unlink( "$cache/updates" );

  $i = 1;
  foreach( $xml as $w ) :
    $dir = trim( `"$cliDir/packal.sh" getDir "$w->bundle" 2> /dev/null` );

    if ( $dir == "FALSE" )
      continue;

    if ( "$w->author" == "$me" ) {
      echo "* Skipping $i... $w->name ($w->bundle)\n";
      $i++;
      continue;
    }

    if ( file_exists( "$dir/packal/package.xml" ) ) {
      echo "Checking $i... $w->name ($w->bundle)";

      $wf = simplexml_load_file( "$dir/packal/package.xml" );
      $wf->updated += 120; // Compensation for time in the generated packages.

      if ( "$w->updated" > "$wf->updated" ) {
        echo " — Update Available";
        file_put_contents( "$cache/updates", $w->bundle . "\n", FILE_APPEND );
        $updatable[] = array( (string) $w->name, (string) $wf->version, (string) $w->version, (string) $w->bundle );
      }
      echo "\n";
      $i++;
    } else {
      if ( $force == FALSE )
        continue;

      echo "Checking $i... Forcing Update for $w->name ($w->bundle)";

      file_put_contents( "$cache/updates", $w->bundle . "\n", FILE_APPEND );
      $updatable[] = array( (string) $w->name, "Forced Update", (string) $w->version, (string) $w->bundle );
      echo "\n";
      $i++;

    }
    
  endforeach;

  if ( ( ! isset( $updatable ) ) || ( ! count( $updatable > 0 ) ) ) {
    echo "Everything is up to date.\n";
    return FALSE;
  }

  echo "Updates available for: ";
  $count = count( $updatable ) - 1;
  foreach ( $updatable as $u ) :
    echo "$u[0] ($u[1] -> $u[2])";
    if ( $count > 0 )
      echo ", ";
    else
      echo ".\n";
    $count--;
  endforeach;
  echo "\n";
  echo "* Note: Packal tracks updates on timestamps not version numbers, so if these seem off, then the workflow author did not update the version number.\n";
  echo "\n";
  $conf = getConfirmation( $yes );

  if ( $conf ) {
    foreach ( $updatable as $u ) :
      echo "Trying to update $u[0] ($u[3])...";
      if ( doUpdate( $u[3] ) ) {
        echo " Success.\n";
      } else {
        echo " ERROR.";
      }
    endforeach;
  } else {
    echo "Aborting.\n";
  }

  // While this method is incomplete, it doesn't quite matter for current functionality.


}

/**
 * Get update confirmation from the user via command line
 * @param bool $yes = FALSE Automatically confirm.
 */
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

/**
 * [doUpdate description]
 * @param {[type]} $bundle [description]
 * @param {[type]} $force  =             FALSE [description]
 */
function doUpdate( $bundle, $force = FALSE ) {

  global $data, $cache, $manifest, $repo, $cliDir;

  if ( is_array( $bundle ) ) {
    $bundle = $bundle[0];
  }


  $dir = trim( `"$cliDir/packal.sh" getDir "$bundle"` );
  // echo $dir;
  // The force variable means to download even if the original
  // is not from Packal. Obviously, since we don't have the
  // update data, this just means to download the update no
  // matter what and to get everything.
  if ( ! $force ) {
    if ( ! file_exists( "$dir/packal/package.xml" ) ) {
      echo "Error: No package information exists.";
      return FALSE;
    }
  }

  $xml = simplexml_load_file( "$manifest" );

  foreach ( $xml as $x ) :
    if ( "$x->bundle" != "$bundle" )
      continue;
    $xml = $x;
    break;
  endforeach;

  // Make the temporary directories.
  if ( ! file_exists( "$cache/update" ) )
    mkdir( "$cache/update" );
  if ( ! file_exists( "$cache/update/$bundle" ) )
    mkdir( "$cache/update/$bundle" );
  if ( ! file_exists( "$cache/update/$bundle/tmp" ) )
    mkdir( "$cache/update/$bundle/tmp" );

  `curl -sL "$repo/$bundle/$xml->file" > "$cache/update/$bundle/$xml->file"`;
  `curl -sL "$repo/$bundle/appcast.xml" > "$cache/update/$bundle/appcast.xml"`;
  `unzip -qo "$cache/update/$bundle/$xml->file" -d "$cache/update/$bundle/tmp"`;


  $valid = verifySignature( "$cache/update/$bundle/appcast.xml",
                            "$cache/update/$bundle/$xml->file",
                            "$dir/packal/$bundle.pub"
          );

  if ( ! $valid ) {
    echo "Error: Cannot verify signature for $xml->file from $bundle.";
    return FALSE;
  }

  // Why can't I just include the function, call it, and be done with it?
  // I think it might be a namespacing issue or something.
  // migratePlist( "$dir/info.plist", "$cache/update/$bundle/tmp/info.plist" );

  // It's too late to figure that out. Maybe tomorrow.
  $cmd = "php " . __DIR__ . "/includes/plist-migration.php \"$dir/info.plist\" \"$cache/update/$bundle/tmp/info.plist\"";
  exec( "$cmd" );

  // Backup the bundle.
  echo `"$cliDir/packal.sh" backup "$bundle"`;
  $cmd = "'" . __DIR__ . "/packal.sh' replaceFiles " . escapeshellarg( $dir ) . ' ' . escapeshellarg( $cache . '/update/' . "$bundle/tmp/");
  
  exec( "$cmd" );

  `rm -fR "$cache/update/$bundle"`;

  $tn = __load( 'terminal-notifier' , 'default' , 'utility' );
  exec( "$tn -title 'Packal Updater' -message '$xml->name has been updated to version $xml->version'" );

  echo "TRUE";
  return TRUE;

}

function doUpdateAll( $force = FALSE ) {
  global $manifest, $cache, $cliDir;

  $xml = simplexml_load_file( "$manifest" );
  $me  = getOption( array( 'username', TRUE ) );

  foreach( $xml as $w ) :
    $dir = trim( `"$cliDir/packal.sh" getDir "$w->bundle" 2> /dev/null` );

    if ( $dir === "FALSE" )
      continue;

    if ( "$w->author" == "$me" ) {
      continue;
    }

    if ( file_exists( "$dir/packal/package.xml" ) ) {
      $wf = simplexml_load_file( "$dir/packal/package.xml" );
      $wf->updated += 120; // Compensation for time in the generated packages.

      if ( "$w->version" != "$wf->version" ) {
        $updatable[] = array( (string) $w->name, (string) $wf->version, (string) $w->version, (string) $w->bundle );
      }
    } else {
      if ( $force == FALSE )
        continue;

      $updatable[] = array( (string) $w->name, "Forced Update", (string) $w->version, (string) $w->bundle );
    
    }
    
  endforeach;

  if ( ( ! isset( $updatable ) ) || ( ! count( $updatable > 0 ) ) )
    return FALSE;

  foreach ( $updatable as $u ) :
    doUpdate( $u[3] );
  endforeach;
}

/**
 * Checks to the signature of a package
 * @param  string 	$appcast 	an xml file containing the signature (path)
 * @param  string 	$package 	a file that has been signed (path)
 * @param  string 	$key     	the public key to use for checking (path)
 * @return [type]          [description]
 */
function verifySignature( $appcast , $package , $key ) {

  $appcast = simplexml_load_file( $appcast );
  $signature = $appcast->signature;

  $data = sha1_file( $package , false );

  // fetch public key from certificate and ready it
  $fp = fopen( $key , 'r' );
  $cert = fread( $fp , filesize( $key ) );
  fclose( $fp );

  // Get the public key
  $id = openssl_get_publickey( $cert );

  // Get the result of the signature
  $result = openssl_verify( $data , base64_decode( $signature ) , $id , OPENSSL_ALGO_SHA1 );

  // Free key from memory
  openssl_free_key( $id );

  // Return the result
  return $result;

}

////////////////////////////////////////////////////////////////////////////////
/// Strongarm functions
// This isn't implemented currently. For the future.
function forcePackal() {
global $manifest, $cache;

  $xml = simplexml_load_file( "$manifest" );
  $me  = getOption( array( 'username', TRUE ) );

}
