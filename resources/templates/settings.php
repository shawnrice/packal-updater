<h4>General Settings</h4>
<?php
	// Set the user's home directory
	$home = exec('echo $HOME');
	// The location of the config file
	$config = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice/config/config.xml";

if (! file_exists($config)) {
	resetToDefaults();
}

$config = simplexml_load_file($config);

?>
<div id = "config-options">
	<div class='option'>
		<div class='label-text'>
			<label for="backup">Number of Backups to Keep</label>
		</div>
		<div class='fancy-select'>
			<select id='backup' name='backup' onchange="writeConfig('backup');">
			<?php
				$backup = $config->backup;
				echo "<option";
				if ($backup == 0) echo " selected";
				echo" value='0'>None</option>";
				echo "<option";
				if ($backup == 1) echo " selected";
				echo" value='1'>One</option>";
				echo "<option";
				if ($backup == 2) echo " selected";
				echo" value='2'>Two</option>";
				echo "<option";
				if ($backup == 3) echo " selected";
				echo" value='3'>Three</option>";
				echo "<option";
				if ($backup == 4) echo " selected";
				echo" value='4'>Four</option>";
				echo "<option";
				if ($backup == 5) echo " selected";
				echo" value='5'>Five</option>";
				echo "<option";
				if ($backup == 6) echo " selected";
				echo" value='6'>Six</option>";
				echo "<option";
				if ($backup == 7) echo " selected";
				echo" value='7'>Seven</option>";
				echo "<option";
				if ($backup == 8) echo " selected";
				echo" value='8'>Eight</option>";
				echo "<option";
				if ($backup == 9) echo " selected";
				echo" value='9'>Nine</option>";
				echo "</select>";
			?>
		</div>
	</div>
	<div class="information">
		<p style="text-align: right;"><a href="#" onclick="openBackupDir();">Open Backup Dir</a></p>
	</div>
	<div class='option'>
		<div class='label-text'>
			<label for="auto_add">Auto-add control of updates to Packal</label>
		</div>
		<div class="onoffswitch">
			<input id='auto_add' class="onoffswitch-checkbox" type='checkbox'
		<?php if ($config->auto_add) echo " checked"; ?> 
		 onclick="writeConfig('auto_add');">
		    <label class="onoffswitch-label" for="auto_add">
		        <div class="onoffswitch-inner"></div>
		        <div class="onoffswitch-switch"></div>
		    </label>
		</div>
	</div>
	<div class="information">
		<p> Have Packal automatically control updates of Workflows that you have installed
			when they appear on Packal.org, even if you downloaded them elsewhere.
		</p>
	</div>
	<div class='option'>
		<div class='label-text'>
			<label for="report">Report Anonymous Workflow Data</label>
		</div>
		<div class="onoffswitch">
			<input id='report' class="onoffswitch-checkbox" type='checkbox' 
		<?php if ($config->report == 1) { echo " checked "; } ?>
		onclick="writeConfig('report');">
		    <label class="onoffswitch-label" for="report" >
		        <div class="onoffswitch-inner"></div>
		        <div class="onoffswitch-switch"></div>
		    </label>
		</div>
	</div>
	<div class="information">
		<p> Report data about which Workflows you have installed, enabled, and disabled. 
			No identifying information is sent. The reports help Packal.org create a more
			intelligent "Popular Workflows" list and is shown to authors of their Workflows.
			Turn this off to opt-out.
		</p>
	</div>
	<div class='option'>
		<div class='label-text'>
			<label for="notify">Notification Style</label>
		</div>
		<div class='fancy-select'>
			<select id='notify' name='notify' onchange="writeConfig('notify');">
	<?php
			echo "<option";
			if ($config->notify == "Native") echo " selected";
			echo ">Native</option>";
			echo "<option";
			if ($config->notify == "Growl") echo " selected";
			echo ">Growl</option>";
			echo "<option";
			if ($config->notify == "OS X") echo " selected";
			echo ">OS X</option>";
	?>
			</select>
		</div>
	</div>
	<div class="information">
		<p>	Packal can notify you when your updates are done. Select the style that you want. "Native"
			comes from an application bundled with Packal. Notifications will be better utilized in 
			future versions.
		</p>
	</div>
	<h4>Packal Workflow Authoring</h4>
	<div class='option'>
		<div class='label-text'>
			<label for='username'>Packal Username</label>
		</div>
		<div class='fancy-text'>
			<input id='username' onchange="writeConfig('username');" type='text' size='20' name='username' 
<?php 	if ( $config->username ) echo "value='$config->username'";
	  	else echo "placeholder='username'";
?>
>		</div>
	</div>
	<div class='information'>
		<p>If you have an account on Packal.org and have contributed Workflows, then please put in 
		your username, so that we don't try to update the Workflows that you have contributed (you
		probably have the most recent copy anyway).
		</p>
	</div>
	<div class='option'>
		<div class='label-text'>
			<label for='api_key'>Packal API Key</label>
		</div>
		<div class='fancy-text'>
			<input id='api_key' type='text' size='20' name='api_key' disabled value="xxxxxxxxxxx">
		</div>
	</div>
	<div class="information">
			<p>The API key is currently disabled. In a future version, you will be able to push <em>updates</em>
		of your Workflows to Packal from Alfred. Until that update, ignore this box.
		</p>
	</div> 
</div>