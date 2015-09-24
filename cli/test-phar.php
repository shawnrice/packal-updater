<?php
$phar_name    = 'Packal.phar';
try {
    // open an existing phar
    $p = new Phar($phar_name, 0);
    // Phar extends SPL's DirectoryIterator class
    foreach (new RecursiveIteratorIterator($p) as $file) {
        // $file is a PharFileInfo class, and inherits from SplFileInfo
        // echo $file->getFileName() . "\n";
        echo str_replace( 'phar://' . __DIR__ . '/Packal.phar', '', $file->getPathName() ) . "\n";
    }
} catch (Exception $e) {
	echo "There was an error opening the phar.\n";
	print_r( $e );
}