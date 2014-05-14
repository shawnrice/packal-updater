<?php
/**
 * tags demonstration
 *
 * @author this tag is parsed, but this @version tag is ignored
 * @version 1.0 this version tag is parsed
 */

namespace CFPropertyList;

// Require CFPropertyList
require_once __DIR__.'/../libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php';
// Require David Ferguson's Workflows class
require_once '../libraries/workflows.php';

// require_once(__DIR__.'/check-server-status.php');
require_once 'config-functions.php';

$config = loadConfig();

/**
 * Write the initial 'keep-alive' zombie file before letting the js maintain it.
 * This call just ensures that the kill-webserver script doesn't kill the webserver
 * too soon. It's probably uncessary, but it's a nice safety net.
 */
require_once 'webserver-keep-alive-update.php';

// Add the plist functions for later use...
require_once 'plist-functions.php';

// Avoid collisions
if ( ! isset( $w ) ) {
	// Escape the new Workflow object so it doesn't collide withthe CFPropertyList namespace
	$w = new \Workflows();
}

// Check if Growl is installed.
$cmd = "ps aux | grep Growl | grep -v grep";
if ( shell_exec( $cmd ) ) {
	$growl = true;   // Growl is running.
} else {
	$growl  = false;  // Growl is not running, so assume it isn't installed.
}

/**
 * Include our generic header, which contains the html header, but it also
 *  loads our keep-alive.js to make sure the webserver doesn't commit
 *  suicide while we still need it.
 *
 *
 */
include '../resources/templates/header.php';
// This includes the keep alive function.
// Within that, we could also have something that checks the external internet connection
// If it fails, then we disable the update functionality, and if it passes, then we enable it.
// Obviously, this need to be run immediately.
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
</div> <!-- End Sidebar -->

<div id="content">

<div id="header">
	<div>
		<div>
			<h2>Packal:</h2>
		</div>
		<div>
			<h4> manage your Alfred 2 Workflows</h4>
		</div>
	</div>
</div>
<div id="status" class="section">
<h5>Packal Status</h5>

<?php


$manifest = simplexml_load_file( '../manifest.xml' );


// Scan the Workflows directory to get a copy of each one installed
$installedWorkflows = scandir( "../../" );

// Okay, let's unset some hidden files.
$unset = array( "." , ".." , ".DS_Store" );
if ( in_array( $installedWorkflows[0] , $unset ) )
	unset( $installedWorkflows[0] );
if ( in_array( $installedWorkflows[1] , $unset ) )
	unset( $installedWorkflows[1] );
if ( in_array( $installedWorkflows[2] , $unset ) )
	unset( $installedWorkflows[2] );


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
foreach ( $installedWorkflows as $workflowDirectory ) {

	// We already unset these values, but redundancy isn't really a bad thing.
	if ( ! ( ( $workflowDirectory == '.' ) || ( $workflowDirectory == '..' )  || ( $workflowDirectory == '.DS_Store' ) ) ) {

		// Set this to the individual plist
		$plist = __DIR__ . '/../../' . $workflowDirectory . '/info.plist';


		/**
		 * Create a new CFPropertyList instance that loads the info.plist file from the
		 * workflow.
		 */
		$workflow = new CFPropertyList( $plist , CFPropertyList::FORMAT_XML );

		$tmp = $workflow->toArray();

		if ( ! empty( $tmp['bundleid'] ) ) {
			// We have a bundle. Let's log the information.
			$workflowBundles[$i]['bundle']   = $tmp['bundleid'];
			$workflowBundles[$i]['name']   = $tmp['name'];
			$workflowBundles[$i]['dir']   = $workflowDirectory;
		} else {
			// There is no bundle, so push that name to the no bundle list.
			$workflowNoBundle[] = $tmp['name'];
		}
		// Increment the counter.
		$i++;
		// Clear some memory
		unset( $tmp );
	}
	// Clear some memory
	unset( $workflow );
}

/**
 * Now we have the installed workflows with bundles and without. Let's check
 * the bundles against what we have in the manifest.
 *
 */

foreach ( $workflowBundles as $workflow ) {

	if ( in_array( $workflow['bundle'] , $availableBundles ) ) {
		array_push( $availableWorkflows , $workflow );
	}

}

// This is some code to see if the manifest is fresh.
$date = time() - filectime( '../manifest.xml' ) ;
if ( ( $date / ( 60 * 60 * 24 * 7 ) ) > 1 ) {
	$weeks = floor( $date / ( 60 * 60 * 24 * 7 ) );
	$date = $date - ( $weeks * 60 * 60 * 24 * 7 );
}
if ( ( $date / ( 60 * 60 * 24 ) ) > 1 ) {
	$days = floor( $date / ( 60 * 60 * 24 ) );
	$date = $date - ( $days * 60 * 60 * 24 );
}
if ( ( $date / ( 60 * 60 ) ) > 1 ) {
	$hours = floor( $date / ( 60 * 60 ) );
	$date = $date - ( $hours * 60 * 60 );
}
if ( ( $date / ( 60 ) ) > 1 ) {
	$minutes = floor( $date / ( 60 ) );
	$date = $date - ( $minutes * 60 );
}
if ( $date > 1 ) {
	$seconds = $date;
}




// echo "<p class='letterpress'>Manifest last updated $weeks weeks and $days days and $hours hours $minutes minutes and $seconds seconds ago </p>";
if ( isset( $weeks ) ) {
	echo "<p class='letterpress'>The manifest is over a week old.</p>";
} else if ( ! isset( $days ) ) {
		echo "<p class='letterpress'>The manifest is less than a day old.</p>";
	} else {
	echo "<p class='letterpress'>The manifest is about $days days old.</p>";
}

?>

<table class='table-info'>
	<tr>
		<td>Workflows in Manifest</td><td><?php echo $manifest->count(); ?></td>
	</tr>
	<tr>
		<td>Workflows Installed</td><td><?php echo count($installedWorkflows); ?></td>
	</tr>
	<tr>
		<td>Workflows Installed with no Bundles</td><td><?php echo count($workflowNoBundle); ?></td>
	</tr>
	<tr>
		<td>Workfows You've Contributed</td><td></td>
	</tr>
</table>



<?php

echo $config->username;
echo "<p class='letterpress'>There are " . $manifest->count() . " workflows in the manifest.</p>";
echo "<p class='letterpress'>Of those, " . count( $workflowBundles ) . " have bundle ids.</p>";
echo "<p class='letterpress'>And the other " . count( $workflowNoBundle ) . " have no bundle ids and so Packal cannot interact with them.</p>";

// Let's put a way to get the version for the packaging info and place it here.
echo "<p class='letterpress'>Packal v1.0</p>";
echo "<button disabled>Upgrade Packal</button>";


$blacklist = array();
// Get the blacklist xml object
$blacklist = getBlacklist();

?>

<h4>Packal</h4>
<?php
$base_dir = exec( "pushd ../../ > /dev/null; pwd ; popd > /dev/null;" );
foreach ( $availableWorkflows as $workflow ) {

	if ( in_array( $workflow['bundle'] , $blacklist ) ) {
		$no_update = true;
	} else {
		$no_update = false;
	}

	// So, Packal needs a special updating mechanism because, well, it's hard to overwrite the files that
	// are overwritng things.
	if ( $workflow['bundle'] == 'com.packal.shawn.patrick.rice' ) {
		unset( $availableWorkflows[array_search( 'com.packal.shawn.patrick.rice', $availableWorkflows )] );
	}
	if ( ! $no_update ) {
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
		unset( $availableWorkflows[array_search( 'com.packal.shawn.patrick.rice', $availableWorkflows )] );
	}
	if ( $no_update ) {
		printWorkflowOptionEntry( $base_dir . "/" . $workflow['dir'] , $workflow['dir'] , $no_update , false );
	}
}

if ( ! $tmp ) {
	echo "<p>There are no blacklisted workflows</p>";
	print_r( $tmp );
}

?>
<h4>Not Available</h4>
<p>These Workflows are installed and have bundle ids, but they are not yet available on Packal. If you would like
for any of these workflows to have auto-updates, please contact the Workflow author and encourage them to upload
the Workflow on Packal.org</p>
<?php

foreach ( $workflowBundles as $workflow ) {
	printWorkflowOptionEntry( $base_dir . "/" . $workflow['dir'] , $workflow['dir'] , false , true );
}



?>

<h4>No Bundle</h4>
<p>These Workflows have no bundle ids set, and so Packal doesn't know how to identify them.</p>
<?php

if ( count( $workflowNoBundle ) ) {

	foreach ( $workflowNoBundle as $workflow ) {
?>
			<div class = "workflow-option nobundle">
				<div class="workflow-option-name workflow-not-managed">
	<?php  echo $workflow; ?>
				</div>
			</div>
	<?php
	}
} else {
	echo "<p>All your workflows have bundle ids.</p>";
}



?>
</div>


<!-- Here are the global display options. -->
<div id="global" class="hide section">
<?php include '../resources/templates/settings.php'; ?>
</div>

<div id='roadmap' class='hide section'>
<?php include '../resources/static/roadmap.html'; ?>
</div>
<div id='about' class='hide section'>
<?php include '../resources/static/about-section.html'; ?>
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
			<table id='". $workflow['bundle'] . "'>
				<tr id='row-". str_replace( '.' , '-', $workflow['bundle'] ) . "'>
					<td class='td-input'>&nbsp;</td>
					<td class='tdd'><label for='" . $workflow['bundle'] . "'>" . $workflow['name'] . "</label></td>
					<td class='tdd'>(" . $workflow['bundle'] . ")</td>
					<td class='tdd update-button'><input id='" . str_replace( ".", "", $workflow['bundle'] ) . "' class='update-button-button' type='button' value='Install Update' onclick=\"updateWorkflow(this);\"></td>
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
	$base_dir = exec( "pushd ../../ > /dev/null; pwd ; popd > /dev/null;" );

	if ( file_exists( $filename ) ) {
		echo "$filename was last modified: " . date( "Y-m-d", filemtime( $filename ) );
	}

}

/**
 * Query the blacklist xml file and returns an array of blacklisted Workflows
 *
 * @return xml object  an xml object of the blacklist file
 */
function getBlacklist() {
	// Set the user's home directory
	$home = exec( 'echo $HOME' );
	// The location of the data directory
	$data = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice/";
	$blacklist = $data . "blacklist/blacklist.xml";

	// If blacklist directory doesn't exist, then make it.
	if ( ! file_exists( $data . "blacklist" ) ) mkdir( $data . "blacklist" );
	// If blacklist file doesn't exist, then put in one that is basically empty.
	if ( ! file_exists( $data . "blacklist/blacklist.xml" ) ) {
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
 * Returns a formatted div for the Workflow options section of the Packal config
 *
 * @param string  $dir       path to the directory where the workflow is stored
 * @param bool    $blacklist whether or not we return the blacklisted markup
 * @param bool    $nobundle  there is no bundle, so return that markup
 * @return string            the html for the entry
 */
function printWorkflowOptionEntry( $dir , $topdir , $blacklist , $no_manifest ) {
	// Just load the plist, well, read the contents of the plist with plist buddy.
	$name = getName( $dir );
	if ( ! $no_manifest ) { $bundle = getBundle( $dir ); }
	if ( ! $no_manifest ) {
		$bundle_escaped = str_replace( '.', '' , $bundle ); ?>
	<div class = "workflow-option">
<?php
		if ( $blacklist == false ) { ?>
      <input id='<?php echo $bundle_escaped; ?>-blacklist' type='checkbox' checked onclick='blacklistWorkflow("<?php echo $bundle_escaped; ?>");'>
<?php
    } else {
			echo "<input id='$bundle_escaped-blacklist' type='checkbox' onclick='blacklistWorkflow(\"$bundle_escaped\");'>\n";
		} ?>
      <label for=<?php echo "'$bundle_escaped-blacklist'";?>><?php echo $name; ?></label>
      <?php echo "<input id='$bundle_escaped-dir' name='$bundle_escaped' type='hidden' value='$dir'>\n"; ?>
      <?php echo "<input id='$bundle_escaped-bundle' name='$bundle_escaped-bundle' type='hidden' value='$bundle'>\n"; ?>
	</div>
<?php
	} else {
		// This should never be called, but, whatevs. It's still here just in case.
?>
	<div class = "workflow-option nobundle">
    <?php echo $name; ?>
	</div>
<?php
	}
}
?>
