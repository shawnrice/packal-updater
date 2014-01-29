<?php

date_default_timezone_set("America/NEW_YORK");
ini_set("auto_detect_line_endings", "yes");
error_reporting(0);

require_once('workflows.php');
$w = new Workflows();

$d = $w->data();
$c = $w->cache();

if (! file_exists($d) ) {
	mkdir($d);
}
if (! file_exists($d.'/icons') ) {
	mkdir($d.'/icons');
}
if (! file_exists($d.'/archive') ) {
	mkdir($d.'/archive');
}
if (! file_exists($c) ) {
	mkdir($c);
}

$repo = "https://github.com/packal/repo";
$manifest = "https://github.com/packal/repo/raw/master/manifest.json";
$icons = "https://github.com/packal/repo/raw/master/icons/icons.tar.gz";
$stamp = date('U');

$mani = "manifest-$stamp.json";

	$cmd = "mv " . str_replace(" ", "\ ", $d) . "/manifest* " . str_replace(" ", "\ ", $d) . "/archive/";
if (file_exists( $d . '/update')) {
	$update = file_get_contents( $d . '/update');
	exec( $cmd );
} else {
	$new = true;
	$contents .= "File generated automatically by packal. Do not edit.\n";
	$contents .= "----------------------------------------------------\n";
	$contents .= $stamp;
	file_put_contents( $d . '/update', $contents);
}

	$contents .= "File generated automatically by packal. Do not edit.\n";
	$contents .= "----------------------------------------------------\n";
	$contents .= $stamp;
	file_put_contents( $d . '/update', $contents);

$cmd = "curl -L -s -o '$d/manifest-$stamp.json' $manifest 2>&1 >/dev/null";
exec($cmd);
$cmd = "curl -L -s -o '$c/icons.tar.gz' $icons 2>&1 >/dev/null";
exec($cmd);
$cmd = "tar zxf '$c/icons.tar.gz' -C '$d/icons/'";
exec($cmd);
$cmd = "rm $d/manifest.json";
exec($cmd);
$cmd = "ln -s $d/$mani $d/manifest.json";
echo $cmd;
exec($cmd);

echo "Packages list updated.";

/*

Set the paths
Check when the last update was
Pull in all old update info to an array
Get the new update info
Put info into a file and an array
Find the difference on those
Check to see how many packages need updating
Check to see how many packages are new


//	tar zxf 

*/