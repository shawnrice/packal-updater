<?php

/**
 * tags demonstration
 * @author this tag is parsed, but this @version tag is ignored
 * @version 1.0 this version tag is parsed
 */

/**
 *
 *
 * 
 */

namespace CFPropertyList;

// just in case...
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

/**
 * Require CFPropertyList
 */
require_once(__DIR__.'/../libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php');

/**
 * Create a new CFPropertyList instance that loads the info.plist file from the 
 * workflow.
 */



$manifest = simplexml_load_file('manifest.xml');

$installedWorkflows = scandir("../");
$workflowBundles = array();
$workflowNoBundle = array();
$availableWorkflows = array();
$availableBundles = array();
$i = 0;

foreach ( $manifest as $workflow ) {
  $availableBundles[] = (string)$workflow->bundle;
}


foreach ($installedWorkflows as $workflowDirectory) {
  
  if (! ( ( $workflowDirectory == '.' ) || ( $workflowDirectory == '..' ) ) ) {

    $dir = __DIR__ . '/../../' . $workflowDirectory . '/info.plist';

    $workflow = new CFPropertyList( $dir , CFPropertyList::FORMAT_XML );

    foreach ( $workflow-> toArray() as $key => $val ) {

      if ( $key === "bundleid") {
        $workflowBundles[$i]['bundleid'] = $val;
        if ( in_array( $val , $availableBundles ) ) {
             array_push($availableWorkflows, $val);
        }
      }

      if ( $key === "name") $workflowBundles[$i]['name'] = $val;

    }

    if ( empty($workflowBundles[$i]['bundleid'] ) ) {
      $workflowNoBudle[$i]['name'] = $workflowBundles[$i]['name'];
      unset( $workflowBundles[$i] );
    }

    $i++;

  }
  unset($workflow);

}

echo "There are " . $manifest->count() . " workflows in the manifest.";
newline();
echo "Of those, " . count($workflowBundles) . " have bundle ids";
newline();
echo "And the other " . count($workflowNoBudle) . " have no bundle ids and so Packal cannot interact with them.";


/** 
 * Variables:
 *
 * $workflowBundles     --  (bundle) user installed workflows' bundles ids
 * $workflowNoBundle    --  (name) names of installed workflows with no bundle ids
 * 
 * $availableBundles    --  (bundle)These are the bundle ids of each workflow in the manifest
 * 
 * $availableWorkflows  --  (bundle) workflows that the user has installed that are in the manifest
 * 
 */

/**
 *
 *  File structure for installed versions
 *  {bundle}.info
 *  Name
 *  Bundle
 *  Version
 *  Updated (in UNIX epoch time)
 *
 *  Key(?)
 *
 *  I need to generate these files if they don't already exist,
 *  and I need to generate them if someone claims an already installed workflow...
 *
 *
 *  So, how do I register something with Packal if it was downloaded from Packal?
 *            We'll have a folder inside it!
 * 
 */

/** 
 * @param dir
 * @param skeipVerification
 * @return mixed
*/
function importWorkflow( $dir, $skipVerification = false ) {

  /**
   *
   *  I am invoked after the signature has been verified
   * 
   *  Basically, what I do is 
   *  -- 1 call the migration function;
   *  -- 2 remove the contents of the workflow that is there
   *  -- 3 input the new contents of the workflow
   *  -- 4 return either true or an error code
   * 
   * 
   */

}



newline();

foreach ($manifest as $workflow) {

//  echo $workflow->name;
//  newline();

}





$plist = new CFPropertyList( __DIR__.'/info.plist', CFPropertyList::FORMAT_XML );



foreach ( $plist-> toArray() as $key => $val ) {

  if ( $key === "bundleid") $bundleid = $val;
  if ( $key === "objects" ) {
    foreach ($val as $obj) {
      $object = returnObject( $obj );
//      print_r($object);

    }
  }

}

/**
 *
 *  function that takes a single entry from the object array in the plist and 
 *  pulls out the necessary information so that we can migrate properly.
 * 
 */

function returnObject ( $object ) {

  switch ($object['type']) {
    case 'alfred.workflow.trigger.hotkey':
      $value = array(
        'type' => 'hotkey',
        'uuid' => $object['uid'],
        'action' => $object['config']['action'],
        'argument' => $object['config']['argument'],
        'hotkey' => $object['config']['hotkey'],
        'hotmod' => $object['config']['hotmod'],
        'leftcursor' => $object['config']['leftcursor'],
        'modsmode' => $object['config']['modsmode']
        );
      break;

    case 'alfred.workflow.output.notification':
      $value = array(
        'type' => 'notification',
        'uuid' => $object['uid'],
        'kind' => $object['config']['output']
        );
      break;

    case 'alfred.workflow.input.scriptfilter':
      $value = array(
        'type' => 'scriptfilter',
        'uuid' => $object['uid'],
        'keyword' => $object['config']['keyword']
        );
      break;

    case 'alfred.workflow.input.keyword':
      $value = array(
        'type' => 'keyword',
        'uuid' => $object['uid'],
        'keyword' => $object['config']['keyword']
        );
      break;
    default:
      return "None";
      break;
  }
  return $value;
}



/*// load an existing list
$plist = new CFPropertyList( __DIR__.'/sample.xml.plist' );


foreach( $plist->getValue(true) as $key => $value )
{
  if( $key == "City Of Birth" )
  {
    $value->setValue( 'Mars' );
  }
  
  if( $value instanceof \Iterator )
  {
    // The value is a CFDictionary or CFArray, you may continue down the tree
  }
}


// save data
$plist->save( __DIR__.'/modified.plist', CFPropertyList::FORMAT_XML );*/


function newline() {
    echo '
  ';
}

/*
Make sure that the directory exists
packal/hancock // Public Key
packal/appcast // Appcast
*/
/**
 *
 *  So, this is how we do the signing:
 *  First, we just put the entire manifest of the repo in the main folder.
 *  Packal grabs that file and see if there are any updates.
 *  If there are updates, then it grabs the AppCast for each update-able workflow
 *  That appcast has the signature in it.
 *  So, Packal checks it. If it's cool, then it downloads the file into a temporary space.
 *  After that it migrates the plist file.
 *  Lastly, it overwrites the directory with the new files. Hurrah!
 *
 *  On the server side, we'll have to create the appcast signature when a new version is uploaded
 *  And we might have to insert some keys....
 *  We can do this per workflow...
 *
 *  So, that means that we'll need a new directory outside of the webroot for packal called something like "keys"...
 *  It'll be there under the uuid folder name.
 *
 *
 *
 *
 *  The public key is in the workflow folder.
 * 
 */



?>
