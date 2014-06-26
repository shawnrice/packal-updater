<?php

// Must be in workflow root directory.

// @TODO: Rewrite all of this as a class so that we can use more persistent variables
// and test connections fewer times.

require_once( 'libraries/workflows.php' );
require_once( 'functions.php' );

// Set date/time to avoid warnings/errors.
if ( ! ini_get('date.timezone') ) {
  $tz = exec( 'tz=`ls -l /etc/localtime` && echo ${tz#*/zoneinfo/}' );
  ini_set( 'date.timezone', $tz );
}



// Get the version of OSX; if we aren't using Mavericks, then they don't get access to the GUI.
$osx = exec( "sw_vers | grep 'ProductVersion:' | grep -o '10\.[0-9]*'" );

// So, they can use the GUI if they're using Mavericks or Yosemite.
if ( ( $osx == '10.9') || ( $osx == '10.10' ) )
  $gui = TRUE;

firstRun();

$bundle = 'com.packal';
$q      = $argv;
$w      = new Workflows;
$HOME   = exec( 'echo $HOME' );
$data   = "$HOME/Library/Application Support/Alfred 2/Workflow Data/$bundle";
$cache  = "$HOME/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/$bundle";

$connection = checkConnection();

if ( $connection === FALSE ) {
      $w->result( '', '', 'Warning: no viable Internet connection', 
      'Some features of this workflow will be unavailable without a solid Internet connection', 'assets/icons/task-attention.png', 'no', '');
}

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
  file_put_contents( "$data/config/blacklist.json", utf8_encode( json_encode( $blacklist ) ) );
}

generateEndpoints();

// Not all of these are used right now.
if ( ! file_exists( "$data/config/config.xml" ) ) {
  $d = '<?xml version="1.0" encoding="UTF-8"?><config></config>';
  $config = new SimpleXMLElement( $d );  
  $config->packalAccount = 0;
  $config->forcePackal = 0;
  $config->backups = 3;
  $config->username = '';
  $config->authorName = '';
  $config->workflowReporting = '1';
  $config->notifications = '2';
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
        'The workflow manifest is not valid, and there is no valid Internet connection to retrieve a new one.', 'assets/icons/task-reject.png', 'no', '');
      echo $w->toxml();
      die();
    }
}

// Update the manifest if past cache.
// This is a potential failure spot.
if ( $connection !== FALSE )
  exec( "'" . __DIR__ . "/cli/packal.sh' update" );

// Do the workflow reporting script as long as the config option is set.
if ( $config->workflowReporting == '1' )
  exec( "nohup php '" . __DIR__ . "/report-usage-data.php'  > /dev/null 2>&1 &" );

$blacklist = json_decode( file_get_contents( "$data/config/blacklist.json" ), TRUE );
$manifest  = @simplexml_load_file( "$data/manifest.xml" );

// The manifest is not valid. Let's try to get it before we fail.
if ( empty( $manifest ) ) {
  if ( getManifest() === FALSE ) {
    $w->result( '', '', 'Error: Packal Updater', 
      'The workflow manifest is not valid, and there is no valid Internet connection to retrieve a new one.', 'assets/icons/task-reject.png', 'no', '');
    echo $w->toxml();
    die();
  }
  // So, the we didn't get an error, but let's try to make sure it is valid XML.
  $manifest = @simplexml_load_file( "$data/manifest.xml" );
  if ( empty( $manifest ) ) {
    $w->result( '', '', 'Error: Packal Updater', 
      'The workflow manifest is not valid, and there is no valid Internet connection to retrieve a new one.', 'assets/icons/task-reject.png', 'no', '');
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
      if ( (string) $p->version != $wf[ "$k" ]['version'] ) {
        if ( ! in_array( $k, $blacklist ) ) {
          $updates[ $k ][ 'name' ] = (string) $p->name;
          $updates[ $k ][ 'path' ] = "../$v";
          $updates[ $k ][ 'version' ] = (string) $p->version;
        }
      }
    }
  }
endforeach;

if ( empty( $q[1] ) ) {
  if ( ! isset( $updates ) )
    $w->result( 'updates', 'updates', 'All of your workflows are up to date.', "", 'assets/icons/task-complete.png', 'no', '');
  else  {
    if ( count( $updates ) > 1 )
      $message = "There are " . count( $updates ) . " updates pending.";
    else
      $message = "There is 1 update pending.";

    $w->result( 'updates', 'updates', 'Updates available', $message, '', 'no', 'update');
  }

  if ( ( date( 'U', mktime() ) - date( 'U', filemtime( "$data/manifest.xml" ) < 86400 ) ) ) {
    $manifestTime = getManifestModTime();
    $w->result( '', 'manifest-update', 'The manifest is up to date.', "Last updated $manifestTime", 'assets/icons/task-complete.png', 'yes', '');
  } else {
    $w->result( '', 'manifest-update', 'The manifest is out of date.', "Last updated $manifestTime", 'assets/icons/task-attention.png', 'yes', '');
  }

  if ( $gui === TRUE ) {
    $w->result( 'open-gui', 'open-gui', "Open Graphical 'Application'", 
    'Configure this workflow, Update workflows, and learn about this workflow. (RECOMMENDED)', 'assets/icons/applications-education-miscellaneous.png', 'yes', '');
  } else {
    $w->result( '', '', "GUI Not Available'", 
    'A GUI to configure and operate this workflow is available if you have OS X 10.9 or 10.10.', 'assets/icons/applications-education-miscellaneous.png', 'no', '');
  }

  if ( isset( $mywf ) )
    $mywf = count( $mywf );
  else
    $mywf = 0;

  // if ( isset( $config->authorName ) && ( ! empty( $config->authorName ) ) {
  //   $w->result( '', '', 'Informational',
  //     "There are $count workflows in the manifest," . 
  //     " of which, you have " . count( $packal ) .
  //     " installed, and you wrote " . $mywf . " of those.", '', 'no', '');    
  // }

  $w->result( 'blacklist', 'blacklist', 'Manage Blacklist', 'Configure which workflows Packal updates', 'assets/icons/flag-black.png', 'no', 'blacklist' );

  $w->result( '', '', 'Informational',
    "There are $count workflows in the manifest," . 
    " of which, you have " . count( $packal ) .
    " installed, and you wrote " . $mywf . " of those.", 'assets/icons/help-about.png', 'no', '');

    $w->result( '', 'setup', 'Configure', 'Make this workflow work best for you.', 'assets/icons/applications-system.png', 'no', 'setup');
  
  // Option to install a cron script
  // 
  if ( file_exists( '../' . $json[ 'alfred.cron.spr' ] ) && 
        ( ! file_exists( "$HOME/Library/Application Support/Alfred 2/Workflow Data/alfred.cron.spr/scripts/packal_updater") ) ) {
    $plist = '../' . $json[ 'alfred.cron.spr' ] . '/info.plist';
    $icon = '../' . $json[ 'alfred.cron.spr' ] . '/icons/timer.png';
    if ( exec( "/usr/libexec/PlistBuddy -c \"Print :disabled\" '$plist' 2> /dev/null" ) != 'true' )
      $w->result( '', 'install-cron-script', 'Install Alfred Cron Script', 'Make Alfred Cron check for updates for you.', "$icon", 'yes', '');
  }

  echo $w->toxml();
  die();
}

if ( strpos( $q[1], 'update' ) !== FALSE ) {
  if ( ! isset( $updates ) )
    $updates = array();
  if ( count( $updates ) > 0 ) {
    if ( count( $updates ) > 1 ) {
      // Don't allow the updates if there is no internet connection.
      if ( $connection !== FALSE )
        $w->result( 'update-all', 'update-all', "Update all workflows", '', '', 'yes', '');
    }
    foreach( $updates as $k => $v ) :

      // Get the workflow icon, if it exists, otherwise, fallback to package icon
      if ( file_exists( $v[ 'path' ] . "/icon.png" ) )
        $icon = $v[ 'path' ] . "/icon.png";
      else
        $icon = 'assets/icons/package.png';
      if ( $connection !== FALSE )
        $w->result( "update-$k", "update-$k", "Update " . $v[ 'name' ], "Update version " . $v[ 'version' ] . " => " . $wf[ $k ][ 'version' ], $v[ 'path' ] . "/icon.png", '', '');
      else
        $w->result( "update-$k", '', "An update for " . $v[ 'name' ] . ' is available.', "Update version " . $v[ 'version' ] . " => " . $wf[ $k ][ 'version' ] . '. << Exception: no viable Internet connection. Update impossible. >>' , $v[ 'path' ] . "/icon.png", 'no', '');
    endforeach;
  } else {
    $w->result( '', '', "All of your workflows are up to date.", '', 'assets/icons/task-complete.png', 'no', '');
  }

  echo $w->toxml();
  // We're done here.
  die();
}

if ( strpos( $q[1], 'setup' ) !== FALSE ) {

  // The next argument removes the forced-setup option.
  if ( ( ! file_exists( "$data/config/first-run-alfred" ) ) && ( ! file_exists( "$data/config/first-run" ) ) )
    file_put_contents( "$data/config/first-run-alfred", 'done' );

  $options = array(
    'authorName' => 'What name do you use when you write workflows?',
    'packalAccount' => 'Do you have an account on Packal?',
    'username' => 'What is your Packal username?',
    'workflowReporting' => 'Would you like to send anonymous data about your installed workflows to Packal.org?',
    'backups' => 'How many backups of workflows would you like to keep?'
  );
  foreach ( $options as $k => $v ) :
    if ( isset( $config->$k ) ) {
      if ( ( (string) $config->$k == '1' ) && ( $k != 'backups' ) ) {
          $message = "Current value: Yes";
      } else if ( ( (string) $config->$k == '0' ) && ( $k != 'backups' ) ) {
          $message = "Current value: No";
      } else {
        if ( ! empty( $config->$k ) )
          $message = "Current value: " . $config->$k;
        else
          $message = "< not set >";  
      }
    } else {
        $message = "Not set.";
    }
    
    // Setup icons per option
    $icon = 'assets/icons/';
    switch ( $k ) :
      case 'authorName':
        $icon .= 'code-context.png';
        break;
      case 'username' :
        $icon .= 'im-user.png';
        break;
      case 'workflowReporting' :
        $icon .= 'svn-commit.png';
        break;
      case 'backups' :
        $icon .= 'player-time.png';
        break;
      case 'packalAccount' :
        $icon .= 'flag.png';
        break;
      default:
        $icon = '';
        break;
    endswitch;

    if ( ( $k == 'username' ) && ( $config->packalAccount == 1 ) ) {
        $w->result( "set-$k", "set-$k", $v, $message, $icon, 'yes', '');
    } else if ( ( $k == 'packalAccount' ) && ( $config->packalAccount == 1 ) ) {
      continue;
    } else if ( ! ( $k == 'username' ) ) {
      $w->result( "set-$k", "set-$k", $v, $message, $icon, 'yes', '');
    }
  endforeach;

  echo $w->toxml();
  die();

} else if ( strpos( $q[1], 'blacklist' ) !== FALSE ) {
  // Option to Blacklist a workflow
  
  // @TODO: Sort the workflows by name.
  foreach ( $json as $k => $v ) :
    if ( file_exists( "../$v/packal/package.xml" ) ) {
      if ( in_array( $k, $manifestBundles ) ) {
        if ( ! in_array( $k, $blacklist ) ) {
          $w->result( "blacklist-$k", "blacklist-$k", "Add '" . $wf[ $k ][ 'name' ] . "' to blacklist", "Prevent Packal from updating '" . $wf[ $k ][ 'name' ] . "' ($k)", 'assets/icons/user-online.png', 'yes', '');
        } else {
          $w->result( "whitelist-$k", "whitelist-$k", "Remove '" . $wf[ $k ][ 'name' ] . "' from blacklist", "Let Packal update '" . $wf[ $k ][ 'name' ] . "' ($k)", 'assets/icons/user-offline.png', 'yes', '');
        }
      }
    }
  endforeach;

  echo $w->toxml();
  die();
}

// For all good measures....
echo $w->toxml();
die();


  

?>
