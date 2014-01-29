
/**
 * Set a variable and invoke the keepAlive function and execute every 20 seconds
 * @param  {function} 	keepAlive() call to function
 * @param  {int} 		20000 		try every 20 seconds
 * @return {void}                 	returns nothing, just runs every 20 seconds
 */
var keepMeAlive=setInterval(function(){keepAlive()},20000);

/**
 * A callback to a keep alive script that updates a file to let a bash script know
 * not to kill the webserver
 * @return {void} no return value
 */
function keepAlive() {
	xmlhttp=new XMLHttpRequest();
	xmlhttp.open("GET","http://localhost:7893/scripts/webserver-keep-alive-update.php");
	xmlhttp.send();
}

/**
 * Just an ajax call to open the backups directory
 * @return {void} no return value
 */
function openBackupDir() {
    $.ajax({
       type: "POST",
       url: "open-backup-directory.php",
     });
}


function backupWorkflow(dir) {

	var value = { "dir" : dir };
	var handler = $.post("backup-workflow.php", value )
		.done(function(data) {
			console.log(data);
	});

}

/**
 * Ajax callback to write a new value to the config file
 * @param  {string} item an id of some sort of input
 * @return {void}   we return nothing
 */
function writeConfig(item) {

	var send = false;

	if (item == 'backup') {
		var value = $('#' + item ).val();
		var sendback = { "backup" : value };
		send = true;
	}

	if (item == 'auto_add') {
		if ($('#' + item ).prop('checked')) {
			var sendback = { "auto_add": "1" };
		} else {
			var sendback = { "auto_add": "0" };
		}
		send = true;
	}

	if (item == 'report') {
		if ($('#' + item ).prop('checked')) {
			var sendback = { "report": "1" };
		} else {
			var sendback = { "report": "0" };
		}
		send = true;
	}

	if (item == 'notify') {
		var value = $('#' + item ).val();
		var sendback = { "notify" : value };
		send = true;
	}

	if (item == 'username') {
		// I need to add an error check to make sure that the username exists before it's sent.
		// Write script drush script to load the user and make sure that it exists on Packal
		// Receive Success or Failure before submitting.
		var value = $('#' + item ).val();
		var sendback = { "username" : value };
		console.log(value);
		send = true;
	}

//	Disabled for now.
//	if (item == 'api_key') {
//		var value = $('#' + item ).val();
//		var sendback = { "api_key" : value };
//		send = true;
//	}

	if (send) {
		var handler = $.post("http://localhost:7893/scripts/write-config-callback.php", sendback )
			.done(function(data) {
				console.log(data);
		});

	}
}


function blacklistWorkflow(item) {

	var send = false;

	var bundle = $('#' + item + '-bundle').val();
	var dir = $('#' + item + '-dir').val();
	
	if ($('#' + item + '-blacklist').prop('checked') ) {
		var method = "whitelist";
	} else {
		var method = "blacklist" ;
	}
	var sendback = { "bundle" : bundle , "dir" : dir , "method" : method };

	var send = true;

	if (send) {
		var handler = $.post("http://localhost:7893/scripts/blacklist-ajax.php", sendback )
			.done(function(data) {
//				console.log(data);
		});

	}

}

/**
 * Ajax callback to get a new copy of the manifest from Github
 * @param  {string} 	just dummy data, which I can probably remove 
 * @return {void}     	returns nothing
 */
function getManifest(item) {
	var handler = $.post("download-manifest.php", 'something' )
//			old debugging code
//			.done(function(data) {
//				alert(data);
//			})
			;
}
