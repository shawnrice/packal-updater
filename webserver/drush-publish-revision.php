<?php

$counter = 1;
// Stupidly, we have to get the args passed this way.
// The first arg is the nid, and the second is the vid.
while ( $arg = drush_shift() ) {
  if ( $counter == 1 ) $nid = $arg;
  else if ( $counter == 2 ) $vid = $arg;

  $counter++;
}

$node = node_load( $nid , $vid ); 
$node->status = 1; 
$msg = node_save($node); 

$node = node_load( $nid ); 
$node->status = 1; 
$msg = node_save($node); 

print_r($msg);