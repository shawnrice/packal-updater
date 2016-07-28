<?php

require_once( '../functions.php' );
require_once( '../init.php' );
firstRun();

// Set date/time to avoid warnings/errors.
if ( ! ini_get('date.timezone') ) {
  $tz = exec( 'tz=`ls -l /etc/localtime` && echo ${tz#*/zoneinfo/}' );
  ini_set( 'date.timezone', $tz );
}

$HOME = exec( 'echo $HOME' );
$data = DATA_DIR;

$workflowsDir = dirname( __DIR__ . '/../' ) . '/../';

// Start script.
if ( isset( $_GET[ 'action' ] ) ) {
  $action = $_GET[ 'action' ];
} else if ( isset( $_POST[ 'page' ] ) ) {
  $page   = $_POST[ 'page' ];
} else {
  $page   = 'status';
}

if ( ! file_exists( "$data/config/firstRun" ) ) {
  $page   = 'settings';
}

if ( ! file_exists( "$data/config/config.xml" ) ) {
  $d = '<?xml version="1.0" encoding="UTF-8"?><config></config>';
  $config = new SimpleXMLElement( $d );
  $config->packalAccount = 0;
  $config->forcePackal   = 0;
  $config->backups       = 3;
  $config->username      = '';
  $config->authorName    = '';
  $config->notifications = '2';
  $config->apiKey        = '';
  $config->asXML( "$data/config/config.xml" );
  unset( $config );
}


$bundle    = 'com.packal';
$config    = simplexml_load_file( "$data/config/config.xml" );
$me        = $config->username;
$end       = "$data/endpoints/endpoints.json";
$workflows = json_decode( file_get_contents( $end ), TRUE );
$manifest  = simplexml_load_file( "$data/manifest.xml" );

if ( ! file_exists( "$data/config/blacklist.json" ) )
  exec( "touch '$data/config/blacklist.json'" );
$blacklist = json_decode( file_get_contents( "$data/config/blacklist.json" ), TRUE );

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

if ( isset( $page ) ) {
  switch ( $page ):
    case 'blacklist':
      blacklist();
      break;
    case 'about' :
      about();
      break;
    case 'settings':
      settings();
      break;
    case 'backup' :
      backups();
      break;
    case 'updates' :
      updates();
      break;
    case 'status' :
      status();
      break;
    default:
    // Of course, we shouldn't get here because all the calls to this file
    // are controlled.
      echo "<h1>$page</h1>";
      echo "<p>You shouldn't be seeing this message. Some error has occured " .
      "please contact the workflow author.</p>";
      break;
  endswitch;
} else if ( isset( $action ) ) {

  switch ( $action ) :
    case 'writeBlacklist' :
      writeBlacklist();
      die();
      break;
    case 'openDirectory' :
      openDirectory();
      die();
      break;
    case 'writeConfig' :
      writeConfig();
      break;
    case 'updateManifest' :
      updateManifest();
      break;
    case 'updateWorkflow' :
      updateWorkflow();
      break;
    case 'deleteFile' :
      deleteFile();
      break;
    default:
      echo "Action";
      break;
  endswitch;
}
// We should never really get here, so just die.
die();

?>

<?php
  foreach ( $workflows as $b => $d ) :
    $d = substr( $d, strrpos( $d, '/' ) + 1 );
    $classes = 'box';
    if ( ! in_array( $b, $manifestBundles ) )
      $classes .= ' disabled';
    if ( isset( $wf[ "$b" ] ) ) {
      if ( $wf[ "$b" ][ 'author' ] == "$me" )
        $classes .= ' disabled author';
    }

    if ( file_exists( "$workflowsDir/$d/icon.png" ) )
      $file = "$workflowsDir/$d/icon.png";
    else
      $file = 'assets/images/package.png';
    ?>
    <div class = <?php echo "'$classes'"; ?> >
      <h3>
        <?php
          echo exec( "/usr/libexec/PlistBuddy -c \"Print :name\" '$workflowsDir/$d/info.plist'" );
          if ( isset( $wf[ "$b" ] ) )
            echo " (" . $wf[ "$b" ]['version'] . ")";
        ?>
      </h3>
      <p>By <?php echo exec( "/usr/libexec/PlistBuddy -c \"Print :createdby\" '$workflowsDir/$d/info.plist'" ); ?></p>
      <p><?php echo "$b"; ?></p>
      <div class = 'wficon-container'>

        <img src=<?php echo "'$file'"; ?> class = 'wficon' />
      </div>
    </div>
    <?php

  endforeach;

/**
 * Writes the Backups Tab
 *
 * @return  [type]  [description]
 */
function backups() {

  global $data;

?>
<h1>Backups</h1>
<p class='clearfix'>&nbsp;</p>
<div id='' title=<?php echo "'$data/backups'";?> class='open-backup-dir'><h3>Open backups directory<i id='' title=<?php echo "'$data/backups'";?>  class='fa fa-folder-open fa-lg open-directory'></i></h3></div>

<?php
 $backups = array_diff( scandir( "$data/backups" ), array( '.', '..', '.DS_Store') );

 foreach ( $backups as $b ) :

  ?> <div class='backup-box'> <?php
   echo "<h2>$b <i id='$b' title='$b' class='fa fa-folder-open fa-lg open-directory'></i></h2>";
   $dir = array_diff( scandir( "$data/backups/$b" ), array( '.', '..', '.DS_Store') );
   if ( count( $dir ) > 0 ) {
      echo "<ul class='backups fa-ul'>";
      foreach ( $dir as $d ) :
        $d = str_replace( '.alfredworkflow', '', $d );
        $file = $d;
        $pattern = "/([0-9]{4})-([0-9]{2})-([0-9]{2})-([0-9]{2})\.([0-9]{2})\.([0-9]{2})-/";
        preg_match( $pattern, $d, $matches);
        if ( isset( $matches[0] ) ) {
          $d = str_replace( $matches[0], '', $d );
          $date = date( 'M d, Y H:i', strtotime( "$matches[2]/$matches[3]/$matches[1] $matches[4]:$matches[5]:$matches[6]" ) );
          echo "<li class=''><i id='$b/$file' title='$file' class='fa fa-times-circle delete-backup'></i> &nbsp;From $date</li>";
       } else {
         echo "<li>$d</li>";
       }
      endforeach;
      echo '</ul>';
    } else {
      echo '<h3>No backups found.</h3>';
    }
   ?></div><?php
 endforeach;
  ?>
  <div id='open-dir-dialog'>This is text</div>
  <div id='delete-backup-dialog'>This is text</div>
  <p class='clearfix'>&nbsp;</p><p class='clearfix'>&nbsp;</p>
  <script type='text/javascript'>
    $( '.fa-times-circle' ).click( function() {
      directory = <?php echo "'/$data/backups/'"; ?> + $( this ).attr( 'id' ) + '.alfredworkflow';
      $( '.ui-dialog-content' ).html( 'Delete <code>`' + $( this ).attr( 'title' ) + '.alfredworkflow`</code>?' );
      $( '.ui-dialog-content' ).attr( 'title', directory );
      $( "#delete-backup-dialog" ).dialog( 'open' );
    });
    $( '.fa-folder-open' ).click( function() {
      directory = <?php echo "'/$data/backups/'"; ?> + $( this ).attr( 'id' );
      content = $( this ).attr( 'title' ) + ' backup directory';
      if ( $( this ).attr( 'id' ) == '' ) {
        content = 'backups directory'
      }
      $( '.ui-dialog-content' ).html( 'Open <code>`' + content + '`</code>?' );
      $( '.ui-dialog-content' ).attr( 'title', directory );
      $( "#open-dir-dialog" ).dialog( 'open' );
    });
    $( "#open-dir-dialog" ).dialog({
      modal: true,
      autoOpen: false,
      dialogClass: "no-close",
      position: { my: 'top', at: 'top+50', of: '.pane'},
      hide: { effect: "fade", duration: 150 },
      show: { effect: "fade", duration: 150 },
      minWidth: 500,
      // minHeight: 150,
      resizable: false,
      buttons: {
        Yes: function() {
          $.get( "packal.php", { action: 'openDirectory', 'directory': $( this ).attr( 'title' ) } );
          $( this ).dialog( "close" );
        },
        No: function() {
          $( this ).dialog( 'close' );
        }
      }
    });
    $( "#delete-backup-dialog" ).dialog({
      modal: true,
      autoOpen: false,
      dialogClass: "no-close",
      hide: { effect: "fade", duration: 150 },
      show: { effect: "fade", duration: 150 },
      minWidth: 500,
      resizable: false,
      buttons: {
        Yes: function() {
          $.get( "packal.php", { action: 'deleteFile', 'file': $( this ).attr( 'title' ) },
              function() {
                $( '.pane' ).load( 'packal.php', { 'page': 'backup' } );
              }
            );
          $( this ).dialog( "close" );
        },
        No: function() {
          $( this ).dialog( 'close' );
        }
      }
    });
  </script>
  <?php
}


/**
 * Writes the Blacklist Tab
 *
 * @return  [type]  [description]
 */
function blacklist() {
  global $me, $manifest, $workflows, $wf, $config, $blacklist, $data, $workflowsDir;

  foreach ( $workflows as $bundle => $dir ) {
    $workflows[ $bundle ] = substr( $dir, strrpos( $dir, '/' ) + 1 );

    if ( isset( $wf[ $bundle ] ) ) {
      if ( $wf[ $bundle ][ 'author' ] != "$me" ) {
        $eligible[ $bundle ][ 'name' ]    = (string) $wf[ $bundle ][ 'name' ];
        $eligible[ $bundle ][ 'author' ]  = (string) $wf[ $bundle ][ 'author' ];
        $eligible[ $bundle ][ 'version' ] = (string) $wf[ $bundle ][ 'version' ];
      } else {
        $mine[ $bundle ] = (string) $wf[ $bundle ][ 'name' ];
      }
    }
  }

  if ( isset( $eligible ) )
    uasort($eligible, "sortWorkflowByName");

  if ( isset( $mine ) )
    asort( $mine );
  ?>

  <h1>Blacklist</h1>
  <p>&nbsp;</p>
  <?php

  if ( isset( $eligible ) ) {
    foreach ( $eligible as $bundle => $w ) :
      $classes = "box blist $bundle";

      if ( file_exists( $workflowsDir . '/' . $workflows[ $bundle ] ."/icon.png" ) )
        $file = "$workflowsDir/" . $workflows[ $bundle ] ."/icon.png";
      else
        $file = 'assets/images/package.png';

      if ( ! is_array( $blacklist ) )
        $blacklist = array();
      if ( in_array( $bundle, $blacklist ) )
        $disabled = '';
      else
        $disabled = ' hide';

      $bundleFix = str_replace( '.', '-', $bundle );
      ?>

      <div id=<?php echo "'$bundleFix'"; ?> class = <?php echo "'$classes'"; ?> >
        <div id=<?php echo "'disabled-$bundleFix'"?> class=<?php echo "'blacklisted$disabled $bundle'"?>>
          <i class=<?php echo "'fa fa-times disabled-x'"?>></i>
        </div>
        <div><h3><?php echo $w['name']; ?></h3></div>
        <div class='short'><p> <?php echo $wf[ $bundle ][ 'short' ]; ?></p></div>
        <div class = 'wficon-container'>
          <img alt='workflow icon' src=<?php echo "'serve_image.php?file=$file'"; ?> class = 'wficon' />
        </div>
      </div>

      <?php
    endforeach;
  }

  if ( isset( $mine ) ) {
  ?>
  <p class='clearfix'>&nbsp;</p>
  <h2>Mine</h2>
  <hr class='separator' />
  <p>
  <span class="fa-stack fa-lg">
    <i class="fa fa-circle fa-stack-2x text-danger" style="color: #ABC">&nbsp;</i>
    <i class="fa fa-info fa-stack-1x" style="z-index: 100;">&nbsp;</i>
    </span>
These are your workflows that are found on Packal. We won't try to update them.</p>
  <?php
  foreach ( $mine as $bundle => $name ) :
    $classes = 'box disabled';

    if ( file_exists( $workflowsDir . '/' . $workflows[ $bundle ] ."/icon.png" ) )
      $file = "$workflowsDir/" . $workflows[ $bundle ] ."/icon.png";
    else
      $file = 'assets/images/package.png';
    ?>
    <div class = <?php echo "'$classes'"; ?> >
      <div><h3><?php echo $name; ?></h3></div>
      <div class='short'><p> <?php echo $wf[ $bundle ][ 'short' ]; ?></p></div>
      <div class = 'wficon-container'>
        <img alt='workflow icon' src=<?php echo "'serve_image.php?file=$file'"; ?> class = 'wficon' />
      </div>
    </div>
    <?php
  endforeach;
}

  if ( ! ( isset( $eligible ) || isset( $mine ) ) ) {
    echo "You have no workflows installed from Packal.";
  }
  ?>

  <script type='text/javascript' >
  $( '.blist' ).click( function() {
    bundle="#disabled-" + $( this ).attr( 'id' );
    classes=$( this ).attr( 'class' );
    $.get( "packal.php", { action: 'writeBlacklist', 'bundle': classes } );
    if ($( bundle ).is(":visible")) {
      // Not blacklisted
      $( bundle ).addClass( 'hide' );
    } else {
      // Blacklisted
      $( bundle ).removeClass( 'hide' );
    }
  });
  </script>

  <?php
}

/**
 * Writes the Settings Tab
 *
 * @return  [type]  [description]
 */
function settings() {
  global $config, $data, $workflowsDir;
  require_once( '../init.php' );
  ?>
  <h1>Settings</h1>
  <?php
    if ( ! file_exists( DATA_DIR .'/config/firstRun' ) ) {
      ?>
      <div class='firstRun'>
        <p>Since this is your first time using the updater, please fill out a few of these settings.</p>
      </div>
      <?php
      echo "<p>". DATA_DIR .'/config/firstRun' . "</p>";
      print_r( $_SERVER );
      file_put_contents( DATA_DIR .'/config/firstRun', "done" );
    }
  ?>
  <p class='clearfix'>&nbsp;</p>
  <div class='settings-form'>
      <p>
        When I write my workflows, I use <input name='authorName' type='text' placeholder='My Author Name'
          value=<?php echo "'$config->authorName'"; ?> title='This is the name you put into the workflow via the Alfred Workflow GUI.'> as the name.
      </p>
      <p>
      I <select name='packalAccount' class='packal-username' >
          <option <?php if ($config->packalAccount == 0) echo 'selected'; ?>>do not</option>
          <option <?php if ($config->packalAccount == 1) echo 'selected'; ?>>do</option>
        </select>
        have a Packal account<span class='packal-account'> with the username
        <input name='username' type='text' placeholder='My Packal Username'
        value=<?php echo "'$config->username'"; ?> title='This is your Packal Username.'></span>.
      </p>
      <p>
      Keep
      <select name='backups' class='number-of-backups'>
        <option <?php if ($config->backups == 1) echo 'selected'; ?>>one backup</option>
        <option <?php if ($config->backups == 2) echo 'selected'; ?>>two backups</option>
        <option <?php if ($config->backups == 3) echo 'selected'; ?>>three backups</option>
        <option <?php if ($config->backups == 4) echo 'selected'; ?>>four backups</option>
        <option <?php if ($config->backups == 5) echo 'selected'; ?>>five backups</option>
      </select>
      of updated workflows.
      </p>

      <p>
      Please <select name='workflowReporting' class='workflow-reporting'>
        <option <?php if ($config->workflowReporting == 1) echo 'selected'; ?>>do</option>
        <option <?php if ($config->workflowReporting == 0) echo 'selected'; ?>>do not</option>
      </select>
      send anonymous workflow data to Packal.<a href='#'
      title='This workflow will send information about which workflows you have installed, whether
       or not they are enabled or disabled, and whether or not you downloaded them from Packal.
       The reporting is as anonymous as possible. For more information, click the "about" tab.'>
      <sup><i class="fa fa-question" style="opacity: .5; color: purple;"></i></sup></a>
      </p>

    <!--   <p>
      I <select name='forcePackal' class='force-packal'>
        <option <?php if ($config->forcePackal == 0) echo 'selected'; ?>>do not</option>
        <option <?php if ($config->forcePackal == 1) echo 'selected'; ?>>do</option>
      </select>
      want Packal to update all my workflows (regardless of whether or not they were downloaded from Packal).
      </p> -->
<!--
      <p>When updating workflows without the GUI, notify me
      <select name='notifications' class='notification-options'>
        <option <?php if ($config->notifications == 2) echo 'selected'; ?>>per workflow updated</option>
        <option <?php if ($config->notifications == 1) echo 'selected'; ?>>after all updates are complete</option>
        <option <?php if ($config->notifications == 0) echo 'selected'; ?>>not at all</option>
      </select>
      .
      </p> -->

    <span id='packal-username'></span>
    <span id='number-of-backups'></span>
    <span id='workflow-reporting'></span>
    <span id='force-packal'></span>
    <span id='notification-options'></span>
    <span id='username'></span>
    <span id='authorName'></span>

    <script type='text/javascript' >
      // From http://digitalzoomstudio.net/2013/06/19/calculate-text-width-with-jquery/
      jQuery.fn.textWidth = function(){
        var _t = jQuery(this);
        var html_org = _t.html();
        if(_t[0].nodeName=='INPUT'){
            html_org = _t.val();
        }
        var html_calcS = '<span>' + html_org + '</span>';
        jQuery('body').append(html_calcS);
        var _lastspan = jQuery('span').last();
        _lastspan.css({
            'font-size' : _t.css('font-size')
            ,'font-family' : _t.css('font-family')
        })
        var width =_lastspan.width() + 5;
        _lastspan.remove();
        return width;
      };
      $(function() {
        $( document ).tooltip({
          show: { effect: "fade", duration: 200, delay: 500 },
          hide: { effect: "fade", duration: 50 },
          position: {
            my: "center bottom-5",
            at: "left+18 top",
            using: function( position, feedback ) {
              $( this ).css( position );
              $( "<div>" )
                .addClass( "arrow" )
                .addClass( feedback.vertical )
                .addClass( feedback.horizontal )
                .appendTo( this );
            }
          }
          // If we create the tooltip on the fly (rather than how we do it here, then we can
          // use something like this to avoid the weird jumping around when things go to far
          // in and out).
          // ,
          // open: function( event, ui ) {
          //   var $id = $( ui.tooltip ).attr( 'id' );
          //   $( 'div.ui-tooltip' ).not( '#' + $id ).remove();
          // }
        });
      });
      $( 'input[type="text"]' ).each( function() {
        id = $( this ).attr( 'name' );
        $( '#' + id ).html( $( this ).val() );
        width = $( '#' + id ).textWidth();
        if ( $( this ).val() == '' ) {
          width = 150;
        } else if ( width < 90 ) {
          width = 90;
        }
        $( this ).css( 'width', width + 5 );
      });
      $( document ).ready( function() {
        var selects = [
          'packal-username',
          'number-of-backups',
          'workflow-reporting',
          'force-packal',
          'notification-options'
        ];
        for ( var x in selects ) {
          $( '#' + selects[x] ).html( $( '.' + selects[x] ).val() );
          width = $( '#' + selects[x] ).width();
          $( '.' + selects[x] ).css( 'width', width );
        }
        if ( $( '.packal-username' ).val() == 'do not' ) {
          $( '.packal-account' ).hide();
        }
      });

      $( '.packal-username' ).on( 'change', function() {
        value = $( this ).val();
        if ( value == 'do' ) {
          $( '.packal-account' ).fadeIn( 'fast' );
        } else {
          $( '.packal-account' ).hide();
        }
      });
      $( 'select' ).change( function() {
        c = $( this ).attr( 'class' );
        $( '#' + c ).html( $( this ).val() );
        width = $( '#' + c ).width();
        $( this ).css( 'width', width );
      });
      $( 'select' ).change( function() {
        key = $( this ).attr( 'name' );
        value = $( this ).val();
        $.get( "packal.php", { action: 'writeConfig', 'key': key, 'value': value });
      });
      $( 'input' ).change( function() {
        key = $( this ).attr( 'name' );
        value = $( this ).val();
         $.get( "packal.php", { action: 'writeConfig', 'key': key, 'value': value });
      });
      function resizeInput() {
        $( this ).attr( 'size', $( this ).val().length);
      }
      $( 'input[type="text"]' ).keyup( function() {
        id = $( this ).attr( 'name' );
        $( '#' + id ).html( $( this ).val() );
        width = $( '#' + id ).textWidth();
        if ( $( this ).val() == '' ) {
          width = 150;
        } else if ( width < 90 ) {
          width = 90;
        }
        $( this ).css( 'width', width + 5 );
      });
    </script>
  </div>

  <?php
}

function status() {
  global $wf, $config, $workflows, $blacklist, $manifestBundles, $me, $data, $workflowsDir;
  $endpoints = json_decode( file_get_contents( "$data/endpoints/endpoints.json" ), TRUE );

  $updates = false;
  foreach ( $endpoints as $k => $v ) :
    if ( file_exists( "$v/packal/package.xml" ) ) {
      $w = simplexml_load_file( "$v/packal/package.xml" );

      if ( in_array( $k, $manifestBundles ) ) {
        if ( $wf[ "$k" ][ 'version' ] != (string) $w->version ) {
          if ( ! in_array( $k, $blacklist ) ) {
            $updates = true;
            break;
          }
        }
    }
  }
  endforeach;

  $meta = array();
  $meta[ 'mineOnPackal' ] = array();
  $meta[ 'myWorkflows' ] = array();
  if ( isset( $config->username ) && ( $config->username ) ) {
    foreach ( $wf as $bundle => $value ) :
      if ( $value[ 'author' ] == "$config->username" ) {
        $meta[ 'mineOnPackal' ][] = $bundle;
      }
    endforeach;
  }

  foreach ( $workflows as $bundle => $dir ) :
    $dir = substr( $dir, strrpos( $dir, '/' ) + 1 );
    $workflows[ $bundle ] = $dir; // Installed workflows
    $meta[ 'installedWorkflows' ][] = $bundle;
    if ( isset( $config->authorName ) && ( $config->authorName ) ) {
      // Look here only if the user has set authorName value.
        if ( exec( "/usr/libexec/PlistBuddy -c \"Print :createdby\" '$workflowsDir/$dir/info.plist' &2> /dev/null" ) == $config->authorName )
          $meta[ 'myWorkflows' ][] = $bundle;
    }

    if ( file_exists( "$workflowsDir/$dir/packal/package.xml" ) && in_array( $bundle, $manifestBundles ) ) {
      $meta[ 'fromPackal' ][] = $bundle;
    } else if ( in_array( $bundle, $manifestBundles ) ) {
      if ( isset( $config->username ) && ( $config->username ) ) {
        if ( ! ( in_array( "$bundle", $meta[ 'mineOnPackal' ] ) ) )
          $meta[ 'availableOnPackal' ][] = $bundle;
      } else {
        $meta[ 'availableOnPackal' ][] = $bundle;
      }
    }

  endforeach;

  $time = getManifestModTime();

  foreach ( $workflows as $k => $v ) :
    if ( file_exists( "$workflowsDir/$v/packal/package.xml" ) && in_array( $k, $manifestBundles ) ) {
      $packal[] = $k;
    }
  endforeach;

  $connection = checkConnection();
?>

<h1>Status</h1>

<div class='status-body'>
    <?php
  if ( $updates ) {
?>
<div class='updates-available'>
  <p><h4>Updates are available.</h4></p>
  <p class='clearfix'> </p>
</div>
<?php
  }
?>
<div>
  <p id='manifest-status'>
    The manifest was last updated <strong><?php echo "$time" ; ?></strong>
    <?php
      if ( $connection !== false )
        echo "<span class='update-manifest'>(Update)</span>";
    ?>
  </p>
</div>
<p class='clearfix'> </p>
<div><p>You have <strong><?php echo count( $workflows ); ?></strong> workflows installed with Bundle IDs.</p></div>
<p class='clearfix'> </p>
<?php
if ( count( $meta[ 'myWorkflows'] ) > 0 ) {
  ?>
  <div><p>Of those, <strong><?php echo count( $meta[ 'myWorkflows' ] ) ?></strong> are ones that you wrote.</p>
    <div id='myworkflows' class='detail-accordion'>
      <h3>Details</h3>
      <div class='detail-accordion'>
          <?php
            foreach ( $meta[ 'myWorkflows' ] as $m ) :
              if ( isset( $wf[ $m ][ 'name' ] ) )
                $name = $wf[ $m ][ 'name' ];
              else {
                $dir = $workflows[ $m ];
                $name = exec( "/usr/libexec/PlistBuddy -c \"Print :name\" '$workflowsDir/$dir/info.plist' &2> /dev/null" );
              }
              echo '<p><strong>' . $name . '</strong> (' . $m . ")</p>";
            endforeach;
            ?>
      </div>
    </div>
</div>
<p class='clearfix'> </p>
<?php
}
?>

<div><p>You have installed <strong><?php echo count( $meta[ 'fromPackal' ] ); ?></strong> from Packal.</p>
<div id='frompackal' class='detail-accordion'>
  <h3>Details</h3>
  <div class='detail-accordion'>
      <?php
    foreach ( $meta[ 'fromPackal' ] as $m ) :
      echo '<p><strong>' . $wf[ $m ][ 'name' ] . '</strong> (' . $m . ")</p>";
    endforeach;
        ?>
  </div>
</div>
</div>
<?php if ( isset( $meta[ 'availableOnPackal' ] ) && ( count( $meta[ 'availableOnPackal' ] ) > 0 ) ) : ?>
<p class='clearfix'> </p>
<div>
  <p>
    Of the others,
    <strong><?php echo count( $meta[ 'availableOnPackal' ] ); ?></strong>
    can be downloaded and updated from Packal.
  </p>
<div id='availableOnPackal' class='detail-accordion'>
  <h3>Details</h3>
  <div class='detail-accordion2'>
      <?php
    foreach ( $meta[ 'availableOnPackal' ] as $m ) :
      if ( ! empty( $m ) ) {
      echo '<div><p><strong>' . $wf[ $m ][ 'name' ] . '</strong>';
      echo " ($m)" . "</p><div id='" . $wf[ $m ][ 'url' ] .
        "' class='url-open'>View on Packal</div></div>";
      }
    endforeach;
        ?>
  </div>
</div>
</div>
<?php endif; ?>
<p class='clearfix'> </p>
  <div>
    <p>
      There are
      <strong><?php echo count( $wf ); ?></strong>
      workflows available on Packal.
    </p>
  </div>
  <p class='clearfix'> </p>
<?php
  if ( count( $meta[ 'mineOnPackal' ] ) > 0 ) {
?>
  <div>
    <p>
      You've written
      <strong><?php echo count( $meta[ 'mineOnPackal' ] ); ?></strong>
      of the ones on Packal.
    </p>
  </div>
<?php
}
?>
</div>
<?php
  if ( $connection !== false ) {
    ?>
  <script>
    myVar = setTimeout( function() {
      $( '.hijack' ).click( function() {
        event.preventDefault();
        link = $( this ).attr( 'href' );
        $.get( "packal.php", { action: 'openDirectory', 'directory': link } );
      });
      }, 1500);
  </script>
  <script>

</script>
  <?php
}
?>
  <script type='text/javascript'>
  $( "#myworkflows" ).accordion({ active: false, collapsible: true });
  $( "#frompackal" ).accordion({ active: false, collapsible: true });
  $( "#availableOnPackal" ).accordion({ active: false, collapsible: true });
  $( '.url-open' ).click( function() {
    dir = $( this ).attr( 'id' );
    $.get( "packal.php", { action: 'openDirectory', 'directory': dir } );
  });
  $( '.update-manifest' ).click( function() {
    $.ajax({
      url: 'packal.php',
      beforeSend: function( xhr ) {
        $( '#updating-overlay' ).show();
      },
      data: { action: 'updateManifest' },
    }).done(function( data ) {
      $( '#updating-overlay' ).hide();
      $( '#manifest-status' ).html( data );
    });
  });
  $( '.updates-available' ).click( function() {
    $( '.pane' ).html("<div class='preloader'><h2>Loading...</h2><img alt='preloader' src='assets/images/preloader.gif' /></div>");
    setTimeout(function() {
      $( '.pane' ).load( 'packal.php', { 'page': 'updates' } );
    }, 1000);

  });
  $( document ).ready( function() {
    if ( <?php echo date( 'U', mktime() ) - date( 'U', filemtime( "$data/manifest.xml" ) ); ?> > 86400 ) {
      console.log( 'test' );
      $.ajax({
        url: 'packal.php',
        beforeSend: function( xhr ) {
          $( '#updating-overlay' ).show();
        },
        data: { action: 'updateManifest' },
        }).done(function( data ) {
          $( '#updating-overlay' ).hide();
          $( '#manifest-status' ).html( data );
      });
    }
  });

  </script>
<?php
}


/**
 * Writes the About Tab
 *
 * @return  [type]  [description]
 */
function about() {
?>

<div id='about-section'>
  <h2>How Does it Work?</h2>
  <p>
    In a nutshell, it updates the workflows that you've downloaded from <a href='http://www.packal.org' class='hijack'>Packal.org</a>.
  </p>
  <h3>Packal.org</h3>
  <p>
    This workflow is a companion to <a href='http://www.packal.org' class='hijack'>Packal.org</a>. It can update any workflows
    that you have downloaded from the site. However, it does not work with any
    other sources. See the <a href='http://www.packal.org/terms-service' class='hijack'>Terms of Service</a> for more about Packal.org.
  </p>

  <h3>Code Signing</h3>
  <p>
    All packages downloaded from Packal have been signed by the server, and the
    signature is checked by this workflow before an update is allowed to
    continue. This check ensures that the package hasn't been tampered with.
  </p>
  <h3>Plist Migration</h3>
  <p>
    When Alfred updates workflows normally, it strips any hotkeys found in the
    new <code>info.plist</code> file, and it also migrates any hotkeys / keywords
    that the user set. The Packal updater does the same thing. However, if you
    have modified the original workflow, then the updater will overwrite those
    modifications. If you do not want a workflow to be overwritten, then just
    mark it off under the blacklist tab.
  </p>
  <h3>Anonymous Workflow Reporting</h3>
  <p>
    This workflow has the possibility to send the Packal website information
    about which workflows you have installed. The information sent includes only
    the Bundle IDs of the workflows you have installed along with the names,
    whether or not they are enabled/disabled, and whether or not they were downloaded
    from Packal. The information is sent, at most, once per week, and the unique
    identifier used comes from a random-ish string that has been hashed so that
    there is no real way to get any real information from your computer. For a more
    technical explanation, the unique identifier is a hashed (256) of the following
    command:
  </p>
  <p>
    <code>ioreg -rd1 -c IOPlatformExpertDevice | awk '/IOPlatformUUID/ { split($0, line, "\""); printf("%s\n", line[4]); }'</code>
  </p>
  <p>
    Nothing else about your system is sent, and the reporting mechanism ignores workflows
    without Bundle IDs. The information is used in order to determine the popularity of
    workflows in order to display better statistics for the workflows on Packal to be
    displayed for trending/popular workflows as well as for reports to workflow developers
    about the popularity of their work. If you want a more detailed understanding of the
    reporting mechanism, look at the file name <code>report-usage-data.php</code> in the
    workflow root.
  </p>
  <h2>Support this Project</h2>
  <p>The Packal Updater Workflow is an extension of
    <a href='http://www.packal.org' class='hijack'>Packal.org</a>, which is
    developed, maintained, and <em>funded</em> by Shawn Patrick Rice. While
    there are ads on the website, they don't cover the server costs. If you'd
    like to chip in to help Packal running, then click the little, yellow button
    below.
  </p>
  <p class='clearfix'></p>
  <div style='text-align: center;'>
  <a href='https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=rice@shawnrice.com' class='hijack'>
    <img src='assets/images/paypal.gif' alt="PayPal - The safer, easier way to pay online!" />
  </a>
  </div>
  <h2>License</h2>
  <p>Code is provided AS IS under the GPLv3 license.</p>

  <p>Copyright © <?php printCopyrightYear(); ?>  Shawn Patrick Rice</p>
  <p>
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
  </p>
  <p>
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
  </p>
  <p>
    You should have received a copy of the GNU General Public License
    along with this program.  If not,
    <a href='http://www.gnu.org/licenses/' class='hijack'>see the license here</a>.
  </p>

  <h2>Credit Where Credit is Due</h2>
  <p>
    I have to thank <a href='http://www.packal.org/users/deanishe' class='hijack'>Dean Jackson</a> and
    <a href='http://www.packal.org/users/tyler-eich' class='hijack'>Tyler Eich</a> for feedback and testing.
  </p>

  <p>
    This workflow uses <a href='http://dferg.us/'>David Ferguson</a>'s <a href='http://dferg.us/workflows-class/' class='hijack'>Workflows Class</a>.
  </p>

  <p>
    It also uses <a href='http://rodneyrehm.de/en/' class='hijack'>Rodney Rehm</a>'s and
    <a href='https://github.com/ckruse' class='hijack'>Christian Kruse</a>'s
    <a href='https://github.com/rodneyrehm/CFPropertyList' class='hijack'>CFProperty List</a> class to aid with plist migration.
  </p>

  <p>
    For the dynamic layout of this "application," code was used from the overly
    talented <a href='http://www.linkedin.com/in/manoela' class='hijack'>Manoela
    "Mary Lou" Ilic</a> from
    <a href='http://tympanus.net/codrops/' class='hijack'>Codrops</a>.
  </p>

  <p>
    Specifically, the
    <a href='http://tympanus.net/codrops/2013/09/30/animated-border-menus/'
     class='hijack'>menus</a> were adapted as was a bit of the
    <a href='http://tympanus.net/codrops/2013/05/21/natural-language-form-with-custom-input-elements/'
     class='hijack'>settings form</a>.
  </p>
  <p>
    GUI icons from
    <a href='http://fortawesome.github.io/Font-Awesome/' class='hijack'>
    Font Awesome</a>.
  </p>
  <p>
    The workflow icons from the
    <a href='http://www.archlinux.org/packages/extra/any/oxygen-icons/download' class='hijack'>
    Oxygen set</a>.
  </p>
  <p>
    The updater makes use of <a href='https://github.com/alloy/terminal-notifier' class='hijack'>Terminal Notifier</a>.
  </p>

  <p>
    All of the workflow and theme authors who have contributed their work on Packal.org.
  </p>

  <p>
    And, of course, <a href='https://twitter.com/preppeller' class='hijack'>Andrew</a> and <a href='http://thatcanadiangirl.co.uk/' class='hijack'>Vero</a> Pepperrell,
    affectionately known as the <a href='http://www.alfredapp.com' class='hijack'>Alfred</a> team,
    without whom none of this would be possible — or practical.
  </p>
</div>
<script>
  $( '.hijack' ).click( function() {
    event.preventDefault();
    link = $( this ).attr( 'href' );
    $.get( "packal.php", { action: 'openDirectory', 'directory': link } );
  });
</script>
<?php
}


function updates() {
  global $manifestBundles, $wf, $workflowsDir, $data, $blacklist;
  $manifest = simplexml_load_file( "$data/manifest.xml" );
  $endpoints = json_decode( file_get_contents( "$data/endpoints/endpoints.json" ), TRUE );
  foreach ( $endpoints as $k => $v ) :
    $pattern = '/[\/A-Za-z0-9-_ .]{1,}\/user\.workflow\.([0-9A-Z.-]{1,})/';
    preg_match($pattern, $v, $matches );
    if ( isset( $matches[1] ) )
      $endpoints[$k] = 'user.workflow.' . $matches[1];
  endforeach;
  ?>
  <div><h1>Updates</h1></div>
  <p class='clearfix'>&nbsp;</p>
  <?php
  foreach ( $endpoints as $k => $v ) :
    if ( file_exists( "$workflowsDir/$v/packal/package.xml" ) ) {
      $w = simplexml_load_file( "$workflowsDir/$v/packal/package.xml" );
      if ( in_array( $k, $manifestBundles ) ) {
        if ( $wf[ "$k" ][ 'version' ] != (string) $w->version ) {
          if ( ! in_array( $k, $blacklist ) ) {
            // These are the ones that need updating.
            $updates[] = $wf[ "$k" ][ 'name' ];
            ?>
            <div class="update-box">
              <div class='update-icon'>
<?php
            if ( file_exists( "$workflowsDir/$v/icon.png" ) )
              echo "<img src='" . "serve_image.php?file=" . $workflowsDir . "/" . $v ."/icon.png'>";
?>
              </div>
              <div class='update-content'>
                <h1><?php echo $wf[ "$k" ][ 'name' ]; ?></h1>
                <p><small>(<?php echo $k; ?>)</small></p>
                <p><h3>Current: <span style='font-size: 1.25em'><?php echo $w->version; ?></span> <i class="fa fa-long-arrow-right fa-lg"></i> Proposed: <span style='font-size: 1.25em'><?php echo $wf[ "$k" ][ 'version' ]; ?></span></h3></p>
              </div>
              <div class='update-button'>
                <button type="button" name="" value=<?php echo "'$k'"; ?> class="css3button">Update</button>
              </div>
            </div>

            <?php
          }
        }
      }
    }
  endforeach;

  // No updates are available.
  if ( ! isset( $updates ) ) {
    echo "<div class='updates-complete-backdrop'></div>";
    echo "<div class='center-me-please'><h2>All of your workflows are up to date.</h2></div>";
  }
  ?>
  <script>
    $( '.css3button' ).click( function() {
      // dir = $( this ).attr( 'id' ); , data: { action: 'updateManifest' }
      bundle = $( this ).val();
      $.ajax({
        url: 'packal.php',
        beforeSend: function( xhr ) {
          $( '#updating-overlay' ).show();
        },
        data: { action: 'updateWorkflow', workflow: bundle },
      }).done(function( data ) {
        $( '.pane' ).load( 'packal.php', { page: 'updates' } ).hide().fadeIn('fast').delay(50);
        $( '#updating-overlay' ).hide();
      });
    });
    $( document ).ready( function() {
    if ( <?php echo date( 'U', mktime() ) - date( 'U', filemtime( "$data/manifest.xml" ) ); ?> > 86400 ) {
      $.ajax({
        url: 'packal.php',
        beforeSend: function( xhr ) {
          $( '#updating-overlay' ).show();
        },
        data: { action: 'updateManifest' },
        }).done(function( data ) {
          $( '#updating-overlay' ).hide();
      });
      $( '.pane' ).load( 'packal.php', { page: 'updates' } ).hide().fadeIn('fast').delay(50);

    }
  });
  </script>
  <?php
}


/*******************************************************************************
 * Actions
 ******************************************************************************/

/**
 * Callback to alter value in Blacklist.json
 *
 * @return  [type]  [description]
 */
function writeBlacklist() {
  global $data;
  $file = "$data/config/blacklist.json";

  // The request is transmitting the classes on the div, and,
  // here, we're just removing the extraneous ones.
  $bundle = trim( str_replace( 'box blist ', '', $_GET[ 'bundle' ]));

  if ( file_exists( $file ) )
    $blacklist = json_decode( file_get_contents( $file ), TRUE );
  else
    $blacklist = array();

  if ( ! is_array( $blacklist ) )
    $blacklist = array();

  if ( in_array( $bundle, $blacklist ) )
    unset( $blacklist[ array_search( $bundle, $blacklist ) ] );
  else
    $blacklist[] = $bundle;

  file_put_contents( "$data/config/blacklist.json", utf8_encode( json_encode( $blacklist ) ) );
}


/**
 * Writes a Config Value
 *
 * @return  [type]  [description]
 */
function writeConfig() {
  global $config, $data;

  $backups = array( 1 => 'one',
                    2 => 'two',
                    3 => 'three',
                    4 => 'four',
                    5 => 'five'
  );

  $key = $_GET[ 'key' ];
  $value = $_GET[ 'value' ];
  switch ( $key ) :
    case 'packalAccount' :
    case 'workflowReporting':
    case 'forcePackal':
      if ( $value == 'do' )
        $value = 1;
      else
        $value = 0;
    break;
    case 'backups' :
      $value = str_replace( 'backup', '', $value );
      $value = str_replace( 's', '', $value );
      $value = array_search( trim( $value ), $backups );
    break;
    case 'notifications' :
      if ( $value == 'after all updates are complete' )
        $value = '1';
      if ( $value == 'per workflow updated' )
        $value = '2';
      if ( $value == 'not at all' )
        $value = '0';
      break;
  endswitch;

  $config->$key = $value;
  $config->asXML( "$data/config/config.xml" );
  echo "$key: $value";
}

function openDirectory() {
  if ( strpos( $_GET[ 'directory' ] , 'http://') !== false )
    $dir = $_GET[ 'directory' ] ;
  else
    $dir = $_GET[ 'directory' ];
  exec( "open '$dir'" );
}

function updateManifest() {
  global $data;
  if ( getManifest() == false ) {
    $time = getManifestModTime();
    echo "The manifest was last updated <strong>$time</strong> However it couldn't be updated because of no viable Internet connection.";
    die();
  }

  $time = getManifestModTime();
  echo "The manifest was last updated <strong>$time</strong>";
  die();
}

function updateWorkflow() {
  $bundle = $_GET[ 'workflow' ];
  $call = exec( "php '" . __DIR__ . "/../cli/packal.php' doUpdate '$bundle'");
  echo $call;
  die();
}

function deleteFile() {
  $file = $_GET[ 'file' ];
  exec( "rm '$file'" );
}


/*******************************************************************************
 * Utility Functions
 ******************************************************************************/


function printCopyrightYear() {
  $year = date( 'Y', mktime() );

  if ( $year != 2014 )
    echo "2014 – $year";
  else
    echo "2014";
}

?>
