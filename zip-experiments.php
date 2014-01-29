<?php

/**
 *
 *  So there are two main things that I need to do with these experiements:
 *  
 *  -- 1) Strip out the files to make sure that they aren't in there when
 *          the workflow is uploaded ( i.e. sanitize them )
 *  -- 2) Put them into the workflow file so that I can use them later.
 *
 * 
 */


$zip = new ZipArchive;
$a = 'signing.zip';

if ( $zip->statName( 'packal/hancock' ) ) {
    
}

if ( $zip->statName( 'packal/appcast' ) ) {

}

if ( $zip->open($a) ) {
    echo "Archive exists and is valid.\n";
    if ( $zip->statName('packal/hancock') ) {
        echo "Hancock exists in the packal directory.\n";

    } else {
        echo "Folder 'packal' doesn't exist.\n";
        if($zip->addEmptyDir('packal')) {
            echo "Created the folder 'packal' in the archive.\n";
        } else {
            echo "Could not create the directory\n";
        }
    }            
}
$zip->close();


exit();



if ($zip->open($a, ZipArchive::CREATE) === TRUE) {
    $zip->addFromString('packal/hancock','this is a test');
    $zip->close();
    echo "Created file";
} else {
    echo "Dammit.";
}

exit();

/*
//////

    $zip->extractTo('/my/destination/dir/');



The following reads a file from inside of a zip archive

$zip = new ZipArchive;
if ($zip->open('Caffeinate Control.alfredworkflow') === TRUE) {
    echo $zip->getFromName('update.json');
    $zip->close();
} else {
    echo 'failed';
}

Returns TRUE on success or FALSE on failure.

Example #1 Add an entry to a new archive

<?php
$zip = new ZipArchive;
$res = $zip->open('test.zip', ZipArchive::CREATE);
if ($res === TRUE) {
    $zip->addFromString('test.txt', 'file content goes here');
    $zip->close();
    echo 'ok';
} else {
    echo 'failed';
}
?>
Example #2 Add file to a directory inside an archive

<?php
$zip = new ZipArchive;
if ($zip->open('test.zip') === TRUE) {
    $zip->addFromString('dir/test.txt', 'file content goes here');
    $zip->close();
    echo 'ok';
} else {
    echo 'failed';
}
?>


see if a file exists -- returns false on failure
$zip->statName('packal'));            // If that is true, then keep going
$zip->statName('packal/hancock'));    // Check to see if this exists
$zip->statName('packal/appcast'));    // Check to see if this exists

//////

The following code can be used to get a list of all the file names in a zip file. 
$za = new ZipArchive(); 

$za->open('theZip.zip'); 

for( $i = 0; $i < $za->numFiles; $i++ ){ 
    $stat = $za->statIndex( $i ); 
    print_r( basename( $stat['name'] ) . PHP_EOL ); 
} 



<?php
$zip = new ZipArchive;
if ($zip->open('test.zip') === TRUE) {
    if($zip->addEmptyDir('newDirectory')) {
        echo 'Created a new root directory';
    } else {
        echo 'Could not create the directory';
    }
    $zip->close();
} else {
    echo 'failed';
}
?>


Example #1 Deleting a file and directory from an archive, using names

<?php
$zip = new ZipArchive;
if ($zip->open('test1.zip') === TRUE) {
    $zip->deleteName('testfromfile.php');
    $zip->deleteName('testDir/');
    $zip->close();
    echo 'ok';
} else {
    echo 'failed';
}
?>

detect errors for zip files.

    $zip = new ZipArchive();

    // ZipArchive::CHECKCONS will enforce additional consistency checks
    $res = $zip->open( $file , ZipArchive::CHECKCONS );
    switch($res) {
        case ZipArchive::ER_NOZIP :
            return "Not a valid zip";
        case ZipArchive::ER_INCONS :
            return "Consistency check failed";
        case ZipArchive::ER_CRC :
            return "Checksum failed";
    }

*/


?>