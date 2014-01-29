<?php

	// This file should just be included places to avoid warnings.

	// I can't assume that the user has set the timezone variable in the php.ini file. If that isn't set, then we're going to get
	// those annoying php date errors scolding us for not having the timezone set, so, we'll just set it here (for the duration
	// of the script) as a precaution.

	// Get the computer's timezone as a string.
	$output = exec( '/usr/sbin/systemsetup -gettimezone' );

	// Remove the preface of the timezone string so we're left with a valid php timezone.
	$return = preg_replace('/Time Zone: /' , '' , $output);

	// Set the default timezone.
	date_default_timezone_set($return);