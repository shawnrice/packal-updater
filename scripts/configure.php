<?php
/**
 * tags demonstration
 * @author this tag is parsed, but this @version tag is ignored
 * @version 1.0 this version tag is parsed
 */

namespace CFPropertyList;

/**
 * Require CFPropertyList
 */
require_once(__DIR__.'/../libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php');
// require_once(__DIR__.'/check-server-status.php');
/**
 * Require David Ferguson's Workflows class
 */
require_once('../libraries/workflows.php');

/**
 * Write the initial 'keep-alive' zombie file before letting the js maintain it.
 * This call just ensures that the kill-webserver script doesn't kill the webserver
 * too soon. It's probably uncessary, but it's a nice safety net.
 */
require_once('webserver-keep-alive-update.php');

// Add the plist functions for later use...
require_once('plist-functions.php');

// Avoid collisions
if (! isset( $w ) ) {
	// Escape the new Workflow object so it doesn't collide withthe CFPropertyList namespace
	$w = new \Workflows();	
}

// Check if Growl is installed.
$cmd = "ps aux | grep Growl | grep -v grep";
if ( shell_exec( $cmd ) ) {
	$growl = true; 		// Growl is running.
} else {
	$growl  = false; 	// Growl is not running, so assume it isn't installed.
}

/**
 *
 *	Include our generic header, which contains the html header, but it also
 * 	loads our keep-alive.js to make sure the webserver doesn't commit
 * 	suicide while we still need it.
 *
 *
 */
include('../resources/templates/header.php');
// This includes the keep alive function.
// Within that, we could also have something that checks the external internet connection
// If it fails, then we disable the update functionality, and if it passes, then we enable it.
// Obviously, this need to be run immediately.

?>

<script>
jQuery( document ).ready( function( $ ) {
	$('#screen').fadeOut('300');
	$.ajax({
        url : 'check-server-status.php',
        success : handleData
    });
// COMMENTED OUT BELOW WHILE DEVELOPING

	// timer = setTimeout(function() {
	// $('.message').fadeOut('3000');
	// $('div.section').delay('100').animate({top: '-=30'},400);
 //    }, 20000);


});
function getData(  ) {
    $.ajax({
        url : 'scripts/check-server-status.php',
        type: 'POST',
        data: { id: id },
        success : handleData
    });
}
function handleData(data) {
	$("#messages").html(data);
}
function enableUpdates(data) {
	// Write a function to enable the update buttons when server connection is detected.
}

function stepProgressBar( id , id_text , amount ) {
	var width = $( id ).css( 'width' );
	width = str_replace( 'px' , '' , width );
	if ( width < 500 ) {
		$( id ).animate({ width: '+=' + amount + '%' } , 200 );
		$( id_text ).html( ( ( width/5 ) + 20 ) + "%" );
	}
}

function str_replace( needle , replace , haystack ) {
  return haystack.replace( new RegExp( needle , 'g' ) , replace );
}


// Send notifications through notification center. Use notify.js
//document.querySelector('#show_button').addEventListener('click', function() {
//if (window.webkitNotifications.checkPermission() == 0) { // 0 is PERMISSION_ALLOWED
// function defined in step 2
//    notification_test = window.webkitNotifications.createNotification(
//'icon.png', 'Notification Title', 'Notification content...');
//    notification_test.ondisplay = function() { ... do something ... };
//   notification_test.onclose = function() { ... do something else ... };
//    notification_test.show();
//  } else {
//window.webkitNotifications.requestPermission();
//}
//}, false);

</script>

<?php
// <input type="button" value="Refresh" onClick="window.location.reload()">
?>
<!-- Here's just a screen to hide the site while it loads... it looked funny. It fades out on load. -->
<!--<div id='screen' style="background-color: black; height: 100%; width: 100%; position: fixed; z-index:99999999;"></div>-->
<!-- The container fixes itself to the size of our pop-up window -->
<div id="container">
<!-- Navigation element -->
<div id="sidebar">
<!-- There is a much more elegant way to do the scripts below, but I'll write that in another version. -->
	<ul>
		<li id="status-button" class="active"
			onclick="$('#global').fadeOut('100');
					 $('#workflow').fadeOut('100');
					 $('#about').fadeOut('100');
					 $('#roadmap').fadeOut('100');
					 $('#status').delay('200').fadeIn('100');
					 $('#global-button').animate({backgroundColor: '#333'}, 300);
					 $('#workflow-button').animate({backgroundColor: '#333'}, 300);
					 $('#about-button').animate({backgroundColor: '#333'}, 300);
					 $('#roadmap-button').animate({backgroundColor: '#333'}, 300);					 
					 $('#status-button').animate({backgroundColor: '#393939'}, 300);
					 ">Status</li>
		<li id="global-button" class=""
			onclick="$('#status').fadeOut('100');
					 $('#workflow').fadeOut('100');
 					 $('#about').fadeOut('100');
					 $('#roadmap').fadeOut('100');
					 $('#global').delay('200').fadeIn('100');
					 $('#status-button').animate({backgroundColor: '#333'}, 300);
					 $('#workflow-button').animate({backgroundColor: '#333'}, 300);
					 $('#about-button').animate({backgroundColor: '#333'}, 300);
					 $('#roadmap-button').animate({backgroundColor: '#333'}, 300);					 
					 $('#global-button').animate({backgroundColor: '#393939'}, 300);
					 ">Settings</li>
		<li id="workflow-button" class=""
			onclick="$('#status').fadeOut('100');
					 $('#global').fadeOut('100');
					 $('#about').fadeOut('100');
					 $('#roadmap').fadeOut('100');
					 $('#workflow').delay('200').fadeIn('100');
					 $('#global-button').animate({backgroundColor: '#333'}, 300);
					 $('#status-button').animate({backgroundColor: '#333'}, 300);
					 $('#about-button').animate({backgroundColor: '#333'}, 300);
					 $('#roadmap-button').animate({backgroundColor: '#333'}, 300);
					 $('#workflow-button').animate({backgroundColor: '#393939'}, 300);
					 ">Workflow Options</li>
		<li id="about-button" class=""
			onclick="$('#status').fadeOut('100');
					 $('#global').fadeOut('100');
					 $('#workflow').fadeOut('100');
					 $('#roadmap').fadeOut('100');					 
					 $('#about').delay('200').fadeIn('100');
					 $('#global-button').animate({backgroundColor: '#333'}, 300);
					 $('#workflow-button').animate({backgroundColor: '#333'}, 300);
					 $('#status-button').animate({backgroundColor: '#333'}, 300);
					 $('#roadmap-button').animate({backgroundColor: '#333'}, 300);
					 $('#about-button').animate({backgroundColor: '#393939'}, 300);
					 ">About</li>
		<li id="roadmap-button" class=""
			onclick="$('#status').fadeOut('100');
					 $('#global').fadeOut('100');
					 $('#about').fadeOut('100');
					 $('#workflow').fadeOut('100');					 
					 $('#roadmap').delay('200').fadeIn('100');
					 $('#global-button').animate({backgroundColor: '#333'}, 300);
					 $('#status-button').animate({backgroundColor: '#333'}, 300);
					 $('#workflow-button').animate({backgroundColor: '#333'}, 300);
					 $('#about-button').animate({backgroundColor: '#333'}, 300);					 					 
					 $('#roadmap-button').animate({backgroundColor: '#393939'}, 300);
					 ">Roadmap</li>
	</ul>
</div>

<div id="content">

<div id="header" style="margin-top: 1em;">
	<div>
		<div style='float:left; position:relative;min-width:100px; margin-right:1em;'>
			<h2>Packal:</h2>
		</div>
		<div style='float:left; position:relative;min-width:400px;top: 2px;'>
			<h4> manage your Alfred 2 Workflows</h4>
		</div>
	</div>
	<div style='opacity: .85; float:right; position:relative;margin-right:12em;'>
		<img src="/resources/images/blanched/packal-man_blanched-icon.png">
	</div>
<!--	<div style='float:left; position:relative;min-height: 3px;top: 50px;margin-left: 1%; min-width: 95%;'>
		<hr class="divider">
	</div> -->
</div>
<!-- A place to display messages. -->
<div id="messages">

</div>
<a href="/resources/templates/welcome.php">Try the Animation</a>
<!-- The functions for the status as well as general workflow loading -->
<!-- I need to remove the logic from the view here. Next version. -->
<div id="status" class="section">
<h5>Packal Status</h5>

<?php


// I really need to clean this thing up. The lists are being generated, but I need to
// 	1 Fix the styling
// 	2 Check the config preferences for the "auto_add" function
// 	3 Move the ones installed but blacklisted (if not auto-add) into the blacklisted array
// 	4 then I need to rewrite how those work... and the entire update logic

/**
 *
 *	Load the last copy of the manifest.
 *
 */
// Currently using a dev copy in the Workflow folder. This will be changed to one at
// $data/global/manifest.xml
// We'll get the copy from github

$manifest = simplexml_load_file('../manifest.xml');

// Set blank variables for use shortly...

// Scan the Workflows directory to get a copy of each one installed
$installedWorkflows = scandir("../../");
// Unset "."
unset($installedWorkflows[0]);
// Unset ".."
unset($installedWorkflows[1]);

// A list for installed workflows with bundles
$workflowBundles = array();

// A list for installed workflows with no bundles
$workflowNoBundle = array();

// All the bundle ids from the manifest
$availableBundles = array();

// All the installed workflows that have a match in the manifest
$availableWorkflows = array();

// Just a counter.
$i = 0;

// Collect the bundle ids for each of the workflows in the manifest
foreach ( $manifest as $workflow ) {
  $availableBundles[] = (string)$workflow->bundle;
}

$correct = array();

// Cycle through the directories to get a birdseye view of what's installed
foreach ($installedWorkflows as $workflowDirectory) {

  // We already unset these values, but redundancy isn't really a bad thing.
  if (! ( ( $workflowDirectory == '.' ) || ( $workflowDirectory == '..' ) ) ) {

  	// Set this to the individual plist
    $plist = __DIR__ . '/../../' . $workflowDirectory . '/info.plist';


/**
 * Create a new CFPropertyList instance that loads the info.plist file from the
 * workflow.
 */
    $workflow = new CFPropertyList( $plist , CFPropertyList::FORMAT_XML );

    $tmp = $workflow->toArray();

    if (! empty( $tmp['bundleid'] ) ) {
    	// We have a bundle. Let's log the information.
    	$workflowBundles[$i]['bundle']  	= $tmp['bundleid'];
    	$workflowBundles[$i]['name'] 		= $tmp['name'];
    	$workflowBundles[$i]['dir']			= $workflowDirectory;
    } else {
    	// There is no bundle, so push that name to the no bundle list.
    	$workflowNoBundle[] = $tmp['name'];
    }
    // Increment the counter.
    $i++;
    // Clear some memory
    unset($tmp);
  }
  // Clear some memory
  unset($workflow);
}

/**
 *
 *	Now we have the installed workflows with bundles and without. Let's check
 *	the bundles against what we have in the manifest.
 *
 */

foreach ( $workflowBundles as $workflow ) {

	if ( in_array( $workflow['bundle'] , $availableBundles ) ) {
		array_push( $availableWorkflows , $workflow );
	}

}

$date = time() - filectime( '../manifest.xml' ) ;
if ( ( $date / ( 60 * 60 * 24 * 7 ) ) > 1 ) {
	$weeks = floor($date / ( 60 * 60 * 24 * 7 ) );
	$date = $date - ($weeks * 60 * 60 * 24 * 7);
}
if ( ( $date / ( 60 * 60 * 24 ) ) > 1 ) {
	$days = floor($date / ( 60 * 60 * 24 ) );
	$date = $date - ($days * 60 * 60 * 24 );
}
if ( ( $date / ( 60 * 60 ) ) > 1 ) {
	$hours = floor($date / ( 60 * 60 ) );
	$date = $date - ($hours * 60 * 60 );
}
if ( ( $date / ( 60 ) ) > 1 ) {
	$minutes = floor($date / ( 60 ) );
	$date = $date - ( $minutes * 60 );
}
if ( $date > 1 ) {
	$seconds = $date;
}

// echo "<p class='letterpress'>Manifest last updated $weeks weeks and $days days and $hours hours $minutes minutes and $seconds seconds ago </p>";
if ( isset($weeks) ) {
	echo "<p class='letterpress'>The manifest is over a week old.</p>";
} else if ( ! isset( $days ) ) {
	echo "<p class='letterpress'>The manifest is less than a day old.</p>";
} else {
	echo "<p class='letterpress'>The manifest is about $days days old.</p>";
}
echo "<p class='letterpress'>There are " . $manifest->count() . " workflows in the manifest.</p>";
echo "<p class='letterpress'>Of those, " . count($workflowBundles) . " have bundle ids.</p>";
echo "<p class='letterpress'>And the other " . count($workflowNoBundle) . " have no bundle ids and so Packal cannot interact with them.</p>";

// Let's put a way to get the version for the packaging info and place it here.
echo "<p class='letterpress'>Packal v1.0</p>";
echo "<button disabled>Upgrade Packal</button>";


/**
 * Check to see if packal has an upgrade available.
 * @return bool 	true/false
 */
function checkPackalUpgrade() {
// This function will go somewhere else


	return false;
}

/**
 * Upgrades Packal itself
 * @return bool true/false
 */
function selfUpgrade() {
// This function will go somewhere else
// And, really, what I need to do is to 
// 		1 download the file
// 		2 copy a migration script into the data directory
// 		3 run and disown the script
// 		4 that script needs a sleep timer on it for a bit
// 		5 delete packal directory / overwrite it.
// 		6 there should be no other keywords or hotkeys set
// 			so I won't worry about overwriting those
// 		7 applescript to invoke packal again
// 		8 upgrade notes for packal should be shown
// 
	return false;
}

/**
 * Invokes the native notification utility
 * @param  string $title    Title of the notification
 * @param  string $subtitle Subtitle of the notification
 * @param  string $message  Message
 * @return bool           
 */
function notify( $title = 'Packal' , $subtitle , $message ) {
	// This function will be migrated to its own php file
	// so that we can call it via ajax.

	$cmd = "command to invoke the native utility";
	exec($cmd);
}

function workflowOption( $bundle ) {

}

/**
 * Query the blacklist xml file and returns an array of blacklisted Workflows
 * @return xml object 	an xml object of the blacklist file
 */
function getBlacklist() {
	// Set the user's home directory
	$home = exec('echo $HOME');
	// The location of the data directory
	$data = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice/";
	$blacklist = $data . "blacklist/blacklist.xml";

	// If blacklist directory doesn't exist, then make it.
	if (! file_exists( $data . "blacklist") ) mkdir( $data . "blacklist" );
	// If blacklist file doesn't exist, then put in one that is basically empty.
	if (! file_exists( $data . "blacklist/blacklist.xml" ) ) {
		$blacklist  = "<?xml version='1.0' encoding='UTF-8' ?>\n";
		$blacklist .= "<blacklist>\n";
		$blacklist .= "</blacklist>\n";
		file_put_contents( $data . "blacklist/blacklist.xml" , $blacklist );
	}

	$blacklist = simplexml_load_file( $data . "blacklist/blacklist.xml" );
	$return = array();
	foreach ( $blacklist as $w ) {

		$bundle = (string)$w->bundle;
		$dir = (string)$w->dir;
		$return[$dir] = $bundle;
	}
	return $return;
}




/**
 * 
 * Variables for the Workflow options:
 *
 * In the manifest and installed
 * Installed but not in the manifest
 * Not bundleid
 * 
 */
?>
<style>

.workflow-option {
	width: 100%;
	height: 60px;
	font-size: 2em;
}

.workflow-option .onoffswitch {
	float: left;
	position: relative;
}

</style>
<?php
$blacklist = array();
// Get the blacklist xml object
$blacklist = getBlacklist();

?>

<h4>Packal</h4>
<?php
$base_dir = exec("pushd ../../ > /dev/null; pwd ; popd > /dev/null;");
foreach ( $availableWorkflows as $workflow ) {

	if ( in_array( $workflow['bundle'] , $blacklist ) ) {
		$no_update = true;
	} else {
		$no_update = false;
	}

	// So, Packal needs a special updating mechanism because, well, it's hard to overwrite the files that
	// are overwritng things.
	if ( $workflow['bundle'] == 'com.packal.shawn.patrick.rice' ) {
		unset( $availableWorkflows[array_search('com.packal.shawn.patrick.rice', $availableWorkflows)] );
	}
	if (! $no_update ) {
		printWorkflowOptionEntry( $base_dir . "/" . $workflow['dir'] , $workflow['dir'] , $no_update , false );
	}
}
?>
<h4>Blacklisted</h4>
<?php
$tmp = false;
foreach ( $availableWorkflows as $workflow ) {

	if ( in_array( $workflow['bundle'] , $blacklist ) ) {
		$no_update = true;
		$tmp = true;
	} else {
		$no_update = false;
	}

	// So, Packal needs a special updating mechanism because, well, it's hard to overwrite the files that
	// are overwritng things.
	if ( $workflow['bundle'] == 'com.packal.shawn.patrick.rice' ) {
		unset( $availableWorkflows[array_search('com.packal.shawn.patrick.rice', $availableWorkflows)] );
	}
	if ( $no_update ) {
		printWorkflowOptionEntry( $base_dir . "/" . $workflow['dir'] , $workflow['dir'] , $no_update , false );
	}
}

if (! $tmp ) {
	echo "<p>There are no blacklisted workflows</p>";
	print_r($tmp);
}

?>
<h4>Not Available</h4>
<p>These Workflows are installed and have bundle ids, but they are not yet available on Packal. If you would like
for any of these workflows to have auto-updates, please contact the Workflow author and encourage them to upload
the Workflow on Packal.org</p>
<?php

	foreach( $workflowBundles as $workflow ) {
		printWorkflowOptionEntry( $base_dir . "/" . $workflow['dir'] , $workflow['dir'] , false , true );
	}



?>

<h4>No Bundle</h4>
<p>These Workflows have no bundle ids set, and so Packal doesn't know how to identify them.</p>
<?php

if ( count( $workflowNoBundle ) ) {

	foreach( $workflowNoBundle as $workflow ) {
	?>
			<div class = "workflow-option nobundle">
				<div class="workflow-option-name workflow-not-managed">
	<?php		echo $workflow; ?>
				</div>
			</div>
	<?php
	}
} else {
	echo "<p>All your workflows have bundle ids.</p>";
}

/**
 * Returns a formatted div for the Workflow options section of the Packal config
 * @param  string 	$dir       	the path to the directory where the workflow is stored
 * @param  bool 	$blacklist 	whether or not we return the blacklisted version markup
 * @param  bool 	$nobundle  	there is no bundle, so return that markup
 * @return string            	the html for the entry
 */
function printWorkflowOptionEntry( $dir , $topdir , $blacklist , $no_manifest ) {
// Just load the plist, well, read the contents of the plist with plist buddy.


	$name = getName( $dir );
	if (! $no_manifest ) { $bundle = getBundle( $dir ); }
	$classes = "workflow_option ";
	if (! $no_manifest ) {
		$bundle_escaped = str_replace('.', '' , $bundle);
?>
	<div class = "workflow-option">
		<div class = "workflow-option-switch">
			<div class="onoffswitch">
<?php 
			if ( $blacklist == false ) {
				echo "<input id='$bundle_escaped-blacklist' class='onoffswitch-checkbox' type='checkbox' checked onclick='blacklistWorkflow(\"$bundle_escaped\");'>\n";
			} else {
				echo "<input id='$bundle_escaped-blacklist' class='onoffswitch-checkbox' type='checkbox' onclick='blacklistWorkflow(\"$bundle_escaped\");'>\n";
			}
?>			    <label class="onoffswitch-label" for=<?php echo "'$bundle_escaped-blacklist'";?>>
			        <div class="onoffswitch-inner"></div>
			        <div class="onoffswitch-switch"></div>
			    </label>
			</div>
		</div>
<?php	echo "<input id='$bundle_escaped-dir' name='$bundle_escaped' type='hidden' value='$dir'>\n"; ?>
<?php	echo "<input id='$bundle_escaped-bundle' name='$bundle_escaped-bundle' type='hidden' value='$bundle'>\n"; ?>
		<div class="workflow-option-name">
			<?php echo $name; ?>
		</div>
	</div>
<?php
	} else {
// This should never be called, but, whatevs. It's still here just in case.
?>
	<div class = "workflow-option nobundle">
		<div class="workflow-option-name">
<?php		echo $name; ?>
		</div>
	</div>
<?php		
	}
}

// For the not auto-updating...
// If the workflow appears in the manifest, and the auto-add is false
// Then automatically put it on the blacklist

// Foreach in manifest and installed

// Order to display
// 
// Workflows in the manifest
// 
// Workflows with bundles but not available in the manifest
// 
// Workflows with no bundles

?>
</div>


<!-- Here are the global display options. -->
<div id="global" class="hide section">
<?php include('../resources/templates/settings.php'); ?>
</div>

<div id='roadmap' class='hide section'>
<?php include('../resources/static/roadmap.html'); ?>
</div>
<div id='about' class='hide section'>
<?php include('../resources/static/about-section.html'); ?>
</div>

<script>

function updateWorkflow(e) {
    var currentRow = $(e).closest('tr');
    var id = $(e).attr('id');
    id = 'status-bar-' + id;
    $(currentRow).after('<tr class="updating-status"><td class="td-input">&nbsp;</td><td>&nbsp;</td><td><div class="status-outer"><div id=\"' + id + '\" class="status-inner"></div></div></td><td style="text-align: center;">1 / 5</td></tr>');
    $(e).prop('disabled', true);
    $(e).prop('value', 'Installing...');
}
</script>

<!-- Here are the individual workflows that Packal controls. -->
<div id="workflow" class="section hide">
	<h4>Workflows with Bundles</h4>
	<table id="workflow-list">
<?php


// Create the form to specify which workflows are available for Packal to control.
$stripe = true;
foreach ( $availableWorkflows as $workflow ) {
	if ( $stripe ) {
		$bg = "background-a";
		$stripe = false;
	}
	else {
		$bg = "background-b";
		$stripe = true;
	}
	echo "
		<tr class='$bg'>
		<td class='test'>
			<table id='". $workflow['bundle'] . "' style='width: 100%; text-align: left;'>
				<tr id='row-". str_replace('.' , '-', $workflow['bundle'] ) . "'>
					<td class='td-input'>&nbsp;</td>
					<td class='tdd'><label for='" . $workflow['bundle'] . "'>" . $workflow['name'] . "</label></td>
					<td class='tdd' style='font-size:9px; text-shadow: none; text-align: left;'>(" . $workflow['bundle'] . ")</td>
					<td class='tdd update-button'><input id='" . str_replace(".", "", $workflow['bundle']) . "' class='update-button-button' type='button' value='Install Update' onclick=\"updateWorkflow(this);\"></td>
				</tr>
			</table>
		</td>
		</tr>
		<tr class='spacer'><td class='spacer'>&nbsp;</td></tr>";
}
?>
	</table>
</div>


</div>
</div>
</body>
</html>


<?php

function refreshLocalManifest() {
	$base_dir = exec("pushd ../../ > /dev/null; pwd ; popd > /dev/null;");

	if (file_exists($filename)) {
	    echo "$filename was last modified: " . date ("Y-m-d", filemtime($filename));
	}

}


?>