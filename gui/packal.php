<?php

  // Set date/time to avoid warnings/errors.
  if ( ! ini_get('date.timezone') ) {
    $tz = exec( 'tz=`ls -l /etc/localtime` && echo ${tz#*/zoneinfo/}' );
    ini_set( 'date.timezone', $tz );
  }

  $HOME = exec( 'echo $HOME' );
  $data = "$HOME/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice";
  $workflowsDir = "../../";
  // Start script.
  if ( isset( $_GET[ 'action' ] ) )
    $action = $_GET[ 'action' ];
  else if ( isset( $_POST[ 'page' ] ) )
    $page   = $_POST[ 'page' ];
  else
    $page   = 'updates';

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
  }


  $bundle    = 'com.packal.shawn.patrick.rice';
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
<div id='open-backup-dir'><h3>Open backups directory.</h3></div>

<?php
 $backups = array_diff( scandir( "$data/backups" ), array( '.', '..', '.DS_Store') );

 foreach ( $backups as $b ) :

  ?> <div class='backup-box'> <?php
   echo "<h2>$b <i id='$b' class='fa fa-search-plus fa-lg open-directory' style='margin-left: .5em;'></i></h2>";
   $dir = array_diff( scandir( "$data/backups/$b" ), array( '.', '..', '.DS_Store') );
   echo "<ul class='backups fa-ul'>";
   foreach ( $dir as $d ) :
     $d = str_replace( '.alfredworkflow', '', $d );
     $file = $d;
     $pattern = "/([0-9]{4})-([0-9]{2})-([0-9]{2})-([0-9]{2})\.([0-9]{2})\.([0-9]{2})-/";
     preg_match( $pattern, $d, $matches);
     if ( isset( $matches[0] ) ) {
       $d = str_replace( $matches[0], '', $d );
       $date = date( 'M d, Y H:i', strtotime( "$matches[2]/$matches[3]/$matches[1] $matches[4]:$matches[5]:$matches[6]" ) );
       echo "<li class=''><i id='$file' class='fa fa-times-circle delete-backup'></i> &nbsp;From $date</li>";
    } else {
      echo "<li>$d</li>";
    }
   endforeach;
   echo '</ul>';
   ?></div><?php
 endforeach;
  ?>
  <p class='clearfix'>&nbsp;</p><p class='clearfix'>&nbsp;</p>
  <script type='text/javascript'>
    $( '.fa-times-circle' ).click( function() {
      confirm( 'Delete backup file "' + $( this ).attr( 'id' ) + '.alfredworkflow"?' );
    });
    $( '.fa-search-plus' ).click( function() {
      if ( confirm( 'Open the "' + $( this ).attr( 'id' ) + '" backups folder.' ) ) {
        directory = <?php echo "'/$data/backups/'"; ?> + $( this ).attr( 'id' );
        $.get( "packal.php", { action: 'openDirectory', 'directory': directory } );
      }
    });
    $( '#open-backup-dir' ).click( function() {
      if ( confirm( 'Open the backups directory?' ) ) {
        $.get( "packal.php", { action: 'openDirectory', 'directory': <?php echo "'$data/backups'";?> } );
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

      if ( file_exists( $workflowsDir . $workflows[ $bundle ] ."/icon.png" ) )
        $file = TRUE;
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
          <i class=<?php echo "'fa fa-times'"?> style='line-height: 190px; font-size: 250px; padding-left: 20%;'></i>
        </div>
        <div><h3><?php echo $w['name']; ?></h3></div>
        <div class='short'><p> <?php echo $wf[ $bundle ][ 'short' ]; ?></p></div>
        <div class = 'wficon-container'>
          <img alt='workflow icon' src=<?php if ( $file === TRUE ) echo "serve_image.php?file=" . __DIR__ . "/" . $workflowsDir . $workflows[ $bundle ] ."/icon.png"; ?> class = 'wficon' />
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

    if ( file_exists( $workflowsDir . $workflows[ $bundle ] ."/icon.png" ) )
      $file = TRUE;
    else
      $file = 'assets/images/package.png';
    ?>
    <div class = <?php echo "'$classes'"; ?> >
      <div><h3><?php echo $name; ?></h3></div>
      <div class='short'><p> <?php echo $wf[ $bundle ][ 'short' ]; ?></p></div>
      <div class = 'wficon-container'>
        <img alt='workflow icon' src=<?php if ( $file === TRUE ) echo "serve_image.php?file=" . __DIR__ . "/" . $workflowsDir . $workflows[ $bundle ] ."/icon.png"; else echo $file; ?> class = 'wficon' />
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
  ?>
  <h1>Settings</h1>
  <p class='clearfix'>&nbsp;</p>
  <div class='settings-form'>
      <p>
        When I write my workflows, I use <input name='authorName' type='text' placeholder='My Author Name'
          value=<?php echo "'$config->authorName'"; ?>> as the name.
      </p>
      <p>
      I <select name='packalAccount' class='packal-username' >
          <option <?php if ($config->packalAccount == 0) echo 'selected'; ?>>do not</option>
          <option <?php if ($config->packalAccount == 1) echo 'selected'; ?>>do</option>
        </select>
        have a Packal account<span class='packal-account'> with the username 
        <input name='username' type='text' placeholder='My Packal Username'
        value=<?php echo "'$config->authorName'"; ?>></span>.
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
      send anonymous workflow data to Packal.<sup><i class="fa fa-question" style="opacity: .5;"></i></sup>
      </p>

      <p>
      I <select name='forcePackal' class='force-packal'>
        <option <?php if ($config->forcePackal == 0) echo 'selected'; ?>>do not</option>
        <option <?php if ($config->forcePackal == 1) echo 'selected'; ?>>do</option>
      </select>
      want Packal to update all my workflows (regardless of whether or not they were downloaded from Packal).
      </p>

      <p>When updating workflows without the GUI, notify me
      <select name='notifications' class='notification-options'>
        <option <?php if ($config->notifications == 2) echo 'selected'; ?>>per workflow updated</option>
        <option <?php if ($config->notifications == 1) echo 'selected'; ?>>after all updates are complete</option>
        <option <?php if ($config->notifications == 0) echo 'selected'; ?>>not at all</option>
      </select>
      .
      </p>
      
    <span id='packal-username'></span>
    <span id='number-of-backups'></span>
    <span id='workflow-reporting'></span>
    <span id='force-packal'></span>
    <span id='notification-options'></span>

    <script type='text/javascript' >
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
      $( 'input[type="text"]' ).keyup( resizeInput ).each( resizeInput );
    </script>
  </div>

  <?php

}

function status() {
  global $wf, $config, $workflows, $blacklist, $manifestBundles, $me, $data, $workflowsDir;

  $meta = array();

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
        if ( exec( "/usr/libexec/PlistBuddy -c \"Print :createdby\" '$workflowsDir$dir/info.plist' &2> /dev/null" ) == $config->authorName )
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
?>

<h1>Status</h1>

<div class='status-body'>
<div>
  <p id='manifest-status'>The manifest was last updated <strong><?php echo "$time" ; ?></strong></p>
<div class='update-manifest'>Update Manifest</div>
</div>

<div><p>You have <strong><?php echo count( $workflows ); ?></strong> workflows installed with Bundle IDs.</p></div>
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
<div>
  <p>
    Of the others, 
    <strong><?php echo count( $meta[ 'availableOnPackal' ] ); ?></strong>
    can be downloaded and updated from Packal.
  </p>
</div>
<div id='availableOnPackal' class='detail-accordion'>
  <h3 class='detail-accordion2'>Details</h3>
  <div>
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

  <div>
    <p>
      There are 
      <strong><?php echo count( $wf ); ?></strong>
      workflows available on Packal.
    </p>
  </div>

  <div>
    <p>
      You've written 
      <strong><?php echo count( $meta[ 'mineOnPackal' ] ); ?></strong>
      of the ones on Packal.
    </p>
  </div>
</div>
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
    In a nutshell, it updates the workflows that you've downloaded from Packal.org.
  </p>
  <h3>Packal.org</h3>
  <p>
    This workflow is a companion to Packal.org. It can update any workflows
    that you have downloaded from the site. However, it does not work with any
    other sources.
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
    <a href='http://www.packal.org' class='hijack' >Packal.org</a>, which is 
    developed, maintained, and <em>funded</em> by Shawn Patrick Rice. While 
    there are ads on the website, they don't cover the server costs. If you'd 
    like to chip in to help Packal running, then click the little, yellow button
    below.
  </p>
  <p class='clearfix'></p>
  <div style='text-align: center;'>
  <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
    <input type="hidden" name="cmd" value="_donations">
    <input type="hidden" name="business" value="rice@shawnrice.org">
    <input type="hidden" name="item_name" value="Donation">
    <input type="hidden" name="currency_code" value="USD">
    <input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_LG.gif:NonHostedGuest">
    <input type="image" src="assets/images/paypal.gif" name="submit" alt="PayPal - The safer, easier way to pay online!">
    <img alt="" border="0" src="data:image/gif;base64,R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==">
  </form>
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
  Fonts
  Merrasat?
  Lato?

  <p>
    For the dynamic layout of this "application" code was used from the overly
    talented <a href='http://www.linkedin.com/in/manoela' class='hijack'>Manoela 
    "Mary Lou" Ilic</a> from 
    <a href='http://tympanus.net/codrops/' class='hijack'>Codrops</a>.
  </p>
  <p>
    Specifically, the 
    <a href='http://tympanus.net/codrops/2013/09/30/animated-border-menus/'
     class='hijack'>menus</a> were adapted as was the 
    <a href='http://tympanus.net/codrops/2013/05/21/natural-language-form-with-custom-input-elements/' 
     class='hijack'>settings form</a>.
  </p>
  <p>
    Icons from 
    <a href='http://fortawesome.github.io/Font-Awesome/' class='hijack'>
    Font Awesome</a>.
  </p>

  <p>
    The updater makes use of Terminal Notifier.
    Zebra Tooltips
    https://github.com/stefangabos/Zebra_Tooltips/
  </p>

  <p>
    And, of course, <a href='http://www.alfredapp.com' class='hijack'>Alfred</a>
    itself without which none of this would be possible — or practical.
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
        if ( ($wf[ "$k" ][ 'updated' ] ) > $w->updated + 500 && ( ! in_array( $k, $blacklist ) ) ) {
          // These are the ones that need updating.
          $updates[] = $wf[ "$k" ][ 'name' ];
          ?>
          <div class="update-box">
          
          <div class='update-icon'>
          <?php
          if ( file_exists( "$workflowsDir/$v/icon.png" ) )
            echo "<img src='serve_image.php?file=" . __DIR__ . "/" . $workflowsDir . $v ."/icon.png'>";
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

  file_put_contents( "$data/config/blacklist.json", json_encode( $blacklist ) );
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
  if ( strpos( $_GET[ 'directory' ] , 'http://') !== FALSE )
    $dir = $_GET[ 'directory' ] ;
  else 
    $dir = $_GET[ 'directory' ];
  exec( "open '$dir'" );
}

function updateManifest() {
  global $data;

  $call = exec( "../cli/packal.sh update TRUE TRUE");
  $time = getManifestModTime();
  echo "The manifest was last updated <strong>$time</strong>";
  die();
}

function updateWorkflow() {
  $bundle = $_GET[ 'workflow' ];
  $call = exec( "php " . __DIR__ . "/../cli/packal.php doUpdate '$bundle'");
  echo $call;
  die();
}

function deleteFile() {
  $file = $_GET[ 'file' ];
  echo $file;
}


/*******************************************************************************
 * Utility Functions
 ******************************************************************************/

function sortWorkflowByName( $a, $b ) {
  return $a[ 'name' ] > $b[ 'name' ];
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


function printCopyrightYear() {
  $year = date( 'Y', mktime() );
  
  if ( $year != 2014 )
    echo "2014 – $year";
  else
    echo "2014";
}

?>
