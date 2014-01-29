<?php

// TODO: add in error checking and handling.

$counter = 1;
// Stupidly, we have to get the args passed this way.
while ( $arg = drush_shift() ) {
  if ( $counter == 1 ) $uid = $arg;
  if ( $counter == 2 ) $bundle = $arg;
  $counter++;
}

$user = user_load( $uid );
$mail = $user->mail;
$name = $user->name;

file_put_contents( "/www/files/packal/github/tmp/$bundle/username" , $name );
file_put_contents( "/www/files/packal/github/tmp/$bundle/mail" , $mail );