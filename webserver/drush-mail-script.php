<?php

/**
 * Args:
 *  username
 * 	email-address
 * 	workflow name
 * 	version
 *  kind
 *
 * 	invoke with drush: drush @packal scr drush-mail-script.php user email name version kind
 */


$counter = 1;
// Stupidly, we have to get the args passed this way.
// The first arg is the nid, and the second is the vid.
while ( $arg = drush_shift() ) {
  if ( $counter == 1 ) $user = $arg;
  else if ( $counter == 2 ) $to = $arg;
  else if ( $counter == 3 ) $name = $arg;
  else if ( $counter == 4 ) $version = $arg;
  else if ( $counter == 5 ) $kind = $arg;

  $counter++;
}

if ($kind == 'success') {
	$body = "$user,
Your Workflow ( $name ) has been updated to version $version.
It is now live on Packal to download and for others to update.

Thank you for your contribution.

-- The Packal Bot
";
	$subject = "Packal Update: Your Workflow is now available on Packal";
} else if ($kind == 'virus') {
	$body = "$user,
A virus has been detected in your latest submission to $name, version $version.
Please look into this problem.

The file and record associated has been deleted from the system. If this was an update to an existing Workflow, the previous version is still available through Packal.

If you believe that this message was created in error, then please contact us at packal@packal.org.

Thank you for your contribution.

-- The Packal Bot
";
	$subject = "Packal Update: Your Workflow had a virus";
} else if ($kind == 'zip') {
	$body = "$user,
A problem has been detected in your latest submission to $name, version $version.
The Workflow file is not a valid zip file (all Alfred2 Workflow files are actually zip files). When testing the file (try 'unzip -t <filename>' from the terminal on your computer), we encountered an error. If the file is valid on your computer, then there may have been a problem when the file was uploaded to Packal. Please look into this problem.

The file and record associated has been deleted from the system. If this was an update to an existing Workflow, the previous version is still available through Packal.

If you have any questions about this error, then please contact us at packal@packal.org.

Thank you for your contribution.

-- The Packal Bot
";
	$subject = "Packal Update: Your Workflow had an error";
} else {
	return false;
}

	$module = 'my_snippet';
	$key = 'notify';
	$language = language_default();
	$params = array();
	$from = variable_get('site_mail', '');
	$send = FALSE;
	$message = drupal_mail($module, $key, $to, $language, $params, $from, $send);
	$message['subject'] = $subject;
	$message['body'] = explode("\n", $body);
	$system = drupal_mail_system($module, $key);
	$message = $system->format($message);
	$message['result'] = $system->mail($message);