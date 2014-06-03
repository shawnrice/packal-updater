
/**
 * Set a variable and invoke the keepAlive function and execute every 20 seconds
 * @param  {function} 	keepAlive() call to function
 * @param  {int} 		20000 		try every 20 seconds
 * @return {void}                 	returns nothing, just runs every 20 seconds
 */
var keepMeAlive=setInterval( function() { keepAlive() }, 20000 );

/**
 * A callback to a keep alive script that updates a file to let a bash script know
 * not to kill the webserver
 * @return {void} no return value
 */
function keepAlive() {
	xmlhttp=new XMLHttpRequest();
	xmlhttp.open( "GET","http://localhost:7893/webserver-keep-alive-update.php" );
	xmlhttp.send();
	console.log( "Calling home.");
}
