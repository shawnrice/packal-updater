<?php

error_reporting(0);


$dest = $argv[1];
$file = $argv[2];
$dest = str_replace(" ", "\ ", $dest);
$f = fopen($file, 'r');	

if ($f) {
	$cmd = "curl -L -s -o $dest $file 2>&1 >/dev/null";
	exec($cmd);
}