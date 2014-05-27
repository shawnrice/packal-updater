<?php

/**
 *
 * This is the file that receives the input from the script filter and then starts everything.
 * 
 */

// $q = $argv[1];

$dir = exec( "pwd" );
// Start the webserver and disown it
exec("$dir/resources/applications/php-packal -S localhost:7893 ");
//> /dev/null 2>&1; disown $!

// Start the webserver kill script and disown it
exec("bash $dir/scripts/check-and-kill-webserver.sh ");
// > /dev/null 2>&1; disown $!
// Wait a second so that we can make sure that the webserver has started before we open the viewer.
sleep(3);

// Open the config page in the viewer and disown it
exec("open resources/applications/viewer.app --args http://localhost:7893/gui ");	
//> /dev/null 2>&1; disown $!

?>