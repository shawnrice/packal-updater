<?php

/**
 *
 * Utility script to determine whether a server is reachable. Here, we've hard
 * coded github. So, we'll use this to see if Packal can reach Github; if it
 * can't, then it will throw error messages when trying to update.
 *
 * We're making sure that we can reach Github in under one second because it'll
 * be too painful to update anything otherwise.
 * 
 */


if ( checkServer() ) {
?>
	<div class='informational message' onclick="$(this).fadeOut('3000'); $('div.section').delay('100').animate({top: '-=50'},400);">
	<div class='close-box'>x</div><div>The server is reachable.</div></div>
<?php
} else {
?>
	<div class='warning message'>The server is not reachable. An Internet connection is needed to update your Workflows, but you can still configure Packal's behavior offline.</div>
<?php
}
function checkServer() {

  $url = 'github.com';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (200==$retcode) {
        return true;
    } else {
        return false;
    }


	// $waitTimeoutInSeconds = 1; 

	// try {
	// 	$fp = fsockopen( "github.com" , "80" , $errCode , $errStr , $waitTimeoutInSeconds );
	// } catch (Exception $e) {
	// 	$fp = false;
	// }

	// if ( $fp ) {   
	//    return true;
	// } else {
	//    return false; 
	// } 
	// fclose( $fp );

}

?>